<?php

namespace UC\ApprovalSystem\Controllers;

use UC\ApprovalSystem\Controllers\BaseController;
use UC\ApprovalSystem\Models\User;
use UC\ApprovalSystem\Models\Admin;
use UC\ApprovalSystem\Models\Project;
use UC\ApprovalSystem\Models\ProjectStage;
use UC\ApprovalSystem\Models\Document;
use UC\ApprovalSystem\Models\DocumentTemplate;
use UC\ApprovalSystem\Models\ProjectFeedback;
use UC\ApprovalSystem\Services\EmailService;
use UC\ApprovalSystem\Services\FileService;
use UC\ApprovalSystem\Services\NotificationService;
use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Validator;
use UC\ApprovalSystem\Utils\Helper;

/**
 * Controlador de Administración
 * Maneja todas las funciones administrativas del sistema
 */
class AdminController extends BaseController
{
    private $emailService;
    private $fileService;
    private $notificationService;
    private $logger;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->requireAdminAuth();
        
        $this->emailService = new EmailService();
        $this->fileService = new FileService();
        $this->notificationService = new NotificationService();
        $this->logger = new Logger();
    }

    /**
     * Dashboard principal de administración
     */
    public function dashboard()
    {
        try {
            $stats = $this->getDashboardStats();
            $recentActivity = $this->getRecentActivity();
            $systemHealth = $this->getSystemHealth();
            $alerts = $this->getSystemAlerts();

            $this->render('admin/dashboard', [
                'title' => 'Panel de Administración',
                'stats' => $stats,
                'recent_activity' => $recentActivity,
                'system_health' => $systemHealth,
                'alerts' => $alerts
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error en dashboard admin', ['error' => $e->getMessage()]);
            $this->handleError('Error al cargar el dashboard');
        }
    }

    /**
     * Gestión de usuarios
     */
    public function users()
    {
        try {
            $page = (int)($this->getParam('page') ?? 1);
            $search = $this->getParam('search');
            $role = $this->getParam('role');
            $area = $this->getParam('area');

            $filters = array_filter([
                'search' => $search,
                'role' => $role,
                'area' => $area
            ]);

            $users = User::paginate($page, 20, $filters);
            $totalUsers = User::count($filters);
            $areas = Helper::getAreas();
            $roles = ['admin', 'area_admin', 'reviewer', 'client'];

            $this->render('admin/users', [
                'title' => 'Gestión de Usuarios',
                'users' => $users,
                'total_users' => $totalUsers,
                'current_page' => $page,
                'total_pages' => ceil($totalUsers / 20),
                'filters' => $filters,
                'areas' => $areas,
                'roles' => $roles
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error en gestión de usuarios', ['error' => $e->getMessage()]);
            $this->handleError('Error al cargar usuarios');
        }
    }

    /**
     * Crear/Editar usuario
     */
    public function userForm()
    {
        $userId = $this->getParam('id');
        $user = $userId ? User::findById($userId) : null;

        if ($this->isPost()) {
            return $this->handleUserSave($user);
        }

        $areas = Helper::getAreas();
        $roles = ['admin', 'area_admin', 'reviewer', 'client'];

        $this->render('admin/user-form', [
            'title' => $user ? 'Editar Usuario' : 'Nuevo Usuario',
            'user' => $user,
            'areas' => $areas,
            'roles' => $roles
        ]);
    }

    /**
     * Eliminar usuario
     */
    public function deleteUser()
    {
        $this->requirePost();
        
        try {
            $userId = $this->getParam('id');
            $user = User::findById($userId);

            if (!$user) {
                $this->jsonResponse(['success' => false, 'message' => 'Usuario no encontrado']);
                return;
            }

            // No permitir eliminar el último admin
            if ($user->role === 'admin' && User::countByRole('admin') <= 1) {
                $this->jsonResponse(['success' => false, 'message' => 'No se puede eliminar el último administrador']);
                return;
            }

            $user->delete();
            
            $this->logger->info('Usuario eliminado', [
                'admin_id' => $this->getCurrentUser()->id,
                'deleted_user_id' => $userId,
                'deleted_user_email' => $user->email
            ]);

            $this->jsonResponse(['success' => true, 'message' => 'Usuario eliminado correctamente']);

        } catch (Exception $e) {
            $this->logger->error('Error eliminando usuario', ['error' => $e->getMessage()]);
            $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar usuario']);
        }
    }

    /**
     * Gestión de proyectos
     */
    public function projects()
    {
        try {
            $page = (int)($this->getParam('page') ?? 1);
            $status = $this->getParam('status');
            $area = $this->getParam('area');
            $search = $this->getParam('search');

            $filters = array_filter([
                'status' => $status,
                'area' => $area,
                'search' => $search
            ]);

            $projects = Project::paginate($page, 20, $filters);
            $totalProjects = Project::count($filters);
            $areas = Helper::getAreas();
            $statuses = ['draft', 'in_progress', 'under_review', 'approved', 'rejected', 'cancelled'];

            $projectStats = $this->getProjectStats();

            $this->render('admin/projects', [
                'title' => 'Gestión de Proyectos',
                'projects' => $projects,
                'total_projects' => $totalProjects,
                'current_page' => $page,
                'total_pages' => ceil($totalProjects / 20),
                'filters' => $filters,
                'areas' => $areas,
                'statuses' => $statuses,
                'project_stats' => $projectStats
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error en gestión de proyectos', ['error' => $e->getMessage()]);
            $this->handleError('Error al cargar proyectos');
        }
    }

    /**
     * Detalle de proyecto para admin
     */
    public function projectDetail()
    {
        try {
            $projectId = $this->getParam('id');
            $project = Project::findById($projectId);

            if (!$project) {
                $this->handleError('Proyecto no encontrado', 404);
                return;
            }

            $stages = ProjectStage::getByProject($projectId);
            $documents = Document::getByProject($projectId);
            $feedback = ProjectFeedback::getByProject($projectId);
            $timeline = $this->getProjectTimeline($projectId);

            $this->render('admin/project-detail', [
                'title' => "Proyecto: {$project->name}",
                'project' => $project,
                'stages' => $stages,
                'documents' => $documents,
                'feedback' => $feedback,
                'timeline' => $timeline
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error en detalle de proyecto', ['error' => $e->getMessage()]);
            $this->handleError('Error al cargar proyecto');
        }
    }

    /**
     * Forzar cambio de estado de proyecto
     */
    public function forceProjectStatus()
    {
        $this->requirePost();
        
        try {
            $projectId = $this->getParam('project_id');
            $newStatus = $this->getParam('status');
            $reason = $this->getParam('reason');

            $project = Project::findById($projectId);
            if (!$project) {
                $this->jsonResponse(['success' => false, 'message' => 'Proyecto no encontrado']);
                return;
            }

            $oldStatus = $project->status;
            $project->status = $newStatus;
            $project->updated_at = date('Y-m-d H:i:s');
            $project->save();

            // Registrar el cambio forzado
            $this->logger->warning('Estado de proyecto forzado por admin', [
                'admin_id' => $this->getCurrentUser()->id,
                'project_id' => $projectId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason
            ]);

            // Notificar al propietario del proyecto
            $this->notificationService->notifyProjectStatusForced(
                $project,
                $oldStatus,
                $newStatus,
                $reason,
                $this->getCurrentUser()
            );

            $this->jsonResponse(['success' => true, 'message' => 'Estado actualizado correctamente']);

        } catch (Exception $e) {
            $this->logger->error('Error forzando estado de proyecto', ['error' => $e->getMessage()]);
            $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar estado']);
        }
    }

    /**
     * Gestión de plantillas de documentos
     */
    public function documentTemplates()
    {
        try {
            $area = $this->getParam('area');
            $areas = Helper::getAreas();
            
            $templates = DocumentTemplate::getAll($area ? ['area' => $area] : []);
            $templateStats = DocumentTemplate::getUsageStats();

            $this->render('admin/document-templates', [
                'title' => 'Plantillas de Documentos',
                'templates' => $templates,
                'template_stats' => $templateStats,
                'areas' => $areas,
                'current_area' => $area
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error en plantillas de documentos', ['error' => $e->getMessage()]);
            $this->handleError('Error al cargar plantillas');
        }
    }

    /**
     * Crear/Editar plantilla de documento
     */
    public function documentTemplateForm()
    {
        $templateId = $this->getParam('id');
        $template = $templateId ? DocumentTemplate::findById($templateId) : null;

        if ($this->isPost()) {
            return $this->handleTemplateeSave($template);
        }

        $areas = Helper::getAreas();

        $this->render('admin/document-template-form', [
            'title' => $template ? 'Editar Plantilla' : 'Nueva Plantilla',
            'template' => $template,
            'areas' => $areas
        ]);
    }

    /**
     * Eliminar plantilla de documento
     */
    public function deleteDocumentTemplate()
    {
        $this->requirePost();
        
        try {
            $templateId = $this->getParam('id');
            $template = DocumentTemplate::findById($templateId);

            if (!$template) {
                $this->jsonResponse(['success' => false, 'message' => 'Plantilla no encontrada']);
                return;
            }

            // Verificar si está en uso
            $usageCount = Document::countByTemplate($templateId);
            if ($usageCount > 0) {
                $this->jsonResponse([
                    'success' => false, 
                    'message' => "No se puede eliminar. La plantilla está siendo usada en {$usageCount} documentos"
                ]);
                return;
            }

            $template->delete();
            
            $this->logger->info('Plantilla eliminada', [
                'admin_id' => $this->getCurrentUser()->id,
                'template_id' => $templateId,
                'template_name' => $template->name
            ]);

            $this->jsonResponse(['success' => true, 'message' => 'Plantilla eliminada correctamente']);

        } catch (Exception $e) {
            $this->logger->error('Error eliminando plantilla', ['error' => $e->getMessage()]);
            $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar plantilla']);
        }
    }

    /**
     * Configuración del sistema
     */
    public function settings()
    {
        if ($this->isPost()) {
            return $this->handleSettingsSave();
        }

        try {
            $settings = $this->getSystemSettings();
            $areas = Helper::getAreas();

            $this->render('admin/settings', [
                'title' => 'Configuración del Sistema',
                'settings' => $settings,
                'areas' => $areas
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error en configuración', ['error' => $e->getMessage()]);
            $this->handleError('Error al cargar configuración');
        }
    }

    /**
     * Reportes del sistema
     */
    public function reports()
    {
        try {
            $reportType = $this->getParam('type') ?? 'overview';
            $dateFrom = $this->getParam('date_from');
            $dateTo = $this->getParam('date_to');
            $area = $this->getParam('area');

            $reportData = $this->generateReport($reportType, [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'area' => $area
            ]);

            $areas = Helper::getAreas();

            $this->render('admin/reports', [
                'title' => 'Reportes del Sistema',
                'report_type' => $reportType,
                'report_data' => $reportData,
                'areas' => $areas,
                'filters' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'area' => $area
                ]
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error en reportes', ['error' => $e->getMessage()]);
            $this->handleError('Error al generar reporte');
        }
    }

    /**
     * Exportar reporte
     */
    public function exportReport()
    {
        try {
            $reportType = $this->getParam('type');
            $format = $this->getParam('format') ?? 'csv';
            $filters = $this->getPostData();

            $reportData = $this->generateReport($reportType, $filters);
            
            $filename = "reporte_{$reportType}_" . date('Y-m-d_H-i-s') . ".{$format}";
            
            switch ($format) {
                case 'csv':
                    $this->exportCSV($reportData, $filename);
                    break;
                case 'excel':
                    $this->exportExcel($reportData, $filename);
                    break;
                default:
                    throw new Exception('Formato no soportado');
            }

        } catch (Exception $e) {
            $this->logger->error('Error exportando reporte', ['error' => $e->getMessage()]);
            $this->jsonResponse(['success' => false, 'message' => 'Error al exportar reporte']);
        }
    }

    /**
     * Limpieza del sistema
     */
    public function systemCleanup()
    {
        $this->requirePost();
        
        try {
            $cleanupType = $this->getParam('type');
            $dryRun = $this->getParam('dry_run') === 'true';

            $results = [];

            switch ($cleanupType) {
                case 'temp_files':
                    $results = $this->fileService->cleanupTempFiles($dryRun);
                    break;
                case 'old_logs':
                    $results = $this->cleanupOldLogs($dryRun);
                    break;
                case 'expired_sessions':
                    $results = $this->cleanupExpiredSessions($dryRun);
                    break;
                case 'orphaned_documents':
                    $results = $this->cleanupOrphanedDocuments($dryRun);
                    break;
                case 'all':
                    $results = $this->performFullCleanup($dryRun);
                    break;
                default:
                    throw new Exception('Tipo de limpieza no válido');
            }

            if (!$dryRun) {
                $this->logger->info('Limpieza del sistema ejecutada', [
                    'admin_id' => $this->getCurrentUser()->id,
                    'cleanup_type' => $cleanupType,
                    'results' => $results
                ]);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => $dryRun ? 'Vista previa de limpieza completada' : 'Limpieza completada',
                'results' => $results
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error en limpieza del sistema', ['error' => $e->getMessage()]);
            $this->jsonResponse(['success' => false, 'message' => 'Error en la limpieza']);
        }
    }

    /**
     * Health check del sistema
     */
    public function healthCheck()
    {
        try {
            $checks = [
                'database' => $this->checkDatabase(),
                'file_system' => $this->checkFileSystem(),
                'email_service' => $this->checkEmailService(),
                'cas_service' => $this->checkCASService(),
                'disk_space' => $this->checkDiskSpace(),
                'memory_usage' => $this->checkMemoryUsage()
            ];

            $overallHealth = array_reduce($checks, function($carry, $check) {
                return $carry && $check['status'] === 'ok';
            }, true);

            $this->jsonResponse([
                'success' => true,
                'overall_health' => $overallHealth ? 'healthy' : 'unhealthy',
                'checks' => $checks,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error en health check', ['error' => $e->getMessage()]);
            $this->jsonResponse([
                'success' => false,
                'overall_health' => 'error',
                'message' => 'Error al verificar el sistema'
            ]);
        }
    }

    // Métodos privados de apoyo

    private function getDashboardStats()
    {
        return [
            'total_users' => User::count(),
            'total_projects' => Project::count(),
            'active_projects' => Project::count(['status' => 'in_progress']),
            'pending_reviews' => Project::count(['status' => 'under_review']),
            'total_documents' => Document::count(),
            'total_feedback' => ProjectFeedback::count(),
            'projects_by_status' => Project::getCountByStatus(),
            'projects_by_area' => Project::getCountByArea(),
            'recent_registrations' => User::count(['created_at' => '>= ' . date('Y-m-d', strtotime('-7 days'))]),
            'avg_approval_time' => Project::getAverageApprovalTime()
        ];
    }

    private function getRecentActivity()
    {
        return [
            'recent_projects' => Project::getRecent(5),
            'recent_documents' => Document::getRecent(5),
            'recent_feedback' => ProjectFeedback::getRecent(10),
            'recent_users' => User::getRecent(5)
        ];
    }

    private function getSystemHealth()
    {
        return [
            'database' => $this->checkDatabase(),
            'file_system' => $this->checkFileSystem(),
            'email_service' => $this->checkEmailService(),
            'disk_space' => $this->checkDiskSpace()
        ];
    }

    private function getSystemAlerts()
    {
        $alerts = [];
        
        // Proyectos con retraso
        $delayedProjects = Project::getDelayed();
        if (count($delayedProjects) > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => count($delayedProjects) . ' proyecto(s) con retraso',
                'action' => '/admin/projects?status=delayed'
            ];
        }

        // Espacio en disco bajo
        $diskUsage = $this->checkDiskSpace();
        if ($diskUsage['usage_percent'] > 85) {
            $alerts[] = [
                'type' => 'error',
                'message' => 'Espacio en disco bajo: ' . $diskUsage['usage_percent'] . '%',
                'action' => '/admin/system-cleanup'
            ];
        }

        // Documentos sin revisar por mucho tiempo
        $oldDocuments = Document::getOldUnreviewed();
        if (count($oldDocuments) > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => count($oldDocuments) . ' documento(s) sin revisar por más de 7 días',
                'action' => '/admin/documents?status=under_review&old=true'
            ];
        }

        return $alerts;
    }

    private function handleUserSave($user)
    {
        try {
            $validator = new Validator($this->getPostData());
            
            $rules = [
                'email' => 'required|email',
                'first_name' => 'required|min:2',
                'last_name' => 'required|min:2',
                'role' => 'required|in:admin,area_admin,reviewer,client'
            ];

            if (!$user) {
                $rules['password'] = 'required|min:8';
            }

            if (!$validator->validate($rules)) {
                $this->jsonResponse(['success' => false, 'errors' => $validator->getErrors()]);
                return;
            }

            $data = $validator->getData();

            if (!$user) {
                $user = new User();
                $user->created_at = date('Y-m-d H:i:s');
            }

            $user->email = $data['email'];
            $user->first_name = $data['first_name'];
            $user->last_name = $data['last_name'];
            $user->role = $data['role'];
            $user->areas = isset($data['areas']) ? json_encode($data['areas']) : null;
            $user->is_active = isset($data['is_active']) ? 1 : 0;
            $user->updated_at = date('Y-m-d H:i:s');

            if (isset($data['password']) && !empty($data['password'])) {
                $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $user->save();

            $this->logger->info('Usuario guardado por admin', [
                'admin_id' => $this->getCurrentUser()->id,
                'user_id' => $user->id,
                'action' => $user->id ? 'update' : 'create'
            ]);

            $this->jsonResponse(['success' => true, 'message' => 'Usuario guardado correctamente']);

        } catch (Exception $e) {
            $this->logger->error('Error guardando usuario', ['error' => $e->getMessage()]);
            $this->jsonResponse(['success' => false, 'message' => 'Error al guardar usuario']);
        }
    }

    private function handleTemplateSave($template)
    {
        try {
            $validator = new Validator($this->getPostData());
            
            $rules = [
                'name' => 'required|min:3',
                'area' => 'required',
                'description' => 'required|min:10',
                'order_index' => 'required|integer|min:0'
            ];

            if (!$validator->validate($rules)) {
                $this->jsonResponse(['success' => false, 'errors' => $validator->getErrors()]);
                return;
            }

            $data = $validator->getData();

            if (!$template) {
                $template = new DocumentTemplate();
                $template->created_at = date('Y-m-d H:i:s');
            }

            $template->name = $data['name'];
            $template->area = $data['area'];
            $template->description = $data['description'];
            $template->order_index = (int)$data['order_index'];
            $template->is_required = isset($data['is_required']) ? 1 : 0;
            $template->allowed_extensions = isset($data['allowed_extensions']) ? $data['allowed_extensions'] : 'pdf,doc,docx';
            $template->max_file_size = isset($data['max_file_size']) ? (int)$data['max_file_size'] : 10485760;
            $template->instructions = $data['instructions'] ?? '';
            $template->is_active = isset($data['is_active']) ? 1 : 0;
            $template->updated_at = date('Y-m-d H:i:s');

            $template->save();

            $this->logger->info('Plantilla guardada por admin', [
                'admin_id' => $this->getCurrentUser()->id,
                'template_id' => $template->id,
                'action' => $template->id ? 'update' : 'create'
            ]);

            $this->jsonResponse(['success' => true, 'message' => 'Plantilla guardada correctamente']);

        } catch (Exception $e) {
            $this->logger->error('Error guardando plantilla', ['error' => $e->getMessage()]);
            $this->jsonResponse(['success' => false, 'message' => 'Error al guardar plantilla']);
        }
    }

    private function requireAdminAuth()
    {
        $user = $this->getCurrentUser();
        if (!$user || !in_array($user->role, ['admin', 'area_admin'])) {
            $this->handleError('Acceso denegado', 403);
        }
    }

    // Implementar métodos de health check, limpieza, reportes, etc.
    // ... (continúa con más métodos de apoyo)
}