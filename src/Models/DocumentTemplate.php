<?php
/**
 * Modelo de Plantillas de Documentos
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Models;

use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Helper;
use UC\ApprovalSystem\Services\FileService;

class DocumentTemplate extends BaseModel 
{
    protected $table = 'document_templates';
    
    protected $fillable = [
        'area_name',
        'template_name',
        'display_name',
        'description',
        'file_path',
        'file_size',
        'mime_type',
        'version',
        'is_required',
        'order_sequence',
        'instructions',
        'metadata'
    ];
    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
    
    /**
     * Crear plantilla desde archivo subido
     */
    public static function createFromFile(string $areaName, string $templateName, string $displayName, array $fileInfo, array $options = []): ?self 
    {
        $templateData = [
            'area_name' => $areaName,
            'template_name' => $templateName,
            'display_name' => $displayName,
            'description' => $options['description'] ?? '',
            'file_path' => $fileInfo['relative_path'],
            'file_size' => $fileInfo['size'],
            'mime_type' => $fileInfo['mime_type'],
            'version' => $options['version'] ?? '1.0',
            'is_required' => $options['is_required'] ?? true,
            'order_sequence' => $options['order_sequence'] ?? 0,
            'instructions' => $options['instructions'] ?? '',
            'metadata' => json_encode($options['metadata'] ?? [])
        ];
        
        $template = static::create($templateData);
        
        if ($template) {
            Logger::info('Plantilla de documento creada', [
                'template_id' => $template->id,
                'area_name' => $areaName,
                'template_name' => $templateName,
                'file_size' => $fileInfo['size']
            ]);
        }
        
        return $template;
    }
    
    /**
     * Verificar si es requerida
     */
    public function isRequired(): bool 
    {
        return (bool) $this->is_required;
    }
    
    /**
     * Obtener metadata de la plantilla
     */
    public function getMetadata(): array 
    {
        $metadata = json_decode($this->metadata ?? '{}', true);
        return is_array($metadata) ? $metadata : [];
    }
    
    /**
     * Actualizar metadata
     */
    public function updateMetadata(array $newMetadata): bool 
    {
        $currentMetadata = $this->getMetadata();
        $updatedMetadata = array_merge($currentMetadata, $newMetadata);
        
        $this->metadata = json_encode($updatedMetadata);
        return $this->save();
    }
    
    /**
     * Obtener información del archivo de plantilla
     */
    public function getFileInfo(): array 
    {
        $fileService = new FileService();
        $fileInfo = $fileService->getFileInfo($this->file_path);
        
        return array_merge($fileInfo ?? [], [
            'template_name' => $this->template_name,
            'display_name' => $this->display_name,
            'version' => $this->version,
            'is_required' => $this->isRequired(),
            'file_type' => Helper::getFileType($this->file_path),
            'file_icon' => Helper::getFileIcon($this->file_path),
            'formatted_size' => Helper::formatFileSize($this->file_size)
        ]);
    }
    
    /**
     * Verificar si el archivo de plantilla existe
     */
    public function fileExists(): bool 
    {
        $fileService = new FileService();
        return $fileService->fileExists($this->file_path);
    }
    
    /**
     * Copiar plantilla a proyecto
     */
    public function copyToProject(int $projectId): array 
    {
        $fileService = new FileService();
        
        $result = $fileService->copyTemplate($this->file_path, $this->area_name, $projectId);
        
        if ($result['success']) {
            Logger::info('Plantilla copiada a proyecto', [
                'template_id' => $this->id,
                'project_id' => $projectId,
                'template_name' => $this->template_name,
                'area_name' => $this->area_name
            ]);
        }
        
        return $result;
    }
    
    /**
     * Generar URL de descarga
     */
    public function getDownloadUrl(): string 
    {
        return "/download_template.php?template_id={$this->id}&token=" . $this->generateDownloadToken();
    }
    
    /**
     * Generar token de descarga temporal
     */
    public function generateDownloadToken(): string 
    {
        $data = [
            'template_id' => $this->id,
            'expires' => time() + 3600, // 1 hora
            'area_name' => $this->area_name
        ];
        
        return base64_encode(json_encode($data));
    }
    
    /**
     * Validar token de descarga de plantilla
     */
    public static function validateDownloadToken(string $token): ?array 
    {
        try {
            $data = json_decode(base64_decode($token), true);
            
            if (!$data || !isset($data['expires']) || $data['expires'] < time()) {
                return null;
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Logger::warning('Token de descarga de plantilla inválido', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Actualizar versión de plantilla
     */
    public function updateVersion(string $newVersion, array $fileInfo = null): bool 
    {
        $oldVersion = $this->version;
        $this->version = $newVersion;
        
        if ($fileInfo) {
            $this->file_path = $fileInfo['relative_path'];
            $this->file_size = $fileInfo['size'];
            $this->mime_type = $fileInfo['mime_type'];
            
            // Actualizar metadata con información de la actualización
            $metadata = $this->getMetadata();
            $metadata['updated_at'] = time();
            $metadata['previous_version'] = $oldVersion;
            $this->metadata = json_encode($metadata);
        }
        
        $result = $this->save();
        
        if ($result) {
            Logger::info('Versión de plantilla actualizada', [
                'template_id' => $this->id,
                'template_name' => $this->template_name,
                'old_version' => $oldVersion,
                'new_version' => $newVersion,
                'file_updated' => $fileInfo !== null
            ]);
        }
        
        return $result;
    }
    
    /**
     * Marcar como requerida o no requerida
     */
    public function setRequired(bool $required): bool 
    {
        $this->is_required = $required;
        $result = $this->save();
        
        if ($result) {
            Logger::info('Estado de requerimiento de plantilla cambiado', [
                'template_id' => $this->id,
                'template_name' => $this->template_name,
                'is_required' => $required
            ]);
        }
        
        return $result;
    }
    
    /**
     * Actualizar orden de secuencia
     */
    public function updateOrder(int $newOrder): bool 
    {
        $oldOrder = $this->order_sequence;
        $this->order_sequence = $newOrder;
        $result = $this->save();
        
        if ($result) {
            Logger::info('Orden de plantilla actualizado', [
                'template_id' => $this->id,
                'template_name' => $this->template_name,
                'old_order' => $oldOrder,
                'new_order' => $newOrder
            ]);
        }
        
        return $result;
    }
    
    /**
     * Obtener documentos generados desde esta plantilla
     */
    public function getGeneratedDocuments()
    {
        return Document::where('template_id', $this->id)->orderBy('created_at', 'desc');
    }
    
    /**
     * Contar documentos generados
     */
    public function getUsageCount(): int 
    {
        return Document::where('template_id', $this->id)->count();
    }
    
    /**
     * Buscar plantillas por área
     */
    public static function findByArea(string $areaName)
    {
        return static::where('area_name', $areaName)->orderBy('order_sequence')->orderBy('display_name');
    }
    
    /**
     * Buscar plantillas requeridas por área
     */
    public static function findRequiredByArea(string $areaName)
    {
        return static::where('area_name', $areaName)
                    ->where('is_required', true)
                    ->orderBy('order_sequence')
                    ->orderBy('display_name');
    }
    
    /**
     * Buscar plantillas opcionales por área
     */
    public static function findOptionalByArea(string $areaName)
    {
        return static::where('area_name', $areaName)
                    ->where('is_required', false)
                    ->orderBy('order_sequence')
                    ->orderBy('display_name');
    }
    
    /**
     * Obtener todas las áreas con plantillas
     */
    public static function getAreasWithTemplates(): array 
    {
        $db = Database::getInstance();
        
        $query = "SELECT area_name, COUNT(*) as template_count 
                  FROM document_templates 
                  GROUP BY area_name 
                  ORDER BY area_name";
        
        return $db->select($query);
    }
    
    /**
     * Obtener estadísticas de plantillas
     */
    public static function getStats(): array 
    {
        $db = Database::getInstance();
        
        // Estadísticas por área
        $areaQuery = "SELECT area_name, 
                        COUNT(*) as total_templates,
                        SUM(CASE WHEN is_required = 1 THEN 1 ELSE 0 END) as required_templates,
                        SUM(CASE WHEN is_required = 0 THEN 1 ELSE 0 END) as optional_templates,
                        AVG(file_size) as avg_file_size,
                        SUM(file_size) as total_file_size
                      FROM document_templates 
                      GROUP BY area_name";
        $areaStats = $db->select($areaQuery);
        
        // Plantillas más utilizadas
        $usageQuery = "SELECT dt.template_name, dt.display_name, dt.area_name, COUNT(d.id) as usage_count
                       FROM document_templates dt
                       LEFT JOIN documents d ON dt.id = d.template_id
                       GROUP BY dt.id, dt.template_name, dt.display_name, dt.area_name
                       ORDER BY COUNT(d.id) DESC
                       LIMIT 10";
        $usageStats = $db->select($usageQuery);
        
        // Tipos de archivo más comunes
        $typeQuery = "SELECT 
                        SUBSTRING_INDEX(file_path, '.', -1) as extension,
                        COUNT(*) as count,
                        SUM(file_size) as total_size
                      FROM document_templates 
                      GROUP BY extension";
        $typeStats = $db->select($typeQuery);
        
        return [
            'total_templates' => array_sum(array_column($areaStats, 'total_templates')),
            'total_required' => array_sum(array_column($areaStats, 'required_templates')),
            'total_optional' => array_sum(array_column($areaStats, 'optional_templates')),
            'by_area' => $areaStats,
            'most_used' => $usageStats,
            'by_file_type' => $typeStats,
            'total_storage_mb' => Helper::bytesToMB(array_sum(array_column($areaStats, 'total_file_size')))
        ];
    }
    
    /**
     * Verificar integridad de todas las plantillas
     */
    public static function verifyAllTemplatesIntegrity(): array 
    {
        $templates = static::all();
        $results = [
            'total_checked' => count($templates),
            'valid' => 0,
            'invalid' => 0,
            'missing_files' => 0,
            'details' => []
        ];
        
        foreach ($templates as $template) {
            $status = 'valid';
            
            if (!$template->fileExists()) {
                $status = 'missing_file';
                $results['missing_files']++;
            } else {
                $results['valid']++;
            }
            
            $results['details'][] = [
                'template_id' => $template->id,
                'template_name' => $template->template_name,
                'area_name' => $template->area_name,
                'file_path' => $template->file_path,
                'status' => $status
            ];
        }
        
        return $results;
    }
    
    /**
     * Sincronizar plantillas desde configuración
     */
    public static function syncFromConfig(): array 
    {
        $appConfig = include __DIR__ . '/../../config/app.php';
        $configTemplates = $appConfig['document_templates'] ?? [];
        
        $synced = [];
        $errors = [];
        
        foreach ($configTemplates as $areaName => $templates) {
            foreach ($templates as $templateFile => $displayName) {
                $templateName = pathinfo($templateFile, PATHINFO_FILENAME);
                
                // Verificar si la plantilla ya existe
                $existing = static::where('area_name', $areaName)
                                 ->where('template_name', $templateName)
                                 ->first();
                
                if (!$existing) {
                    // Crear nueva plantilla
                    $templatePath = "templates/{$areaName}/{$templateFile}";
                    
                    if (file_exists($templatePath)) {
                        $template = static::create([
                            'area_name' => $areaName,
                            'template_name' => $templateName,
                            'display_name' => $displayName,
                            'file_path' => $templatePath,
                            'file_size' => filesize($templatePath),
                            'mime_type' => mime_content_type($templatePath),
                            'version' => '1.0',
                            'is_required' => true,
                            'order_sequence' => 0
                        ]);
                        
                        if ($template) {
                            $synced[] = $template->toArray();
                        }
                    } else {
                        $errors[] = "Archivo de plantilla no encontrado: {$templatePath}";
                    }
                }
            }
        }
        
        Logger::info('Sincronización de plantillas desde configuración completada', [
            'synced_count' => count($synced),
            'errors_count' => count($errors)
        ]);
        
        return [
            'synced' => $synced,
            'errors' => $errors
        ];
    }
    
    /**
     * Obtener plantillas faltantes para un proyecto
     */
    public static function getMissingForProject(int $projectId, string $areaName): array 
    {
        $requiredTemplates = static::findRequiredByArea($areaName)->get();
        $uploadedDocuments = Document::findByProjectAndArea($projectId, $areaName)->get();
        
        $uploadedTemplateIds = [];
        foreach ($uploadedDocuments as $document) {
            if ($document->template_id) {
                $uploadedTemplateIds[] = $document->template_id;
            }
        }
        
        $missingTemplates = [];
        foreach ($requiredTemplates as $template) {
            if (!in_array($template->id, $uploadedTemplateIds)) {
                $missingTemplates[] = $template;
            }
        }
        
        return $missingTemplates;
    }
    
    /**
     * Validar completitud de documentos para un área
     */
    public static function validateProjectCompleteness(int $projectId, string $areaName): array 
    {
        $missingTemplates = static::getMissingForProject($projectId, $areaName);
        $requiredCount = static::findRequiredByArea($areaName)->count();
        $completedCount = $requiredCount - count($missingTemplates);
        
        return [
            'is_complete' => empty($missingTemplates),
            'required_templates' => $requiredCount,
            'completed_templates' => $completedCount,
            'completion_percentage' => $requiredCount > 0 ? round(($completedCount / $requiredCount) * 100, 2) : 100,
            'missing_templates' => $missingTemplates
        ];
    }
    
    /**
     * Exportar plantillas de un área
     */
    public static function exportAreaTemplates(string $areaName): array 
    {
        $templates = static::findByArea($areaName)->get();
        $export = [];
        
        foreach ($templates as $template) {
            $export[] = [
                'template_name' => $template->template_name,
                'display_name' => $template->display_name,
                'description' => $template->description,
                'version' => $template->version,
                'is_required' => $template->isRequired(),
                'order_sequence' => $template->order_sequence,
                'instructions' => $template->instructions,
                'file_info' => $template->getFileInfo(),
                'usage_count' => $template->getUsageCount()
            ];
        }
        
        return $export;
    }
    
    /**
     * Buscar plantillas por texto
     */
    public static function search(string $query, string $areaName = null): array 
    {
        $searchQuery = "SELECT * FROM document_templates 
                       WHERE (display_name LIKE ? OR description LIKE ? OR template_name LIKE ?)";
        $params = ["%{$query}%", "%{$query}%", "%{$query}%"];
        
        if ($areaName) {
            $searchQuery .= " AND area_name = ?";
            $params[] = $areaName;
        }
        
        $searchQuery .= " ORDER BY display_name";
        
        $db = Database::getInstance();
        $results = $db->select($searchQuery, $params);
        
        return array_map(function($row) {
            $template = new static($row);
            $template->exists = true;
            $template->syncOriginal();
            return $template;
        }, $results);
    }
    
    /**
     * Duplicar plantilla a otra área
     */
    public function duplicateToArea(string $targetArea, string $newDisplayName = null): ?self 
    {
        // Verificar que no exista ya en el área destino
        $existing = static::where('area_name', $targetArea)
                         ->where('template_name', $this->template_name)
                         ->first();
        
        if ($existing) {
            Logger::warning('Plantilla ya existe en área destino', [
                'template_id' => $this->id,
                'target_area' => $targetArea,
                'existing_id' => $existing->id
            ]);
            return null;
        }
        
        // Crear copia
        $duplicate = $this->replicate(['id', 'created_at', 'updated_at']);
        $duplicate->area_name = $targetArea;
        $duplicate->display_name = $newDisplayName ?: $this->display_name;
        
        // Copiar archivo físico a nueva ubicación
        $fileService = new FileService();
        $newPath = str_replace($this->area_name, $targetArea, $this->file_path);
        
        if (copy($this->file_path, $newPath)) {
            $duplicate->file_path = $newPath;
            $duplicate->save();
            
            Logger::info('Plantilla duplicada a otra área', [
                'original_id' => $this->id,
                'duplicate_id' => $duplicate->id,
                'target_area' => $targetArea
            ]);
            
            return $duplicate;
        }
        
        return null;
    }
    
    /**
     * Obtener historial de uso de la plantilla
     */
    public function getUsageHistory(int $days = 30): array 
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $query = "SELECT DATE(d.created_at) as date, COUNT(*) as usage_count,
                    p.project_code, p.title as project_title, u.name as user_name
                  FROM documents d
                  JOIN projects p ON d.project_id = p.id
                  JOIN users u ON d.uploaded_by_user_id = u.id
                  WHERE d.template_id = ? AND d.created_at >= ?
                  GROUP BY DATE(d.created_at), p.id
                  ORDER BY d.created_at DESC";
        
        return $this->db->select($query, [$this->id, $startDate]);
    }
    
    /**
     * Convertir a array para API
     */
    public function toApiArray(): array 
    {
        $fileInfo = $this->getFileInfo();
        
        return [
            'id' => $this->id,
            'area_name' => $this->area_name,
            'template_name' => $this->template_name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'version' => $this->version,
            'is_required' => $this->isRequired(),
            'order_sequence' => $this->order_sequence,
            'instructions' => $this->instructions,
            'file_size' => $this->file_size,
            'formatted_size' => Helper::formatFileSize($this->file_size),
            'mime_type' => $this->mime_type,
            'file_type' => Helper::getFileType($this->file_path),
            'file_icon' => Helper::getFileIcon($this->file_path),
            'download_url' => $this->getDownloadUrl(),
            'file_exists' => $this->fileExists(),
            'usage_count' => $this->getUsageCount(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'metadata' => $this->getMetadata()
        ];
    }
}