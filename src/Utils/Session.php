<?php
/**
 * Sistema de Manejo de Sesiones
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Utils;

class Session 
{
    private static $started = false;
    private static $config;
    
    /**
     * Inicializar sesión
     */
    public static function start(): void 
    {
        if (self::$started) {
            return;
        }
        
        self::$config = include __DIR__ . '/../../config/app.php';
        
        // Configurar parámetros de sesión
        self::configureSession();
        
        // Iniciar sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            self::$started = true;
            
            // Regenerar ID si es necesario
            self::regenerateIfNeeded();
            
            // Verificar timeout
            self::checkTimeout();
            
            Logger::debug('Sesión iniciada', [
                'session_id' => session_id(),
                'user_id' => self::get('user_id'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
    }
    
    /**
     * Configurar parámetros de sesión
     */
    private static function configureSession(): void 
    {
        $sessionConfig = self::$config['session'];
        
        ini_set('session.name', $sessionConfig['name']);
        ini_set('session.gc_maxlifetime', $sessionConfig['lifetime']);
        ini_set('session.cookie_lifetime', $sessionConfig['lifetime']);
        ini_set('session.cookie_path', $sessionConfig['path']);
        ini_set('session.cookie_domain', $sessionConfig['domain']);
        ini_set('session.cookie_secure', $sessionConfig['secure'] ? '1' : '0');
        ini_set('session.cookie_httponly', $sessionConfig['httponly'] ? '1' : '0');
        ini_set('session.cookie_samesite', $sessionConfig['samesite']);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.entropy_length', '32');
        ini_set('session.hash_function', 'sha256');
    }
    
    /**
     * Obtener valor de sesión
     */
    public static function get(string $key, $default = null) 
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Establecer valor de sesión
     */
    public static function set(string $key, $value): void 
    {
        self::start();
        $_SESSION[$key] = $value;
        
        Logger::debug('Valor de sesión establecido', [
            'key' => $key,
            'session_id' => session_id()
        ]);
    }
    
    /**
     * Verificar si existe una clave en sesión
     */
    public static function has(string $key): bool 
    {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Eliminar valor de sesión
     */
    public static function remove(string $key): void 
    {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            
            Logger::debug('Valor de sesión eliminado', [
                'key' => $key,
                'session_id' => session_id()
            ]);
        }
    }
    
    /**
     * Limpiar toda la sesión
     */
    public static function clear(): void 
    {
        self::start();
        
        $oldSessionId = session_id();
        $_SESSION = [];
        
        Logger::info('Sesión limpiada', [
            'old_session_id' => $oldSessionId
        ]);
    }
    
    /**
     * Destruir sesión completamente
     */
    public static function destroy(): void 
    {
        self::start();
        
        $oldSessionId = session_id();
        $userId = self::get('user_id');
        
        // Limpiar variables de sesión
        $_SESSION = [];
        
        // Eliminar cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destruir sesión
        session_destroy();
        self::$started = false;
        
        Logger::info('Sesión destruida', [
            'old_session_id' => $oldSessionId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Regenerar ID de sesión
     */
    public static function regenerate(bool $deleteOldSession = true): void 
    {
        self::start();
        
        $oldSessionId = session_id();
        session_regenerate_id($deleteOldSession);
        $newSessionId = session_id();
        
        // Actualizar timestamp de regeneración
        self::set('_regenerated_at', time());
        
        Logger::info('ID de sesión regenerado', [
            'old_session_id' => $oldSessionId,
            'new_session_id' => $newSessionId,
            'user_id' => self::get('user_id')
        ]);
    }
    
    /**
     * Regenerar si es necesario (por seguridad)
     */
    private static function regenerateIfNeeded(): void 
    {
        $lastRegeneration = self::get('_regenerated_at', 0);
        $regenerateInterval = self::$config['security']['session_regenerate_interval'];
        
        if (time() - $lastRegeneration > $regenerateInterval) {
            self::regenerate();
        }
    }
    
    /**
     * Verificar timeout de sesión
     */
    private static function checkTimeout(): void 
    {
        $lastActivity = self::get('_last_activity');
        $timeout = self::$config['session']['lifetime'];
        
        if ($lastActivity && (time() - $lastActivity > $timeout)) {
            Logger::warning('Sesión expirada por timeout', [
                'session_id' => session_id(),
                'user_id' => self::get('user_id'),
                'last_activity' => date('Y-m-d H:i:s', $lastActivity),
                'timeout_seconds' => $timeout
            ]);
            
            self::destroy();
            return;
        }
        
        // Actualizar última actividad
        self::set('_last_activity', time());
    }
    
    /**
     * Establecer información del usuario autenticado
     */
    public static function setUser(array $userData): void 
    {
        self::start();
        
        self::set('authenticated', true);
        self::set('user_id', $userData['id'] ?? null);
        self::set('user_email', $userData['email'] ?? null);
        self::set('user_name', $userData['name'] ?? null);
        self::set('user_role', $userData['role'] ?? 'user');
        self::set('user_areas', $userData['areas'] ?? []);
        self::set('login_time', time());
        self::set('login_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        // Regenerar sesión por seguridad
        self::regenerate();
        
        Logger::auth('Usuario autenticado en sesión', [
            'user_id' => $userData['id'] ?? null,
            'email' => $userData['email'] ?? null,
            'role' => $userData['role'] ?? 'user',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    
    /**
     * Obtener información del usuario autenticado
     */
    public static function getUser(): ?array 
    {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => self::get('user_id'),
            'email' => self::get('user_email'),
            'name' => self::get('user_name'),
            'role' => self::get('user_role'),
            'areas' => self::get('user_areas', []),
            'login_time' => self::get('login_time'),
            'login_ip' => self::get('login_ip')
        ];
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public static function isAuthenticated(): bool 
    {
        self::start();
        return (bool) self::get('authenticated', false);
    }
    
    /**
     * Verificar si el usuario es administrador
     */
    public static function isAdmin(): bool 
    {
        return self::hasRole('admin');
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public static function hasRole(string $role): bool 
    {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $userRole = self::get('user_role');
        return $userRole === $role || $userRole === 'admin';
    }
    
    /**
     * Verificar si el usuario tiene acceso a un área específica
     */
    public static function hasAreaAccess(string $area): bool 
    {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        // Administradores tienen acceso a todas las áreas
        if (self::isAdmin()) {
            return true;
        }
        
        $userAreas = self::get('user_areas', []);
        return in_array($area, $userAreas);
    }
    
    /**
     * Obtener áreas accesibles por el usuario
     */
    public static function getUserAreas(): array 
    {
        if (!self::isAuthenticated()) {
            return [];
        }
        
        if (self::isAdmin()) {
            return array_keys(self::$config['areas']);
        }
        
        return self::get('user_areas', []);
    }
    
    /**
     * Manejar mensajes flash
     */
    public static function flash(string $type, string $message): void 
    {
        self::start();
        
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        
        if (!isset($_SESSION['_flash'][$type])) {
            $_SESSION['_flash'][$type] = [];
        }
        
        $_SESSION['_flash'][$type][] = $message;
    }
    
    /**
     * Obtener mensajes flash
     */
    public static function getFlash(string $type = null): array 
    {
        self::start();
        
        if ($type === null) {
            $flash = $_SESSION['_flash'] ?? [];
            unset($_SESSION['_flash']);
            return $flash;
        }
        
        $messages = $_SESSION['_flash'][$type] ?? [];
        unset($_SESSION['_flash'][$type]);
        
        return $messages;
    }
    
    /**
     * Verificar si hay mensajes flash
     */
    public static function hasFlash(string $type = null): bool 
    {
        self::start();
        
        if ($type === null) {
            return !empty($_SESSION['_flash']);
        }
        
        return !empty($_SESSION['_flash'][$type]);
    }
    
    /**
     * Generar y almacenar token CSRF
     */
    public static function generateCsrfToken(): string 
    {
        self::start();
        
        $token = bin2hex(random_bytes(32));
        self::set('_csrf_token', $token);
        self::set('_csrf_token_time', time());
        
        return $token;
    }
    
    /**
     * Verificar token CSRF
     */
    public static function verifyCsrfToken(string $token): bool 
    {
        self::start();
        
        $sessionToken = self::get('_csrf_token');
        $tokenTime = self::get('_csrf_token_time', 0);
        
        // Verificar si el token existe y no ha expirado (1 hora)
        if (!$sessionToken || (time() - $tokenTime > 3600)) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Obtener token CSRF actual
     */
    public static function getCsrfToken(): ?string 
    {
        self::start();
        
        $token = self::get('_csrf_token');
        $tokenTime = self::get('_csrf_token_time', 0);
        
        // Si no existe o ha expirado, generar uno nuevo
        if (!$token || (time() - $tokenTime > 3600)) {
            return self::generateCsrfToken();
        }
        
        return $token;
    }
    
    /**
     * Registrar actividad del usuario
     */
    public static function logActivity(string $action, array $context = []): void 
    {
        if (!self::isAuthenticated()) {
            return;
        }
        
        $context['user_id'] = self::get('user_id');
        $context['user_email'] = self::get('user_email');
        $context['session_id'] = session_id();
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        Logger::activity($action, $context);
    }
    
    /**
     * Obtener estadísticas de la sesión
     */
    public static function getSessionStats(): array 
    {
        self::start();
        
        return [
            'session_id' => session_id(),
            'authenticated' => self::isAuthenticated(),
            'user_id' => self::get('user_id'),
            'user_email' => self::get('user_email'),
            'user_role' => self::get('user_role'),
            'login_time' => self::get('login_time'),
            'last_activity' => self::get('_last_activity'),
            'login_ip' => self::get('login_ip'),
            'session_lifetime' => self::$config['session']['lifetime'],
            'time_remaining' => self::getTimeRemaining(),
            'csrf_token_exists' => self::has('_csrf_token'),
            'flash_messages' => count($_SESSION['_flash'] ?? [])
        ];
    }
    
    /**
     * Obtener tiempo restante de sesión
     */
    public static function getTimeRemaining(): int 
    {
        if (!self::isAuthenticated()) {
            return 0;
        }
        
        $lastActivity = self::get('_last_activity', time());
        $lifetime = self::$config['session']['lifetime'];
        $remaining = $lifetime - (time() - $lastActivity);
        
        return max(0, $remaining);
    }
    
    /**
     * Extender sesión
     */
    public static function extend(): void 
    {
        if (self::isAuthenticated()) {
            self::set('_last_activity', time());
            
            Logger::debug('Sesión extendida', [
                'user_id' => self::get('user_id'),
                'session_id' => session_id()
            ]);
        }
    }
    
    /**
     * Verificar si la sesión está próxima a expirar
     */
    public static function isNearExpiration(int $warningMinutes = 5): bool 
    {
        $remaining = self::getTimeRemaining();
        return $remaining > 0 && $remaining <= ($warningMinutes * 60);
    }
    
    /**
     * Limpiar sesiones expiradas (para ejecutar en cron)
     */
    public static function cleanExpiredSessions(): int 
    {
        $sessionPath = session_save_path() ?: sys_get_temp_dir();
        $files = glob($sessionPath . '/sess_*');
        $cleaned = 0;
        $maxLifetime = self::$config['session']['lifetime'];
        
        foreach ($files as $file) {
            if (filemtime($file) + $maxLifetime < time()) {
                unlink($file);
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            Logger::info('Sesiones expiradas limpiadas', [
                'cleaned_count' => $cleaned,
                'max_lifetime' => $maxLifetime
            ]);
        }
        
        return $cleaned;
    }
}