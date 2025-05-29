<?php
/**
 * Servicio de Autenticación CAS
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Services;

use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Session;
use UC\ApprovalSystem\Models\User;
use UC\ApprovalSystem\Models\Admin;

class CASService 
{
    private $config;
    private $serverConfig;
    private $clientConfig;
    
    public function __construct() 
    {
        $this->config = include __DIR__ . '/../../config/cas.php';
        $this->serverConfig = $this->config['server'];
        $this->clientConfig = $this->config['client'];
    }
    
    /**
     * Redirigir al login de CAS
     */
    public function redirectToLogin(string $serviceUrl = null): void 
    {
        $serviceUrl = $serviceUrl ?? $this->clientConfig['service_url'];
        
        $loginUrl = $this->config['urls']['login'] . '?' . http_build_query([
            'service' => $serviceUrl
        ]);
        
        Logger::info('Redirigiendo a login CAS', [
            'service_url' => $serviceUrl,
            'login_url' => $loginUrl,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        header("Location: {$loginUrl}");
        exit;
    }
    
    /**
     * Validar ticket de CAS
     */
    public function validateTicket(string $ticket, string $serviceUrl = null): ?array 
    {
        $serviceUrl = $serviceUrl ?? $this->clientConfig['service_url'];
        
        try {
            // En modo mock, usar datos de prueba
            if ($this->config['development']['mock_enabled']) {
                return $this->validateMockTicket($ticket);
            }
            
            $validateUrl = $this->config['urls']['validate'] . '?' . http_build_query([
                'service' => $serviceUrl,
                'ticket' => $ticket
            ]);
            
            Logger::debug('Validando ticket CAS', [
                'ticket' => substr($ticket, 0, 10) . '...',
                'service_url' => $serviceUrl,
                'validate_url' => $validateUrl
            ]);
            
            $response = $this->makeHttpRequest($validateUrl);
            
            if (!$response) {
                Logger::error('Error obteniendo respuesta de CAS');
                return null;
            }
            
            return $this->parseValidationResponse($response);
            
        } catch (\Exception $e) {
            Logger::error('Error validando ticket CAS: ' . $e->getMessage(), [
                'ticket' => substr($ticket, 0, 10) . '...',
                'service_url' => $serviceUrl
            ]);
            return null;
        }
    }
    
    /**
     * Validar ticket en modo mock (desarrollo)
     */
    private function validateMockTicket(string $ticket): ?array 
    {
        $mockUsers = $this->config['development']['mock_users'];
        
        // Simular validación exitosa
        if (strpos($ticket, 'ST-') === 0) {
            // Usar el primer usuario mock como default
            $userData = reset($mockUsers);
            
            Logger::info('Ticket mock validado exitosamente', [
                'ticket' => $ticket,
                'mock_user' => $userData['email']
            ]);
            
            return $userData;
        }
        
        return null;
    }
    
    /**
     * Parsear respuesta de validación de CAS
     */
    private function parseValidationResponse(string $response): ?array 
    {
        // Respuesta de CAS 2.0 es XML
        $xml = simplexml_load_string($response);
        
        if (!$xml) {
            Logger::error('Respuesta XML inválida de CAS', ['response' => $response]);
            return null;
        }
        
        // Registrar namespaces de CAS
        $xml->registerXPathNamespace('cas', 'http://www.yale.edu/tp/cas');
        
        // Verificar si la validación fue exitosa
        $success = $xml->xpath('//cas:authenticationSuccess');
        
        if (empty($success)) {
            $failure = $xml->xpath('//cas:authenticationFailure');
            $errorCode = $failure[0]['code'] ?? 'UNKNOWN';
            $errorMessage = (string)($failure[0] ?? 'Validación fallida');
            
            Logger::warning('Validación CAS fallida', [
                'error_code' => $errorCode,
                'error_message' => $errorMessage
            ]);
            
            return null;
        }
        
        // Extraer datos del usuario
        $user = $xml->xpath('//cas:user')[0] ?? null;
        $attributes = $xml->xpath('//cas:attributes')[0] ?? null;
        
        if (!$user) {
            Logger::error('Usuario no encontrado en respuesta CAS');
            return null;
        }
        
        $userData = [
            'email' => (string)$user,
            'name' => '',
            'first_name' => '',
            'last_name' => '',
            'department' => '',
            'title' => '',
            'phone' => '',
            'employee_id' => '',
            'student_id' => '',
            'groups' => []
        ];
        
        // Extraer atributos si están disponibles
        if ($attributes) {
            $attributeMapping = $this->config['attributes'];
            
            foreach ($attributeMapping as $localKey => $casKey) {
                $value = $attributes->xpath("cas:{$casKey}")[0] ?? null;
                if ($value !== null) {
                    $userData[$localKey] = (string)$value;
                }
            }
            
            // Procesar grupos/roles si existen
            $groups = $attributes->xpath('cas:memberOf');
            if (!empty($groups)) {
                $userData['groups'] = array_map('strval', $groups);
            }
        }
        
        // Si no hay nombre completo, generarlo desde email
        if (empty($userData['name'])) {
            $userData['name'] = $userData['first_name'] . ' ' . $userData['last_name'];
            if (trim($userData['name']) === '') {
                $userData['name'] = explode('@', $userData['email'])[0];
            }
        }
        
        Logger::info('Usuario validado exitosamente por CAS', [
            'email' => $userData['email'],
            'name' => $userData['name'],
            'department' => $userData['department']
        ]);
        
        return $userData;
    }
    
    /**
     * Procesar login exitoso
     */
    public function processSuccessfulLogin(array $casUserData): array 
    {
        try {
            // Verificar si es administrador
            $admin = Admin::findByEmail($casUserData['email']);
            
            if ($admin && $admin->isActive()) {
                // Login como administrador
                Session::setUser([
                    'id' => $admin->id,
                    'email' => $admin->email,
                    'name' => $admin->name,
                    'role' => $admin->role,
                    'areas' => $admin->getAreas(),
                    'permissions' => $admin->getPermissions()
                ]);
                
                Logger::auth('Administrador logueado via CAS', [
                    'admin_id' => $admin->id,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'areas' => $admin->getAreas()
                ]);
                
                return [
                    'success' => true,
                    'user_type' => 'admin',
                    'user' => $admin,
                    'redirect_url' => '/admin/dashboard.php'
                ];
            }
            
            // Verificar si es usuario regular
            $user = User::findByEmail($casUserData['email']);
            
            if (!$user) {
                // Crear nuevo usuario
                $user = User::createFromCAS($casUserData);
                
                if (!$user) {
                    throw new \Exception('Error creando usuario desde datos CAS');
                }
                
                Logger::info('Nuevo usuario creado desde CAS', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } else {
                // Actualizar datos existentes
                $user->updateFromCAS($casUserData);
            }
            
            // Verificar si el usuario está activo
            if (!$user->isActive()) {
                Logger::warning('Intento de login de usuario inactivo', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'status' => $user->status
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Usuario inactivo. Contacte al administrador.',
                    'redirect_url' => '/login.php?error=inactive'
                ];
            }
            
            // Registrar login
            $user->recordLogin();
            
            // Establecer sesión
            Session::setUser([
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->getFullName(),
                'role' => 'user',
                'areas' => [],
                'permissions' => []
            ]);
            
            Logger::auth('Usuario logueado via CAS', [
                'user_id' => $user->id,
                'email' => $user->email,
                'login_count' => $user->login_count
            ]);
            
            return [
                'success' => true,
                'user_type' => 'user',
                'user' => $user,
                'redirect_url' => '/dashboard.php'
            ];
            
        } catch (\Exception $e) {
            Logger::error('Error procesando login CAS: ' . $e->getMessage(), [
                'cas_data' => $casUserData
            ]);
            
            return [
                'success' => false,
                'error' => 'Error interno procesando autenticación',
                'redirect_url' => '/login.php?error=internal'
            ];
        }
    }
    
    /**
     * Logout de CAS
     */
    public function logout(string $redirectUrl = null): void 
    {
        $user = Session::getUser();
        
        // Limpiar sesión local
        Session::destroy();
        
        // URL de logout de CAS
        $logoutUrl = $this->config['urls']['logout'];
        
        if ($redirectUrl) {
            $logoutUrl .= '?' . http_build_query(['service' => $redirectUrl]);
        }
        
        Logger::auth('Usuario cerró sesión', [
            'user_id' => $user['id'] ?? null,
            'email' => $user['email'] ?? null,
            'logout_url' => $logoutUrl
        ]);
        
        header("Location: {$logoutUrl}");
        exit;
    }
    
    /**
     * Verificar si el usuario actual está autenticado
     */
    public function isAuthenticated(): bool 
    {
        return Session::isAuthenticated();
    }
    
    /**
     * Obtener usuario actual
     */
    public function getCurrentUser(): ?array 
    {
        return Session::getUser();
    }
    
    /**
     * Verificar permisos del usuario actual
     */
    public function hasPermission(string $permission, string $area = null): bool 
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $user = $this->getCurrentUser();
        
        // Si es admin, tiene todos los permisos
        if ($user['role'] === 'admin') {
            return true;
        }
        
        // Verificar permisos específicos
        $permissions = $user['permissions'] ?? [];
        
        if (isset($permissions[$permission]) && $permissions[$permission] === true) {
            return true;
        }
        
        if ($area && isset($permissions[$area][$permission]) && $permissions[$area][$permission] === true) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Middleware para requerir autenticación
     */
    public function requireAuth(): void 
    {
        if (!$this->isAuthenticated()) {
            Logger::warning('Acceso no autenticado detectado', [
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            $this->redirectToLogin();
        }
    }
    
    /**
     * Middleware para requerir rol de administrador
     */
    public function requireAdmin(): void 
    {
        $this->requireAuth();
        
        if (!Session::isAdmin()) {
            Logger::warning('Acceso no autorizado a área admin', [
                'user_id' => Session::get('user_id'),
                'email' => Session::get('user_email'),
                'url' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
            
            header('HTTP/1.0 403 Forbidden');
            header('Location: /403.php');
            exit;
        }
    }
    
    /**
     * Middleware para requerir acceso a área específica
     */
    public function requireAreaAccess(string $area): void 
    {
        $this->requireAuth();
        
        if (!Session::hasAreaAccess($area)) {
            Logger::warning('Acceso no autorizado a área específica', [
                'user_id' => Session::get('user_id'),
                'email' => Session::get('user_email'),
                'required_area' => $area,
                'user_areas' => Session::getUserAreas(),
                'url' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
            
            header('HTTP/1.0 403 Forbidden');
            header('Location: /403.php');
            exit;
        }
    }
    
    /**
     * Realizar petición HTTP a servidor CAS
     */
    private function makeHttpRequest(string $url): ?string 
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->config['security']['curl_timeout'],
                'user_agent' => 'UC-ApprovalSystem/1.0',
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => $this->config['security']['ssl_verify_peer'],
                'verify_peer_name' => $this->config['security']['ssl_verify_host']
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            Logger::error('Error en petición HTTP a CAS', [
                'url' => $url,
                'error' => $error['message'] ?? 'Unknown error'
            ]);
            return null;
        }
        
        Logger::debug('Respuesta HTTP de CAS recibida', [
            'url' => $url,
            'response_length' => strlen($response)
        ]);
        
        return $response;
    }
    
    /**
     * Obtener configuración de CAS
     */
    public function getConfig(): array 
    {
        return $this->config;
    }
    
    /**
     * Verificar salud del servicio CAS
     */
    public function healthCheck(): array 
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'cas_server_reachable' => false,
            'mock_mode' => $this->config['development']['mock_enabled'],
            'configuration' => [
                'server_hostname' => $this->serverConfig['hostname'],
                'server_port' => $this->serverConfig['port'],
                'login_url' => $this->config['urls']['login'],
                'validate_url' => $this->config['urls']['validate']
            ]
        ];
        
        // En modo mock no verificar conectividad
        if ($this->config['development']['mock_enabled']) {
            $health['cas_server_reachable'] = true;
            $health['mock_mode_warning'] = 'Sistema en modo desarrollo con usuarios mock';
            return $health;
        }
        
        // Verificar conectividad con servidor CAS
        try {
            $testUrl = "https://{$this->serverConfig['hostname']}:{$this->serverConfig['port']}/cas/";
            $context = stream_context_create([
                'http' => ['timeout' => 5],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $response = @file_get_contents($testUrl, false, $context);
            
            if ($response !== false) {
                $health['cas_server_reachable'] = true;
            } else {
                $health['status'] = 'unhealthy';
                $health['issues'][] = 'No se puede conectar al servidor CAS';
            }
            
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = 'Error verificando servidor CAS: ' . $e->getMessage();
        }
        
        return $health;
    }
    
    /**
     * Obtener estadísticas de autenticación
     */
    public function getAuthStats(): array 
    {
        // Esta función podría expandirse para incluir estadísticas de la BD
        return [
            'current_session' => Session::getSessionStats(),
            'mock_mode' => $this->config['development']['mock_enabled'],
            'session_timeout' => $this->config['session']['lifetime'],
            'cas_server' => $this->serverConfig['hostname']
        ];
    }
}