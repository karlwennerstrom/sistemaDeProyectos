<?php
/**
 * Modelo de Feedback de Proyectos
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Models;

use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Helper;

class ProjectFeedback extends BaseModel 
{
    protected $table = 'project_feedback';
    
    protected $fillable = [
        'project_id',
        'stage_id',
        'admin_id',
        'feedback_text',
        'feedback_type',
        'priority',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'parent_feedback_id',
        'attachments',
        'metadata'
    ];
    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
    
    /**
     * Buscar feedback por administrador
     */
    public static function findByAdmin(int $adminId)
    {
        return static::where('admin_id', $adminId)->orderBy('created_at', 'desc');
    }
    
    /**
     * Buscar feedback por tipo
     */
    public static function findByType(string $feedbackType)
    {
        return static::where('feedback_type', $feedbackType)->orderBy('created_at', 'desc');
    }
    
    /**
     * Buscar feedback por prioridad
     */
    public static function findByPriority(string $priority)
    {
        return static::where('priority', $priority)->orderBy('created_at', 'desc');
    }
    
    /**
     * Buscar feedback no resuelto
     */
    public static function unresolved()
    {
        return static::where('is_resolved', false)->orderBy('priority', 'desc')->orderBy('created_at', 'asc');
    }
    
    /**
     * Buscar feedback resuelto
     */
    public static function resolved()
    {
        return static::where('is_resolved', true)->orderBy('resolved_at', 'desc');
    }
    
    /**
     * Buscar feedback de alta prioridad
     */
    public static function highPriority()
    {
        return static::whereIn('priority', ['high', 'critical'])->orderBy('created_at', 'asc');
    }
    
    /**
     * Buscar feedback crítico
     */
    public static function critical()
    {
        return static::where('priority', 'critical')->orderBy('created_at', 'asc');
    }
    
    /**
     * Obtener feedback principal (no respuestas)
     */
    public static function mainFeedback()
    {
        return static::whereNull('parent_feedback_id');
    }
    
    /**
     * Obtener respuestas únicamente
     */
    public static function replies()
    {
        return static::whereNotNull('parent_feedback_id');
    }
    
    /**
     * Obtener estadísticas de feedback
     */
    public static function getStats(): array 
    {
        $db = Database::getInstance();
        
        // Estadísticas por tipo
        $typeQuery = "SELECT feedback_type, COUNT(*) as count 
                     FROM project_feedback 
                     GROUP BY feedback_type";
        $typeStats = $db->select($typeQuery);
        
        // Estadísticas por prioridad
        $priorityQuery = "SELECT priority, COUNT(*) as count,
                            SUM(CASE WHEN is_resolved = 1 THEN 1 ELSE 0 END) as resolved_count,
                            SUM(CASE WHEN is_resolved = 0 THEN 1 ELSE 0 END) as pending_count
                          FROM project_feedback 
                          GROUP BY priority";
        $priorityStats = $db->select($priorityQuery);
        
        // Estadísticas por estado
        $statusQuery = "SELECT 
                          SUM(CASE WHEN is_resolved = 1 THEN 1 ELSE 0 END) as resolved,
                          SUM(CASE WHEN is_resolved = 0 THEN 1 ELSE 0 END) as pending,
                          COUNT(*) as total
                        FROM project_feedback";
        $statusResult = $db->selectOne($statusQuery);
        
        // Tiempo promedio de resolución
        $avgTimeQuery = "SELECT AVG(DATEDIFF(resolved_at, created_at)) as avg_days 
                        FROM project_feedback 
                        WHERE is_resolved = 1 AND resolved_at IS NOT NULL";
        $avgTimeResult = $db->selectOne($avgTimeQuery);
        
        // Feedback por área (a través de etapas)
        $areaQuery = "SELECT ps.area_name, COUNT(pf.id) as feedback_count,
                        AVG(CASE WHEN pf.is_resolved = 1 THEN DATEDIFF(pf.resolved_at, pf.created_at) END) as avg_resolution_days
                      FROM project_feedback pf
                      LEFT JOIN project_stages ps ON pf.stage_id = ps.id
                      WHERE ps.area_name IS NOT NULL
                      GROUP BY ps.area_name";
        $areaStats = $db->select($areaQuery);
        
        // Top administradores por feedback
        $adminQuery = "SELECT a.name, a.email, COUNT(pf.id) as feedback_count,
                         SUM(CASE WHEN pf.is_resolved = 1 THEN 1 ELSE 0 END) as resolved_count
                       FROM project_feedback pf
                       JOIN admins a ON pf.admin_id = a.id
                       GROUP BY a.id, a.name, a.email
                       ORDER BY COUNT(pf.id) DESC
                       LIMIT 10";
        $adminStats = $db->select($adminQuery);
        
        return [
            'total_feedback' => (int) $statusResult['total'],
            'resolved_feedback' => (int) $statusResult['resolved'],
            'pending_feedback' => (int) $statusResult['pending'],
            'resolution_rate' => $statusResult['total'] > 0 ? round(($statusResult['resolved'] / $statusResult['total']) * 100, 2) : 0,
            'avg_resolution_days' => $avgTimeResult['avg_days'] ? round((float) $avgTimeResult['avg_days'], 1) : null,
            'by_type' => $typeStats,
            'by_priority' => $priorityStats,
            'by_area' => $areaStats,
            'top_reviewers' => $adminStats
        ];
    }
    
    /**
     * Obtener feedback que requiere atención
     */
    public static function requiresAttention(): array 
    {
        $db = Database::getInstance();
        
        // Feedback crítico no resuelto
        $criticalQuery = "SELECT pf.*, p.project_code, p.title as project_title, a.name as admin_name,
                            DATEDIFF(NOW(), pf.created_at) as days_old
                          FROM project_feedback pf
                          JOIN projects p ON pf.project_id = p.id
                          JOIN admins a ON pf.admin_id = a.id
                          WHERE pf.priority = 'critical' AND pf.is_resolved = 0
                          ORDER BY pf.created_at ASC";
        
        // Feedback antiguo sin resolver (más de 7 días)
        $oldQuery = "SELECT pf.*, p.project_code, p.title as project_title, a.name as admin_name,
                       DATEDIFF(NOW(), pf.created_at) as days_old
                     FROM project_feedback pf
                     JOIN projects p ON pf.project_id = p.id
                     JOIN admins a ON pf.admin_id = a.id
                     WHERE pf.is_resolved = 0 
                     AND pf.created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
                     ORDER BY pf.created_at ASC";
        
        $critical = $db->select($criticalQuery);
        $old = $db->select($oldQuery);
        
        return [
            'critical_unresolved' => array_map(function($row) {
                $feedback = new static($row);
                $feedback->exists = true;
                $feedback->syncOriginal();
                $feedback->project_code = $row['project_code'];
                $feedback->project_title = $row['project_title'];
                $feedback->admin_name = $row['admin_name'];
                $feedback->days_old = $row['days_old'];
                $feedback->attention_reason = 'critical_priority';
                return $feedback;
            }, $critical),
            'old_unresolved' => array_map(function($row) {
                $feedback = new static($row);
                $feedback->exists = true;
                $feedback->syncOriginal();
                $feedback->project_code = $row['project_code'];
                $feedback->project_title = $row['project_title'];
                $feedback->admin_name = $row['admin_name'];
                $feedback->days_old = $row['days_old'];
                $feedback->attention_reason = 'old_unresolved';
                return $feedback;
            }, $old)
        ];
    }
    
    /**
     * Obtener resumen de feedback para un proyecto
     */
    public static function getProjectSummary(int $projectId): array 
    {
        $db = Database::getInstance();
        
        $query = "SELECT 
                    COUNT(*) as total_feedback,
                    SUM(CASE WHEN is_resolved = 1 THEN 1 ELSE 0 END) as resolved_count,
                    SUM(CASE WHEN is_resolved = 0 THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN priority = 'critical' AND is_resolved = 0 THEN 1 ELSE 0 END) as critical_pending,
                    SUM(CASE WHEN priority = 'high' AND is_resolved = 0 THEN 1 ELSE 0 END) as high_pending,
                    SUM(CASE WHEN feedback_type = 'requirement' AND is_resolved = 0 THEN 1 ELSE 0 END) as requirements_pending
                  FROM project_feedback 
                  WHERE project_id = ?";
        
        $result = $db->selectOne($query, [$projectId]);
        
        // Feedback por área
        $areaQuery = "SELECT ps.area_name, COUNT(pf.id) as feedback_count,
                        SUM(CASE WHEN pf.is_resolved = 0 THEN 1 ELSE 0 END) as pending_count
                      FROM project_feedback pf
                      LEFT JOIN project_stages ps ON pf.stage_id = ps.id
                      WHERE pf.project_id = ? AND ps.area_name IS NOT NULL
                      GROUP BY ps.area_name";
        $areaFeedback = $db->select($areaQuery, [$projectId]);
        
        return [
            'total_feedback' => (int) $result['total_feedback'],
            'resolved_count' => (int) $result['resolved_count'],
            'pending_count' => (int) $result['pending_count'],
            'critical_pending' => (int) $result['critical_pending'],
            'high_pending' => (int) $result['high_pending'],
            'requirements_pending' => (int) $result['requirements_pending'],
            'resolution_rate' => $result['total_feedback'] > 0 ? round(($result['resolved_count'] / $result['total_feedback']) * 100, 2) : 0,
            'by_area' => $areaFeedback,
            'has_blocking_issues' => ($result['critical_pending'] > 0 || $result['requirements_pending'] > 0)
        ];
    }
    
    /**
     * Obtener feed de actividad de feedback
     */
    public static function getActivityFeed(int $limit = 50): array 
    {
        $db = Database::getInstance();
        
        $query = "SELECT pf.*, p.project_code, p.title as project_title, 
                    a.name as admin_name, ps.area_name,
                    'feedback' as activity_type
                  FROM project_feedback pf
                  JOIN projects p ON pf.project_id = p.id
                  JOIN admins a ON pf.admin_id = a.id
                  LEFT JOIN project_stages ps ON pf.stage_id = ps.id
                  ORDER BY pf.created_at DESC
                  LIMIT ?";
        
        $results = $db->select($query, [$limit]);
        
        return array_map(function($row) {
            $feedback = new static($row);
            $feedback->exists = true;
            $feedback->syncOriginal();
            $feedback->project_code = $row['project_code'];
            $feedback->project_title = $row['project_title'];
            $feedback->admin_name = $row['admin_name'];
            $feedback->area_name = $row['area_name'];
            return $feedback;
        }, $results);
    }
    
    /**
     * Buscar feedback por texto
     */
    public static function search(string $query, array $filters = []): array 
    {
        $db = Database::getInstance();
        
        $searchQuery = "SELECT pf.*, p.project_code, p.title as project_title, 
                          a.name as admin_name, ps.area_name
                        FROM project_feedback pf
                        JOIN projects p ON pf.project_id = p.id
                        JOIN admins a ON pf.admin_id = a.id
                        LEFT JOIN project_stages ps ON pf.stage_id = ps.id
                        WHERE pf.feedback_text LIKE ?";
        
        $params = ["%{$query}%"];
        
        // Aplicar filtros
        if (isset($filters['project_id'])) {
            $searchQuery .= " AND pf.project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        if (isset($filters['feedback_type'])) {
            $searchQuery .= " AND pf.feedback_type = ?";
            $params[] = $filters['feedback_type'];
        }
        
        if (isset($filters['priority'])) {
            $searchQuery .= " AND pf.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (isset($filters['is_resolved'])) {
            $searchQuery .= " AND pf.is_resolved = ?";
            $params[] = $filters['is_resolved'] ? 1 : 0;
        }
        
        if (isset($filters['area_name'])) {
            $searchQuery .= " AND ps.area_name = ?";
            $params[] = $filters['area_name'];
        }
        
        $searchQuery .= " ORDER BY pf.created_at DESC LIMIT 100";
        
        $results = $db->select($searchQuery, $params);
        
        return array_map(function($row) {
            $feedback = new static($row);
            $feedback->exists = true;
            $feedback->syncOriginal();
            $feedback->project_code = $row['project_code'];
            $feedback->project_title = $row['project_title'];
            $feedback->admin_name = $row['admin_name'];
            $feedback->area_name = $row['area_name'];
            return $feedback;
        }, $results);
    }
    
    /**
     * Obtener feedback con hilos de conversación
     */
    public static function getWithThreads(int $projectId): array 
    {
        $mainFeedback = static::where('project_id', $projectId)
                             ->whereNull('parent_feedback_id')
                             ->orderBy('created_at', 'desc')
                             ->get();
        
        $threaded = [];
        
        foreach ($mainFeedback as $feedback) {
            $replies = $feedback->replies()->get();
            
            $threaded[] = [
                'feedback' => $feedback,
                'replies' => $replies,
                'reply_count' => count($replies),
                'last_reply_at' => !empty($replies) ? $replies[count($replies) - 1]->created_at : null
            ];
        }
        
        return $threaded;
    }
    
    /**
     * Marcar feedback masivamente como resuelto
     */
    public static function bulkResolve(array $feedbackIds, int $resolverAdminId, string $resolutionNote = ''): int 
    {
        $resolvedCount = 0;
        
        foreach ($feedbackIds as $feedbackId) {
            $feedback = static::find($feedbackId);
            if ($feedback && $feedback->resolve($resolverAdminId, $resolutionNote)) {
                $resolvedCount++;
            }
        }
        
        Logger::info('Feedback resuelto masivamente', [
            'resolved_count' => $resolvedCount,
            'total_requested' => count($feedbackIds),
            'resolver_id' => $resolverAdminId
        ]);
        
        return $resolvedCount;
    }
    
    /**
     * Exportar feedback de un proyecto
     */
    public static function exportProjectFeedback(int $projectId): array 
    {
        $feedback = static::findByProject($projectId)->get();
        $export = [];
        
        foreach ($feedback as $item) {
            $admin = $item->admin();
            $stage = $item->stage();
            
            $export[] = [
                'id' => $item->id,
                'feedback_text' => $item->feedback_text,
                'feedback_type' => $item->feedback_type,
                'priority' => $item->priority,
                'is_resolved' => $item->isResolved(),
                'created_at' => $item->created_at,
                'resolved_at' => $item->resolved_at,
                'admin_name' => $admin ? $admin->name : 'Desconocido',
                'admin_email' => $admin ? $admin->email : 'Desconocido',
                'area_name' => $stage ? $stage->area_name : null,
                'stage_name' => $stage ? $stage->stage_name : null,
                'is_reply' => $item->isReply(),
                'attachments_count' => count($item->getAttachments()),
                'metadata' => $item->getMetadata()
            ];
        }
        
        return $export;
    }
    
    /**
     * Obtener métricas de rendimiento por área
     */
    public static function getAreaPerformanceMetrics(): array 
    {
        $db = Database::getInstance();
        
        $query = "SELECT ps.area_name,
                    COUNT(pf.id) as total_feedback,
                    SUM(CASE WHEN pf.is_resolved = 1 THEN 1 ELSE 0 END) as resolved_feedback,
                    AVG(CASE WHEN pf.is_resolved = 1 THEN DATEDIFF(pf.resolved_at, pf.created_at) END) as avg_resolution_days,
                    SUM(CASE WHEN pf.priority = 'critical' THEN 1 ELSE 0 END) as critical_feedback,
                    SUM(CASE WHEN pf.feedback_type = 'requirement' THEN 1 ELSE 0 END) as requirement_feedback
                  FROM project_feedback pf
                  JOIN project_stages ps ON pf.stage_id = ps.id
                  WHERE pf.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  GROUP BY ps.area_name
                  ORDER BY total_feedback DESC";
        
        $results = $db->select($query);
        
        return array_map(function($row) {
            $total = (int) $row['total_feedback'];
            $resolved = (int) $row['resolved_feedback'];
            
            return [
                'area_name' => $row['area_name'],
                'total_feedback' => $total,
                'resolved_feedback' => $resolved,
                'pending_feedback' => $total - $resolved,
                'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
                'avg_resolution_days' => $row['avg_resolution_days'] ? round((float) $row['avg_resolution_days'], 1) : null,
                'critical_feedback' => (int) $row['critical_feedback'],
                'requirement_feedback' => (int) $row['requirement_feedback']
            ];
        }, $results);
    }
    
    /**
     * Limpiar feedback antiguo resuelto
     */
    public static function cleanOldResolvedFeedback(int $daysToKeep = 365): int 
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        $query = "DELETE FROM project_feedback 
                  WHERE is_resolved = 1 
                  AND resolved_at < ?";
        
        $db = Database::getInstance();
        $deletedCount = $db->delete($query, [$cutoffDate]);
        
        if ($deletedCount > 0) {
            Logger::info('Feedback antiguo resuelto eliminado', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate
            ]);
        }
        
        return $deletedCount;
    }
    
    /**
     * Convertir a array para API
     */
    public function toApiArray(): array 
    {
        $admin = $this->admin();
        $stage = $this->stage();
        $resolvedBy = $this->resolvedByAdmin();
        $parentFeedback = $this->parentFeedback();
        
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'stage_id' => $this->stage_id,
            'feedback_text' => $this->feedback_text,
            'feedback_type' => $this->feedback_type,
            'priority' => $this->priority,
            'is_resolved' => $this->isResolved(),
            'is_reply' => $this->isReply(),
            'is_high_priority' => $this->isHighPriority(),
            'is_critical' => $this->isCritical(),
            'created_at' => $this->created_at,
            'resolved_at' => $this->resolved_at,
            'admin' => $admin ? [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email
            ] : null,
            'stage' => $stage ? [
                'id' => $stage->id,
                'area_name' => $stage->area_name,
                'stage_name' => $stage->stage_name
            ] : null,
            'resolved_by' => $resolvedBy ? [
                'id' => $resolvedBy->id,
                'name' => $resolvedBy->name,
                'email' => $resolvedBy->email
            ] : null,
            'parent_feedback' => $parentFeedback ? [
                'id' => $parentFeedback->id,
                'feedback_text' => Helper::truncate($parentFeedback->feedback_text, 50)
            ] : null,
            'replies_count' => $this->replies()->count(),
            'attachments' => $this->getAttachments(),
            'metadata' => $this->getMetadata(),
            'priority_color' => Helper::getStatusColor($this->priority),
            'time_ago' => Helper::timeAgo($this->created_at)
        ];
    }

    /**
     * Crear un nuevo feedback para un proyecto
     */
    public static function createFeedback(int $projectId, int $adminId, string $feedbackText, array $options = []): ?self 
    {
        $feedbackData = [
            'project_id' => $projectId,
            'stage_id' => $options['stage_id'] ?? null,
            'admin_id' => $adminId,
            'feedback_text' => $feedbackText,
            'feedback_type' => $options['feedback_type'] ?? 'comment',
            'priority' => $options['priority'] ?? 'medium',
            'is_resolved' => false,
            'parent_feedback_id' => $options['parent_feedback_id'] ?? null,
            'attachments' => isset($options['attachments']) ? json_encode($options['attachments']) : null,
            'metadata' => isset($options['metadata']) ? json_encode($options['metadata']) : null
        ];
        
        $feedback = static::create($feedbackData);
        
        if ($feedback) {
            // Registrar en historial del proyecto
            ProjectHistory::create([
                'project_id' => $projectId,
                'user_id' => $adminId,
                'user_type' => 'admin',
                'action' => 'feedback_added',
                'description' => "Feedback agregado: " . Helper::truncate($feedbackText, 100),
                'old_values' => json_encode([]),
                'new_values' => json_encode(['feedback_id' => $feedback->id, 'feedback_type' => $feedback->feedback_type]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Feedback creado para proyecto', [
                'feedback_id' => $feedback->id,
                'project_id' => $projectId,
                'admin_id' => $adminId,
                'feedback_type' => $feedback->feedback_type,
                'priority' => $feedback->priority,
                'text_length' => strlen($feedbackText)
            ]);
            
            // Notificar al cliente si es necesario
            $feedback->notifyClient();
        }
        
        return $feedback;
    }
     public function project(): ?Project 
    {
        return Project::find($this->project_id);
    }
     public function stage(): ?ProjectStage 
    {
        return $this->stage_id ? ProjectStage::find($this->stage_id) : null;
    }
    
    /**
     * Obtener administrador que creó el feedback
     */
    public function admin(): ?Admin 
    {
        return Admin::find($this->admin_id);
    }
    
    /**
     * Obtener administrador que resolvió el feedback
     */
    public function resolvedByAdmin(): ?Admin 
    {
        return $this->resolved_by ? Admin::find($this->resolved_by) : null;
    }
    
    /**
     * Obtener feedback padre (si es respuesta)
     */
    public function parentFeedback(): ?self 
    {
        return $this->parent_feedback_id ? static::find($this->parent_feedback_id) : null;
    }
    
    /**
     * Obtener respuestas a este feedback
     */

    /**
     * Verificar si está resuelto
     */
    public function isResolved(): bool 
    {
        return (bool) $this->is_resolved;
    }
    
    /**
     * Verificar si es una respuesta
     */
    public function isReply(): bool 
    {
        return $this->parent_feedback_id !== null;
    }
    
    /**
     * Verificar si es de alta prioridad
     */
    public function isHighPriority(): bool 
    {
        return in_array($this->priority, ['high', 'critical']);
    }
    
    /**
     * Verificar si es crítico
     */
    public function isCritical(): bool 
    {
        return $this->priority === 'critical';
    }
    
    /**
     * Obtener attachments del feedback
     */
    public function getAttachments(): array 
    {
        $attachments = json_decode($this->attachments ?? '[]', true);
        return is_array($attachments) ? $attachments : [];
    }
    
    /**
     * Agregar attachment
     */
    public function addAttachment(array $attachmentInfo): bool 
    {
        $attachments = $this->getAttachments();
        $attachments[] = $attachmentInfo;
        
        $this->attachments = json_encode($attachments);
        return $this->save();
    }
    
    /**
     * Obtener metadata del feedback
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
     * Resolver feedback
     */
    public function resolve(int $resolverAdminId, string $resolutionNote = ''): bool 
    {
        if ($this->isResolved()) {
            return true; // Ya está resuelto
        }
        
        $this->is_resolved = true;
        $this->resolved_by = $resolverAdminId;
        $this->resolved_at = date('Y-m-d H:i:s');
        
        // Agregar nota de resolución a metadata
        if ($resolutionNote) {
            $metadata = $this->getMetadata();
            $metadata['resolution_note'] = $resolutionNote;
            $metadata['resolved_at'] = time();
            $this->metadata = json_encode($metadata);
        }
        
        $result = $this->save();
        
        if ($result) {
            // Registrar en historial
            ProjectHistory::create([
                'project_id' => $this->project_id,
                'user_id' => $resolverAdminId,
                'user_type' => 'admin',
                'action' => 'feedback_resolved',
                'description' => "Feedback resuelto: " . Helper::truncate($this->feedback_text, 100) . ($resolutionNote ? " - {$resolutionNote}" : ''),
                'old_values' => json_encode(['is_resolved' => false]),
                'new_values' => json_encode(['is_resolved' => true, 'resolved_by' => $resolverAdminId]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Feedback resuelto', [
                'feedback_id' => $this->id,
                'project_id' => $this->project_id,
                'resolved_by' => $resolverAdminId,
                'resolution_note' => $resolutionNote
            ]);
        }
        
        return $result;
    }
    
    /**
     * Reabrir feedback
     */
    public function reopen(int $adminId, string $reason = ''): bool 
    {
        if (!$this->isResolved()) {
            return true; // Ya está abierto
        }
        
        $this->is_resolved = false;
        $this->resolved_by = null;
        $this->resolved_at = null;
        
        // Agregar nota de reapertura a metadata
        if ($reason) {
            $metadata = $this->getMetadata();
            $metadata['reopened_reason'] = $reason;
            $metadata['reopened_by'] = $adminId;
            $metadata['reopened_at'] = time();
            $this->metadata = json_encode($metadata);
        }
        
        $result = $this->save();
        
        if ($result) {
            // Registrar en historial
            ProjectHistory::create([
                'project_id' => $this->project_id,
                'user_id' => $adminId,
                'user_type' => 'admin',
                'action' => 'feedback_reopened',
                'description' => "Feedback reabierto: " . Helper::truncate($this->feedback_text, 100) . ($reason ? " - {$reason}" : ''),
                'old_values' => json_encode(['is_resolved' => true]),
                'new_values' => json_encode(['is_resolved' => false]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Feedback reabierto', [
                'feedback_id' => $this->id,
                'project_id' => $this->project_id,
                'reopened_by' => $adminId,
                'reason' => $reason
            ]);
        }
        
        return $result;
    }
    
    /**
     * Cambiar prioridad
     */
    public function changePriority(string $newPriority, int $adminId): bool 
    {
        $validPriorities = ['low', 'medium', 'high', 'critical'];
        
        if (!in_array($newPriority, $validPriorities)) {
            Logger::warning('Prioridad de feedback inválida', [
                'feedback_id' => $this->id,
                'invalid_priority' => $newPriority,
                'valid_priorities' => $validPriorities
            ]);
            return false;
        }
        
        $oldPriority = $this->priority;
        $this->priority = $newPriority;
        
        $result = $this->save();
        
        if ($result) {
            // Registrar en historial
            ProjectHistory::create([
                'project_id' => $this->project_id,
                'user_id' => $adminId,
                'user_type' => 'admin',
                'action' => 'feedback_priority_changed',
                'description' => "Prioridad de feedback cambiada de '{$oldPriority}' a '{$newPriority}'",
                'old_values' => json_encode(['priority' => $oldPriority]),
                'new_values' => json_encode(['priority' => $newPriority]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Prioridad de feedback cambiada', [
                'feedback_id' => $this->id,
                'project_id' => $this->project_id,
                'old_priority' => $oldPriority,
                'new_priority' => $newPriority,
                'changed_by' => $adminId
            ]);
        }
        
        return $result;
    }
    
    /**
     * Agregar respuesta al feedback
     */
    public function addReply(int $adminId, string $replyText, array $options = []): ?self 
    {
        $replyOptions = array_merge($options, [
            'parent_feedback_id' => $this->id,
            'stage_id' => $this->stage_id,
            'feedback_type' => 'comment' // Las respuestas son siempre comentarios
        ]);
        
        return static::createFeedback($this->project_id, $adminId, $replyText, $replyOptions);
    }
    
    /**
     * Notificar al cliente sobre el feedback
     */
    public function notifyClient(): void 
    {
        $project = $this->project();
        $admin = $this->admin();
        
        if (!$project || !$admin) {
            return;
        }
        
        $stage = $this->stage();
        $areaName = $stage ? $stage->area_name : 'Sistema';
        
        // Aquí se integraría con el servicio de notificaciones
        Logger::info('Notificación de feedback enviada al cliente', [
            'feedback_id' => $this->id,
            'project_id' => $this->project_id,
            'client_email' => $project->user()->email ?? 'unknown',
            'feedback_type' => $this->feedback_type,
            'priority' => $this->priority
        ]);
    }
    
    /**
     * Buscar feedback por proyecto
     */
    public static function findByProject(int $projectId)
    {
        return static::where('project_id', $projectId)->orderBy('created_at', 'desc');
    }
    
    /**
     * Buscar feedback por etapa
     */
    public static function findByStage(int $stageId)
    {
        return static::where('stage_id', $stageId)->orderBy('created_at', 'desc');
    }
}
    
    