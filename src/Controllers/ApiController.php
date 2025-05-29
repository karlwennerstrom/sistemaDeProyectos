<?php

namespace UC\ApprovalSystem\Controllers;

use UC\ApprovalSystem\Controllers\BaseController;
use UC\ApprovalSystem\Models\User;
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
 * Controlador API REST
 * Maneja todas las peticiones de la API del sistema
 */
class ApiController extends BaseController
{
    private $fileService;
    private $notificationService;
    private $logger;

    public function __construct()
    {
        parent::__construct();
        
        // Configurar headers para API
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        // Manejar preflight OPTIONS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $this->fileService = new FileService();
        $this->notificationService = new NotificationService();
        $this->logger = new Logger();
    }

    /**
     * Información de la API
     */
    public function info()
    {
        $this->apiResponse([
            'name' => 'Sistema de Aprobación UC - API',
            'version' => '1.0.0',
            'description' => 'API REST para el Sistema de Aprobación Multi-Área',
            'endpoints' => [
                'auth' => '/api/auth/*',
                'projects' => '/api/projects/*',
                'documents' => '/api/documents/*',
                'feedback' => '/api/feedback/*',
                'users' => '/api/users/*',
                'health' => '/api/health'
            ],
            'areas' => Helper::getAreas(),
            'timestamp' => date('c')
        ]);
    }

    /**
     * Health check de la API
     */
    public function health()
    {
        try {
            $health = [
                'status' => 'ok',
                'timestamp' => date('c'),
                'checks' => [
                    'database' => $this->checkDatabaseConnection(),
                    'file_system' => $this->checkFileSystemHealth(),
                    'memory' => $this->getMemoryUsage(),
                    'uptime' => $this->getUptime()
                ]
            ];

            $this->apiResponse($health);

        } catch (Exception $e) {
            $this->logger->error('API health check failed', ['error' => $e->getMessage()]);
            $this->apiError('Health check failed', 500);
        }
    }

    // === AUTENTICACIÓN ===

    /**
     * Login de usuario
     */
    public function authLogin()
    {
        $this->requirePost();
        
        try {
            $validator = new Validator($this->getJsonInput());
            
            if (!$validator->validate([
                'email' => 'required|email',
                'password' => 'required'
            ])) {
                $this->apiError('Datos inválidos', 400, $validator->getErrors());
                return;
            }

            $data = $validator->getData();
            $user = User::findByEmail($data['email']);

            if (!$user || !$user->verifyPassword($data['password'])) {
                $this->apiError('Credenciales inválidas', 401);
                return;
            }

            if (!$user->is_active) {
                $this->apiError('Usuario inactivo', 401);
                return;
            }

            // Generar token de sesión
            $token = $this->generateApiToken($user);
            
            $this->logger->info('API login successful', ['user_id' => $user->id]);

            $this->apiResponse([
                'success' => true,
                'user' => $user->toArray(['password']), // Excluir password
                'token' => $token,
                'expires_at' => date('c', strtotime('+8 hours'))
            ]);

        } catch (Exception $e) {
            $this->logger->error('API login error', ['error' => $e->getMessage()]);
            $this->apiError('Error en autenticación', 500);
        }
    }

    /**
     * Información del usuario actual
     */
    public function authMe()
    {
        $this->requireApiAuth();
        
        $user = $this->getCurrentUser();
        $this->apiResponse([
            'user' => $user->toArray(['password']),
            'permissions' => $this->getUserPermissions($user),
            'areas' => $user->getAreas()
        ]);
    }

    /**
     * Logout
     */
    public function authLogout()
    {
        $this->requirePost();
        $this->requireApiAuth();
        
        // Invalidar token
        $this->invalidateApiToken();
        
        $this->apiResponse(['success' => true, 'message' => 'Logout exitoso']);
    }

    // === PROYECTOS ===

