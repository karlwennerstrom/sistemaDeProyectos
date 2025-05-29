<?php
/**
 * Modelo de Documentos
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Models;

use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Helper;
use UC\ApprovalSystem\Services\FileService;

class Document extends BaseModel 
{
    protected $table = 'documents';
    
    protected $fillable = [
        'project_id',
        'template_id',
        'area_name',
        'document_name',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'checksum',
        'version',
        'is_latest',
        'uploaded_by_user_id',
        'upload_ip',
        'status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'metadata'
    ];
    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
    
    /**
     * Crear documento desde archivo subido
     */
    public static function createFromUpload(array $fileInfo, int $projectId, string $areaName, int $userId, int $templateId = null): ?self 
    {
        $documentData = [
            'project_id' => $projectId,
            'template_id' => $templateId,
            'area_name' => $areaName,
            'document_name' => $fileInfo['original_name'],
            'original_filename' => $fileInfo['original_name'],
            'file_path' => $fileInfo['relative_path'],
            'file_size' => $fileInfo['size'],
            'mime_type' => $fileInfo['mime_type'],
            'checksum' => $fileInfo['checksum'],
            'version' => 1,
            'is_latest' => true,
            'uploaded_by_user_id' => $userId,
            'upload_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'status' => 'uploaded',
            'metadata' => json_encode([
                'upload_timestamp' => $fileInfo['upload_timestamp'],
                'file_extension' => $fileInfo['extension']
            ])
        ];
        
        // Marcar versiones anteriores como no-latest si existen
        static::markPreviousVersionsAsOld($projectId, $areaName, $fileInfo['original_name']);
        
        $document = static::create($documentData);
        
        if ($document) {
            Logger::info('Documento creado desde archivo subido', [
                'document_id' => $document->id,
                'project_id' => $projectId,
                'area_name' => $areaName,
                'filename' => $fileInfo['original_name'],
                'file_size' => $fileInfo['size'],
                'user_id' => $userId
            ]);
        }
        
        return $document;
    }
    
    /**
     * Marcar versiones anteriores como no-latest
     */
    private static function markPreviousVersionsAsOld(int $projectId, string $areaName, string $documentName): void 
    {
        $query = "UPDATE documents 
                  SET is_latest = 0 
                  WHERE project_id = ? 
                  AND area_name = ? 
                  AND document_name = ? 
                  AND is_latest = 1";
        
        $db = Database::getInstance();
        $db->update($query, [$projectId, $areaName, $documentName]);
    }
    
    /**
     * Obtener proyecto asociado
     */
    public function project(): ?Project 
    {
        return Project::find($this->project_id);
    }
    
    /**
     * Obtener plantilla asociada
     */
    public function template(): ?DocumentTemplate 
    {
        return $this->template_id ? DocumentTemplate::find($this->template_id) : null;
    }
    
    /**
     * Obtener usuario que subió el documento
     */
    public function uploadedByUser(): ?User 
    {
        return User::find($this->uploaded_by_user_id);
    }
    
    /**
     * Obtener administrador que revisó
     */
    public function reviewedByAdmin(): ?Admin 
    {
        return $this->reviewed_by ? Admin::find($this->reviewed_by) : null;
    }
    
    /**
     * Verificar si es la versión más reciente
     */
    public function isLatest(): bool 
    {
        return (bool) $this->is_latest;
    }
    
    /**
     * Verificar si está bajo revisión
     */
    public function isUnderReview(): bool 
    {
        return $this->status === 'under_review';
    }
    
    /**
     * Verificar si está aprobado
     */
    public function isApproved(): bool 
    {
        return $this->status === 'approved';
    }
    
    /**
     * Verificar si está rechazado
     */
    public function isRejected(): bool 
    {
        return $this->status === 'rejected';
    }
    
    /**
     * Verificar si requiere cambios
     */
    public function requiresChanges(): bool 
    {
        return $this->status === 'requires_changes';
    }
    
    /**
     * Obtener metadata del documento
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
     * Cambiar estado del documento
     */
    public function changeStatus(string $newStatus, string $notes = '', int $reviewerId = null): bool 
    {
        $validStatuses = ['uploaded', 'under_review', 'approved', 'rejected', 'requires_changes'];
        
        if (!in_array($newStatus, $validStatuses)) {
            Logger::warning('Estado de documento inválido', [
                'document_id' => $this->id,
                'invalid_status' => $newStatus,
                'valid_statuses' => $validStatuses
            ]);
            return false;
        }
        
        $oldStatus = $this->status;
        $this->status = $newStatus;
        
        if ($notes) {
            $this->review_notes = $notes;
        }
        
        if ($reviewerId) {
            $this->reviewed_by = $reviewerId;
            $this->reviewed_at = date('Y-m-d H:i:s');
        }
        
        $result = $this->save();
        
        if ($result) {
            // Registrar en historial del proyecto
            ProjectHistory::create([
                'project_id' => $this->project_id,
                'user_id' => $reviewerId,
                'user_type' => $reviewerId ? 'admin' : 'system',
                'action' => 'document_status_changed',
                'description' => "Estado del documento '{$this->document_name}' cambiado de '{$oldStatus}' a '{$newStatus}'" . ($notes ? ": {$notes}" : ''),
                'old_values' => json_encode(['document_status' => $oldStatus]),
                'new_values' => json_encode(['document_status' => $newStatus]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Estado de documento cambiado', [
                'document_id' => $this->id,
                'project_id' => $this->project_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reviewer_id' => $reviewerId,
                'notes' => $notes
            ]);
        }
        
        return $result;
    }
    
    /**
     * Aprobar documento
     */
    public function approve(int $reviewerId, string $notes = ''): bool 
    {
        return $this->changeStatus('approved', $notes, $reviewerId);
    }
    
    /**
     * Rechazar documento
     */
    public function reject(int $reviewerId, string $notes): bool 
    {
        return $this->changeStatus('rejected', $notes, $reviewerId);
    }
    
    /**
     * Marcar como requiere cambios
     */
    public function requireChanges(int $reviewerId, string $notes): bool 
    {
        return $this->changeStatus('requires_changes', $notes, $reviewerId);
    }
    
    /**
     * Crear nueva versión del documento
     */
    public function createNewVersion(array $fileInfo, int $userId): ?self 
    {
        // Marcar versión actual como no-latest
        $this->is_latest = false;
        $this->save();
        
        // Crear nueva versión
        $newVersion = $this->replicate(['id', 'created_at', 'updated_at']);
        $newVersion->file_path = $fileInfo['relative_path'];
        $newVersion->file_size = $fileInfo['size'];
        $newVersion->mime_type = $fileInfo['mime_type'];
        $newVersion->checksum = $fileInfo['checksum'];
        $newVersion->version = $this->version + 1;
        $newVersion->is_latest = true;
        $newVersion->uploaded_by_user_id = $userId;
        $newVersion->upload_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $newVersion->status = 'uploaded';
        $newVersion->review_notes = null;
        $newVersion->reviewed_by = null;
        $newVersion->reviewed_at = null;
        
        // Actualizar metadata
        $metadata = $this->getMetadata();
        $metadata['upload_timestamp'] = $fileInfo['upload_timestamp'];
        $metadata['previous_version'] = $this->id;
        $newVersion->metadata = json_encode($metadata);
        
        $newVersion->save();
        
        if ($newVersion) {
            Logger::info('Nueva versión de documento creada', [
                'new_document_id' => $newVersion->id,
                'previous_document_id' => $this->id,
                'project_id' => $this->project_id,
                'version' => $newVersion->version,
                'user_id' => $userId
            ]);
        }
        
        return $newVersion;
    }
    
    /**
     * Obtener versiones anteriores
     */
    public function getPreviousVersions()
    {
        return static::where('project_id', $this->project_id)
                    ->where('area_name', $this->area_name)
                    ->where('document_name', $this->document_name)
                    ->where('version', '<', $this->version)
                    ->orderBy('version', 'desc');
    }
    
    /**
     * Obtener versión más reciente
     */
    public function getLatestVersion(): ?self 
    {
        return static::where('project_id', $this->project_id)
                    ->where('area_name', $this->area_name)
                    ->where('document_name', $this->document_name)
                    ->where('is_latest', true)
                    ->first();
    }
    
    /**
     * Verificar integridad del archivo
     */
    public function verifyIntegrity(): bool 
    {
        $fileService = new FileService();
        return $fileService->verifyFileIntegrity($this->file_path, $this->checksum);
    }
    
    /**
     * Obtener información del archivo
     */
    public function getFileInfo(): array 
    {
        $fileService = new FileService();
        $fileInfo = $fileService->getFileInfo($this->file_path);
        
        return array_merge($fileInfo ?? [], [
            'document_name' => $this->document_name,
            'original_filename' => $this->original_filename,
            'version' => $this->version,
            'status' => $this->status,
            'uploaded_at' => $this->created_at,
            'file_type' => Helper::getFileType($this->original_filename),
            'file_icon' => Helper::getFileIcon($this->original_filename),
            'formatted_size' => Helper::formatFileSize($this->file_size)
        ]);
    }
    
    /**
     * Generar URL de descarga
     */
    public function getDownloadUrl(): string 
    {
        return "/download.php?document_id={$this->id}&token=" . $this->generateDownloadToken();
    }
    
    /**
     * Generar token de descarga temporal
     */
    public function generateDownloadToken(): string 
    {
        $data = [
            'document_id' => $this->id,
            'expires' => time() + 3600, // 1 hora
            'checksum' => $this->checksum
        ];
        
        return base64_encode(json_encode($data));
    }
    
    /**
     * Validar token de descarga
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
            Logger::warning('Token de descarga inválido', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Buscar documentos por proyecto
     */
    public static function findByProject(int $projectId)
    {
        return static::where('project_id', $projectId)
                    ->where('is_latest', true)
                    ->orderBy('area_name')
                    ->orderBy('created_at', 'desc');
    }
    
    /**
     * Buscar documentos por área
     */
    public static function findByArea(string $areaName)
    {
        return static::where('area_name', $areaName)
                    ->where('is_latest', true)
                    ->orderBy('created_at', 'desc');
    }
    
    /**
     * Buscar documentos por estado
     */
    public static function findByStatus(string $status)
    {
        return static::where('status', $status)
                    ->where('is_latest', true)
                    ->orderBy('created_at', 'desc');
    }
    
    /**
     * Buscar documentos pendientes de revisión
     */
    public static function pendingReview()
    {
        return static::whereIn('status', ['uploaded', 'under_review'])
                    ->where('is_latest', true)
                    ->orderBy('created_at', 'asc');
    }
    
    /**
     * Obtener estadísticas de documentos
     */
    public static function getStats(): array 
    {
        $db = Database::getInstance();
        
        // Estadísticas por estado
        $statusQuery = "SELECT status, COUNT(*) as count 
                       FROM documents 
                       WHERE is_latest = 1 
                       GROUP BY status";
        $statusStats = $db->select($statusQuery);
        
        // Estadísticas por área
        $areaQuery = "SELECT area_name, COUNT(*) as count,
                        AVG(file_size) as avg_size,
                        SUM(file_size) as total_size
                      FROM documents 
                      WHERE is_latest = 1 
                      GROUP BY area_name";
        $areaStats = $db->select($areaQuery);
        
        // Estadísticas por tipo de archivo
        $typeQuery = "SELECT 
                        SUBSTRING_INDEX(original_filename, '.', -1) as extension,
                        COUNT(*) as count,
                        SUM(file_size) as total_size
                      FROM documents 
                      WHERE is_latest = 1 
                      GROUP BY extension";
        $typeStats = $db->select($typeQuery);
        
        // Documentos subidos por día (últimos 30 días)
        $dailyQuery = "SELECT DATE(created_at) as date, COUNT(*) as count 
                       FROM documents 
                       WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                       GROUP BY DATE(created_at)";
        $dailyStats = $db->select($dailyQuery);
        
        return [
            'total_documents' => array_sum(array_column($statusStats, 'count')),
            'by_status' => $statusStats,
            'by_area' => $areaStats,
            'by_type' => $typeStats,
            'daily_uploads' => $dailyStats,
            'total_storage_mb' => Helper::bytesToMB(array_sum(array_column($areaStats, 'total_size')))
        ];
    }
    
    /**
     * Obtener documentos más recientes
     */
    public static function getRecent(int $limit = 10): array 
    {
        $query = "SELECT d.*, p.project_code, p.title as project_title, u.name as uploaded_by_name 
                  FROM documents d
                  JOIN projects p ON d.project_id = p.id
                  JOIN users u ON d.uploaded_by_user_id = u.id
                  WHERE d.is_latest = 1
                  ORDER BY d.created_at DESC 
                  LIMIT ?";
        
        $db = Database::getInstance();
        return $db->select($query, [$limit]);
    }
    
    /**
     * Buscar documentos por proyecto y área
     */
    public static function findByProjectAndArea(int $projectId, string $areaName)
    {
        return static::where('project_id', $projectId)
                    ->where('area_name', $areaName)
                    ->where('is_latest', true)
                    ->orderBy('created_at', 'desc');
    }
    
    /**
     * Obtener documentos que requieren atención
     */
    public static function requiresAttention(): array 
    {
        $db = Database::getInstance();
        
        // Documentos pendientes hace más de 3 días
        $query = "SELECT d.*, p.project_code, p.title as project_title,
                    DATEDIFF(NOW(), d.created_at) as days_pending
                  FROM documents d
                  JOIN projects p ON d.project_id = p.id
                  WHERE d.status IN ('uploaded', 'under_review')
                  AND d.is_latest = 1
                  AND d.created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)
                  ORDER BY d.created_at ASC";
        
        $results = $db->select($query);
        
        return array_map(function($row) {
            $document = new static($row);
            $document->exists = true;
            $document->syncOriginal();
            $document->days_pending = $row['days_pending'];
            $document->project_code = $row['project_code'];
            $document->project_title = $row['project_title'];
            return $document;
        }, $results);
    }
    
    /**
     * Limpiar versiones antiguas de documentos
     */
    public static function cleanOldVersions(int $versionsToKeep = 5): int 
    {
        $db = Database::getInstance();
        
        // Obtener documentos que tienen más versiones de las permitidas
        $query = "SELECT project_id, area_name, document_name, COUNT(*) as version_count
                  FROM documents 
                  GROUP BY project_id, area_name, document_name
                  HAVING COUNT(*) > ?";
        
        $documentsWithManyVersions = $db->select($query, [$versionsToKeep]);
        $deletedCount = 0;
        
        foreach ($documentsWithManyVersions as $docGroup) {
            // Obtener versiones antiguas a eliminar
            $deleteQuery = "SELECT id, file_path FROM documents 
                           WHERE project_id = ? AND area_name = ? AND document_name = ?
                           ORDER BY version DESC 
                           LIMIT 999 OFFSET ?";
            
            $toDelete = $db->select($deleteQuery, [
                $docGroup['project_id'],
                $docGroup['area_name'],
                $docGroup['document_name'],
                $versionsToKeep
            ]);
            
            foreach ($toDelete as $oldVersion) {
                $document = static::find($oldVersion['id']);
                if ($document && $document->delete()) {
                    // Eliminar archivo físico
                    $fileService = new FileService();
                    $fileService->deleteFile($oldVersion['file_path']);
                    $deletedCount++;
                }
            }
        }
        
        if ($deletedCount > 0) {
            Logger::info('Versiones antiguas de documentos eliminadas', [
                'deleted_count' => $deletedCount,
                'versions_kept' => $versionsToKeep
            ]);
        }
        
        return $deletedCount;
    }
    
    /**
     * Convertir a array para API
     */
    public function toApiArray(): array 
    {
        $fileInfo = $this->getFileInfo();
        
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'template_id' => $this->template_id,
            'area_name' => $this->area_name,
            'document_name' => $this->document_name,
            'original_filename' => $this->original_filename,
            'file_size' => $this->file_size,
            'formatted_size' => Helper::formatFileSize($this->file_size),
            'mime_type' => $this->mime_type,
            'file_type' => Helper::getFileType($this->original_filename),
            'file_icon' => Helper::getFileIcon($this->original_filename),
            'version' => $this->version,
            'is_latest' => $this->isLatest(),
            'status' => $this->status,
            'review_notes' => $this->review_notes,
            'reviewed_at' => $this->reviewed_at,
            'uploaded_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'uploaded_by' => $this->uploadedByUser() ? $this->uploadedByUser()->getFullName() : null,
            'reviewed_by' => $this->reviewedByAdmin() ? $this->reviewedByAdmin()->name : null,
            'download_url' => $this->getDownloadUrl(),
            'integrity_valid' => $this->verifyIntegrity(),
            'metadata' => $this->getMetadata()
        ];
    }
}