<?php
/**
 * Modelo de Administradores y Revisores
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Models;

use UC\ApprovalSystem\Utils\Logger;

class Admin extends BaseModel 
{
    protected $table = 'admins';
    
    protected $fillable = [
        'email',
        'name',
        'role',
        'areas',
        'permissions',
        'status',
        'notification_preferences'
    ];
    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
    
    /**
     * Buscar administrador por email
     */
    public static function findByEmail(string $email): ?self 
    {
        return static::where('email', $email)->where('status', 'active')->first();
    }
    
    /**
     * Crear administrador desde datos CAS
     */
    public static function createFromCAS(array $casData, string $role = 'reviewer', array $areas = []): ?self 
    {
        $adminData = [
            'email' => $casData['email'] ?? '',
            'name' => $casData['name'] ?? '',
            'role' => $role,
            'areas' => json_encode($areas),
            'permissions' => json_encode([]),
            'status' => 'active',
            'notification_preferences' => json_encode([
                'email_notifications' => true,
                'project_assigned' => true,
                'document_uploaded' => true,
                'reminder_pending' => true,
                'weekly_summary' => true
            ])
        ];
        
        $admin = static::create($adminData);
        
        if ($admin) {
            Logger::info('Administrador creado desde CAS', [
                'admin_id' => $admin->id,
                'email' => $admin->email,
                'role' => $admin->role,
                'areas' => $areas
            ]);
        }
        
        return $admin;
    }
    
    /**
     * Verificar si es administrador general
     */
    public function isAdmin(): bool 
    {
        return $this->role === 'admin';
    }
    
    /**
     * Verificar si es supervisor
     */
    public function isSupervisor(): bool 
    {
        return $this->role === 'supervisor';
    }
    
    /**
     * Verificar si es revisor
     */
    public function isReviewer(): bool 
    {
        return $this->role === 'reviewer';
    }
    
    /**
     * Verificar si está activo
     */
    public function isActive(): bool 
    {
        return $this->status === 'active';
    }
    
    /**
     * Obtener áreas asignadas
     */
    public function getAreas(): array 
    {
        $areas = json_decode($this->areas ?? '[]', true);
        return is_array($areas) ? $areas : [];
    }
    
    /**
     * Verificar si tiene acceso a un área específica
     */
    public function hasAreaAccess(string $area): bool 
    {
        if ($this->isAdmin()) {
            return true; // Admin tiene acceso a todas las áreas
        }
        
        $areas = $this->getAreas();
        return in_array($area, $areas) || in_array('all', $areas);
    }
    
    /**
     * Obtener permisos
     */
    public function getPermissions(): array 
    {
        $permissions = json_decode($this->permissions ?? '{}', true);
        return is_array($permissions) ? $permissions : [];
    }
    
    /**
     * Verificar si tiene un permiso específico
     */
    public function hasPermission(string $permission, string $area = null): bool 
    {
        if ($this->isAdmin()) {
            return true; // Admin tiene todos los permisos
        }
        
        $permissions = $this->getPermissions();
        
        // Verificar permiso global
        if (isset($permissions[$permission]) && $permissions[$permission] === true) {
            return true;
        }
        
        // Verificar permiso por área
        if ($area && isset($permissions[$area][$permission]) && $permissions[$area][$permission] === true) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si puede revisar proyectos
     */
    public function canReview(string $area = null): bool 
    {
        if (!$this->isActive()) {
            return false;
        }
        
        if ($area && !$this->hasAreaAccess($area)) {
            return false;
        }
        
        return $this->hasPermission('review', $area);
    }
    
    /**
     * Verificar si puede aprobar proyectos
     */
    public function canApprove(string $area = null): bool 
    {
        if (!$this->isActive()) {
            return false;
        }
        
        if ($area && !$this->hasAreaAccess($area)) {
            return false;
        }
        
        return $this->hasPermission('approve', $area);
    }
    
    /**
     * Verificar si puede rechazar proyectos
     */
    public function canReject(string $area = null): bool 
    {
        if (!$this->isActive()) {
            return false;
        }
        
        if ($area && !$this->hasAreaAccess($area)) {
            return false;
        }
        
        return $this->hasPermission('reject', $area);
    }
    
    /**
     * Asignar áreas al administrador
     */
    public function assignAreas(array $areas): bool 
    {
        $this->areas = json_encode($areas);
        $result = $this->save();
        
        if ($result) {
            Logger::info('Áreas asignadas a administrador', [
                'admin_id' => $this->id,
                'email' => $this->email,
                'areas' => $areas
            ]);
        }
        
        return $result;
    }
    
    /**
     * Asignar permisos al administrador
     */
    public function assignPermissions(array $permissions): bool 
    {
        $this->permissions = json_encode($permissions);
        $result = $this->save();
        
        if ($result) {
            Logger::info('Permisos asignados a administrador', [
                'admin_id' => $this->id,
                'email' => $this->email,
                'permissions' => $permissions
            ]);
        }
        
        return $result;
    }
    
    /**
     * Obtener preferencias de notificación
     */
    public function getNotificationPreferences(): array 
    {
        $preferences = json_decode($this->notification_preferences ?? '{}', true);
        
        // Valores por defecto
        $defaults = [
            'email_notifications' => true,
            'project_assigned' => true,
            'document_uploaded' => true,
            'reminder_pending' => true,
            'weekly_summary' => true,
            'deadline_warning' => true,
            'bulk_actions' => false
        ];
        
        return array_merge($defaults, is_array($preferences) ? $preferences : []);
    }
    
    /**
     * Actualizar preferencias de notificación
     */
    public function updateNotificationPreferences(array $preferences): bool 
    {
        $currentPreferences = $this->getNotificationPreferences();
        $newPreferences = array_merge($currentPreferences, $preferences);
        
        $this->notification_preferences = json_encode($newPreferences);
        return $this->save();
    }
    
    /**
     * Verificar si debe recibir notificación
     */
    public function shouldReceiveNotification(string $type): bool 
    {
        $preferences = $this->getNotificationPreferences();
        
        if (!($preferences['email_notifications'] ?? true)) {
            return false; // Notificaciones deshabilitadas globalmente
        }
        
        return $preferences[$type] ?? false;
    }
    
    /**
     * Obtener proyectos asignados
     */
    public function assignedProjects()
    {
        return ProjectStage::where('assigned_to', $this->id)
                          ->whereIn('status', ['pending', 'in_progress']);
    }
    
    /**
     * Obtener proyectos por área
     */
    public function projectsByArea(string $area)
    {
        return ProjectStage::where('area_name', $area)
                          ->where('assigned_to', $this->id);
    }
    
    /**
     * Obtener estadísticas de trabajo
     */
    public function getWorkloadStats(): array 
    {
        $assignedProjects = $this->assignedProjects()->get();
        
        $stats = [
            'total_assigned' => count($assignedProjects),
            'pending' => 0,
            'in_progress' => 0,
            'overdue' => 0,
            'by_area' => [],
            'avg_completion_time' => null,
            'total_completed_this_month' => 0
        ];
        
        foreach ($assignedProjects as $stage) {
            $stats[$stage->status]++;
            
            // Contar por área
            if (!isset($stats['by_area'][$stage->area_name])) {
                $stats['by_area'][$stage->area_name] = 0;
            }
            $stats['by_area'][$stage->area_name]++;
            
            // Verificar si está atrasado
            if ($stage->due_date && strtotime($stage->due_date) < time() && $stage->status !== 'completed') {
                $stats['overdue']++;
            }
        }
        
        // Obtener estadísticas adicionales
        $stats['avg_completion_time'] = $this->getAverageCompletionTime();
        $stats['total_completed_this_month'] = $this->getCompletedThisMonth();
        
        return $stats;
    }
    
    /**
     * Obtener tiempo promedio de completado
     */
    private function getAverageCompletionTime(): ?float 
    {
        $query = "SELECT AVG(DATEDIFF(end_date, start_date)) as avg_days 
                  FROM project_stages 
                  WHERE assigned_to = ? 
                  AND status = 'completed' 
                  AND start_date IS NOT NULL 
                  AND end_date IS NOT NULL";
        
        $result = $this->db->selectOne($query, [$this->id]);
        return $result['avg_days'] ? round((float) $result['avg_days'], 1) : null;
    }
    
    /**
     * Obtener proyectos completados este mes
     */
    private function getCompletedThisMonth(): int 
    {
        $startOfMonth = date('Y-m-01 00:00:00');
        $endOfMonth = date('Y-m-t 23:59:59');
        
        $query = "SELECT COUNT(*) as count 
                  FROM project_stages 
                  WHERE assigned_to = ? 
                  AND status = 'completed' 
                  AND end_date BETWEEN ? AND ?";
        
        $result = $this->db->selectOne($query, [$this->id, $startOfMonth, $endOfMonth]);
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Buscar administradores por rol
     */
    public static function findByRole(string $role)
    {
        return static::where('role', $role)->where('status', 'active');
    }
    
    /**
     * Buscar revisores por área
     */
    public static function findReviewersByArea(string $area)
    {
        return static::where('role', 'reviewer')
                    ->where('status', 'active')
                    ->get()
                    ->filter(function($admin) use ($area) {
                        return $admin->hasAreaAccess($area);
                    });
    }
    
    /**
     * Obtener administrador con menor carga de trabajo por área
     */
    public static function getLeastBusyReviewer(string $area): ?self 
    {
        $reviewers = static::findReviewersByArea($area);
        
        if (empty($reviewers)) {
            return null;
        }
        
        $leastBusy = null;
        $minWorkload = PHP_INT_MAX;
        
        foreach ($reviewers as $reviewer) {
            $workload = $reviewer->assignedProjects()->count();
            
            if ($workload < $minWorkload) {
                $minWorkload = $workload;
                $leastBusy = $reviewer;
            }
        }
        
        return $leastBusy;
    }
    
    /**
     * Obtener todos los administradores activos
     */
    public static function active()
    {
        return static::where('status', 'active');
    }
    
    /**
     * Obtener estadísticas generales de administradores
     */
    public static function getGeneralStats(): array 
    {
        $db = Database::getInstance();
        
        // Contar por rol
        $roleQuery = "SELECT role, COUNT(*) as count FROM admins WHERE status = 'active' GROUP BY role";
        $roleResults = $db->select($roleQuery);
        
        $roleCounts = [];
        foreach ($roleResults as $row) {
            $roleCounts[$row['role']] = (int) $row['count'];
        }
        
        // Carga de trabajo por área
        $workloadQuery = "SELECT 
                            a.email,
                            a.name,
                            JSON_UNQUOTE(JSON_EXTRACT(a.areas, '$[0]')) as primary_area,
                            COUNT(ps.id) as assigned_projects,
                            COUNT(CASE WHEN ps.status = 'in_progress' THEN 1 END) as active_projects
                          FROM admins a
                          LEFT JOIN project_stages ps ON ps.assigned_to = a.id AND ps.status IN ('pending', 'in_progress')
                          WHERE a.role = 'reviewer' AND a.status = 'active'
                          GROUP BY a.id, a.email, a.name";
        
        $workloadResults = $db->select($workloadQuery);
        
        return [
            'total_admins' => array_sum($roleCounts),
            'role_breakdown' => $roleCounts,
            'reviewer_workload' => $workloadResults,
            'areas_coverage' => static::getAreasCoverage(),
            'notification_preferences' => static::getNotificationStats()
        ];
    }
    
    /**
     * Obtener cobertura por áreas
     */
    private static function getAreasCoverage(): array 
    {
        $db = Database::getInstance();
        
        $query = "SELECT areas FROM admins WHERE status = 'active' AND role IN ('reviewer', 'supervisor')";
        $results = $db->select($query);
        
        $coverage = [];
        $appConfig = include __DIR__ . '/../../config/app.php';
        $allAreas = array_keys($appConfig['areas']);
        
        foreach ($allAreas as $area) {
            $coverage[$area] = 0;
        }
        
        foreach ($results as $row) {
            $areas = json_decode($row['areas'] ?? '[]', true);
            if (is_array($areas)) {
                foreach ($areas as $area) {
                    if ($area === 'all') {
                        foreach ($allAreas as $areaName) {
                            $coverage[$areaName]++;
                        }
                    } elseif (isset($coverage[$area])) {
                        $coverage[$area]++;
                    }
                }
            }
        }
        
        return $coverage;
    }
    
    /**
     * Obtener estadísticas de notificaciones
     */
    private static function getNotificationStats(): array 
    {
        $db = Database::getInstance();
        
        $query = "SELECT notification_preferences FROM admins WHERE status = 'active'";
        $results = $db->select($query);
        
        $stats = [
            'email_notifications_enabled' => 0,
            'project_assigned_enabled' => 0,
            'document_uploaded_enabled' => 0,
            'reminder_pending_enabled' => 0,
            'weekly_summary_enabled' => 0
        ];
        
        $total = count($results);
        
        foreach ($results as $row) {
            $preferences = json_decode($row['notification_preferences'] ?? '{}', true);
            
            if (($preferences['email_notifications'] ?? true)) {
                $stats['email_notifications_enabled']++;
            }
            if (($preferences['project_assigned'] ?? true)) {
                $stats['project_assigned_enabled']++;
            }
            if (($preferences['document_uploaded'] ?? true)) {
                $stats['document_uploaded_enabled']++;
            }
            if (($preferences['reminder_pending'] ?? true)) {
                $stats['reminder_pending_enabled']++;
            }
            if (($preferences['weekly_summary'] ?? true)) {
                $stats['weekly_summary_enabled']++;
            }
        }
        
        // Convertir a porcentajes
        foreach ($stats as $key => $count) {
            $stats[$key] = $total > 0 ? round(($count / $total) * 100, 1) : 0;
        }
        
        return $stats;
    }
    
    /**
     * Activar administrador
     */
    public function activate(): bool 
    {
        $this->status = 'active';
        $result = $this->save();
        
        if ($result) {
            Logger::info('Administrador activado', [
                'admin_id' => $this->id,
                'email' => $this->email,
                'role' => $this->role
            ]);
        }
        
        return $result;
    }
    
    /**
     * Desactivar administrador
     */
    public function deactivate(): bool 
    {
        $this->status = 'inactive';
        $result = $this->save();
        
        if ($result) {
            Logger::info('Administrador desactivado', [
                'admin_id' => $this->id,
                'email' => $this->email,
                'role' => $this->role
            ]);
            
            // Reasignar proyectos pendientes
            $this->reassignPendingProjects();
        }
        
        return $result;
    }
    
    /**
     * Reasignar proyectos pendientes a otros revisores
     */
    private function reassignPendingProjects(): void 
    {
        $pendingStages = ProjectStage::where('assigned_to', $this->id)
                                   ->whereIn('status', ['pending', 'in_progress'])
                                   ->get();
        
        foreach ($pendingStages as $stage) {
            $newReviewer = static::getLeastBusyReviewer($stage->area_name);
            
            if ($newReviewer) {
                $stage->assigned_to = $newReviewer->id;
                $stage->save();
                
                Logger::info('Proyecto reasignado automáticamente', [
                    'project_stage_id' => $stage->id,
                    'from_admin' => $this->id,
                    'to_admin' => $newReviewer->id,
                    'area' => $stage->area_name
                ]);
            }
        }
    }
    
    /**
     * Cambiar rol del administrador
     */
    public function changeRole(string $newRole): bool 
    {
        $validRoles = ['admin', 'supervisor', 'reviewer'];
        
        if (!in_array($newRole, $validRoles)) {
            return false;
        }
        
        $oldRole = $this->role;
        $this->role = $newRole;
        
        // Si se degrada de admin, quitar acceso a todas las áreas
        if ($oldRole === 'admin' && $newRole !== 'admin') {
            $this->areas = json_encode([]);
            $this->permissions = json_encode([]);
        }
        
        $result = $this->save();
        
        if ($result) {
            Logger::info('Rol de administrador cambiado', [
                'admin_id' => $this->id,
                'email' => $this->email,
                'old_role' => $oldRole,
                'new_role' => $newRole
            ]);
        }
        
        return $result;
    }
    
    /**
     * Obtener historial de actividad
     */
    public function getActivityHistory(int $days = 30): array 
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $query = "SELECT 
                    'project_stage' as type,
                    ps.id as entity_id,
                    p.project_code,
                    p.title as project_title,
                    ps.area_name,
                    ps.status,
                    ps.updated_at as activity_date
                  FROM project_stages ps
                  JOIN projects p ON ps.project_id = p.id
                  WHERE ps.assigned_to = ? 
                  AND ps.updated_at >= ?
                  
                  UNION ALL
                  
                  SELECT 
                    'feedback' as type,
                    pf.id as entity_id,
                    p.project_code,
                    p.title as project_title,
                    '' as area_name,
                    pf.feedback_type as status,
                    pf.created_at as activity_date
                  FROM project_feedback pf
                  JOIN projects p ON pf.project_id = p.id
                  WHERE pf.admin_id = ?
                  AND pf.created_at >= ?
                  
                  ORDER BY activity_date DESC
                  LIMIT 50";
        
        return $this->db->select($query, [$this->id, $startDate, $this->id, $startDate]);
    }
    
    /**
     * Verificar si el administrador puede realizar una acción específica
     */
    public function can(string $action, string $area = null, $resource = null): bool 
    {
        // Administrador general puede hacer todo
        if ($this->isAdmin()) {
            return true;
        }
        
        // Verificar si está activo
        if (!$this->isActive()) {
            return false;
        }
        
        // Verificar acceso al área
        if ($area && !$this->hasAreaAccess($area)) {
            return false;
        }
        
        // Verificar acciones específicas
        switch ($action) {
            case 'review':
                return $this->canReview($area);
            case 'approve':
                return $this->canApprove($area);
            case 'reject':
                return $this->canReject($area);
            case 'assign':
                return $this->isSupervisor() || $this->isAdmin();
            case 'manage_users':
                return $this->isAdmin();
            case 'view_reports':
                return $this->isSupervisor() || $this->isAdmin();
            case 'bulk_actions':
                return $this->isSupervisor() || $this->isAdmin();
            default:
                return false;
        }
    }
    
    /**
     * Convertir a array para API
     */
    public function toApiArray(): array 
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
            'areas' => $this->getAreas(),
            'status' => $this->status,
            'workload' => $this->assignedProjects()->count(),
            'permissions' => $this->getPermissions(),
            'notification_preferences' => $this->getNotificationPreferences(),
            'created_at' => $this->created_at
        ];
    }
    
    /**
     * Buscar administradores disponibles para asignación
     */
    public static function getAvailableForAssignment(string $area): array 
    {
        $reviewers = static::findReviewersByArea($area);
        $available = [];
        
        foreach ($reviewers as $reviewer) {
            $workload = $reviewer->getWorkloadStats();
            
            // Considerar disponible si tiene menos de 10 proyectos asignados
            if ($workload['total_assigned'] < 10) {
                $available[] = [
                    'admin' => $reviewer,
                    'workload' => $workload['total_assigned'],
                    'areas' => $reviewer->getAreas()
                ];
            }
        }
        
        // Ordenar por menor carga de trabajo
        usort($available, function($a, $b) {
            return $a['workload'] <=> $b['workload'];
        });
        
        return $available;
    }
    
    /**
     * Notificar al administrador
     */
    public function notify(string $type, array $data): bool 
    {
        if (!$this->shouldReceiveNotification($type)) {
            return false;
        }
        
        // Aquí se integraría con el servicio de notificaciones
        Logger::info('Notificación enviada a administrador', [
            'admin_id' => $this->id,
            'email' => $this->email,
            'notification_type' => $type,
            'data' => $data
        ]);
        
        return true;
    }
}