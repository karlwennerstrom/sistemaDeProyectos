<?php
/**
 * Controlador de Proyectos
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Controllers;

use UC\ApprovalSystem\Models\Project;
use UC\ApprovalSystem\Models\User;
use UC\ApprovalSystem\Models\ProjectStage;
use UC\ApprovalSystem\Models\Document;
use UC\ApprovalSystem\Models\ProjectFeedback;
use UC\ApprovalSystem\Services\NotificationService;
use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Session;
use UC\ApprovalSystem\Utils\Helper;
use UC\ApprovalSystem\Utils\Validator;

class ProjectController extends BaseController 
{
    private $notificationService;
    
    public function __construct() 
    {
        parent::__construct();
        $this->notificationService = new NotificationService();
    }
    
    /**
     * Listar proyectos (dashboard del usuario)
     */
    public function index(): void 
    {
        $this->requireAuth();
        
        $userId = $this->getCurrentUserId();
        $isAdmin = Session::isAdmin();
        
        // Parámetros de filtrado
        $status = $this->getInput('status');
        $priority = $this->getInput('priority');
        $search = $this->getInput('search');
        $pagination = $this->getPaginationParams();
        
        if ($isAdmin) {
            // Administradores ven todos los proyectos
            $projectsQuery = Project::query();
            
            // Filtrar por área si es revisor específico
            $userAreas = Session::getUserAreas();
            if (!empty($userAreas) && !in_array('all', $userAreas)) {
                $projectsQuery->whereIn('current_stage', $userAreas);
            }
        } else {
            // Usuarios solo ven sus proyectos
            $projectsQuery = Project::findByUser($userId);
        }
        
        // Aplicar filtros
        if ($status) {
            $projectsQuery->where('status', $status);
        }
        
        if ($priority) {
            $projectsQuery->where('priority', $priority);
        }
        
        if ($search) {
            $projectsQuery->where(function($query) use ($search) {
                $query->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('project_code', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        
        $projects = $projectsQuery->orderBy('updated_at', 'desc')
                                 ->limit($pagination['per_page'])
                                 ->offset($pagination['offset'])
                                 ->get();
        
        $totalProjects = $projectsQuery->count();
        
        // Estadísticas
        $stats = [
            'total' => $totalProjects,
            'by_status' => [],
            'by_priority' => [],
            'overdue' => 0
        ];
        
        if (!empty($projects)) {
            foreach ($projects as $project) {
                $stats['by_status'][$project->status] = ($stats['by_status'][$project->status] ?? 0) + 1;
                $stats['by_priority'][$project->priority] = ($stats['by_priority'][$project->priority] ?? 0) + 1;
                
                if ($project->isOverdue()) {
                    $stats['overdue']++;
                }
            }
        }
        
        if ($this->isJsonRequest()) {
            $this->jsonSuccess([
                'projects' => array_map(fn($p) => $p->toApiArray(), $projects),
                'pagination' => [
                    'current_page' => $pagination['page'],
                    'per_page' => $pagination['per_page'],
                    'total' => $totalProjects,
                    'total_pages' => ceil($totalProjects / $pagination['per_page'])
                ],
                'stats' => $stats,
                'filters' => [
                    'status' => $status,
                    'priority' => $priority,
                    'search' => $search
                ]
            ]);
            return;
        }
        
        $data = [
            'title' => $isAdmin ? 'Gestión de Proyectos' : 'Mis Proyectos',
            'projects' => $projects,
            'stats' => $stats,
            'pagination' => Helper::paginate($projects, $pagination['page'], $pagination['per_page']),
            'filters' => [
                'status' => $status,
                'priority' => $priority,
                'search' => $search
            ],
            'is_admin' => $isAdmin,
            'areas' => Helper::config('areas', []),
            'statuses' => Helper::config('project_statuses', []),
            'priorities' => Helper::config('priorities', [])
        ];
        
        $this->addBreadcrumbs($data, [
            ['title' => 'Inicio', 'url' => '/dashboard.php'],
            ['title' => $isAdmin ? 'Gestión de Proyectos' : 'Mis Proyectos']
        ]);
        
        $viewPath = $isAdmin ? 'admin/projects/index' : 'client/projects/index';
        $this->view($viewPath, $data);
    }
    
    /**
     * Mostrar formulario de nuevo proyecto
     */
    public function create(): void 
    {
        $this->requireAuth();
        
        if (Session::isAdmin()) {
            $this->redirect('/admin/projects/');
            return;
        }
        
        $data = [
            'title' => 'Nuevo Proyecto',
            'priorities' => Helper::config('priorities', []),
            'areas' => Helper::config('areas', [])
        ];
        
        $this->addBreadcrumbs($data, [
            ['title' => 'Inicio', 'url' => '/dashboard.php'],
            ['title' => 'Mis Proyectos', 'url' => '/projects/'],
            ['title' => 'Nuevo Proyecto']
        ]);
        
        $this->view('client/projects/create', $data);
    }
    
    /**
     * Guardar nuevo proyecto
     */
    public function store(): void 
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $data = $this->getAllInputs();
        $data['user_id'] = $this->getCurrentUserId();
        
        // Validar datos
        $validation = Validator::validateProject($data);
        
        if (!$validation['valid']) {
            if ($this->isJsonRequest()) {
                $this->jsonError('Datos de proyecto inválidos', $validation['errors'], 422);
                return;
            } else {
                $this->redirectWithMessage('/projects/create', 
                    'Error en los datos del proyecto: ' . implode(', ', array_flatten($validation['errors'])), 'error');
                return;
            }
        }
        
        try {
            // Crear proyecto
            $project = Project::createProject($data);
            
            if (!$project) {
                throw new \Exception('Error creando proyecto en base de datos');
            }
            
            // Notificar creación
            $this->notificationService->notifyProjectSubmitted($project);
            
            Session::logActivity('project_created', [
                'project_id' => $project->id,
                'project_code' => $project->project_code
            ]);
            
            if ($this->isJsonRequest()) {
                $this->jsonSuccess($project->toApiArray(), 'Proyecto creado exitosamente');
            } else {
                $this->redirectWithMessage("/projects/{$project->id}", 
                    "Proyecto {$project->project_code} creado exitosamente", 'success');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Mostrar detalles del proyecto
     */
    public function show(): void 
    {
        $this->requireAuth();
        
        $projectId = (int) $this->getInput('id');
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->notFound('Proyecto no encontrado');
            return;
        }
        
        // Verificar permisos
        $userId = $this->getCurrentUserId();
        $isAdmin = Session::isAdmin();
        $isOwner = $project->user_id === $userId;
        
        if (!$isAdmin && !$isOwner) {
            $this->forbidden('No tiene permisos para ver este proyecto');
            return;
        }
        
        // Obtener datos relacionados
        $stages = $project->stages()->get();
        $documents = $project->documents()->get();
        $feedback = ProjectFeedback::getWithThreads($projectId);
        $feedbackSummary = ProjectFeedback::getProjectSummary($projectId);
        $user = $project->user();
        
        if ($this->isJsonRequest()) {
            $this->jsonSuccess([
                'project' => $project->toDetailedArray(),
                'stages' => array_map(fn($s) => $s->toApiArray(), $stages),
                'documents' => array_map(fn($d) => $d->toApiArray(), $documents),
                'feedback' => $feedback,
                'feedback_summary' => $feedbackSummary,
                'user' => $user ? $user->toApiArray() : null
            ]);
            return;
        }
        
        $data = [
            'title' => "Proyecto {$project->project_code}",
            'project' => $project,
            'stages' => $stages,
            'documents' => $documents,
            'feedback' => $feedback,
            'feedback_summary' => $feedbackSummary,
            'user' => $user,
            'is_admin' => $isAdmin,
            'is_owner' => $isOwner,
            'can_edit' => $project->canBeEdited() && $isOwner,
            'can_submit' => $project->canBeSubmitted() && $isOwner,
            'areas' => Helper::config('areas', []),
            'statuses' => Helper::config('project_statuses', []),
            'priorities' => Helper::config('priorities', [])
        ];
        
        $this->addBreadcrumbs($data, [
            ['title' => 'Inicio', 'url' => '/dashboard.php'],
            ['title' => $isAdmin ? 'Gestión de Proyectos' : 'Mis Proyectos', 'url' => '/projects/'],
            ['title' => $project->project_code]
        ]);
        
        $viewPath = $isAdmin ? 'admin/projects/show' : 'client/projects/show';
        $this->view($viewPath, $data);
    }
    
    /**
     * Mostrar formulario de edición
     */
    public function edit(): void 
    {
        $this->requireAuth();
        
        $projectId = (int) $this->getInput('id');
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->notFound('Proyecto no encontrado');
            return;
        }
        
        // Solo el dueño puede editar si está en borrador o rechazado
        $userId = $this->getCurrentUserId();
        $isOwner = $project->user_id === $userId;
        
        if (!$isOwner || !$project->canBeEdited()) {
            $this->forbidden('No puede editar este proyecto en su estado actual');
            return;
        }
        
        $data = [
            'title' => "Editar Proyecto {$project->project_code}",
            'project' => $project,
            'priorities' => Helper::config('priorities', []),
            'areas' => Helper::config('areas', [])
        ];
        
        $this->addBreadcrumbs($data, [
            ['title' => 'Inicio', 'url' => '/dashboard.php'],
            ['title' => 'Mis Proyectos', 'url' => '/projects/'],
            ['title' => $project->project_code, 'url' => "/projects/{$project->id}"],
            ['title' => 'Editar']
        ]);
        
        $this->view('client/projects/edit', $data);
    }
    
    /**
     * Actualizar proyecto
     */
    public function update(): void 
    {
        $this->requireAuth();
        
        if (!$this->isPost() && !$this->isPut()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $projectId = (int) $this->getInput('id');
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->jsonError('Proyecto no encontrado', [], 404);
            return;
        }
        
        // Verificar permisos
        $userId = $this->getCurrentUserId();
        $isOwner = $project->user_id === $userId;
        
        if (!$isOwner || !$project->canBeEdited()) {
            $this->jsonError('No puede editar este proyecto', [], 403);
            return;
        }
        
        $data = $this->getAllInputs();
        unset($data['id'], $data['csrf_token'], $data['_method']);
        
        // Validar datos
        $validation = Validator::validateProject($data);
        
        if (!$validation['valid']) {
            if ($this->isJsonRequest()) {
                $this->jsonError('Datos inválidos', $validation['errors'], 422);
                return;
            } else {
                $this->redirectWithMessage("/projects/{$project->id}/edit", 
                    'Error en los datos: ' . implode(', ', array_flatten($validation['errors'])), 'error');
                return;
            }
        }
        
        try {
            // Actualizar proyecto
            $updated = $project->update($data);
            
            if (!$updated) {
                throw new \Exception('Error actualizando proyecto');
            }
            
            Session::logActivity('project_updated', [
                'project_id' => $project->id,
                'project_code' => $project->project_code
            ]);
            
            if ($this->isJsonRequest()) {
                $this->jsonSuccess($project->toApiArray(), 'Proyecto actualizado exitosamente');
            } else {
                $this->redirectWithMessage("/projects/{$project->id}", 
                    'Proyecto actualizado exitosamente', 'success');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Enviar proyecto para revisión
     */
    public function submit(): void 
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $projectId = (int) $this->getInput('id');
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->jsonError('Proyecto no encontrado', [], 404);
            return;
        }
        
        // Verificar permisos
        $userId = $this->getCurrentUserId();
        $isOwner = $project->user_id === $userId;
        
        if (!$isOwner || !$project->canBeSubmitted()) {
            $this->jsonError('No puede enviar este proyecto', [], 403);
            return;
        }
        
        try {
            // Enviar proyecto
            $submitted = $project->submit();
            
            if (!$submitted) {
                throw new \Exception('Error enviando proyecto');
            }
            
            // Notificar envío
            $this->notificationService->notifyProjectSubmitted($project);
            
            Session::logActivity('project_submitted', [
                'project_id' => $project->id,
                'project_code' => $project->project_code
            ]);
            
            if ($this->isJsonRequest()) {
                $this->jsonSuccess($project->toApiArray(), 'Proyecto enviado para revisión');
            } else {
                $this->redirectWithMessage("/projects/{$project->id}", 
                    'Proyecto enviado para revisión exitosamente', 'success');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Aprobar proyecto (admin only)
     */
    public function approve(): void 
    {
        $this->requireAdmin();
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $projectId = (int) $this->getInput('id');
        $notes = $this->getInput('notes', '');
        
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->jsonError('Proyecto no encontrado', [], 404);
            return;
        }
        
        try {
            $adminId = $this->getCurrentUserId();
            
            // Cambiar estado a aprobado
            $approved = $project->changeStatus('approved', $notes, $adminId);
            
            if (!$approved) {
                throw new \Exception('Error aprobando proyecto');
            }
            
            // Notificar aprobación
            $currentStage = $project->getCurrentStage();
            $areaName = $currentStage ? $currentStage->area_name : 'Sistema';
            
            $this->notificationService->notifyProjectApproved(
                $project, 
                $areaName, 
                Admin::find($adminId)
            );
            
            Session::logActivity('project_approved', [
                'project_id' => $project->id,
                'project_code' => $project->project_code,
                'notes' => $notes
            ]);
            
            if ($this->isJsonRequest()) {
                $this->jsonSuccess($project->toApiArray(), 'Proyecto aprobado exitosamente');
            } else {
                $this->redirectWithMessage("/admin/projects/{$project->id}", 
                    'Proyecto aprobado exitosamente', 'success');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Rechazar proyecto (admin only)
     */
    public function reject(): void 
    {
        $this->requireAdmin();
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $projectId = (int) $this->getInput('id');
        $reason = $this->getInput('reason');
        
        if (!$reason) {
            $this->jsonError('Razón de rechazo requerida', [], 400);
            return;
        }
        
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->jsonError('Proyecto no encontrado', [], 404);
            return;
        }
        
        try {
            $adminId = $this->getCurrentUserId();
            
            // Cambiar estado a rechazado
            $rejected = $project->changeStatus('rejected', $reason, $adminId);
            
            if (!$rejected) {
                throw new \Exception('Error rechazando proyecto');
            }
            
            // Obtener feedback pendiente para notificación
            $feedbackList = $project->pendingFeedback()->get()->map(function($feedback) {
                return [
                    'area' => $feedback->stage() ? $feedback->stage()->area_name : 'Sistema',
                    'message' => $feedback->feedback_text
                ];
            })->toArray();
            
            // Notificar rechazo
            $this->notificationService->notifyProjectRejected(
                $project, 
                $reason, 
                $feedbackList
            );
            
            Session::logActivity('project_rejected', [
                'project_id' => $project->id,
                'project_code' => $project->project_code,
                'reason' => $reason
            ]);
            
            if ($this->isJsonRequest()) {
                $this->jsonSuccess($project->toApiArray(), 'Proyecto rechazado');
            } else {
                $this->redirectWithMessage("/admin/projects/{$project->id}", 
                    'Proyecto rechazado', 'success');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Poner proyecto en pausa (admin only)
     */
    public function pause(): void 
    {
        $this->requireAdmin();
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $projectId = (int) $this->getInput('id');
        $reason = $this->getInput('reason', 'Pausado por administrador');
        
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->jsonError('Proyecto no encontrado', [], 404);
            return;
        }
        
        try {
            $adminId = $this->getCurrentUserId();
            
            // Cambiar estado a pausado
            $paused = $project->changeStatus('on_hold', $reason, $adminId);
            
            if (!$paused) {
                throw new \Exception('Error pausando proyecto');
            }
            
            Session::logActivity('project_paused', [
                'project_id' => $project->id,
                'project_code' => $project->project_code,
                'reason' => $reason
            ]);
            
            if ($this->isJsonRequest()) {
                $this->jsonSuccess($project->toApiArray(), 'Proyecto pausado');
            } else {
                $this->redirectWithMessage("/admin/projects/{$project->id}", 
                    'Proyecto pausado exitosamente', 'success');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Reanudar proyecto pausado (admin only)
     */
    public function resume(): void 
    {
        $this->requireAdmin();
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $projectId = (int) $this->getInput('id');
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->jsonError('Proyecto no encontrado', [], 404);
            return;
        }
        
        if ($project->status !== 'on_hold') {
            $this->jsonError('El proyecto no está pausado', [], 400);
            return;
        }
        
        try {
            $adminId = $this->getCurrentUserId();
            
            // Reanudar proyecto
            $resumed = $project->changeStatus('in_review', 'Reanudado por administrador', $adminId);
            
            if (!$resumed) {
                throw new \Exception('Error reanudando proyecto');
            }
            
            Session::logActivity('project_resumed', [
                'project_id' => $project->id,
                'project_code' => $project->project_code
            ]);
            
            if ($this->isJsonRequest()) {
                $this->jsonSuccess($project->toApiArray(), 'Proyecto reanudado');
            } else {
                $this->redirectWithMessage("/admin/projects/{$project->id}", 
                    'Proyecto reanudado exitosamente', 'success');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Eliminar proyecto (solo borradores)
     */
    public function delete(): void 
    {
        $this->requireAuth();
        
        if (!$this->isDelete() && !$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $projectId = (int) $this->getInput('id');
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->jsonError('Proyecto no encontrado', [], 404);
            return;
        }
        
        // Solo el dueño puede eliminar proyectos en borrador
        $userId = $this->getCurrentUserId();
        $isOwner = $project->user_id === $userId;
        $isAdmin = Session::isAdmin();
        
        if (!$isOwner && !$isAdmin) {
            $this->jsonError('No tiene permisos para eliminar este proyecto', [], 403);
            return;
        }
        
        if ($project->status !== 'draft') {
            $this->jsonError('Solo se pueden eliminar proyectos en borrador', [], 400);
            return;
        }
        
        try {
            $projectCode = $project->project_code;
            
            // Eliminar proyecto
            $deleted = $project->delete();
            
            if (!$deleted) {
                throw new \Exception('Error eliminando proyecto');
            }
            
            Session::logActivity('project_deleted', [
                'project_code' => $projectCode,
                'deleted_by' => $userId
            ]);
            
            if ($this->isJsonRequest()) {
                $this->jsonSuccess([], 'Proyecto eliminado exitosamente');
            } else {
                $this->redirectWithMessage('/projects/', 
                    'Proyecto eliminado exitosamente', 'success');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Obtener estadísticas de proyectos
     */
    public function stats(): void 
    {
        $this->requireAuth();
        
        $isAdmin = Session::isAdmin();
        $userId = $this->getCurrentUserId();
        
        if ($isAdmin) {
            $stats = Project::getGeneralStats();
        } else {
            // Estadísticas del usuario
            $userProjects = Project::findByUser($userId)->get();
            $stats = [
                'total_projects' => count($userProjects),
                'by_status' => [],
                'by_priority' => [],
                'overdue_count' => 0,
                'completion_rate' => 0
            ];
            
            $completed = 0;
            foreach ($userProjects as $project) {
                $stats['by_status'][$project->status] = ($stats['by_status'][$project->status] ?? 0) + 1;
                $stats['by_priority'][$project->priority] = ($stats['by_priority'][$project->priority] ?? 0) + 1;
                
                if ($project->isOverdue()) {
                    $stats['overdue_count']++;
                }
                
                if ($project->status === 'approved') {
                    $completed++;
                }
            }
            
            if ($stats['total_projects'] > 0) {
                $stats['completion_rate'] = round(($completed / $stats['total_projects']) * 100, 2);
            }
        }
        
        $this->jsonSuccess($stats);
    }
    
    /**
     * Buscar proyectos
     */
    public function search(): void 
    {
        $this->requireAuth();
        
        $query = $this->getInput('q');
        
        if (!$query) {
            $this->jsonError('Parámetro de búsqueda requerido', [], 400);
            return;
        }
        
        $isAdmin = Session::isAdmin();
        $userId = $this->getCurrentUserId();
        
        $searchResults = [];
        
        if ($isAdmin) {
            // Administradores buscan en todos los proyectos
            $projectsQuery = Project::query();
        } else {
            // Usuarios solo en sus proyectos
            $projectsQuery = Project::findByUser($userId);
        }
        
        $projects = $projectsQuery->where(function($q) use ($query) {
                                     $q->where('title', 'LIKE', "%{$query}%")
                                       ->orWhere('project_code', 'LIKE', "%{$query}%")
                                       ->orWhere('description', 'LIKE', "%{$query}%");
                                 })
                                 ->limit(20)
                                 ->get();
        
        foreach ($projects as $project) {
            $searchResults[] = [
                'id' => $project->id,
                'project_code' => $project->project_code,
                'title' => $project->title,
                'status' => $project->status,
                'priority' => $project->priority,
                'created_at' => $project->created_at,
                'url' => "/projects/{$project->id}"
            ];
        }
        
        $this->jsonSuccess([
            'query' => $query,
            'results' => $searchResults,
            'total' => count($searchResults)
        ]);
    }
    
    /**
     * Duplicar proyecto
     */
    public function duplicate(): void 
    {
        $this->requireAuth();
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $projectId = (int) $this->getInput('id');
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->jsonError('Proyecto no encontrado', [], 404);
            return;
        }
        
        // Verificar permisos
        $userId = $this->getCurrentUserId();
        $isOwner = $project->user_id === $userId;
        $isAdmin = Session::isAdmin();
        
        if (!$isOwner && !$isAdmin) {
            $this->jsonError('No tiene permisos para duplicar este proyecto', [], 403);
            return;
        }
        
        try {
            // Duplicar proyecto
            $duplicate = $project->replicate(['project_code', 'created_at', 'updated_at']);
            $duplicate->title = $project->title . ' (Copia)';
            $duplicate->status = 'draft';
            $duplicate->current_stage = 'formalizacion';
            $duplicate->progress_percentage = 0.00;
            $duplicate->actual_completion_date = null;
            $duplicate->user_id = $userId; // Asignar al usuario actual
            
            // Generar nuevo código
            $duplicate->project_code = Project::generateProjectCode();
            $duplicate->save();
            
            if ($duplicate) {
                // Crear etapas iniciales
                $duplicate->createInitialStages();
                
                Session::logActivity('project_duplicated', [
                    'original_project_id' => $project->id,
                    'duplicate_project_id' => $duplicate->id,
                    'original_code' => $project->project_code,
                    'duplicate_code' => $duplicate->project_code
                ]);
                
                if ($this->isJsonRequest()) {
                    $this->jsonSuccess($duplicate->toApiArray(), 'Proyecto duplicado exitosamente');
                } else {
                    $this->redirectWithMessage("/projects/{$duplicate->id}", 
                        "Proyecto duplicado como {$duplicate->project_code}", 'success');
                }
            } else {
                throw new \Exception('Error duplicando proyecto');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Exportar proyectos a CSV
     */
    public function export(): void 
    {
        $this->requireAuth();
        
        $isAdmin = Session::isAdmin();
        $userId = $this->getCurrentUserId();
        $format = $this->getInput('format', 'csv');
        
        if (!in_array($format, ['csv', 'json'])) {
            $this->jsonError('Formato no soportado', [], 400);
            return;
        }
        
        try {
            if ($isAdmin) {
                $projects = Project::all();
            } else {
                $projects = Project::findByUser($userId)->get();
            }
            
            $exportData = [];
            
            foreach ($projects as $project) {
                $user = $project->user();
                
                $exportData[] = [
                    'project_code' => $project->project_code,
                    'title' => $project->title,
                    'description' => $project->description,
                    'status' => $project->status,
                    'priority' => $project->priority,
                    'current_stage' => $project->current_stage,
                    'progress_percentage' => $project->progress_percentage,
                    'budget' => $project->budget,
                    'department' => $project->department,
                    'technical_lead' => $project->technical_lead,
                    'business_owner' => $project->business_owner,
                    'client_name' => $user ? $user->getFullName() : '',
                    'client_email' => $user ? $user->email : '',
                    'estimated_completion_date' => $project->estimated_completion_date,
                    'actual_completion_date' => $project->actual_completion_date,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at
                ];
            }
            
            if ($format === 'csv') {
                $filename = 'proyectos_' . date('Y-m-d_H-i-s') . '.csv';
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                
                // Headers
                if (!empty($exportData)) {
                    fputcsv($output, array_keys($exportData[0]));
                    
                    // Data
                    foreach ($exportData as $row) {
                        fputcsv($output, $row);
                    }
                }
                
                fclose($output);
                
            } else { // JSON
                $filename = 'proyectos_' . date('Y-m-d_H-i-s') . '.json';
                
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            
            Session::logActivity('projects_exported', [
                'format' => $format,
                'count' => count($exportData)
            ]);
            
            exit;
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Obtener proyectos que requieren atención
     */
    public function requiresAttention(): void 
    {
        $this->requireAdmin();
        
        $projects = Project::requiresAttention();
        
        $this->jsonSuccess([
            'projects' => array_map(function($project) {
                return array_merge($project->toApiArray(), [
                    'attention_reason' => $project->attention_reason ?? 'unknown'
                ]);
            }, $projects),
            'total' => count($projects)
        ]);
    }
    
    /**
     * Limpiar proyectos antiguos en borrador
     */
    public function cleanup(): void 
    {
        $this->requireAdmin();
        
        if (!$this->isPost()) {
            $this->jsonError('Método no permitido', [], 405);
            return;
        }
        
        if (!$this->validateCSRF()) {
            $this->jsonError('Token CSRF inválido', [], 403);
            return;
        }
        
        $daysOld = (int) $this->getInput('days_old', 30);
        
        try {
            $deletedCount = Project::cleanOldDrafts($daysOld);
            
            Session::logActivity('projects_cleanup', [
                'days_old' => $daysOld,
                'deleted_count' => $deletedCount
            ]);
            
            $this->jsonSuccess([
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld
            ], "Se eliminaron {$deletedCount} proyectos borrador antiguos");
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
}