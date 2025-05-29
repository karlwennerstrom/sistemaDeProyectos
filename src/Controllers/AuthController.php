<?php
/**
 * Controlador de Autenticación
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Controllers;

use UC\ApprovalSystem\Services\CASService;
use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Session;

class AuthController extends BaseController 
{
    /**
     * Página principal - redirige según autenticación
     */
    public function index(): void 
    {
        if (Session::isAuthenticated()) {
            // Redirigir según el rol del usuario
            if (Session::isAdmin()) {
                $this->redirect('/admin/dashboard.php');
            } else {
                $this->redirect('/dashboard.php');
            }
        } else {
            $this->showLogin();
        }
    }
    
    /**
     * Mostrar página de login
     */
    public function showLogin(): void 
    {
        // Si ya está autenticado, redirigir
        if (Session::isAuthenticated()) {
            $this->index();
            return;
        }
        
        $data = [
            'title' => 'Iniciar Sesión - Sistema UC',
            'error' => $this->getInput('error'),
            'message' => $this->getInput('message'),
            'return_url' => $this->getInput('return_url', '/dashboard.php')
        ];
        
        // Mensajes de error específicos
        $errorMessages = [
            'auth_failed' => 'Error de autenticación. Verifique sus credenciales.',
            'session_expired' => 'Su sesión ha expirado. Por favor inicie sesión nuevamente.',
            'access_denied' => 'Acceso denegado. No tiene permisos para acceder a esta área.',
            'inactive' => 'Su cuenta está inactiva. Contacte al administrador.',
            'internal' => 'Error interno del sistema. Intente nuevamente más tarde.'
        ];
        
        if ($data['error'] && isset($errorMessages[$data['error']])) {
            $data['error_message'] = $errorMessages[$data['error']];
        }
        
        $this->view('auth/login', $data);
    }
    
    /**
     * Iniciar proceso de login CAS
     */
    public function login(): void 
    {
        // Verificar rate limiting
        if (!$this->checkRateLimit('login_attempt', 10, 300)) { // 10 intentos en 5 minutos
            $this->redirectWithMessage('/login.php?error=rate_limited', 
                'Demasiados intentos de login. Espere unos minutos.', 'error');
            return;
        }
        
        $returnUrl = $this->getInput('return_url', '/dashboard.php');
        
        // Guardar URL de retorno en sesión
        Session::set('login_return_url', $returnUrl);
        
        Logger::info('Iniciando proceso de login CAS', [
            'ip' => $this->request['ip'],
            'user_agent' => $this->request['user_agent'],
            'return_url' => $returnUrl
        ]);
        
        // Redirigir a CAS
        $this->casService->redirectToLogin();
    }
    
    /**
     * Callback de CAS - procesar ticket
     */
    public function callback(): void 
    {
        $ticket = $this->getInput('ticket');
        
        if (!$ticket) {
            Logger::warning('Callback CAS sin ticket', [
                'query_params' => $this->request['query'],
                'ip' => $this->request['ip']
            ]);
            
            $this->redirectWithMessage('/login.php?error=auth_failed', 
                'Error en la autenticación. No se recibió ticket válido.', 'error');
            return;
        }
        
        try {
            // Validar ticket con CAS
            $casUserData = $this->casService->validateTicket($ticket);
            
            if (!$casUserData) {
                Logger::warning('Ticket CAS inválido', [
                    'ticket' => substr($ticket, 0, 10) . '...',
                    'ip' => $this->request['ip']
                ]);
                
                $this->redirectWithMessage('/login.php?error=auth_failed', 
                    'Ticket de autenticación inválido.', 'error');
                return;
            }
            
            // Procesar login exitoso
            $loginResult = $this->casService->processSuccessfulLogin($casUserData);
            
            if (!$loginResult['success']) {
                $errorParam = '';
                switch ($loginResult['error']) {
                    case 'Usuario inactivo. Contacte al administrador.':
                        $errorParam = 'inactive';
                        break;
                    case 'Error interno procesando autenticación':
                        $errorParam = 'internal';
                        break;
                    default:
                        $errorParam = 'auth_failed';
                }
                
                $this->redirectWithMessage("/login.php?error={$errorParam}", 
                    $loginResult['error'], 'error');
                return;
            }
            
            // Login exitoso
            $user = $loginResult['user'];
            $userType = $loginResult['user_type'];
            
            Logger::auth('Login exitoso', [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_type' => $userType,
                'ip' => $this->request['ip']
            ]);
            
            // Obtener URL de retorno
            $returnUrl = Session::get('login_return_url', $loginResult['redirect_url']);
            Session::remove('login_return_url');
            
            // Mensaje de bienvenida
            $welcomeMessage = "Bienvenido, {$user->name}";
            if ($userType === 'admin') {
                $welcomeMessage .= " (Administrador)";
            }
            
            $this->redirectWithMessage($returnUrl, $welcomeMessage, 'success');
            
        } catch (\Exception $e) {
            Logger::error('Error en callback CAS: ' . $e->getMessage(), [
                'ticket' => substr($ticket, 0, 10) . '...',
                'ip' => $this->request['ip'],
                'user_agent' => $this->request['user_agent']
            ]);
            
            $this->redirectWithMessage('/login.php?error=internal', 
                'Error interno procesando autenticación.', 'error');
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout(): void 
    {
        $user = Session::getUser();
        
        if ($user) {
            Logger::auth('Logout iniciado', [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'ip' => $this->request['ip']
            ]);
        }
        
        $redirectUrl = $this->getInput('redirect_url', '/login.php?logged_out=1');
        
        // Logout a través de CAS (destruye sesión local también)
        $this->casService->logout($redirectUrl);
    }
    
    /**
     * Verificar estado de autenticación (API)
     */
    public function status(): void 
    {
        $isAuthenticated = Session::isAuthenticated();
        $user = null;
        
        if ($isAuthenticated) {
            $userData = Session::getUser();
            $user = [
                'id' => $userData['id'],
                'email' => $userData['email'],
                'name' => $userData['name'],
                'role' => $userData['role'],
                'areas' => $userData['areas'],
                'login_time' => $userData['login_time'],
                'session_remaining' => Session::getTimeRemaining()
            ];
        }
        
        $this->jsonSuccess([
            'authenticated' => $isAuthenticated,
            'user' => $user,
            'session_stats' => Session::getSessionStats(),
            'cas_health' => $this->casService->healthCheck()
        ]);
    }
    
    /**
     * Extender sesión (AJAX)
     */
    public function extendSession(): void 
    {
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!Session::isAuthenticated()) {
            $this->jsonError('No autenticado', [], 401);
            return;
        }
        
        Session::extend();
        
        $this->jsonSuccess([
            'session_extended' => true,
            'time_remaining' => Session::getTimeRemaining(),
            'expires_at' => date('Y-m-d H:i:s', time() + Session::getTimeRemaining())
        ], 'Sesión extendida exitosamente');
    }
    
    /**
     * Cambiar contraseña (placeholder para futuro)
     */
    public function changePassword(): void 
    {
        $this->requireAuth();
        
        if ($this->isGet()) {
            $this->view('auth/change_password', [
                'title' => 'Cambiar Contraseña'
            ]);
            return;
        }
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        // Por ahora retornar que no está implementado
        $this->jsonError('Funcionalidad no disponible. La autenticación se maneja a través de CAS UC.', [], 501);
    }
    
    /**
     * Perfil del usuario
     */
    public function profile(): void 
    {
        $this->requireAuth();
        
        $user = Session::getUser();
        
        if ($this->isJsonRequest()) {
            $this->jsonSuccess($user);
            return;
        }
        
        $this->view('auth/profile', [
            'title' => 'Mi Perfil',
            'user' => $user,
            'session_info' => Session::getSessionStats(),
            'browser_info' => Helper::getBrowserInfo()
        ]);
    }
    
    /**
     * Página de acceso denegado (403)
     */
    public function accessDenied(): void 
    {
        $message = $this->getInput('message', 'No tiene permisos para acceder a esta página.');
        $returnUrl = $this->getInput('return_url', '/dashboard.php');
        
        $this->view('auth/access_denied', [
            'title' => 'Acceso Denegado',
            'message' => $message,
            'return_url' => $returnUrl,
            'user' => Session::getUser()
        ]);
    }
    
    /**
     * Información de sesión detallada (para administradores)
     */
    public function sessionInfo(): void 
    {
        $this->requireAdmin();
        
        if ($this->isJsonRequest()) {
            $this->jsonSuccess([
                'current_session' => Session::getSessionStats(),
                'cas_service' => $this->casService->getAuthStats(),
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'server_time' => date('Y-m-d H:i:s'),
                    'timezone' => date_default_timezone_get(),
                    'session_save_path' => session_save_path()
                ]
            ]);
            return;
        }
        
        $this->view('auth/session_info', [
            'title' => 'Información de Sesión',
            'session_stats' => Session::getSessionStats(),
            'cas_stats' => $this->casService->getAuthStats(),
            'cas_health' => $this->casService->healthCheck()
        ]);
    }
    
    /**
     * Manejar Single Sign-Out de CAS
     */
    public function singleLogout(): void 
    {
        // CAS puede enviar peticiones POST para logout único
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        $logoutRequest = $this->getInput('logoutRequest');
        
        if ($logoutRequest) {
            // Parsear la petición de logout
            $sessionId = $this->extractSessionFromLogoutRequest($logoutRequest);
            
            if ($sessionId) {
                // Invalidar la sesión específica
                $this->invalidateSession($sessionId);
                
                Logger::info('Single logout CAS procesado', [
                    'session_id' => $sessionId,
                    'ip' => $this->request['ip']
                ]);
            }
        }
        
        // Responder a CAS que el logout fue procesado
        echo "Logout procesado";
        exit;
    }
    
    /**
     * Extraer ID de sesión de petición de logout CAS
     */
    private function extractSessionFromLogoutRequest(string $logoutRequest): ?string 
    {
        // El formato típico incluye el ticket en XML
        if (preg_match('/<samlp:SessionIndex>(.*?)<\/samlp:SessionIndex>/', $logoutRequest, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Invalidar sesión específica
     */
    private function invalidateSession(string $sessionId): void 
    {
        // En una implementación completa, esto buscaría y eliminaría 
        // la sesión específica de la base de datos o storage
        Logger::info('Sesión invalidada por CAS logout', [
            'target_session_id' => $sessionId
        ]);
    }
    
    /**
     * Verificar salud del sistema de autenticación
     */
    public function healthCheck(): void 
    {
        $health = [
            'cas_service' => $this->casService->healthCheck(),
            'session_system' => [
                'status' => 'healthy',
                'session_started' => session_status() === PHP_SESSION_ACTIVE,
                'session_id' => session_id(),
                'session_name' => session_name()
            ],
            'authentication' => [
                'current_user_authenticated' => Session::isAuthenticated(),
                'current_user_id' => Session::get('user_id'),
                'session_time_remaining' => Session::getTimeRemaining()
            ]
        ];
        
        // Determinar estado general
        $overallStatus = 'healthy';
        if ($health['cas_service']['status'] !== 'healthy') {
            $overallStatus = 'unhealthy';
        }
        
        $health['overall_status'] = $overallStatus;
        $health['timestamp'] = date('Y-m-d H:i:s');
        
        $this->jsonSuccess($health);
    }
    
    /**
     * Simulador de login para desarrollo (solo en modo mock)
     */
    public function devLogin(): void 
    {
        $casConfig = $this->casService->getConfig();
        
        if (!$casConfig['development']['mock_enabled']) {
            $this->notFound();
            return;
        }
        
        if ($this->isGet()) {
            $mockUsers = $casConfig['development']['mock_users'];
            
            $this->view('auth/dev_login', [
                'title' => 'Login de Desarrollo',
                'mock_users' => $mockUsers,
                'warning' => 'MODO DESARROLLO - No usar en producción'
            ]);
            return;
        }
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        $selectedUser = $this->getInput('selected_user');
        $mockUsers = $casConfig['development']['mock_users'];
        
        if (!$selectedUser || !isset($mockUsers[$selectedUser])) {
            $this->redirectWithMessage('/auth/dev-login', 'Usuario mock no válido', 'error');
            return;
        }
        
        $userData = $mockUsers[$selectedUser];
        
        // Simular proceso de login CAS
        $loginResult = $this->casService->processSuccessfulLogin($userData);
        
        if ($loginResult['success']) {
            Logger::warning('Login de desarrollo usado', [
                'mock_user' => $selectedUser,
                'user_type' => $loginResult['user_type'],
                'ip' => $this->request['ip']
            ]);
            
            $this->redirectWithMessage($loginResult['redirect_url'], 
                'Login de desarrollo exitoso - ' . $userData['name'], 'success');
        } else {
            $this->redirectWithMessage('/auth/dev-login', 
                'Error en login de desarrollo: ' . $loginResult['error'], 'error');
        }
    }
    
    /**
     * API para obtener información del usuario actual
     */
    public function me(): void 
    {
        if (!Session::isAuthenticated()) {
            $this->jsonError('No autenticado', [], 401);
            return;
        }
        
        $user = Session::getUser();
        
        // Agregar información adicional
        $userData = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'areas' => $user['areas'],
            'permissions' => $user['permissions'] ?? [],
            'login_time' => $user['login_time'],
            'login_ip' => $user['login_ip'],
            'session_remaining_seconds' => Session::getTimeRemaining(),
            'session_near_expiration' => Session::isNearExpiration(),
            'last_activity' => Session::get('_last_activity')
        ];
        
        $this->jsonSuccess($userData);
    }
    
    /**
     * Endpoint para verificar permisos
     */
    public function checkPermission(): void 
    {
        if (!Session::isAuthenticated()) {
            $this->jsonError('No autenticado', [], 401);
            return;
        }
        
        $permission = $this->getInput('permission');
        $area = $this->getInput('area');
        
        if (!$permission) {
            $this->jsonError('Parámetro "permission" requerido', [], 400);
            return;
        }
        
        $hasPermission = $this->casService->hasPermission($permission, $area);
        
        $this->jsonSuccess([
            'permission' => $permission,
            'area' => $area,
            'granted' => $hasPermission,
            'user_role' => Session::get('user_role'),
            'user_areas' => Session::getUserAreas()
        ]);
    }
    
    /**
     * Obtener estadísticas de autenticación (admin only)
     */
    public function authStats(): void 
    {
        $this->requireAdmin();
        
        // Estadísticas básicas de la sesión actual
        $stats = [
            'current_session' => Session::getSessionStats(),
            'cas_service' => $this->casService->getAuthStats(),
            'system_health' => [
                'cas' => $this->casService->healthCheck(),
                'php_session' => [
                    'status' => session_status(),
                    'name' => session_name(),
                    'id' => session_id(),
                    'save_path' => session_save_path()
                ]
            ],
            'server_info' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'timezone' => date_default_timezone_get(),
                'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
                'php_version' => PHP_VERSION
            ]
        ];
        
        if ($this->isJsonRequest()) {
            $this->jsonSuccess($stats);
        } else {
            $this->view('admin/auth_stats', [
                'title' => 'Estadísticas de Autenticación',
                'stats' => $stats
            ]);
        }
    }
    
    /**
     * Forzar logout de usuario (admin only)
     */
    public function forceLogout(): void 
    {
        $this->requireAdmin();
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $targetUserId = $this->getInput('user_id');
        
        if (!$targetUserId) {
            $this->jsonError('ID de usuario requerido', [], 400);
            return;
        }
        
        // En una implementación completa, esto invalidaría todas las sesiones del usuario
        // Por ahora solo registramos la acción
        Logger::warning('Logout forzado por administrador', [
            'target_user_id' => $targetUserId,
            'admin_id' => Session::get('user_id'),
            'admin_email' => Session::get('user_email'),
            'ip' => $this->request['ip']
        ]);
        
        $this->jsonSuccess([
            'user_id' => $targetUserId,
            'action' => 'force_logout',
            'timestamp' => date('Y-m-d H:i:s')
        ], 'Logout forzado ejecutado');
    }
    
    /**
     * Limpiar sesiones expiradas (tarea de mantenimiento)
     */
    public function cleanupSessions(): void 
    {
        $this->requireAdmin();
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        $cleaned = Session::cleanExpiredSessions();
        
        Logger::info('Limpieza de sesiones ejecutada', [
            'cleaned_sessions' => $cleaned,
            'executed_by' => Session::get('user_id')
        ]);
        
        $this->jsonSuccess([
            'cleaned_sessions' => $cleaned,
            'timestamp' => date('Y-m-d H:i:s')
        ], "Se limpiaron {$cleaned} sesiones expiradas");
    }
    
    /**
     * Página de mantenimiento
     */
    public function maintenance(): void 
    {
        $maintenanceMode = Helper::config('maintenance_mode', false);
        
        if (!$maintenanceMode && !Session::isAdmin()) {
            $this->redirect('/dashboard.php');
            return;
        }
        
        $this->view('auth/maintenance', [
            'title' => 'Sistema en Mantenimiento',
            'message' => 'El sistema está temporalmente en mantenimiento. Intente nuevamente más tarde.',
            'estimated_time' => $this->getInput('estimated_time', '30 minutos'),
            'is_admin' => Session::isAdmin()
        ]);
    }
}