    /**
     * Listar proyectos
     */
    public function projectsList()
    {
        $this->requireApiAuth();
        
        try {
            $user = $this->getCurrentUser();
            $page = (int)($this->getParam('page') ?? 1);
            $limit = min((int)($this->getParam('limit') ?? 20), 100);
            $filters = $this->getFiltersFromParams();

            // Aplicar filtros de acceso según rol
            if (!in_array($user->role, ['admin', 'area_admin'])) {
                $filters['user_id'] = $user->id;
            }

            $projects = Project::paginate($page, $limit, $filters);
            $total = Project::count($filters);

            $this->apiResponse([
                'projects' => array_map(function($project) {
                    return $project->toArray();
                }, $projects),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);

        } catch (Exception $e) {
            $this->logger->error('API projects list error', ['error' => $e->getMessage()]);
            $this->apiError('Error al obtener proyectos', 500);
        }
    }

    /**
     * Obtener proyecto específico
     */
    public function projectsGet()
    {
        $this->requireApiAuth();
        
        try {
            $projectId = $this->getParam('id');
            $project = Project::findById($projectId);

            if (!$project) {
                $this->apiError('Proyecto no encontrado', 404);
                return;
            }

            // Verificar permisos
            if (!$this->canAccessProject($project)) {
                $this->apiError('Sin permisos para acceder a este proyecto', 403);
                return;
            }

            $includeStages = $this->getParam('include_stages') === 'true';
            $includeDocuments = $this->getParam('include_documents') === 'true';
            $includeFeedback = $this->getParam('include_feedback') === 'true';

            $response = ['project' => $project->toArray()];

            if ($includeStages) {
                $response['stages'] = ProjectStage::getByProject($projectId);
            }

            if ($includeDocuments) {
                $response['documents'] = Document::getByProject($projectId);
            }

            if ($includeFeedback) {
                $response['feedback'] = ProjectFeedback::getByProject($projectId);
            }

            $this->apiResponse($response);

        } catch (Exception $e) {
            $this->logger->error('API project get error', ['error' => $e->getMessage()]);
            $this->apiError('Error al obtener proyecto', 500);
        }
    }

    /**
     * Crear nuevo proyecto
     */
    public function projectsCreate()
    {
        $this->requirePost();
        $this->requireApiAuth();
        
        try {
            $validator = new Validator($this->getJsonInput());
            
            if (!$validator->validate([
                'name' => 'required|min:3|max:255',
                'description' => 'required|min:10',
                'areas' => 'required|array'
            ])) {
                $this->apiError('Datos inválidos', 400, $validator->getErrors());
                return;
            }

            $data = $validator->getData();
            $user = $this->getCurrentUser();

            $project = new Project();
            $project->name = $data['name'];
            $project->description = $data['description'];
            $project->user_id = $user->id;
            $project->areas = json_encode($data['areas']);
            $project->status = 'draft';
            $project->created_at = date('Y-m-d H:i:s');
            $project->updated_at = date('Y-m-d H:i:s');

            $project->save();

            // Crear etapas para cada área
            $project->createStagesForAreas($data['areas']);

            // Crear documentos desde plantillas
            DocumentTemplate::copyToProject($project->id, $data['areas']);

            $this->logger->info('Project created via API', [
                'project_id' => $project->id,
                'user_id' => $user->id
            ]);

            $this->apiResponse([
                'success' => true,
                'project' => $project->toArray(),
                'message' => 'Proyecto creado correctamente'
            ], 201);

        } catch (Exception $e) {
            $this->logger->error('API project create error', ['error' => $e->getMessage()]);
            $this->apiError('Error al crear proyecto', 500);
        }
    }

    /**
     * Actualizar proyecto
     */
    public function projectsUpdate()
    {
        $this->requirePut();
        $this->requireApiAuth();
        
        try {
            $projectId = $this->getParam('id');
            $project = Project::findById($projectId);

            if (!$project) {
                $this->apiError('Proyecto no encontrado', 404);
                return;
            }

            if (!$this->canEditProject($project)) {
                $this->apiError('Sin permisos para editar este proyecto', 403);
                return;
            }

            $validator = new Validator($this->getJsonInput());
            
            if (!$validator->validate([
                'name' => 'min:3|max:255',
                'description' => 'min:10'
            ])) {
                $this->apiError('Datos inválidos', 400, $validator->getErrors());
                return;
            }

            $data = $validator->getData();

            if (isset($data['name'])) {
                $project->name = $data['name'];
            }
            if (isset($data['description'])) {
                $project->description = $data['description'];
            }
            
            $project->updated_at = date('Y-m-d H:i:s');
            $project->save();

            $this->logger->info('Project updated via API', [
                'project_id' => $project->id,
                'user_id' => $this->getCurrentUser()->id
            ]);

            $this->apiResponse([
                'success' => true,
                'project' => $project->toArray(),
                'message' => 'Proyecto actualizado correctamente'
            ]);

        } catch (Exception $e) {
            $this->logger->error('API project update error', ['error' => $e->getMessage()]);
            $this->apiError('Error al actualizar proyecto', 500);
        }
    }

    /**
     * Eliminar proyecto
     */
    public function projectsDelete()
    {
        $this->requireDelete();
        $this->requireApiAuth();
        
        try {
            $projectId = $this->getParam('id');
            $project = Project::findById($projectId);

            if (!$project) {
                $this->apiError('Proyecto no encontrado', 404);
                return;
            }

            if (!$this->canDeleteProject($project)) {
                $this->apiError('Sin permisos para eliminar este proyecto', 403);
                return;
            }

            $project->delete();

            $this->logger->info('Project deleted via API', [
                'project_id' => $projectId,
                'user_id' => $this->getCurrentUser()->id
            ]);

            $this->apiResponse([
                'success' => true,
                'message' => 'Proyecto eliminado correctamente'
            ]);

        } catch (Exception $e) {
            $this->logger->error('API project delete error', ['error' => $e->getMessage()]);
            $this->apiError('Error al eliminar proyecto', 500);
        }
    }

    /**
     * Cambiar estado del proyecto
     */
    public function projectsChangeStatus()
    {
        $this->requirePost();
        $this->requireApiAuth();
        
        try {
            $projectId = $this->getParam('id');
            $project = Project::findById($projectId);

            if (!$project) {
                $this->apiError('Proyecto no encontrado', 404);
                return;
            }

            $validator = new Validator($this->getJsonInput());
            
            if (!$validator->validate([
                'status' => 'required|in:draft,in_progress,under_review,approved,rejected,cancelled'
            ])) {
                $this->apiError('Estado inválido', 400, $validator->getErrors());
                return;
            }

            $data = $validator->getData();
            $newStatus = $data['status'];

            if (!$project->canChangeStatus($newStatus, $this->getCurrentUser())) {
                $this->apiError('No se puede cambiar al estado solicitado', 400);
                return;
            }

            $oldStatus = $project->status;
            $project->changeStatus($newStatus, $this->getCurrentUser());

            $this->logger->info('Project status changed via API', [
                'project_id' => $project->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => $this->getCurrentUser()->id
            ]);

            $this->apiResponse([
                'success' => true,
                'project' => $project->toArray(),
                'message' => 'Estado actualizado correctamente'
            ]);

        } catch (Exception $e) {
            $this->logger->error('API project status change error', ['error' => $e->getMessage()]);
            $this->apiError('Error al cambiar estado', 500);
        }
    }

    // === DOCUMENTOS ===

    /**
     * Listar documentos
     */
    public function documentsList()
    {
        $this->requireApiAuth();
        
        try {
            $projectId = $this->getParam('project_id');
            $page = (int)($this->getParam('page') ?? 1);
            $limit = min((int)($this->getParam('limit') ?? 20), 100);

            $filters = [];
            if ($projectId) {
                $filters['project_id'] = $projectId;
                
                // Verificar acceso al proyecto
                $project = Project::findById($projectId);
                if (!$project || !$this->canAccessProject($project)) {
                    $this->apiError('Sin acceso al proyecto', 403);
                    return;
                }
            }

            $documents = Document::paginate($page, $limit, $filters);
            $total = Document::count($filters);

            $this->apiResponse([
                'documents' => array_map(function($doc) {
                    return $doc->toArray();
                }, $documents),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);

        } catch (Exception $e) {
            $this->logger->error('API documents list error', ['error' => $e->getMessage()]);
            $this->apiError('Error al obtener documentos', 500);
        }
    }

    /**
     * Subir documento
     */
    public function documentsUpload()
    {
        $this->requirePost();
        $this->requireApiAuth();
        
        try {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->apiError('No se recibió archivo válido', 400);
                return;
            }

            $projectId = $this->getParam('project_id');
            $templateId = $this->getParam('template_id');

            $project = Project::findById($projectId);
            if (!$project || !$this->canEditProject($project)) {
                $this->apiError('Sin permisos para subir documentos a este proyecto', 403);
                return;
            }

            $template = DocumentTemplate::findById($templateId);
            if (!$template) {
                $this->apiError('Plantilla no encontrada', 404);
                return;
            }

            // Validar archivo
            $file = $_FILES['file'];
            $validation = $this->fileService->validateFile($file, $template);
            
            if (!$validation['valid']) {
                $this->apiError('Archivo inválido: ' . $validation['error'], 400);
                return;
            }

            // Guardar archivo
            $uploadResult = $this->fileService->saveUploadedFile($file, $project, $template);
            
            if (!$uploadResult['success']) {
                $this->apiError('Error al guardar archivo: ' . $uploadResult['error'], 500);
                return;
            }

            // Crear documento
            $document = new Document();
            $document->project_id = $projectId;
            $document->template_id = $templateId;
            $document->user_id = $this->getCurrentUser()->id;
            $document->original_name = $file['name'];
            $document->file_path = $uploadResult['file_path'];
            $document->file_size = $file['size'];
            $document->mime_type = $uploadResult['mime_type'];
            $document->checksum = $uploadResult['checksum'];
            $document->status = 'uploaded';
            $document->version = 1;
            $document->created_at = date('Y-m-d H:i:s');
            $document->updated_at = date('Y-m-d H:i:s');

            $document->save();

            $this->logger->info('Document uploaded via API', [
                'document_id' => $document->id,
                'project_id' => $projectId,
                'user_id' => $this->getCurrentUser()->id
            ]);

            $this->apiResponse([
                'success' => true,
                'document' => $document->toArray(),
                'message' => 'Documento subido correctamente'
            ], 201);

        } catch (Exception $e) {
            $this->logger->error('API document upload error', ['error' => $e->getMessage()]);
            $this->apiError('Error al subir documento', 500);
        }
    }

    /**
     * Descargar documento
     */
    public function documentsDownload()
    {
        $this->requireApiAuth();
        
        try {
            $documentId = $this->getParam('id');
            $document = Document::findById($documentId);

            if (!$document) {
                $this->apiError('Documento no encontrado', 404);
                return;
            }

            $project = Project::findById($document->project_id);
            if (!$this->canAccessProject($project)) {
                $this->apiError('Sin permisos para descargar este documento', 403);
                return;
            }

            $downloadResult = $this->fileService->downloadDocument($document);
            
            if (!$downloadResult['success']) {
                $this->apiError('Error al descargar: ' . $downloadResult['error'], 500);
                return;
            }

            // Devolver token de descarga temporal
            $this->apiResponse([
                'download_token' => $downloadResult['token'],
                'download_url' => '/api/documents/download-file/' . $downloadResult['token'],
                'expires_at' => date('c', strtotime('+1 hour'))
            ]);

        } catch (Exception $e) {
            $this->logger->error('API document download error', ['error' => $e->getMessage()]);
            $this->apiError('Error al procesar descarga', 500);
        }
    }

    // === FEEDBACK ===

    /**
     * Listar feedback
     */
    public function feedbackList()
    {
        $this->requireApiAuth();
        
        try {
            $projectId = $this->getParam('project_id');
            $page = (int)($this->getParam('page') ?? 1);
            $limit = min((int)($this->getParam('limit') ?? 20), 100);

            if (!$projectId) {
                $this->apiError('project_id requerido', 400);
                return;
            }

            $project = Project::findById($projectId);
            if (!$project || !$this->canAccessProject($project)) {
                $this->apiError('Sin acceso al proyecto', 403);
                return;
            }

            $feedback = ProjectFeedback::getByProject($projectId, $page, $limit);
            $total = ProjectFeedback::countByProject($projectId);

            $this->apiResponse([
                'feedback' => array_map(function($fb) {
                    return $fb->toArray();
                }, $feedback),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);

        } catch (Exception $e) {
            $this->logger->error('API feedback list error', ['error' => $e->getMessage()]);
            $this->apiError('Error al obtener feedback', 500);
        }
    }

    /**
     * Crear feedback
     */
    public function feedbackCreate()
    {
        $this->requirePost();
        $this->requireApiAuth();
        
        try {
            $validator = new Validator($this->getJsonInput());
            
            if (!$validator->validate([
                'project_id' => 'required|integer',
                'area' => 'required',
                'type' => 'required|in:comment,requirement,suggestion,warning,error',
                'priority' => 'required|in:low,medium,high,critical',
                'message' => 'required|min:10'
            ])) {
                $this->apiError('Datos inválidos', 400, $validator->getErrors());
                return;
            }

            $data = $validator->getData();
            $user = $this->getCurrentUser();

            $project = Project::findById($data['project_id']);
            if (!$project || !$this->canAccessProject($project)) {
                $this->apiError('Sin acceso al proyecto', 403);
                return;
            }

            $feedback = new ProjectFeedback();
            $feedback->project_id = $data['project_id'];
            $feedback->user_id = $user->id;
            $feedback->area = $data['area'];
            $feedback->type = $data['type'];
            $feedback->priority = $data['priority'];
            $feedback->message = $data['message'];
            $feedback->parent_id = $data['parent_id'] ?? null;
            $feedback->status = 'open';
            $feedback->created_at = date('Y-m-d H:i:s');
            $feedback->updated_at = date('Y-m-d H:i:s');

            $feedback->save();

            // Enviar notificaciones
            $this->notificationService->notifyNewFeedback($feedback);

            $this->logger->info('Feedback created via API', [
                'feedback_id' => $feedback->id,
                'project_id' => $data['project_id'],
                'user_id' => $user->id
            ]);

            $this->apiResponse([
                'success' => true,
                'feedback' => $feedback->toArray(),
                'message' => 'Feedback creado correctamente'
            ], 201);

        } catch (Exception $e) {
            $this->logger->error('API feedback create error', ['error' => $e->getMessage()]);
            $this->apiError('Error al crear feedback', 500);
        }
    }

    // === UTILIDADES PRIVADAS ===

    private function requireApiAuth()
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->apiError('Token de autenticación requerido', 401);
            return;
        }

        $user = $this->validateApiToken($token);
        if (!$user) {
            $this->apiError('Token inválido o expirado', 401);
            return;
        }

        $this->setCurrentUser($user);
    }

