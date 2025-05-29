<?php
/**
 * Modelo de Usuarios (Clientes)
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Models;

use UC\ApprovalSystem\Utils\Logger;

class User extends BaseModel 
{
    protected $table = 'users';
    
    protected $fillable = [
        'email',
        'name',
        'first_name',
        'last_name',
        'department',
        'title',
        'phone',
        'employee_id',
        'student_id',
        'status'
    ];
    
    protected $guarded = [
        'id',
        'email_verified',
        'email_verified_at',
        'last_login',
        'login_count',
        'created_at',
        'updated_at'
    ];
    
    /**
     * Buscar usuario por email
     */
    public static function findByEmail(string $email): ?self 
    {
        return static::where('email', $email)->first();
    }
    
    /**
     * Crear usuario desde datos CAS
     */
    public static function createFromCAS(array $casData): ?self 
    {
        $userData = [
            'email' => $casData['email'] ?? '',
            'name' => $casData['name'] ?? '',
            'first_name' => $casData['first_name'] ?? '',
            'last_name' => $casData['last_name'] ?? '',
            'department' => $casData['department'] ?? '',
            'title' => $casData['title'] ?? '',
            'phone' => $casData['phone'] ?? '',
            'employee_id' => $casData['employee_id'] ?? '',
            'student_id' => $casData['student_id'] ?? '',
            'status' => 'active'
        ];
        
        $user = static::create($userData);
        
        if ($user) {
            Logger::info('Usuario creado desde CAS', [
                'user_id' => $user->id,
                'email' => $user->email,
                'department' => $user->department
            ]);
        }
        
        return $user;
    }
    
    /**
     * Actualizar usuario desde datos CAS
     */
    public function updateFromCAS(array $casData): bool 
    {
        $updateData = [
            'name' => $casData['name'] ?? $this->name,
            'first_name' => $casData['first_name'] ?? $this->first_name,
            'last_name' => $casData['last_name'] ?? $this->last_name,
            'department' => $casData['department'] ?? $this->department,
            'title' => $casData['title'] ?? $this->title,
            'phone' => $casData['phone'] ?? $this->phone,
            'employee_id' => $casData['employee_id'] ?? $this->employee_id,
            'student_id' => $casData['student_id'] ?? $this->student_id
        ];
        
        // Solo actualizar si hay cambios
        $hasChanges = false;
        foreach ($updateData as $key => $value) {
            if ($this->getAttribute($key) !== $value) {
                $hasChanges = true;
                break;
            }
        }
        
        if ($hasChanges) {
            $result = $this->update($updateData);
            
            if ($result) {
                Logger::info('Usuario actualizado desde CAS', [
                    'user_id' => $this->id,
                    'email' => $this->email,
                    'changes' => $updateData
                ]);
            }
            
            return $result;
        }
        
        return true; // No hay cambios, consideramos exitoso
    }
    
    /**
     * Registrar login del usuario
     */
    public function recordLogin(): void 
    {
        $this->last_login = date('Y-m-d H:i:s');
        $this->login_count = ($this->login_count ?? 0) + 1;
        $this->save();
        
        Logger::auth('Usuario logueado', [
            'user_id' => $this->id,
            'email' => $this->email,
            'login_count' => $this->login_count,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    
    /**
     * Verificar si el usuario está activo
     */
    public function isActive(): bool 
    {
        return $this->status === 'active';
    }
    
    /**
     * Verificar si el usuario está bloqueado
     */
    public function isBlocked(): bool 
    {
        return $this->status === 'blocked';
    }
    
    /**
     * Activar usuario
     */
    public function activate(): bool 
    {
        $this->status = 'active';
        $result = $this->save();
        
        if ($result) {
            Logger::info('Usuario activado', [
                'user_id' => $this->id,
                'email' => $this->email
            ]);
        }
        
        return $result;
    }
    
    /**
     * Desactivar usuario
     */
    public function deactivate(): bool 
    {
        $this->status = 'inactive';
        $result = $this->save();
        
        if ($result) {
            Logger::info('Usuario desactivado', [
                'user_id' => $this->id,
                'email' => $this->email
            ]);
        }
        
        return $result;
    }
    
    /**
     * Bloquear usuario
     */
    public function block(): bool 
    {
        $this->status = 'blocked';
        $result = $this->save();
        
        if ($result) {
            Logger::warning('Usuario bloqueado', [
                'user_id' => $this->id,
                'email' => $this->email
            ]);
        }
        
        return $result;
    }
    
    /**
     * Verificar email del usuario
     */
    public function verifyEmail(): bool 
    {
        $this->email_verified = true;
        $this->email_verified_at = date('Y-m-d H:i:s');
        $result = $this->save();
        
        if ($result) {
            Logger::info('Email verificado', [
                'user_id' => $this->id,
                'email' => $this->email
            ]);
        }
        
        return $result;
    }
    
    /**
     * Obtener nombre completo del usuario
     */
    public function getFullName(): string 
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }
        
        return $this->name ?? $this->email;
    }
    
    /**
     * Obtener proyectos del usuario
     */
    public function projects()
    {
        return Project::where('user_id', $this->id)->orderBy('created_at', 'desc');
    }
    
    /**
     * Obtener proyectos activos
     */
    public function activeProjects()
    {
        return $this->projects()->whereIn('status', ['submitted', 'in_review']);
    }
    
    /**
     * Obtener proyectos completados
     */
    public function completedProjects()
    {
        return $this->projects()->where('status', 'approved');
    }
    
    /**
     * Contar proyectos por estado
     */
    public function getProjectsCount(): array 
    {
        $projects = $this->projects()->get();
        
        $counts = [
            'total' => count($projects),
            'draft' => 0,
            'submitted' => 0,
            'in_review' => 0,
            'approved' => 0,
            'rejected' => 0,
            'on_hold' => 0
        ];
        
        foreach ($projects as $project) {
            $counts[$project->status] = ($counts[$project->status] ?? 0) + 1;
        }
        
        return $counts;
    }
    
    /**
     * Obtener estadísticas del usuario
     */
    public function getStats(): array 
    {
        $projectCounts = $this->getProjectsCount();
        
        return [
            'user_info' => [
                'id' => $this->id,
                'email' => $this->email,
                'name' => $this->getFullName(),
                'department' => $this->department,
                'title' => $this->title,
                'status' => $this->status,
                'email_verified' => (bool) $this->email_verified,
                'last_login' => $this->last_login,
                'login_count' => $this->login_count ?? 0,
                'member_since' => $this->created_at
            ],
            'projects' => $projectCounts,
            'activity' => [
                'total_documents_uploaded' => $this->getTotalDocumentsUploaded(),
                'avg_project_completion_days' => $this->getAverageProjectCompletionDays(),
                'most_used_priority' => $this->getMostUsedPriority()
            ]
        ];
    }
    
    /**
     * Obtener total de documentos subidos
     */
    private function getTotalDocumentsUploaded(): int 
    {
        $query = "SELECT COUNT(*) as count FROM documents d 
                  JOIN projects p ON d.project_id = p.id 
                  WHERE p.user_id = ?";
        
        $result = $this->db->selectOne($query, [$this->id]);
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Obtener promedio de días para completar proyectos
     */
    private function getAverageProjectCompletionDays(): ?float 
    {
        $query = "SELECT AVG(DATEDIFF(actual_completion_date, created_at)) as avg_days 
                  FROM projects 
                  WHERE user_id = ? AND status = 'approved' AND actual_completion_date IS NOT NULL";
        
        $result = $this->db->selectOne($query, [$this->id]);
        return $result['avg_days'] ? round((float) $result['avg_days'], 1) : null;
    }
    
    /**
     * Obtener prioridad más utilizada
     */
    private function getMostUsedPriority(): ?string 
    {
        $query = "SELECT priority, COUNT(*) as count 
                  FROM projects 
                  WHERE user_id = ? 
                  GROUP BY priority 
                  ORDER BY count DESC 
                  LIMIT 1";
        
        $result = $this->db->selectOne($query, [$this->id]);
        return $result['priority'] ?? null;
    }
    
    /**
     * Buscar usuarios por departamento
     */
    public static function findByDepartment(string $department)
    {
        return static::where('department', $department)->where('status', 'active');
    }
    
    /**
     * Buscar usuarios activos
     */
    public static function active()
    {
        return static::where('status', 'active');
    }
    
    /**
     * Buscar usuarios bloqueados
     */
    public static function blocked()
    {
        return static::where('status', 'blocked');
    }
    
    /**
     * Buscar usuarios con email verificado
     */
    public static function emailVerified()
    {
        return static::where('email_verified', true);
    }
    
    /**
     * Buscar usuarios que se han logueado recientemente
     */
    public static function recentlyActive(int $days = 30)
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return static::where('last_login', '>=', $date);
    }
    
    /**
     * Obtener usuarios más activos
     */
    public static function mostActive(int $limit = 10): array 
    {
        $query = "SELECT u.*, COUNT(p.id) as project_count 
                  FROM users u 
                  LEFT JOIN projects p ON u.id = p.user_id 
                  WHERE u.status = 'active' 
                  GROUP BY u.id 
                  ORDER BY project_count DESC, u.login_count DESC 
                  LIMIT ?";
        
        $db = Database::getInstance();
        $results = $db->select($query, [$limit]);
        
        return array_map(function($row) {
            $user = new static($row);
            $user->exists = true;
            $user->syncOriginal();
            return $user;
        }, $results);
    }
    
    /**
     * Obtener estadísticas generales de usuarios
     */
    public static function getGeneralStats(): array 
    {
        $db = Database::getInstance();
        
        // Contar usuarios por estado
        $statusQuery = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
        $statusResults = $db->select($statusQuery);
        
        $statusCounts = [];
        foreach ($statusResults as $row) {
            $statusCounts[$row['status']] = (int) $row['count'];
        }
        
        // Usuarios registrados por mes (últimos 12 meses)
        $monthlyQuery = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                         FROM users 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                         ORDER BY month";
        $monthlyResults = $db->select($monthlyQuery);
        
        // Top departamentos
        $departmentQuery = "SELECT department, COUNT(*) as count 
                           FROM users 
                           WHERE department IS NOT NULL AND department != '' 
                           GROUP BY department 
                           ORDER BY count DESC 
                           LIMIT 10";
        $departmentResults = $db->select($departmentQuery);
        
        return [
            'total_users' => array_sum($statusCounts),
            'status_breakdown' => $statusCounts,
            'monthly_registrations' => $monthlyResults,
            'top_departments' => $departmentResults,
            'email_verification_rate' => static::getEmailVerificationRate(),
            'average_login_count' => static::getAverageLoginCount()
        ];
    }
    
    /**
     * Obtener tasa de verificación de email
     */
    private static function getEmailVerificationRate(): float 
    {
        $db = Database::getInstance();
        
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified
                  FROM users 
                  WHERE status = 'active'";
        
        $result = $db->selectOne($query);
        
        if ($result['total'] > 0) {
            return round(($result['verified'] / $result['total']) * 100, 1);
        }
        
        return 0.0;
    }
    
    /**
     * Obtener promedio de logins
     */
    private static function getAverageLoginCount(): float 
    {
        $db = Database::getInstance();
        
        $query = "SELECT AVG(login_count) as avg_logins FROM users WHERE status = 'active'";
        $result = $db->selectOne($query);
        
        return round((float) ($result['avg_logins'] ?? 0), 1);
    }
    
    /**
     * Limpiar usuarios inactivos antiguos
     */
    public static function cleanInactiveUsers(int $daysInactive = 365): int 
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysInactive} days"));
        
        $db = Database::getInstance();
        
        // Encontrar usuarios inactivos sin proyectos
        $query = "SELECT u.id FROM users u 
                  LEFT JOIN projects p ON u.id = p.user_id 
                  WHERE u.status = 'inactive' 
                  AND (u.last_login IS NULL OR u.last_login < ?) 
                  AND p.id IS NULL";
        
        $inactiveUsers = $db->select($query, [$cutoffDate]);
        
        $deletedCount = 0;
        foreach ($inactiveUsers as $userData) {
            $user = static::find($userData['id']);
            if ($user && $user->delete()) {
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            Logger::info('Usuarios inactivos eliminados', [
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
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->getFullName(),
            'department' => $this->department,
            'title' => $this->title,
            'status' => $this->status,
            'email_verified' => (bool) $this->email_verified,
            'last_login' => $this->last_login,
            'member_since' => $this->created_at,
            'projects_count' => $this->projects()->count()
        ];
    }
}