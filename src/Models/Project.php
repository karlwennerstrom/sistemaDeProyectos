<?php
/**
 * Modelo de Proyectos
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Models;

use UC\ApprovalSystem\Utils\Logger;

class Project extends BaseModel 
{
    protected $table = 'projects';
    
    protected $fillable = [
        'title',
        'description',
        'user_id',
        'status',
        'priority',
        'current_stage',
        'estimated_completion_date',
        'budget',
        'department',
        'technical_lead',
        'business_owner',
        'tags',
        'metadata'
    ];
    
    protected $guarded = [
        'id',
        'project_code',
        'progress_percentage',
        'actual_completion_date',
        'created_at',
        'updated_at'
    ];
    
    /**
     * Crear nuevo proyecto con código automático
     */
    public static function createProject(array $data): ?self 
    {
        $data['project_code'] = static::generateProjectCode();
        $data['status'] = 'draft';
        $data['current_stage'] = 'formalizacion';
        $data['progress_percentage'] = 0.00;
        
        $project = static::create($data);
        
        if ($project) {
            // Crear etapas iniciales del proyecto
            $project->createInitialStages();
            
            Logger::info('Proyecto creado', [
                'project_id' => $project->id,
                'project_code' => $project->project_code,
                'user_id' => $project->user_id,
                'title' => $project->title
            ]);
        }
        
        return $project;
    }
    
    /**
     * Generar código único de proyecto
     */
    private static function generateProjectCode(): string 
    {
        $year = date('Y');
        
        // Obtener el último número del año
        $db = Database::getInstance();
        $query = "SELECT COUNT(*) as count FROM projects WHERE YEAR(created_at) = ?";
        $result = $db->selectOne($query, [$year]);
        
        $count = ($result['count'] ?? 0) + 1;
        
        return "PROJ-{$year}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Crear etapas iniciales del proyecto
     */
    private function createInitialStages(): void 
    {
        $appConfig = include __DIR__ . '/../../config/app.php';
        $areas = $appConfig['areas'];
        
        $stages = [
            ['area' => 'formalizacion', 'name' => 'Validación de Formalización', 'order' => 1],
            ['area' => 'arquitectura', 'name' => 'Validación de Arquitectura', 'order' => 2],
            ['area' => 'infraestructura', 'name' => 'Validación de Infraestructura', 'order' => 3],
            ['area' => 'seguridad', 'name' => 'Validación de Seguridad', 'order' => 4],
            ['area' => 'basedatos', 'name' => 'Validación de Base de Datos', 'order' => 5],
            ['area' => 'integraciones', 'name' => 'Validación de Integraciones', 'order' => 6],
            ['area' => 'ambientes', 'name' => 'Validación de Ambientes', 'order' => 7],
            ['area' => 'jcps', 'name' => 'Validación de Pruebas', 'order' => 8],
            ['area' => 'monitoreo', 'name' => 'Validación de Monitoreo', 'order' => 9]
        ];
        
        foreach ($stages as $stageData) {
            ProjectStage::create([
                'project_id' => $this->id,
                'area_name' => $stageData['area'],
                'stage_name' => $stageData['name'],
                'status' => $stageData['order'] === 1 ? 'pending' : 'pending',
                'order_sequence' => $stageData['order']
            ]);
        }
        
        Logger::debug('Etapas iniciales creadas para proyecto', [
            'project_id' => $this->id,
            'project_code' => $this->project_code,
            'stages_count' => count($stages)
        ]);
    }
    
    /**
     * Buscar proyecto por código
     */
    public static function findByCode(string $code): ?self 
    {
        return static::where('project_code', $code)->first();
    }
    
    /**
     * Obtener usuario propietario
     */
    public function user(): ?User 
    {
        return User::find($this->user_id);
    }
    
    /**
     * Obtener etapas del proyecto
     */
    public function stages()
    {
        return ProjectStage::where('project_id', $this->id)->orderBy('order_sequence');
    }
    
    /**
     * Obtener etapa actual
     */
    public function getCurrentStage(): ?ProjectStage 
    {
        return ProjectStage::where('project_id', $this->id)
                          ->where('area_name', $this->current_stage)
                          ->first();
    }
    
    /**
     * Obtener siguiente etapa
     */
    public function getNextStage(): ?ProjectStage 
    {
        $currentStage = $this->getCurrentStage();
        
        if (!$currentStage) {
            return null;
        }
        
        return ProjectStage::where('project_id', $this->id)
                          ->where('order_sequence', '>', $currentStage->order_sequence)
                          ->orderBy('order_sequence')
                          ->first();
    }
    
    /**
     * Obtener documentos del proyecto
     */
    public function documents()
    {
        return Document::where('project_id', $this->id)->orderBy('created_at', 'desc');
    }
    
    /**
     * Obtener documentos por área
     */
    public function getDocumentsByArea(string $area)
    {
        return Document::where('project_id', $this->id)
                      ->where('area_name', $area)
                      ->where('is_latest', true)
                      ->orderBy('created_at', 'desc');
    }
    
    /**
     * Obtener feedback del proyecto
     */
    public function feedback()
    {
        return ProjectFeedback::where('project_id', $this->id)->orderBy('created_at', 'desc');
    }
    
    /**
     * Obtener feedback pendiente
     */
    public function pendingFeedback()
    {
        return ProjectFeedback::where('project_id', $this->id)
                             ->where('is_resolved', false)
                             ->orderBy('priority', 'desc')
                             ->orderBy('created_at', 'desc');
    }
    
    /**
     * Obtener historial del proyecto
     */
    public function history()
    {
        return ProjectHistory::where('project_id', $this->id)->orderBy('created_at', 'desc');
    }
    
    /**
     * Cambiar estado del proyecto
     */
    public function changeStatus(string $newStatus, string $reason = '', int $adminId = null): bool 
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;
        
        // Actualizar fecha de finalización si se aprueba
        if ($newStatus === 'approved' && !$this->actual_completion_date) {
            $this->actual_completion_date = date('Y-m-d H:i:s');
        }
        
        $result = $this->save();
        
        if ($result) {
            // Registrar en historial
            ProjectHistory::create([
                'project_id' => $this->id,
                'user_id' => $adminId,
                'user_type' => $adminId ? 'admin' : 'system',
                'action' => 'status_changed',
                'description' => "Estado cambiado de '{$oldStatus}' a '{$newStatus}'" . ($reason ? ": {$reason}" : ''),
                'old_values' => json_encode(['status' => $oldStatus]),
                'new_values' => json_encode(['status' => $newStatus]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Estado de proyecto cambiado', [
                'project_id' => $this->id,
                'project_code' => $this->project_code,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'admin_id' => $adminId,
                'reason' => $reason
            ]);
            
            // Enviar notificación al usuario
            $this->notifyStatusChange($oldStatus, $newStatus, $reason);
        }
        
        return $result;
    }
    
    /**
     * Avanzar a la siguiente etapa
     */
    public function advanceToNextStage(int $adminId = null): bool 
    {
        $currentStage = $this->getCurrentStage();
        $nextStage = $this->getNextStage();
        
        if (!$currentStage || !$nextStage) {
            return false;
        }
        
        // Marcar etapa actual como completada
        $currentStage->status = 'completed';
        $currentStage->end_date = date('Y-m-d H:i:s');
        $currentStage->save();
        
        // Activar siguiente etapa
        $nextStage->status = 'pending';
        $nextStage->start_date = date('Y-m-d H:i:s');
        
        // Asignar revisor automáticamente
        $reviewer = Admin::getLeastBusyReviewer($nextStage->area_name);
        if ($reviewer) {
            $nextStage->assigned_to = $reviewer->id;
        }
        
        $nextStage->save();
        
        // Actualizar etapa actual del proyecto
        $this->current_stage = $nextStage->area_name;
        $this->save();
        
        // Registrar en historial
        ProjectHistory::create([
            'project_id' => $this->id,
            'user_id' => $adminId,
            'user_type' => $adminId ? 'admin' : 'system',
            'action' => 'stage_advanced',
            'description' => "Proyecto avanzó de '{$currentStage->area_name}' a '{$nextStage->area_name}'",
            'old_values' => json_encode(['stage' => $currentStage->area_name]),
            'new_values' => json_encode(['stage' => $nextStage->area_name]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        Logger::info('Proyecto avanzó a siguiente etapa', [
            'project_id' => $this->id,
            'project_code' => $this->project_code,
            'from_stage' => $currentStage->area_name,
            'to_stage' => $nextStage->area_name,
            'assigned_to' => $reviewer ? $reviewer->id : null
        ]);
        
        return true;
    }
    
    /**
     * Calcular progreso del proyecto
     */
    public function calculateProgress(): float 
    {
        $stages = $this->stages()->get();
        $totalStages = count($stages);
        
        if ($totalStages === 0) {
            return 0.0;
        }
        
        $completedStages = 0;
        $partialProgress = 0;
        
        foreach ($stages as $stage) {
            if ($stage->status === 'completed') {
                $completedStages++;
            } elseif ($stage->status === 'in_progress') {
                $partialProgress += $stage->completion_percentage / 100;
            }
        }
        
        $progress = (($completedStages + $partialProgress) / $totalStages) * 100;
        
        // Actualizar en base de datos
        $this->progress_percentage = round($progress, 2);
        $this->save();
        
        return $progress;
    }
    
    /**
     * Verificar si el proyecto está atrasado
     */
    public function isOverdue(): bool 
    {
        if (!$this->estimated_completion_date) {
            return false;
        }
        
        return strtotime($this->estimated_completion_date) < time() && 
               !in_array($this->status, ['approved', 'rejected']);
    }
    
    /**
     * Obtener días de retraso
     */
    public function getDaysOverdue(): int 
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        
        $estimatedDate = strtotime($this->estimated_completion_date);
        $currentDate = time();
        
        return ceil(($currentDate - $estimatedDate) / (24 * 60 * 60));
    }
    
    /**
     * Obtener tiempo estimado restante
     */
    public function getEstimatedTimeRemaining(): ?int 
    {
        if (!$this->estimated_completion_date || $this->status === 'approved') {
            return null;
        }
        
        $estimatedDate = strtotime($this->estimated_completion_date);
        $currentDate = time();
        
        $daysRemaining = ceil(($estimatedDate - $currentDate) / (24 * 60 * 60));
        
        return max(0, $daysRemaining);
    }
    
    /**
     * Obtener tags del proyecto
     */
    public function getTags(): array 
    {
        $tags = json_decode($this->tags ?? '[]', true);
        return is_array($tags) ? $tags : [];
    }
    
    /**
     * Agregar tag al proyecto
     */
    public function addTag(string $tag): bool 
    {
        $tags = $this->getTags();
        
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = json_encode($tags);
            return $this->save();
        }
        
        return true;
    }
    
    /**
     * Remover tag del proyecto
     */
    public function removeTag(string $tag): bool 
    {
        $tags = $this->getTags();
        $index = array_search($tag, $tags);
        
        if ($index !== false) {
            unset($tags[$index]);
            $this->tags = json_encode(array_values($tags));
            return $this->save();
        }
        
        return true;
    }
    
    /**
     * Obtener metadata del proyecto
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
     * Enviar notificación de cambio de estado
     */
    private function notifyStatusChange(string $oldStatus, string $newStatus, string $reason): void 
    {
        // Aquí se integraría con el servicio de notificaciones
        Logger::info('Notificación de cambio de estado enviada', [
            'project_id' => $this->id,
            'project_code' => $this->project_code,
            'user_id' => $this->user_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);
    }
    
    /**
     * Buscar proyectos por estado
     */
    public static function findByStatus(string $status)
    {
        return static::where('status', $status);
    }
    
    /**
     * Buscar proyectos por usuario
     */
    public static function findByUser(int $userId)
    {
        return static::where('user_id', $userId)->orderBy('created_at', 'desc');
    }
    
    /**
     * Buscar proyectos por prioridad
     */
    public static function findByPriority(string $priority)
    {
        return static::where('priority', $priority);
    }
    
    /**
     * Buscar proyectos atrasados
     */
    public static function overdue()
    {
        return static::where('estimated_completion_date', '<', date('Y-m-d'))
                    ->whereNotIn('status', ['approved', 'rejected']);
    }
    
    /**
     * Buscar proyectos activos
     */
    public static function active()
    {
        return static::whereIn('status', ['submitted', 'in_review']);
    }
    
    /**
     * Buscar proyectos por departamento
     */
    public static function findByDepartment(string $department)
    {
        return static::where('department', $department);
    }
    
    /**
     * Buscar proyectos creados en un rango de fechas
     */
    public static function createdBetween(string $startDate, string $endDate)
    {
        return static::where('created_at', '>=', $startDate)
                    ->where('created_at', '<=', $endDate);
    }
    
    /**
     * Obtener estadísticas del proyecto
     */
    public function getStats(): array 
    {
        $stages = $this->stages()->get();
        $documents = $this->documents()->get();
        $feedback = $this->feedback()->get();
        
        return [
            'basic_info' => [
                'id' => $this->id,
                'project_code' => $this->project_code,
                'title' => $this->title,
                'status' => $this->status,
                'priority' => $this->priority,
                'current_stage' => $this->current_stage,
                'progress_percentage' => $this->progress_percentage,
                'created_at' => $this->created_at,
                'estimated_completion' => $this->estimated_completion_date,
                'actual_completion' => $this->actual_completion_date
            ],
            'stages' => [
                'total' => count($stages),
                'completed' => count(array_filter($stages, fn($s) => $s->status === 'completed')),
                'in_progress' => count(array_filter($stages, fn($s) => $s->status === 'in_progress')),
                'pending' => count(array_filter($stages, fn($s) => $s->status === 'pending'))
            ],
            'documents' => [
                'total' => count($documents),
                'by_area' => $this->getDocumentCountByArea(),
                'latest_upload' => $this->getLatestDocumentDate()
            ],
            'feedback' => [
                'total' => count($feedback),
                'resolved' => count(array_filter($feedback, fn($f) => $f->is_resolved)),
                'pending' => count(array_filter($feedback, fn($f) => !$f->is_resolved)),
                'by_priority' => $this->getFeedbackByPriority()
            ],
            'timing' => [
                'days_since_creation' => $this->getDaysSinceCreation(),
                'estimated_days_remaining' => $this->getEstimatedTimeRemaining(),
                'is_overdue' => $this->isOverdue(),
                'days_overdue' => $this->getDaysOverdue()
            ]
        ];
    }
    
    /**
     * Obtener conteo de documentos por área
     */
    private function getDocumentCountByArea(): array 
    {
        $query = "SELECT area_name, COUNT(*) as count 
                  FROM documents 
                  WHERE project_id = ? AND is_latest = 1 
                  GROUP BY area_name";
        
        $results = $this->db->select($query, [$this->id]);
        
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['area_name']] = (int) $row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Obtener fecha del último documento subido
     */
    private function getLatestDocumentDate(): ?string 
    {
        $query = "SELECT MAX(created_at) as latest_date FROM documents WHERE project_id = ?";
        $result = $this->db->selectOne($query, [$this->id]);
        
        return $result['latest_date'];
    }
    
    /**
     * Obtener feedback agrupado por prioridad
     */
    private function getFeedbackByPriority(): array 
    {
        $query = "SELECT priority, COUNT(*) as count 
                  FROM project_feedback 
                  WHERE project_id = ? 
                  GROUP BY priority";
        
        $results = $this->db->select($query, [$this->id]);
        
        $counts = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
        foreach ($results as $row) {
            $counts[$row['priority']] = (int) $row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Obtener días desde creación
     */
    private function getDaysSinceCreation(): int 
    {
        $createdTimestamp = strtotime($this->created_at);
        $currentTimestamp = time();
        
        return floor(($currentTimestamp - $createdTimestamp) / (24 * 60 * 60));
    }
    
    /**
     * Obtener estadísticas generales de proyectos
     */
    public static function getGeneralStats(): array 
    {
        $db = Database::getInstance();
        
        // Conteo por estado
        $statusQuery = "SELECT status, COUNT(*) as count FROM projects GROUP BY status";
        $statusResults = $db->select($statusQuery);
        
        $statusCounts = [];
        foreach ($statusResults as $row) {
            $statusCounts[$row['status']] = (int) $row['count'];
        }
        
        // Conteo por prioridad
        $priorityQuery = "SELECT priority, COUNT(*) as count FROM projects GROUP BY priority";
        $priorityResults = $db->select($priorityQuery);
        
        $priorityCounts = [];
        foreach ($priorityResults as $row) {
            $priorityCounts[$row['priority']] = (int) $row['count'];
        }
        
        // Proyectos por mes (últimos 12 meses)
        $monthlyQuery = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                         FROM projects 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                         ORDER BY month";
        $monthlyResults = $db->select($monthlyQuery);
        
        // Tiempo promedio de completado
        $avgTimeQuery = "SELECT AVG(DATEDIFF(actual_completion_date, created_at)) as avg_days 
                         FROM projects 
                         WHERE status = 'approved' AND actual_completion_date IS NOT NULL";
        $avgTimeResult = $db->selectOne($avgTimeQuery);
        
        // Top departamentos
        $departmentQuery = "SELECT department, COUNT(*) as count 
                           FROM projects 
                           WHERE department IS NOT NULL AND department != '' 
                           GROUP BY department 
                           ORDER BY count DESC 
                           LIMIT 10";
        $departmentResults = $db->select($departmentQuery);
        
        return [
            'total_projects' => array_sum($statusCounts),
            'status_breakdown' => $statusCounts,
            'priority_breakdown' => $priorityCounts,
            'monthly_creation' => $monthlyResults,
            'avg_completion_days' => $avgTimeResult['avg_days'] ? round((float) $avgTimeResult['avg_days'], 1) : null,
            'top_departments' => $departmentResults,
            'overdue_count' => static::overdue()->count(),
            'active_count' => static::active()->count()
        ];
    }
    
    /**
     * Obtener proyectos más activos (con más actividad reciente)
     */
    public static function getMostActive(int $limit = 10): array 
    {
        $db = Database::getInstance();
        
        $query = "SELECT p.*, 
                    (SELECT COUNT(*) FROM documents d WHERE d.project_id = p.id AND d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_documents,
                    (SELECT COUNT(*) FROM project_feedback pf WHERE pf.project_id = p.id AND pf.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_feedback,
                    (SELECT COUNT(*) FROM project_history ph WHERE ph.project_id = p.id AND ph.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_activity
                  FROM projects p 
                  WHERE p.status IN ('submitted', 'in_review')
                  ORDER BY (recent_documents + recent_feedback + recent_activity) DESC, p.updated_at DESC 
                  LIMIT ?";
        
        $results = $db->select($query, [$limit]);
        
        return array_map(function($row) {
            $project = new static($row);
            $project->exists = true;
            $project->syncOriginal();
            return $project;
        }, $results);
    }
    
    /**
     * Buscar proyectos que requieren atención
     */
    public static function requiresAttention(): array 
    {
        $db = Database::getInstance();
        
        $query = "SELECT p.*, 
                    CASE 
                        WHEN p.estimated_completion_date < NOW() AND p.status NOT IN ('approved', 'rejected') THEN 'overdue'
                        WHEN (SELECT COUNT(*) FROM project_feedback pf WHERE pf.project_id = p.id AND pf.is_resolved = 0 AND pf.priority = 'high') > 0 THEN 'high_priority_feedback'
                        WHEN (SELECT COUNT(*) FROM project_stages ps WHERE ps.project_id = p.id AND ps.status = 'in_progress' AND ps.due_date < NOW()) > 0 THEN 'stage_overdue'
                        WHEN p.updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY) AND p.status = 'in_review' THEN 'stalled'
                        ELSE 'normal'
                    END as attention_reason
                  FROM projects p 
                  WHERE p.status IN ('submitted', 'in_review')
                  HAVING attention_reason != 'normal'
                  ORDER BY 
                    CASE attention_reason 
                        WHEN 'overdue' THEN 1
                        WHEN 'high_priority_feedback' THEN 2
                        WHEN 'stage_overdue' THEN 3
                        WHEN 'stalled' THEN 4
                    END";
        
        $results = $db->select($query);
        
        return array_map(function($row) {
            $project = new static($row);
            $project->exists = true;
            $project->syncOriginal();
            $project->attention_reason = $row['attention_reason'];
            return $project;
        }, $results);
    }
    
    /**
     * Limpiar proyectos antiguos en borrador
     */
    public static function cleanOldDrafts(int $daysOld = 30): int 
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
        
        $oldDrafts = static::where('status', 'draft')
                          ->where('created_at', '<', $cutoffDate)
                          ->get();
        
        $deletedCount = 0;
        foreach ($oldDrafts as $project) {
            // Verificar que no tenga documentos subidos
            $documentCount = $project->documents()->count();
            
            if ($documentCount === 0 && $project->delete()) {
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            Logger::info('Proyectos borrador antiguos eliminados', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate
            ]);
        }
        
        return $deletedCount;
    }
    
    /**
     * Verificar si el proyecto puede ser editado
     */
    public function canBeEdited(): bool 
    {
        return in_array($this->status, ['draft', 'rejected']);
    }
    
    /**
     * Verificar si el proyecto puede ser enviado
     */
    public function canBeSubmitted(): bool 
    {
        return $this->status === 'draft';
    }
    
    /**
     * Enviar proyecto para revisión
     */
    public function submit(): bool 
    {
        if (!$this->canBeSubmitted()) {
            return false;
        }
        
        $this->status = 'submitted';
        $this->current_stage = 'arquitectura'; // Primera etapa de revisión
        
        $result = $this->save();
        
        if ($result) {
            // Activar primera etapa
            $firstStage = $this->stages()->orderBy('order_sequence')->first();
            if ($firstStage) {
                $firstStage->status = 'pending';
                $firstStage->start_date = date('Y-m-d H:i:s');
                
                // Asignar revisor automáticamente
                $reviewer = Admin::getLeastBusyReviewer($firstStage->area_name);
                if ($reviewer) {
                    $firstStage->assigned_to = $reviewer->id;
                }
                
                $firstStage->save();
            }
            
            // Registrar en historial
            ProjectHistory::create([
                'project_id' => $this->id,
                'user_id' => $this->user_id,
                'user_type' => 'user',
                'action' => 'submitted',
                'description' => 'Proyecto enviado para revisión',
                'old_values' => json_encode(['status' => 'draft']),
                'new_values' => json_encode(['status' => 'submitted']),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Proyecto enviado para revisión', [
                'project_id' => $this->id,
                'project_code' => $this->project_code,
                'user_id' => $this->user_id
            ]);
        }
        
        return $result;
    }
    
    /**
     * Convertir a array para API
     */
    public function toApiArray(): array 
    {
        return [
            'id' => $this->id,
            'project_code' => $this->project_code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'current_stage' => $this->current_stage,
            'progress_percentage' => $this->progress_percentage,
            'department' => $this->department,
            'technical_lead' => $this->technical_lead,
            'business_owner' => $this->business_owner,
            'estimated_completion_date' => $this->estimated_completion_date,
            'actual_completion_date' => $this->actual_completion_date,
            'budget' => $this->budget,
            'tags' => $this->getTags(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->user() ? $this->user()->toApiArray() : null,
            'is_overdue' => $this->isOverdue(),
            'days_overdue' => $this->getDaysOverdue(),
            'estimated_days_remaining' => $this->getEstimatedTimeRemaining()
        ];
    }
    
    /**
     * Convertir a array detallado para administradores
     */
    public function toDetailedArray(): array 
    {
        $basicInfo = $this->toApiArray();
        
        return array_merge($basicInfo, [
            'stages' => $this->stages()->get()->map(function($stage) {
                return $stage->toApiArray();
            })->toArray(),
            'documents_count' => $this->documents()->count(),
            'feedback_count' => $this->feedback()->count(),
            'pending_feedback_count' => $this->pendingFeedback()->count(),
            'stats' => $this->getStats(),
            'can_be_edited' => $this->canBeEdited(),
            'can_be_submitted' => $this->canBeSubmitted()
        ]);
    }
}