    private function getAuthToken()
    {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return $this->getParam('token');
    }

    private function generateApiToken($user)
    {
        $payload = [
            'user_id' => $user->id,
            'email' => $user->email,
            'issued_at' => time(),
            'expires_at' => time() + (8 * 3600) // 8 horas
        ];
        
        return base64_encode(json_encode($payload));
    }

    private function validateApiToken($token)
    {
        try {
            $payload = json_decode(base64_decode($token), true);
            
            if (!$payload || !isset($payload['user_id']) || !isset($payload['expires_at'])) {
                return false;
            }
            
            if ($payload['expires_at'] < time()) {
                return false;
            }
            
            return User::findById($payload['user_id']);
            
        } catch (Exception $e) {
            return false;
        }
    }

    private function canAccessProject($project)
    {
        $user = $this->getCurrentUser();
        
        if (in_array($user->role, ['admin', 'area_admin'])) {
            return true;
        }
        
        if ($project->user_id == $user->id) {
            return true;
        }
        
        // Verificar si el usuario tiene acceso por área
        $userAreas = $user->getAreas();
        $projectAreas = $project->getAreas();
        
        return !empty(array_intersect($userAreas, $projectAreas));
    }

    private function canEditProject($project)
    {
        $user = $this->getCurrentUser();
        
        if (in_array($user->role, ['admin'])) {
            return true;
        }
        
        return $project->user_id == $user->id;
    }

