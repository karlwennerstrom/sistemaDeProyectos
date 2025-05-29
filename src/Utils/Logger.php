<?php
/**
 * Sistema de Logs
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Utils;

class Logger 
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    private static $config;
    private static $initialized = false;
    
    /**
     * Inicializar el logger
     */
    private static function init(): void 
    {
        if (!self::$initialized) {
            self::$config = include __DIR__ . '/../../config/app.php';
            self::$initialized = true;
            
            // Crear directorio de logs si no existe
            $logPath = self::$config['logging']['path'];
            if (!is_dir($logPath)) {
                mkdir($logPath, 0755, true);
            }
        }
    }
    
    /**
     * Log de emergencia - sistema inutilizable
     */
    public static function emergency(string $message, array $context = []): void 
    {
        self::log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log de alerta - acción debe tomarse inmediatamente
     */
    public static function alert(string $message, array $context = []): void 
    {
        self::log(self::ALERT, $message, $context);
    }
    
    /**
     * Log crítico - condiciones críticas
     */
    public static function critical(string $message, array $context = []): void 
    {
        self::log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log de error - errores de runtime que no requieren acción inmediata
     */
    public static function error(string $message, array $context = []): void 
    {
        self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Log de advertencia - ocurrencias excepcionales que no son errores
     */
    public static function warning(string $message, array $context = []): void 
    {
        self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log de aviso - eventos normales pero significativos
     */
    public static function notice(string $message, array $context = []): void 
    {
        self::log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log informativo - eventos informativos interesantes
     */
    public static function info(string $message, array $context = []): void 
    {
        self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log de debug - información detallada de debug
     */
    public static function debug(string $message, array $context = []): void 
    {
        self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log de autenticación
     */
    public static function auth(string $message, array $context = []): void 
    {
        self::writeToFile('auth', self::INFO, $message, $context);
    }
    
    /**
     * Log de actividad de usuario
     */
    public static function activity(string $message, array $context = []): void 
    {
        $context['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $context['timestamp'] = date('Y-m-d H:i:s');
        
        self::writeToFile('activity', self::INFO, $message, $context);
    }
    
    /**
     * Log principal del sistema
     */
    public static function log(string $level, string $message, array $context = []): void 
    {
        self::init();
        
        $configLevel = self::$config['logging']['level'] ?? 'info';
        
        if (!self::shouldLog($level, $configLevel)) {
            return;
        }
        
        // Log a archivo principal
        self::writeToFile('app', $level, $message, $context);
        
        // Log a archivo específico si es error
        if (in_array($level, [self::ERROR, self::CRITICAL, self::ALERT, self::EMERGENCY])) {
            self::writeToFile('error', $level, $message, $context);
        }
    }
    
    /**
     * Escribir log a archivo específico
     */
    private static function writeToFile(string $channel, string $level, string $message, array $context = []): void 
    {
        self::init();
        
        $logPath = self::$config['logging']['path'];
        $filename = $channel . '.log';
        $filepath = rtrim($logPath, '/') . '/' . $filename;
        
        $timestamp = date('Y-m-d H:i:s');
        $sessionId = session_id() ?: 'no-session';
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid();
        
        // Formatear el mensaje
        $logEntry = self::formatLogEntry($timestamp, $level, $message, $context, [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'request_id' => $requestId
        ]);
        
        // Escribir al archivo
        file_put_contents($filepath, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        // Rotar logs si es necesario
        self::rotateLogsIfNeeded($filepath);
    }
    
    /**
     * Formatear entrada de log
     */
    private static function formatLogEntry(string $timestamp, string $level, string $message, array $context, array $metadata): string 
    {
        $levelUpper = strtoupper($level);
        
        $entry = "[{$timestamp}] [{$levelUpper}]";
        
        if (!empty($metadata['user_id']) && $metadata['user_id'] !== 'anonymous') {
            $entry .= " [USER:{$metadata['user_id']}]";
        }
        
        if (!empty($metadata['request_id'])) {
            $entry .= " [REQ:{$metadata['request_id']}]";
        }
        
        $entry .= " {$message}";
        
        // Agregar contexto si existe
        if (!empty($context)) {
            $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);
            $entry .= " | Context: {$contextJson}";
        }
        
        // Agregar stack trace para errores críticos
        if (in_array($level, [self::ERROR, self::CRITICAL, self::ALERT, self::EMERGENCY])) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $caller = $trace[2] ?? null;
            if ($caller) {
                $file = basename($caller['file'] ?? 'unknown');
                $line = $caller['line'] ?? 'unknown';
                $function = $caller['function'] ?? 'unknown';
                $entry .= " | Called from: {$file}:{$line} in {$function}()";
            }
        }
        
        return $entry;
    }
    
    /**
     * Determinar si se debe loggear según el nivel
     */
    private static function shouldLog(string $level, string $configLevel): bool 
    {
        $levels = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::NOTICE => 2,
            self::WARNING => 3,
            self::ERROR => 4,
            self::CRITICAL => 5,
            self::ALERT => 6,
            self::EMERGENCY => 7
        ];
        
        $currentLevel = $levels[$level] ?? 0;
        $minLevel = $levels[$configLevel] ?? 1;
        
        return $currentLevel >= $minLevel;
    }
    
    /**
     * Rotar logs si exceden el tamaño máximo
     */
    private static function rotateLogsIfNeeded(string $filepath): void 
    {
        if (!file_exists($filepath)) {
            return;
        }
        
        $maxSize = 10 * 1024 * 1024; // 10MB
        $maxFiles = 5;
        
        if (filesize($filepath) > $maxSize) {
            // Rotar archivos existentes
            for ($i = $maxFiles - 1; $i > 0; $i--) {
                $oldFile = $filepath . '.' . $i;
                $newFile = $filepath . '.' . ($i + 1);
                
                if (file_exists($oldFile)) {
                    if ($i == $maxFiles - 1) {
                        unlink($oldFile); // Eliminar el más antiguo
                    } else {
                        rename($oldFile, $newFile);
                    }
                }
            }
            
            // Mover archivo actual
            rename($filepath, $filepath . '.1');
        }
    }
    
    /**
     * Limpiar logs antiguos
     */
    public static function cleanOldLogs(int $daysToKeep = 30): int 
    {
        self::init();
        
        $logPath = self::$config['logging']['path'];
        $files = glob($logPath . '/*.log*');
        $deletedCount = 0;
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            self::info("Limpieza de logs completada", [
                'deleted_files' => $deletedCount,
                'days_kept' => $daysToKeep
            ]);
        }
        
        return $deletedCount;
    }
    
    /**
     * Obtener logs recientes
     */
    public static function getRecentLogs(string $channel = 'app', int $lines = 100): array 
    {
        self::init();
        
        $logPath = self::$config['logging']['path'];
        $filepath = rtrim($logPath, '/') . '/' . $channel . '.log';
        
        if (!file_exists($filepath)) {
            return [];
        }
        
        $logs = [];
        $file = new \SplFileObject($filepath);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $logs[] = self::parseLogLine($line);
            }
            $file->next();
        }
        
        return array_reverse($logs);
    }
    
    /**
     * Parsear línea de log
     */
    private static function parseLogLine(string $line): array 
    {
        $pattern = '/\[([^\]]+)\]\s+\[([^\]]+)\](?:\s+\[USER:([^\]]+)\])?(?:\s+\[REQ:([^\]]+)\])?\s+(.+?)(?:\s+\|\s+Context:\s+(.+?))?(?:\s+\|\s+Called from:\s+(.+))?$/';
        
        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1] ?? '',
                'level' => $matches[2] ?? '',
                'user_id' => $matches[3] ?? null,
                'request_id' => $matches[4] ?? null,
                'message' => $matches[5] ?? '',
                'context' => !empty($matches[6]) ? json_decode($matches[6], true) : null,
                'caller' => $matches[7] ?? null,
                'raw' => $line
            ];
        }
        
        return [
            'timestamp' => '',
            'level' => 'UNKNOWN',
            'user_id' => null,
            'request_id' => null,
            'message' => $line,
            'context' => null,
            'caller' => null,
            'raw' => $line
        ];
    }
    
    /**
     * Obtener estadísticas de logs
     */
    public static function getLogStats(string $channel = 'app'): array 
    {
        $logs = self::getRecentLogs($channel, 1000);
        
        $stats = [
            'total_entries' => count($logs),
            'levels' => [],
            'users' => [],
            'recent_errors' => [],
            'hourly_distribution' => []
        ];
        
        foreach ($logs as $log) {
            // Estadísticas por nivel
            $level = $log['level'];
            $stats['levels'][$level] = ($stats['levels'][$level] ?? 0) + 1;
            
            // Estadísticas por usuario
            if (!empty($log['user_id'])) {
                $stats['users'][$log['user_id']] = ($stats['users'][$log['user_id']] ?? 0) + 1;
            }
            
            // Errores recientes
            if (in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'])) {
                $stats['recent_errors'][] = [
                    'timestamp' => $log['timestamp'],
                    'level' => $level,
                    'message' => $log['message'],
                    'user_id' => $log['user_id']
                ];
            }
            
            // Distribución por hora
            if (!empty($log['timestamp'])) {
                $hour = date('H', strtotime($log['timestamp']));
                $stats['hourly_distribution'][$hour] = ($stats['hourly_distribution'][$hour] ?? 0) + 1;
            }
        }
        
        // Limitar errores recientes a los últimos 10
        $stats['recent_errors'] = array_slice($stats['recent_errors'], -10);
        
        return $stats;
    }
    
    /**
     * Buscar en logs
     */
    public static function searchLogs(string $query, string $channel = 'app', int $maxResults = 50): array 
    {
        $logs = self::getRecentLogs($channel, 1000);
        $results = [];
        
        foreach ($logs as $log) {
            if (stripos($log['message'], $query) !== false || 
                stripos($log['raw'], $query) !== false) {
                $results[] = $log;
                
                if (count($results) >= $maxResults) {
                    break;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Exportar logs a archivo
     */
    public static function exportLogs(string $channel = 'app', string $format = 'json'): string 
    {
        $logs = self::getRecentLogs($channel, 1000);
        $exportPath = 'exports/';
        
        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0755, true);
        }
        
        $filename = $exportPath . $channel . '_export_' . date('Y-m-d_H-i-s') . '.' . $format;
        
        switch ($format) {
            case 'json':
                file_put_contents($filename, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
                
            case 'csv':
                $file = fopen($filename, 'w');
                fputcsv($file, ['Timestamp', 'Level', 'User ID', 'Message', 'Context']);
                
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log['timestamp'],
                        $log['level'],
                        $log['user_id'] ?? '',
                        $log['message'],
                        $log['context'] ? json_encode($log['context']) : ''
                    ]);
                }
                fclose($file);
                break;
                
            case 'txt':
                $content = '';
                foreach ($logs as $log) {
                    $content .= $log['raw'] . "\n";
                }
                file_put_contents($filename, $content);
                break;
        }
        
        self::info("Logs exportados", [
            'channel' => $channel,
            'format' => $format,
            'file' => $filename,
            'entries' => count($logs)
        ]);
        
        return $filename;
    }
    
    /**
     * Verificar salud del sistema de logs
     */
    public static function healthCheck(): array 
    {
        self::init();
        
        $logPath = self::$config['logging']['path'];
        
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'log_directory_writable' => is_writable($logPath),
            'log_directory_exists' => is_dir($logPath),
            'disk_space_mb' => round(disk_free_space($logPath) / 1024 / 1024, 2),
            'log_files' => []
        ];
        
        if (!$health['log_directory_exists']) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = 'Log directory does not exist';
        }
        
        if (!$health['log_directory_writable']) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = 'Log directory is not writable';
        }
        
        if ($health['disk_space_mb'] < 100) {
            $health['status'] = 'warning';
            $health['issues'][] = 'Low disk space';
        }
        
        // Verificar archivos de log
        $logFiles = glob($logPath . '/*.log');
        foreach ($logFiles as $file) {
            $health['log_files'][basename($file)] = [
                'size_mb' => round(filesize($file) / 1024 / 1024, 2),
                'last_modified' => date('Y-m-d H:i:s', filemtime($file)),
                'writable' => is_writable($file)
            ];
        }
        
        return $health;
    }
}