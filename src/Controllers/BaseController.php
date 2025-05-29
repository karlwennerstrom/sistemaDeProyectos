<?php
/**
 * Controlador Base
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Controllers;

use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Session;
use UC\ApprovalSystem\Utils\Helper;
use UC\ApprovalSystem\Utils\Validator;
use UC\ApprovalSystem\Services\CASService;

abstract class BaseController 
{
    protected $casService;
    protected $request;
    protected $response;
    
    public function __construct() 
    {
        $this->casService = new CASService();
        $this->initializeRequest();
        $this->initializeResponse();
        
        // Iniciar sesión automáticamente
        Session::start();
        
        // Log de la petición
        $this->logRequest();
    }
    
    /**
     * Inicializar datos de la petición
     */
    private function initializeRequest(): void 
    {
        $this->request = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'query' => $_GET,
            'body' => $_POST,
            'files' => $_FILES,
            'headers' => $this->getHeaders(),
            'ip' => Helper::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => time()
        ];
    }
    
    /**
     * Inicializar respuesta
     */
    private function initializeResponse(): void 
    {
        $this->response = [
            'status_code' => 200,
            'headers' => [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Frame-Options' => 'DENY',
                'X-Content-Type-Options' => 'nosniff',
                'X-XSS-Protection' => '1; mode=block'
            ],
            'body' => ''
        ];
    }
    
    /**
     * Obtener headers de la petición
     */
    private function getHeaders(): array 
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('HTTP_', '', $key);
                $header = str_replace('_', '-', $header);
                $headers[ucwords(strtolower($header), '-')] = $value;
            }
        }
        return $headers;
    }
    
    /**
     * Log de la petición
     */
    private function logRequest(): void 
    {
        Logger::activity('HTTP Request', [
            'method' => $this->request['method'],
            'uri' => $this->request['uri'],
            'ip' => $this->request['ip'],
            'user_agent' => $this->request['user_agent'],
            'user_id' => Session::get('user_id'),
            'controller' => static::class
        ]);
    }
    
    /**
     * Requerir autenticación
     */
    protected function requireAuth(): void 
    {
        if (!Session::isAuthenticated()) {
            Logger::warning('Acceso no autenticado', [
                'uri' => $this->request['uri'],
                'ip' => $this->request['ip']
            ]);
            
            $this->redirectToLogin();
        }
        
        // Extender sesión si está autenticado
        Session::extend();
    }
    
    /**
     * Requerir rol de administrador
     */
    protected function requireAdmin(): void 
    {
        $this->requireAuth();
        
        if (!Session::isAdmin()) {
            Logger::warning('Acceso no autorizado - requiere admin', [
                'uri' => $this->request['uri'],
                'user_id' => Session::get('user_id'),
                'user_role' => Session::get('user_role')
            ]);
            
            $this->forbidden('Acceso denegado. Se requieren permisos de administrador.');
        }
    }
    
    /**
     * Requerir acceso a área específica
     */
    protected function requireAreaAccess(string $area): void 
    {
        $this->requireAuth();
        
        if (!Session::hasAreaAccess($area)) {
            Logger::warning('Acceso no autorizado - área restringida', [
                'uri' => $this->request['uri'],
                'required_area' => $area,
                'user_id' => Session::get('user_id'),
                'user_areas' => Session::getUserAreas()
            ]);
            
            $this->forbidden("Acceso denegado al área: {$area}");
        }
    }
    
    /**
     * Validar token CSRF
     */
    protected function validateCSRF(): bool 
    {
        $token = $this->getInput('csrf_token');
        
        if (!$token || !Session::verifyCsrfToken($token)) {
            Logger::warning('Token CSRF inválido', [
                'uri' => $this->request['uri'],
                'user_id' => Session::get('user_id'),
                'provided_token' => $token ? substr($token, 0, 10) . '...' : 'none'
            ]);
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtener valor de entrada
     */
    protected function getInput(string $key, $default = null) 
    {
        // Primero verificar POST, luego GET
        if (isset($this->request['body'][$key])) {
            return Helper::sanitize($this->request['body'][$key]);
        }
        
        if (isset($this->request['query'][$key])) {
            return Helper::sanitize($this->request['query'][$key]);
        }
        
        return $default;
    }
    
    /**
     * Obtener todos los inputs
     */
    protected function getAllInputs(): array 
    {
        $inputs = array_merge($this->request['query'], $this->request['body']);
        
        // Sanitizar todos los valores
        array_walk_recursive($inputs, function(&$value) {
            if (is_string($value)) {
                $value = Helper::sanitize($value);
            }
        });
        
        return $inputs;
    }
    
    /**
     * Obtener archivo subido
     */
    protected function getFile(string $key): ?array 
    {
        return $this->request['files'][$key] ?? null;
    }
    
    /**
     * Validar datos de entrada
     */
    protected function validate(array $data, array $rules, array $messages = []): array 
    {
        $validator = Validator::make($data, $rules);
        
        if (!empty($messages)) {
            $validator->setMessages($messages);
        }
        
        $result = [
            'valid' => $validator->validate(),
            'errors' => $validator->errors()
        ];
        
        if (!$result['valid']) {
            Logger::debug('Validación fallida', [
                'errors' => $result['errors'],
                'data_keys' => array_keys($data)
            ]);
        }
        
        return $result;
    }
    
    /**
     * Responder con JSON
     */
    protected function json(array $data, int $statusCode = 200): void 
    {
        $this->response['status_code'] = $statusCode;
        $this->response['headers']['Content-Type'] = 'application/json';
        $this->response['body'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        $this->sendResponse();
    }
    
    /**
     * Responder con éxito JSON
     */
    protected function jsonSuccess($data = [], string $message = 'Operación exitosa'): void 
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Responder con error JSON
     */
    protected function jsonError(string $message, $errors = [], int $statusCode = 400): void 
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], $statusCode);
    }
    
    /**
     * Renderizar vista
     */
    protected function view(string $template, array $data = []): void 
    {
        // Agregar variables globales a la vista
        $data = array_merge($data, [
            'user' => Session::getUser(),
            'is_authenticated' => Session::isAuthenticated(),
            'is_admin' => Session::isAdmin(),
            'csrf_token' => Session::getCsrfToken(),
            'flash_messages' => Session::getFlash(),
            'app_name' => Helper::config('name', 'Sistema UC'),
            'current_url' => $this->request['uri']
        ]);
        
        $templatePath = $this->getTemplatePath($template);
        
        if (!file_exists($templatePath)) {
            Logger::error('Template no encontrado', [
                'template' => $template,
                'path' => $templatePath
            ]);
            
            $this->notFound('Página no encontrada');
            return;
        }
        
        // Extraer variables para la vista
        extract($data);
        
        ob_start();
        include $templatePath;
        $this->response['body'] = ob_get_clean();
        
        $this->sendResponse();
    }
    
    /**
     * Obtener ruta del template
     */
    private function getTemplatePath(string $template): string 
    {
        $basePath = __DIR__ . '/../../views/';
        
        // Si el template no tiene extensión, agregar .php
        if (pathinfo($template, PATHINFO_EXTENSION) === '') {
            $template .= '.php';
        }
        
        return $basePath . $template;
    }
    
    /**
     * Redireccionar
     */
    protected function redirect(string $url, int $statusCode = 302): void 
    {
        $this->response['status_code'] = $statusCode;
        $this->response['headers']['Location'] = $url;
        
        Logger::debug('Redirección', [
            'from' => $this->request['uri'],
            'to' => $url,
            'status_code' => $statusCode
        ]);
        
        $this->sendResponse();
    }
    
    /**
     * Redireccionar con mensaje flash
     */
    protected function redirectWithMessage(string $url, string $message, string $type = 'success'): void 
    {
        Session::flash($type, $message);
        $this->redirect($url);
    }
    
    /**
     * Redireccionar al login
     */
    protected function redirectToLogin(): void 
    {
        $returnUrl = $this->request['uri'];
        $this->redirect("/login.php?return_url=" . urlencode($returnUrl));
    }
    
    /**
     * Respuesta 404
     */
    protected function notFound(string $message = 'Página no encontrada'): void 
    {
        $this->response['status_code'] = 404;
        
        if ($this->isJsonRequest()) {
            $this->jsonError($message, [], 404);
        } else {
            $this->view('errors/404', ['message' => $message]);
        }
    }
    
    /**
     * Respuesta 403
     */
    protected function forbidden(string $message = 'Acceso denegado'): void 
    {
        $this->response['status_code'] = 403;
        
        if ($this->isJsonRequest()) {
            $this->jsonError($message, [], 403);
        } else {
            $this->view('errors/403', ['message' => $message]);
        }
    }
    
    /**
     * Respuesta 500
     */
    protected function internalError(string $message = 'Error interno del servidor'): void 
    {
        $this->response['status_code'] = 500;
        
        Logger::error('Error interno del servidor', [
            'message' => $message,
            'uri' => $this->request['uri'],
            'user_id' => Session::get('user_id')
        ]);
        
        if ($this->isJsonRequest()) {
            $this->jsonError($message, [], 500);
        } else {
            $this->view('errors/500', ['message' => $message]);
        }
    }
    
    /**
     * Verificar si es petición JSON
     */
    protected function isJsonRequest(): bool 
    {
        $contentType = $this->request['headers']['Content-Type'] ?? '';
        $accept = $this->request['headers']['Accept'] ?? '';
        
        return strpos($contentType, 'application/json') !== false ||
               strpos($accept, 'application/json') !== false ||
               $this->getInput('format') === 'json';
    }
    
    /**
     * Verificar si es petición POST
     */
    protected function isPost(): bool 
    {
        return $this->request['method'] === 'POST';
    }
    
    /**
     * Verificar si es petición GET
     */
    protected function isGet(): bool 
    {
        return $this->request['method'] === 'GET';
    }
    
    /**
     * Verificar si es petición PUT
     */
    protected function isPut(): bool 
    {
        return $this->request['method'] === 'PUT' || 
               $this->getInput('_method') === 'PUT';
    }
    
    /**
     * Verificar si es petición DELETE
     */
    protected function isDelete(): bool 
    {
        return $this->request['method'] === 'DELETE' || 
               $this->getInput('_method') === 'DELETE';
    }
    
    /**
     * Obtener parámetros de paginación
     */
    protected function getPaginationParams(): array 
    {
        $page = max(1, (int) $this->getInput('page', 1));
        $perPage = max(1, min(100, (int) $this->getInput('per_page', 10)));
        
        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage
        ];
    }
    
    /**
     * Manejar excepción
     */
    protected function handleException(\Exception $e): void 
    {
        Logger::error('Excepción en controlador: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'controller' => static::class,
            'uri' => $this->request['uri']
        ]);
        
        // En desarrollo mostrar detalles, en producción mensaje genérico
        if (Helper::config('debug', false)) {
            $message = $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine();
        } else {
            $message = 'Ha ocurrido un error interno. Por favor intente nuevamente.';
        }
        
        $this->internalError($message);
    }
    
    /**
     * Enviar respuesta HTTP
     */
    private function sendResponse(): void 
    {
        // Establecer código de estado
        http_response_code($this->response['status_code']);
        
        // Enviar headers
        foreach ($this->response['headers'] as $name => $value) {
            header("{$name}: {$value}");
        }
        
        // Enviar cuerpo de la respuesta
        echo $this->response['body'];
        
        // Log de la respuesta
        Logger::debug('HTTP Response', [
            'status_code' => $this->response['status_code'],
            'content_type' => $this->response['headers']['Content-Type'] ?? 'unknown',
            'body_length' => strlen($this->response['body']),
            'uri' => $this->request['uri']
        ]);
        
        exit;
    }
    
    /**
     * Obtener datos del usuario actual
     */
    protected function getCurrentUser(): ?array 
    {
        return Session::getUser();
    }
    
    /**
     * Obtener ID del usuario actual
     */
    protected function getCurrentUserId(): ?int 
    {
        return Session::get('user_id');
    }
    
    /**
     * Verificar permisos del usuario actual
     */
    protected function userCan(string $permission, string $area = null): bool 
    {
        return $this->casService->hasPermission($permission, $area);
    }
    
    /**
     * Formatear datos para respuesta API
     */
    protected function formatApiResponse($data, string $message = '', array $meta = []): array 
    {
        $response = [
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        
        return $response;
    }
    
    /**
     * Agregar breadcrumbs a los datos de la vista
     */
    protected function addBreadcrumbs(array &$data, array $breadcrumbs): void 
    {
        $data['breadcrumbs'] = Helper::generateBreadcrumbs($breadcrumbs);
    }
    
    /**
     * Verificar límite de tasa (rate limiting)
     */
    protected function checkRateLimit(string $action, int $maxAttempts = 60, int $timeWindow = 60): bool 
    {
        $key = "rate_limit:{$action}:" . $this->request['ip'];
        
        // Implementación básica usando sesión (en producción usar Redis/Memcached)
        $attempts = Session::get($key, 0);
        $windowStart = Session::get($key . '_window', time());
        
        // Reset si ha pasado el tiempo
        if (time() - $windowStart > $timeWindow) {
            $attempts = 0;
            $windowStart = time();
        }
        
        $attempts++;
        
        Session::set($key, $attempts);
        Session::set($key . '_window', $windowStart);
        
        if ($attempts > $maxAttempts) {
            Logger::warning('Rate limit excedido', [
                'action' => $action,
                'ip' => $this->request['ip'],
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts
            ]);
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Limpiar datos de salida (sanitización para XSS)
     */
    protected function sanitizeOutput($data) 
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeOutput'], $data);
        }
        
        if (is_string($data)) {
            return Helper::escape($data);
        }
        
        return $data;
    }
    
    /**
     * Método abstracto que deben implementar los controladores hijos
     */
    abstract public function index();
}