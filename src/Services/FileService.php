<?php
/**
 * Servicio de Gestión de Archivos
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Services;

use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Session;

class FileService 
{
    private $config;
    private $allowedExtensions;
    private $allowedMimeTypes;
    private $maxFileSize;
    private $uploadPath;
    private $templatePath;
    private $tempPath;
    
    public function __construct() 
    {
        $appConfig = include __DIR__ . '/../../config/app.php';
        $this->config = $appConfig['files'];
        
        $this->allowedExtensions = $this->config['allowed_extensions'];
        $this->allowedMimeTypes = $this->config['allowed_mime_types'];
        $this->maxFileSize = $this->config['max_size'];
        $this->uploadPath = $this->config['upload_path'];
        $this->templatePath = $this->config['template_path'];
        $this->tempPath = $this->config['temp_path'];
        
        // Crear directorios si no existen
        $this->ensureDirectoriesExist();
    }
    
    /**
     * Subir archivo
     */
    public function uploadFile(array $file, string $area, int $projectId = null, int $templateId = null): array 
    {
        try {
            // Validar archivo
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error'],
                    'code' => 'VALIDATION_FAILED'
                ];
            }
            
            // Generar información del archivo
            $fileInfo = $this->generateFileInfo($file, $area, $projectId);
            
            // Mover archivo a destino final
            $targetPath = $this->uploadPath . $fileInfo['relative_path'];
            $this->ensureDirectoryExists(dirname($targetPath));
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                Logger::error('Error moviendo archivo subido', [
                    'source' => $file['tmp_name'],
                    'target' => $targetPath,
                    'file_name' => $file['name']
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Error interno moviendo archivo',
                    'code' => 'MOVE_FAILED'
                ];
            }
            
            // Establecer permisos
            chmod($targetPath, 0644);
            
            // Calcular checksum para verificar integridad
            $checksum = hash_file('sha256', $targetPath);
            
            $result = [
                'success' => true,
                'file_info' => array_merge($fileInfo, [
                    'checksum' => $checksum,
                    'upload_timestamp' => time(),
                    'uploaded_by' => Session::get('user_id'),
                    'template_id' => $templateId
                ])
            ];
            
            Logger::info('Archivo subido exitosamente', [
                'file_name' => $fileInfo['original_name'],
                'file_path' => $fileInfo['relative_path'],
                'file_size' => $fileInfo['size'],
                'area' => $area,
                'project_id' => $projectId,
                'user_id' => Session::get('user_id')
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Logger::error('Error subiendo archivo: ' . $e->getMessage(), [
                'file_name' => $file['name'] ?? 'unknown',
                'area' => $area,
                'project_id' => $projectId
            ]);
            
            return [
                'success' => false,
                'error' => 'Error interno procesando archivo',
                'code' => 'INTERNAL_ERROR'
            ];
        }
    }
    
    /**
     * Validar archivo subido
     */
    public function validateFile(array $file): array 
    {
        // Verificar si hay errores de subida
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta directorio temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error escribiendo archivo en disco',
                UPLOAD_ERR_EXTENSION => 'Subida detenida por extensión PHP'
            ];
            
            $error = $errorMessages[$file['error']] ?? 'Error desconocido subiendo archivo';
            
            Logger::warning('Error de subida de archivo', [
                'error_code' => $file['error'],
                'error_message' => $error,
                'file_name' => $file['name'] ?? 'unknown'
            ]);
            
            return ['valid' => false, 'error' => $error];
        }
        
        // Verificar tamaño
        if ($file['size'] > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / 1024 / 1024, 2);
            return [
                'valid' => false, 
                'error' => "El archivo excede el tamaño máximo permitido ({$maxSizeMB} MB)"
            ];
        }
        
        // Verificar si el archivo está vacío
        if ($file['size'] <= 0) {
            return ['valid' => false, 'error' => 'El archivo está vacío'];
        }
        
        // Obtener extensión del archivo
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Verificar extensión permitida
        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'valid' => false, 
                'error' => 'Tipo de archivo no permitido. Extensiones permitidas: ' . implode(', ', $this->allowedExtensions)
            ];
        }
        
        // Verificar MIME type
        $mimeType = mime_content_type($file['tmp_name']);
        $allowedMimeForExtension = $this->allowedMimeTypes[$extension] ?? null;
        
        if ($allowedMimeForExtension && $mimeType !== $allowedMimeForExtension) {
            Logger::warning('MIME type no coincide con extensión', [
                'file_name' => $file['name'],
                'extension' => $extension,
                'detected_mime' => $mimeType,
                'expected_mime' => $allowedMimeForExtension
            ]);
            
            return [
                'valid' => false, 
                'error' => 'El contenido del archivo no coincide con su extensión'
            ];
        }
        
        // Verificar que el archivo no sea ejecutable
        if ($this->isExecutableFile($file['tmp_name'])) {
            Logger::warning('Intento de subir archivo ejecutable bloqueado', [
                'file_name' => $file['name'],
                'mime_type' => $mimeType
            ]);
            
            return ['valid' => false, 'error' => 'No se permiten archivos ejecutables'];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Generar información del archivo
     */
    private function generateFileInfo(array $file, string $area, int $projectId = null): array 
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        
        // Generar nombre único manteniendo la extensión original
        $fileName = $timestamp . '_' . $randomString . '.' . $extension;
        
        // Organizar por área y proyecto
        $relativePath = $area . '/';
        if ($projectId) {
            $relativePath .= 'project_' . $projectId . '/';
        }
        $relativePath .= $fileName;
        
        return [
            'original_name' => $file['name'],
            'file_name' => $fileName,
            'relative_path' => $relativePath,
            'full_path' => $this->uploadPath . $relativePath,
            'size' => $file['size'],
            'mime_type' => mime_content_type($file['tmp_name']),
            'extension' => $extension,
            'area' => $area,
            'project_id' => $projectId
        ];
    }
    
    /**
     * Descargar archivo
     */
    public function downloadFile(string $filePath, string $originalName = null, bool $inline = false): void 
    {
        $fullPath = $this->uploadPath . $filePath;
        
        if (!file_exists($fullPath)) {
            Logger::warning('Intento de descarga de archivo no existente', [
                'file_path' => $filePath,
                'user_id' => Session::get('user_id')
            ]);
            
            header('HTTP/1.0 404 Not Found');
            exit;
        }
        
        $mimeType = mime_content_type($fullPath);
        $fileName = $originalName ?: basename($fullPath);
        $fileSize = filesize($fullPath);
        
        // Headers para descarga
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        if ($inline) {
            header('Content-Disposition: inline; filename="' . $fileName . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
        }
        
        // Leer y enviar archivo
        readfile($fullPath);
        
        Logger::info('Archivo descargado', [
            'file_path' => $filePath,
            'original_name' => $fileName,
            'file_size' => $fileSize,
            'user_id' => Session::get('user_id'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        exit;
    }
    
    /**
     * Eliminar archivo
     */
    public function deleteFile(string $filePath): bool 
    {
        $fullPath = $this->uploadPath . $filePath;
        
        if (!file_exists($fullPath)) {
            Logger::warning('Intento de eliminar archivo no existente', [
                'file_path' => $filePath,
                'user_id' => Session::get('user_id')
            ]);
            return false;
        }
        
        if (unlink($fullPath)) {
            Logger::info('Archivo eliminado', [
                'file_path' => $filePath,
                'user_id' => Session::get('user_id')
            ]);
            
            // Intentar eliminar directorio si está vacío
            $this->cleanupEmptyDirectories(dirname($fullPath));
            
            return true;
        }
        
        Logger::error('Error eliminando archivo', [
            'file_path' => $filePath,
            'user_id' => Session::get('user_id')
        ]);
        
        return false;
    }
    
    /**
     * Verificar si existe un archivo
     */
    public function fileExists(string $filePath): bool 
    {
        return file_exists($this->uploadPath . $filePath);
    }
    
    /**
     * Obtener información de archivo
     */
    public function getFileInfo(string $filePath): ?array 
    {
        $fullPath = $this->uploadPath . $filePath;
        
        if (!file_exists($fullPath)) {
            return null;
        }
        
        return [
            'path' => $filePath,
            'full_path' => $fullPath,
            'size' => filesize($fullPath),
            'mime_type' => mime_content_type($fullPath),
            'extension' => strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)),
            'created_at' => filectime($fullPath),
            'modified_at' => filemtime($fullPath),
            'is_readable' => is_readable($fullPath),
            'is_writable' => is_writable($fullPath)
        ];
    }
    
    /**
     * Copiar plantilla a directorio de proyecto
     */
    public function copyTemplate(string $templatePath, string $area, int $projectId): array 
    {
        $sourceFile = $this->templatePath . $templatePath;
        
        if (!file_exists($sourceFile)) {
            return [
                'success' => false,
                'error' => 'Plantilla no encontrada',
                'code' => 'TEMPLATE_NOT_FOUND'
            ];
        }
        
        $fileName = basename($templatePath);
        $targetDir = $this->uploadPath . $area . '/project_' . $projectId . '/';
        $this->ensureDirectoryExists($targetDir);
        
        $targetFile = $targetDir . $fileName;
        
        if (copy($sourceFile, $targetFile)) {
            chmod($targetFile, 0644);
            
            Logger::info('Plantilla copiada', [
                'source' => $templatePath,
                'target' => $area . '/project_' . $projectId . '/' . $fileName,
                'project_id' => $projectId,
                'user_id' => Session::get('user_id')
            ]);
            
            return [
                'success' => true,
                'file_info' => [
                    'original_name' => $fileName,
                    'relative_path' => $area . '/project_' . $projectId . '/' . $fileName,
                    'size' => filesize($targetFile),
                    'mime_type' => mime_content_type($targetFile)
                ]
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Error copiando plantilla',
            'code' => 'COPY_FAILED'
        ];
    }
    
    /**
     * Crear archivo ZIP con documentos de proyecto
     */
    public function createProjectZip(int $projectId, array $filePaths): string 
    {
        $zipFileName = 'project_' . $projectId . '_documents_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = $this->tempPath . $zipFileName;
        
        $this->ensureDirectoryExists($this->tempPath);
        
        $zip = new \ZipArchive();
        
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('No se pudo crear archivo ZIP');
        }
        
        foreach ($filePaths as $relativePath => $originalName) {
            $fullPath = $this->uploadPath . $relativePath;
            
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $originalName);
            }
        }
        
        $zip->close();
        
        Logger::info('ZIP de proyecto creado', [
            'project_id' => $projectId,
            'zip_file' => $zipFileName,
            'files_count' => count($filePaths),
            'user_id' => Session::get('user_id')
        ]);
        
        return $zipPath;
    }
    
    /**
     * Obtener estadísticas de almacenamiento
     */
    public function getStorageStats(): array 
    {
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'by_area' => [],
            'by_extension' => [],
            'disk_usage' => [
                'total_space' => disk_total_space($this->uploadPath),
                'free_space' => disk_free_space($this->uploadPath),
                'used_space' => 0
            ]
        ];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->uploadPath)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $stats['total_files']++;
                $fileSize = $file->getSize();
                $stats['total_size'] += $fileSize;
                
                // Estadísticas por área
                $relativePath = str_replace($this->uploadPath, '', $file->getPathname());
                $area = explode('/', trim($relativePath, '/'))[0] ?? 'unknown';
                
                if (!isset($stats['by_area'][$area])) {
                    $stats['by_area'][$area] = ['files' => 0, 'size' => 0];
                }
                $stats['by_area'][$area]['files']++;
                $stats['by_area'][$area]['size'] += $fileSize;
                
                // Estadísticas por extensión
                $extension = strtolower($file->getExtension());
                if (!isset($stats['by_extension'][$extension])) {
                    $stats['by_extension'][$extension] = ['files' => 0, 'size' => 0];
                }
                $stats['by_extension'][$extension]['files']++;
                $stats['by_extension'][$extension]['size'] += $fileSize;
            }
        }
        
        $stats['disk_usage']['used_space'] = $stats['disk_usage']['total_space'] - $stats['disk_usage']['free_space'];
        
        return $stats;
    }
    
    /**
     * Limpiar archivos temporales antiguos
     */
    public function cleanupTempFiles(int $maxAge = 3600): int 
    {
        $deleted = 0;
        $cutoffTime = time() - $maxAge;
        
        if (!is_dir($this->tempPath)) {
            return 0;
        }
        
        $files = glob($this->tempPath . '*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        if ($deleted > 0) {
            Logger::info('Archivos temporales limpiados', [
                'deleted_count' => $deleted,
                'max_age_seconds' => $maxAge
            ]);
        }
        
        return $deleted;
    }
    
    /**
     * Verificar integridad de archivo usando checksum
     */
    public function verifyFileIntegrity(string $filePath, string $expectedChecksum): bool 
    {
        $fullPath = $this->uploadPath . $filePath;
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        $actualChecksum = hash_file('sha256', $fullPath);
        $isValid = hash_equals($expectedChecksum, $actualChecksum);
        
        if (!$isValid) {
            Logger::warning('Falla de integridad de archivo detectada', [
                'file_path' => $filePath,
                'expected_checksum' => $expectedChecksum,
                'actual_checksum' => $actualChecksum
            ]);
        }
        
        return $isValid;
    }
    
    /**
     * Verificar si un archivo es ejecutable
     */
    private function isExecutableFile(string $filePath): bool 
    {
        // Leer primeros bytes para detectar firmas ejecutables
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        
        $header = fread($handle, 4);
        fclose($handle);
        
        // Firmas comunes de archivos ejecutables
        $signatures = [
            "\x4D\x5A", // PE (Windows EXE/DLL)
            "\x7F\x45\x4C\x46", // ELF (Linux)
            "\xFE\xED\xFA\xCE", // Mach-O (macOS)
            "\xFE\xED\xFA\xCF", // Mach-O (macOS 64-bit)
            "#!/bin/", // Script shell
            "#!/usr/"  // Script shell
        ];
        
        foreach ($signatures as $signature) {
            if (strpos($header, $signature) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Asegurar que los directorios existan
     */
    private function ensureDirectoriesExist(): void 
    {
        $directories = [
            $this->uploadPath,
            $this->templatePath,
            $this->tempPath
        ];
        
        foreach ($directories as $dir) {
            $this->ensureDirectoryExists($dir);
        }
    }
    
    /**
     * Crear directorio si no existe
     */
    private function ensureDirectoryExists(string $path): void 
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
    
    /**
     * Limpiar directorios vacíos
     */
    private function cleanupEmptyDirectories(string $dir): void 
    {
        if (!is_dir($dir) || $dir === $this->uploadPath) {
            return;
        }
        
        $files = scandir($dir);
        $files = array_diff($files, ['.', '..']);
        
        if (empty($files)) {
            rmdir($dir);
            // Recursivamente limpiar directorios padre
            $this->cleanupEmptyDirectories(dirname($dir));
        }
    }
    
    /**
     * Obtener configuración del servicio
     */
    public function getConfig(): array 
    {
        return $this->config;
    }
    
    /**
     * Verificar salud del servicio de archivos
     */
    public function healthCheck(): array 
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'directories' => [],
            'disk_space' => [],
            'permissions' => []
        ];
        
        $directories = [
            'upload' => $this->uploadPath,
            'template' => $this->templatePath,
            'temp' => $this->tempPath
        ];
        
        foreach ($directories as $name => $path) {
            $exists = is_dir($path);
            $writable = $exists ? is_writable($path) : false;
            $readable = $exists ? is_readable($path) : false;
            
            $health['directories'][$name] = [
                'path' => $path,
                'exists' => $exists,
                'writable' => $writable,
                'readable' => $readable
            ];
            
            if (!$exists) {
                $health['status'] = 'unhealthy';
                $health['issues'][] = "Directorio {$name} no existe: {$path}";
            } elseif (!$writable) {
                $health['status'] = 'unhealthy';
                $health['issues'][] = "Directorio {$name} no es escribible: {$path}";
            } elseif (!$readable) {
                $health['status'] = 'warning';
                $health['issues'][] = "Directorio {$name} no es legible: {$path}";
            }
        }
        
        // Verificar espacio en disco
        $totalSpace = disk_total_space($this->uploadPath);
        $freeSpace = disk_free_space($this->uploadPath);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercent = ($usedSpace / $totalSpace) * 100;
        
        $health['disk_space'] = [
            'total_mb' => round($totalSpace / 1024 / 1024, 2),
            'free_mb' => round($freeSpace / 1024 / 1024, 2),
            'used_mb' => round($usedSpace / 1024 / 1024, 2),
            'usage_percent' => round($usagePercent, 2)
        ];
        
        if ($usagePercent > 90) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = 'Espacio en disco crítico (>90% usado)';
        } elseif ($usagePercent > 80) {
            $health['status'] = 'warning';
            $health['issues'][] = 'Espacio en disco bajo (>80% usado)';
        }
        
        return $health;
    }
}