    private function canDeleteProject($project)
    {
        $user = $this->getCurrentUser();
        
        if ($user->role === 'admin') {
            return true;
        }
        
        return $project->user_id == $user->id && $project->status === 'draft';
    }

    private function getFiltersFromParams()
    {
        $filters = [];
        
        if ($status = $this->getParam('status')) {
            $filters['status'] = $status;
        }
        
        if ($area = $this->getParam('area')) {
            $filters['area'] = $area;
        }
        
        if ($search = $this->getParam('search')) {
            $filters['search'] = $search;
        }
        
        return $filters;
    }

    private function getJsonInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    private function apiResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function apiError($message, $statusCode = 400, $errors = null)
    {
        http_response_code($statusCode);
        
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => date('c')
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function checkDatabaseConnection()
    {
        try {
            $db = \UC\ApprovalSystem\Utils\Database::getInstance();
            $db->query("SELECT 1");
            return ['status' => 'ok', 'message' => 'Database connection healthy'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed'];
        }
    }

    private function checkFileSystemHealth()
    {
        $uploadDir = dirname(__DIR__, 2) . '/uploads';
        
        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            return ['status' => 'error', 'message' => 'Upload directory not writable'];
        }
        
        return ['status' => 'ok', 'message' => 'File system healthy'];
    }

    private function getMemoryUsage()
    {
        return [
            'status' => 'ok',
            'current' => Helper::formatBytes(memory_get_usage()),
            'peak' => Helper::formatBytes(memory_get_peak_usage()),
            'limit' => ini_get('memory_limit')
        ];
    }

    private function getUptime()
    {
        return [
            'status' => 'ok',
            'uptime' => Helper::formatUptime(time() - $_SERVER['REQUEST_TIME'])
        ];
    }

    private function getUserPermissions($user)
    {
        $permissions = [];
        
        switch ($user->role) {
            case 'admin':
                $permissions = ['all'];
                break;
            case 'area_admin':
                $permissions = ['manage_area_projects', 'review_documents', 'manage_feedback'];
                break;
            case 'reviewer':
                $permissions = ['review_documents', 'create_feedback'];
                break;
            case 'client':
                $permissions = ['create_projects', 'upload_documents', 'view_feedback'];
                break;
        }
        
        return $permissions;
    }

    private function requirePost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->apiError('Método POST requerido', 405);
        }
    }

    private function requirePut()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->apiError('Método PUT requerido', 405);
        }
    }

    private function requireDelete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->apiError('Método DELETE requerido', 405);
        }
    }
}