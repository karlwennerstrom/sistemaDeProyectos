<?php
/**
 * Modelo de Etapas de Proyecto
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Models;

use UC\ApprovalSystem\Utils\Logger;

class ProjectStage extends BaseModel 
{
    protected $table = 'project_stages';
    
    protected $fillable = [
        'project_id',
        'area_name',
        'stage_name',
        'status',
        'assigned_to',
        'order_sequence',
        'estimated_hours',
        'actual_hours',
        'start_date',
        'end_date',
        'due_date',
        'reviewer_notes',
        'completion_percentage',
        'required_documents'
    ];
    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
    
    /**
     * Obtener proyecto asociado
     */
    public function project(): ?Project 
    {
        return Project::find($this->project_id);
    }
    
    /**
     * Obtener administrador asignado
     */
    public function assignedAdmin(): ?Admin 
    {
        return $this->assigned_to ? Admin::find($this->assigned_to) : null;
    }
    
    /**
     * Obtener feedback de esta etapa
     */
    public function feedback()
    {
        return ProjectFeedback::where('stage_id', $this->id);
    }
    
    /**
     * Obtener documentos requeridos
     */
    public function getRequiredDocuments(): array 
    {
        $documents = json_decode($this->required_documents ?? '[]', true);
        return is_array($documents) ? $documents : [];
    }
    
    /**
     * Establecer documentos requeridos
     */
    public function setRequiredDocuments(array $documents): bool 
    {
        $this->required_documents = json_encode($documents);
        return $this->save();
    }
    
    /**
     * Verificar si la etapa está pendiente
     */
    public function isPending(): bool 
    {
        return $this->status === 'pending';
    }
    
    /**
     * Verificar si la etapa está en progreso
     */
    public function isInProgress(): bool 
    {
        return $this->status === 'in_progress';
    }
    
    /**
     * Verificar si la etapa está completada
     */
    public function isCompleted(): bool 
    {
        return $this->status === 'completed';
    }
    
    /**
     * Verificar si la etapa falló
     */
    public function isFailed(): bool 
    {
        return $this->status === 'failed';
    }
    
    /**
     * Verificar si la etapa está atrasada
     */
    public function isOverdue(): bool 
    {
        if (!$this->due_date || $this->isCompleted()) {
            return false;
        }
        
        return strtotime($this->due_date) < time();
    }
    
    /**
     * Obtener días de retraso
     */
    public function getDaysOverdue(): int 
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        
        $dueTimestamp = strtotime($this->due_date);
        $currentTimestamp = time();
        
        return ceil(($currentTimestamp - $dueTimestamp) / (24 * 60 * 60));
    }
    
    /**
     * Iniciar etapa
     */
    public function start(int $adminId = null): bool 
    {
        if (!$this->isPending()) {
            return false;
        }
        
        $this->status = 'in_progress';
        $this->start_date = date('Y-m-d H:i:s');
        
        // Asignar revisor si no está asignado
        if (!$this->assigned_to && $adminId) {
            $this->assigned_to = $adminId;
        } elseif (!$this->assigned_to) {
            $reviewer = Admin::getLeastBusyReviewer($this->area_name);
            if ($reviewer) {
                $this->assigned_to = $reviewer->id;
            }
        }
        
        // Establecer fecha límite si no existe (por defecto 7 días)
        if (!$this->due_date) {
            $this->due_date = date('Y-m-d H:i:s', strtotime('+7 days'));
        }
        
        $result = $this->save();
        
        if ($result) {
            // Actualizar proyecto
            $project = $this->project();
            if ($project) {
                $project->current_stage = $this->area_name;
                $project->status = 'in_review';
                $project->save();
            }
            
            // Registrar en historial
            ProjectHistory::create([
                'project_id' => $this->project_id,
                'user_id' => $adminId,
                'user_type' => 'admin',
                'action' => 'stage_started',
                'description' => "Etapa '{$this->stage_name}' iniciada en área '{$this->area_name}'",
                'old_values' => json_encode(['status' => 'pending']),
                'new_values' => json_encode(['status' => 'in_progress']),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Etapa de proyecto iniciada', [
                'stage_id' => $this->id,
                'project_id' => $this->project_id,
                'area_name' => $this->area_name,
                'assigned_to' => $this->assigned_to,
                'admin_id' => $adminId
            ]);
            
            // Notificar al revisor asignado
            if ($this->assigned_to) {
                $this->notifyAssignedReviewer();
            }
        }
        
        return $result;
    }
    
    /**
     * Completar etapa
     */
    public function complete(int $adminId, string $notes = ''): bool 
    {
        if (!$this->isInProgress()) {
            return false;
        }
        
        $this->status = 'completed';
        $this->end_date = date('Y-m-d H:i:s');
        $this->completion_percentage = 100.0;
        
        if ($notes) {
            $this->reviewer_notes = $notes;
        }
        
        // Calcular horas reales si hay fecha de inicio
        if ($this->start_date) {
            $startTimestamp = strtotime($this->start_date);
            $endTimestamp = time();
            $hoursSpent = ($endTimestamp - $startTimestamp) / 3600;
            $this->actual_hours = round($hoursSpent, 2);
        }
        
        $result = $this->save();
        
        if ($result) {
            // Registrar en historial
            ProjectHistory::create([
                'project_id' => $this->project_id,
                'user_id' => $adminId,
                'user_type' => 'admin',
                'action' => 'stage_completed',
                'description' => "Etapa '{$this->stage_name}' completada en área '{$this->area_name}'" . ($notes ? ": {$notes}" : ''),
                'old_values' => json_encode(['status' => 'in_progress']),
                'new_values' => json_encode(['status' => 'completed']),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Etapa de proyecto completada', [
                'stage_id' => $this->id,
                'project_id' => $this->project_id,
                'area_name' => $this->area_name,
                'admin_id' => $adminId,
                'actual_hours' => $this->actual_hours
            ]);
            
            // Verificar si el proyecto puede avanzar automáticamente
            $this->checkProjectAdvancement();
        }
        
        return $result;
    }
    
    /**
     * Marcar etapa como fallida
     */
    public function fail(int $adminId, string $reason): bool 
    {
        if (!$this->isInProgress()) {
            return false;
        }
        
        $this->status = 'failed';
        $this->end_date = date('Y-m-d H:i:s');
        $this->reviewer_notes = $reason;
        
        $result = $this->save();
        
        if ($result) {
            // Marcar proyecto como rechazado
            $project = $this->project();
            if ($project) {
                $project->status = 'rejected';
                $project->save();
            }
            
            // Registrar en historial
            ProjectHistory::create([
                'project_id' => $this->project_id,
                'user_id' => $adminId,
                'user_type' => 'admin',
                'action' => 'stage_failed',
                'description' => "Etapa '{$this->stage_name}' falló en área '{$this->area_name}': {$reason}",
                'old_values' => json_encode(['status' => 'in_progress']),
                'new_values' => json_encode(['status' => 'failed']),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::warning('Etapa de proyecto falló', [
                'stage_id' => $this->id,
                'project_id' => $this->project_id,
                'area_name' => $this->area_name,
                'admin_id' => $adminId,
                'reason' => $reason
            ]);
        }
        
        return $result;
    }
    
    /**
     * Asignar revisor a la etapa
     */
    public function assignReviewer(int $adminId, int $assignedBy = null): bool 
    {
        $admin = Admin::find($adminId);
        
        if (!$admin || !$admin->hasAreaAccess($this->area_name)) {
            return false;
        }
        
        $oldAssignedTo = $this->assigned_to;
        $this->assigned_to = $adminId;
        
        $result = $this->save();
        
        if ($result) {
            // Registrar en historial
            ProjectHistory::create([
                'project_id' => $this->project_id,
                'user_id' => $assignedBy,
                'user_type' => 'admin',
                'action' => 'reviewer_assigned',
                'description' => "Revisor asignado para etapa '{$this->stage_name}' en área '{$this->area_name}': {$admin->name}",
                'old_values' => json_encode(['assigned_to' => $oldAssignedTo]),
                'new_values' => json_encode(['assigned_to' => $adminId]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Revisor asignado a etapa', [
                'stage_id' => $this->id,
                'project_id' => $this->project_id,
                'area_name' => $this->area_name,
                'assigned_to' => $adminId,
                'assigned_by' => $assignedBy
            ]);
            
            // Notificar al nuevo revisor
            $this->notifyAssignedReviewer();
        }
        
        return $result;
    }
    
    /**
     * Actualizar progreso de la etapa
     */
    public function updateProgress(float $percentage, string $notes = ''): bool 
    {
        if ($percentage < 0 || $percentage > 100) {
            return false;
        }
        
        $this->completion_percentage = $percentage;
        
        if ($notes) {
            $this->reviewer_notes = $notes;
        }
        
        $result = $this->save();
        
        if ($result) {
            Logger::debug('Progreso de etapa actualizado', [
                'stage_id' => $this->id,
                'project_id' => $this->project_id,
                'area_name' => $this->area_name,
                'percentage' => $percentage
            ]);
            
            // Actualizar progreso general del proyecto
            $project = $this->project();
            if ($project) {
                $project->calculateProgress();
            }
        }
        
        return $result;
    }
    
    /**
     * Extender fecha límite
     */
    public function extendDueDate(string $newDueDate, string $reason, int $adminId): bool 
    {
        $oldDueDate = $this->due_date;
        $this->due_date = $newDueDate;
        
        $result = $this->save();
        
        if ($result) {
            // Registrar en historial
            ProjectHistory::create([
                'project_id' => $this->project_id,
                'user_id' => $adminId,
                'user_type' => 'admin',
                'action' => 'due_date_extended',
                'description' => "Fecha límite extendida para etapa '{$this->stage_name}': {$reason}",
                'old_values' => json_encode(['due_date' => $oldDueDate]),
                'new_values' => json_encode(['due_date' => $newDueDate]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Logger::info('Fecha límite de etapa extendida', [
                'stage_id' => $this->id,
                'project_id' => $this->project_id,
                'old_due_date' => $oldDueDate,
                'new_due_date' => $newDueDate,
                'reason' => $reason,
                'admin_id' => $adminId
            ]);
        }
        
        return $result;
    }
    
    /**
     * Verificar si el proyecto puede avanzar automáticamente
     */
    private function checkProjectAdvancement(): void 
    {
        $project = $this->project();
        if (!$project) {
            return;
        }
        
        // Verificar si todas las etapas están completadas
        $allStagesCompleted = $project->stages()
                                     ->where('status', '!=', 'completed')
                                     ->count() === 0;
        
        if ($allStagesCompleted) {
            // Aprobar proyecto automáticamente
            $project->status = 'approved';
            $project->actual_completion_date = date('Y-m-d H:i:s');
            $project->save();
            
            Logger::info('Proyecto aprobado automáticamente', [
                'project_id' => $project->id,
                'project_code' => $project->project_code
            ]);
        } else {
            // Avanzar a la siguiente etapa
            $nextStage = $project->stages()
                                ->where('order_sequence', '>', $this->order_sequence)
                                ->where('status', 'pending')
                                ->orderBy('order_sequence')
                                ->first();
            
            if ($nextStage) {
                $nextStage->start();
            }
        }
    }
    
    /**
     * Notificar al revisor asignado
     */
    private function notifyAssignedReviewer(): void 
    {
        $admin = $this->assignedAdmin();
        if (!$admin) {
            return;
        }
        
        // Aquí se integraría con el servicio de notificaciones
        Logger::info('Notificación enviada a revisor asignado', [
            'stage_id' => $this->id,
            'project_id' => $this->project_id,
            'admin_id' => $this->assigned_to,
            'area_name' => $this->area_name
        ]);
    }
    
    /**
     * Buscar etapas por área
     */
    public static function findByArea(string $area)
    {
        return static::where('area_name', $area);
    }
    
    /**
     * Buscar etapas por estado
     */
    public static function findByStatus(string $status)
    {
        return static::where('status', $status);
    }
    
    /**
     * Buscar etapas asignadas a un revisor
     */
    public static function findByReviewer(int $adminId)
    {
        return static::where('assigned_to', $adminId);
    }
    
    /**
     * Buscar etapas atrasadas
     */
    public static function overdue()
    {
        return static::where('due_date', '<', date('Y-m-d H:i:s'))
                    ->whereNotIn('status', ['completed', 'failed', 'skipped']);
    }
    
    /**
     * Buscar etapas pendientes
     */
    public static function pending()
    {
        return static::where('status', 'pending');
    }
    
    /**
     * Buscar etapas en progreso
     */
    public static function inProgress()
    {
        return static::where('status', 'in_progress');
    }
    
    /**
     * Obtener estadísticas por área
     */
    public static function getStatsByArea(string $area): array 
    {
        $db = Database::getInstance();
        
        $query = "SELECT 
                    status,
                    COUNT(*) as count,
                    AVG(completion_percentage) as avg_completion,
                    COUNT(CASE WHEN due_date < NOW() AND status NOT IN ('completed', 'failed') THEN 1 END) as overdue_count
                  FROM project_stages 
                  WHERE area_name = ? 
                  GROUP BY status";
        
        $results = $db->select($query, [$area]);
        
        $stats = [
            'area_name' => $area,
            'total' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'failed' => 0,
            'overdue' => 0,
            'avg_completion' => 0
        ];
        
        foreach ($results as $row) {
            $stats['total'] += $row['count'];
            $stats[$row['status']] = (int) $row['count'];
            $stats['avg_completion'] = round((float) $row['avg_completion'], 1);
            $stats['overdue'] += (int) $row['overdue_count'];
        }
        
        return $stats;
    }
    
    /**
     * Obtener estadísticas generales de etapas
     */
    public static function getGeneralStats(): array 
    {
        $db = Database::getInstance();
        
        // Estadísticas por estado
        $statusQuery = "SELECT status, COUNT(*) as count FROM project_stages GROUP BY status";
        $statusResults = $db->select($statusQuery);
        
        $statusCounts = [];
        foreach ($statusResults as $row) {
            $statusCounts[$row['status']] = (int) $row['count'];
        }
        
        // Estadísticas por área
        $areaQuery = "SELECT area_name, 
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                        COUNT(CASE WHEN due_date < NOW() AND status NOT IN ('completed', 'failed') THEN 1 END) as overdue
                      FROM project_stages 
                      GROUP BY area_name";
        $areaResults = $db->select($areaQuery);
        
        // Tiempo promedio de completado por área
        $avgTimeQuery = "SELECT area_name, AVG(actual_hours) as avg_hours 
                         FROM project_stages 
                         WHERE status = 'completed' AND actual_hours IS NOT NULL 
                         GROUP BY area_name";
        $avgTimeResults = $db->select($avgTimeQuery);
        
        $avgTimes = [];
        foreach ($avgTimeResults as $row) {
            $avgTimes[$row['area_name']] = round((float) $row['avg_hours'], 1);
        }
        
        return [
            'total_stages' => array_sum($statusCounts),
            'status_breakdown' => $statusCounts,
            'area_breakdown' => $areaResults,
            'average_completion_times' => $avgTimes,
            'overdue_stages' => static::overdue()->count(),
            'pending_stages' => static::pending()->count(),
            'in_progress_stages' => static::inProgress()->count()
        ];
    }
    
    /**
     * Reasignar etapas de un revisor a otro
     */
    public static function reassignFromReviewer(int $fromAdminId, int $toAdminId, string $area = null): int 
    {
        $query = static::where('assigned_to', $fromAdminId)
                      ->whereIn('status', ['pending', 'in_progress']);
        
        if ($area) {
            $query = $query->where('area_name', $area);
        }
        
        $stages = $query->get();
        $reassignedCount = 0;
        
        foreach ($stages as $stage) {
            if ($stage->assignReviewer($toAdminId)) {
                $reassignedCount++;
            }
        }
        
        if ($reassignedCount > 0) {
            Logger::info('Etapas reasignadas masivamente', [
                'from_admin' => $fromAdminId,
                'to_admin' => $toAdminId,
                'area' => $area,
                'count' => $reassignedCount
            ]);
        }
        
        return $reassignedCount;
    }
    
    /**
     * Convertir a array para API
     */
    public function toApiArray(): array 
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'area_name' => $this->area_name,
            'stage_name' => $this->stage_name,
            'status' => $this->status,
            'order_sequence' => $this->order_sequence,
            'completion_percentage' => $this->completion_percentage,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'due_date' => $this->due_date,
            'reviewer_notes' => $this->reviewer_notes,
            'required_documents' => $this->getRequiredDocuments(),
            'assigned_admin' => $this->assignedAdmin() ? $this->assignedAdmin()->toApiArray() : null,
            'is_overdue' => $this->isOverdue(),
            'days_overdue' => $this->getDaysOverdue(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}