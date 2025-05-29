<?php
/**
 * Controlador de Documentos
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Controllers;

use UC\ApprovalSystem\Models\Document;
use UC\ApprovalSystem\Models\DocumentTemplate;
use UC\ApprovalSystem\Models\Project;
use UC\ApprovalSystem\Services\FileService;
use UC\ApprovalSystem\Services\NotificationService;
use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Session;
use UC\ApprovalSystem\Utils\Helper;
use UC\ApprovalSystem\Utils\Validator;

class DocumentController extends BaseController 
{
    private $fileService;
    private $notificationService;
    
    public function __construct() 
    {
        parent::__construct();
        $this->fileService = new FileService();
        $this->notificationService = new NotificationService();
    }
    
    /**
     * Listar documentos por proyecto
     */
    public function index(): void 
    {
        $this->requireAuth();
        
        $projectId = (int) $this->getInput('project_id');
        $areaName = $this->getInput('area');
        
        if (!$projectId) {
            $this->jsonError('ID de proyecto requerido', [], 400);
            return;
        }
        
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
            $this->forbidden('No tiene permisos para ver los documentos de este proyecto');
            return;
        }
        
        // Obtener documentos
        if ($areaName) {
            $documents = $project->getDocumentsByArea($areaName)->get();
        } else {
            $documents = $project->documents()->get();
        }
        
        // Obtener plantillas disponibles
        $templates = [];
        if ($areaName) {
            $templates = DocumentTemplate::findByArea($areaName)->get();
        }
        
        if ($this->isJsonRequest()) {
            $this->jsonSuccess([
                'project' => $project->toApiArray(),
                'documents' => array_map(fn($d) => $d->toApiArray(), $documents),
                'templates' => array_map(fn($t) => $t->toApiArray(), $templates),
                'area_name' => $areaName
            ]);
            return;
        }
        
        $data = [
            'title' => "Documentos - Proyecto {$project->project_code}",
            'project' => $project,
            'documents' => $documents,
            'templates' => $templates,
            'area_name' => $areaName,
            'is_admin' => $isAdmin,
            'is_owner' => $isOwner,
            'areas' => Helper::config('areas', [])
        ];
        
        $this->addBreadcrumbs($data, [
            ['title' => 'Inicio', 'url' => '/dashboard.php'],
            ['title' => 'Proyectos', 'url' => '/projects/'],
            ['title' => $project->project_code, 'url' => "/projects/{$project->id}"],
            ['title' => 'Documentos']
        ]);
        
        $viewPath = $isAdmin ? 'admin/documents/index' : 'client/documents/index';
        $this->view($viewPath, $data);
    }
    
    /**
     * Mostrar formulario de subida de documento
     */
    public function create(): void 
    {
        $this->requireAuth();
        
        $projectId = (int) $this->getInput('project_id');
        $areaName = $this->getInput('area');
        $templateId = $this->getInput('template_id');
        
        if (!$projectId) {
            $this->redirect('/projects/');
            return;
        }
        
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->notFound('Proyecto no encontrado');
            return;
        }
        
        // Solo el propietario puede subir documentos
        $userId = $this->getCurrentUserId();
        $isOwner = $project->user_id === $userId;
        
        if (!$isOwner) {
            $this->forbidden('Solo el propietario del proyecto puede subir documentos');
            return;
        }
        
        $template = null;
        if ($templateId) {
            $template = DocumentTemplate::find($templateId);
        }
        
        $templates = [];
        if ($areaName) {
            $templates = DocumentTemplate::findByArea($areaName)->get();
        }
        
        $data = [
            'title' => "Subir Documento - Proyecto {$project->project_code}",
            'project' => $project,
            'area_name' => $areaName,
            'template' => $template,
            'templates' => $templates,
            'areas' => Helper::config('areas', []),
            'max_file_size' => $this->fileService->getConfig()['max_size'],
            'allowed_extensions' => $this->fileService->getConfig()['allowed_extensions']
        ];
        
        $this->addBreadcrumbs($data, [
            ['title' => 'Inicio', 'url' => '/dashboard.php'],
            ['title' => 'Proyectos', 'url' => '/projects/'],
            ['title' => $project->project_code, 'url' => "/projects/{$project->id}"],
            ['title' => 'Documentos', 'url' => "/documents/?project_id={$project->id}"],
            ['title' => 'Subir Documento']
        ]);
        
        $this->view('client/documents/create', $data);
    }
    
    /**
     * Subir documento
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
        
        // Verificar rate limiting para subidas
        if (!$this->checkRateLimit('file_upload', 10, 60)) { // 10 archivos por minuto
            $this->jsonError('Demasiadas subidas. Espere un momento.', [], 429);
            return;
        }
        
        $projectId = (int) $this->getInput('project_id');
        $areaName = $this->getInput('area_name');
        $templateId = $this->getInput('template_id') ? (int) $this->getInput('template_id') : null;
        $file = $this->getFile('document');
        
        // Validaciones básicas
        if (!$projectId || !$areaName) {
            $this->jsonError('Proyecto y área son requeridos', [], 400);
            return;
        }
        
        if (!$file) {
            $this->jsonError('Archivo requerido', [], 400);
            return;
        }
        
        $project = Project::find($projectId);
        
        if (!$project) {
            $this->jsonError('Proyecto no encontrado', [], 404);
            return;
        }
        
        // Verificar permisos
        $userId = $this->getCurrentUserId();
        $isOwner = $project->user_id === $userId;
        
        if (!$isOwner) {
            $this->jsonError('Solo el propietario puede subir documentos', [], 403);
            return;
        }
        
        // Validar datos del documento
        $documentData = [
            'project_id' => $projectId,
            'area_name' => $areaName,
            'document_name' => $file['name'],
            'file' => $file
        ];
        
        $validation = Validator::validateDocument($documentData);
        
        if (!$validation['valid']) {
            $this->jsonError('Archivo inválido', $validation['errors'], 422);
            return;
        }
        
        try {
            // Subir archivo
            $uploadResult = $this->fileService->uploadFile($file, $areaName, $projectId, $templateId);
            
            if (!$uploadResult['success']) {
                $this->jsonError($uploadResult['error'], [], 400);
                return;
            }
            
            // Crear registro de documento
            $document = Document::createFromUpload(
                $uploadResult['file_info'], 
                $projectId, 
                $areaName, 
                $userId, 
                $templateId
            );
            
            if (!$document) {
                // Si falla la creación del registro, eliminar archivo
                $this->fileService->deleteFile($uploadResult['file_info']['relative_path']);
                throw new \Exception('Error creando registro de documento');
            }
            
            // Notificar subida de documento
            $this->notificationService->notifyDocumentUploaded($project, $document->document_name, $areaName);
            
            Session::logActivity('document_uploaded', [
                'document_id' => $document->id,
                'project_id' => $projectId,
                'area_name' => $areaName,
                'file_name' => $file['name'],
                'file_size' => $uploadResult['file_info']['size']
            ]);
            
            if ($this->isJsonRequest()) {
                $this->jsonSuccess($document->toApiArray(), 'Documento subido exitosamente');
            } else {
                $this->redirectWithMessage("/documents/?project_id={$projectId}&area={$areaName}", 
                    'Documento subido exitosamente', 'success');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Mostrar detalles del documento
     */
    public function show(): void 
    {
        $this->requireAuth();
        
        $documentId = (int) $this->getInput('id');
        $document = Document::find($documentId);
        
        if (!$document) {
            $this->notFound('Documento no encontrado');
            return;
        }
        
        $project = $document->project();
        
        if (!$project) {
            $this->notFound('Proyecto asociado no encontrado');
            return;
        }
        
        // Verificar permisos
        $userId = $this->getCurrentUserId();
        $isAdmin = Session::isAdmin();
        $isOwner = $project->user_id === $userId;
        
        if (!$isAdmin && !$isOwner) {
            $this->forbidden('No tiene permisos para ver este documento');
            return;
        }
        
        // Verificar si puede revisar este área
        $canReview = false;
        if ($isAdmin) {
            $canReview = Session::hasAreaAccess($document->area_name);
        }
        
        $template = $document->template();
        $uploadedBy = $document->uploadedByUser();
        $reviewedBy = $document->reviewedByAdmin();
        $previousVersions = $document->getPreviousVersions()->get();
        
        if ($this->isJsonRequest()) {
            $this->jsonSuccess([
                'document' => $document->toApiArray(),
                'project' => $project->toApiArray(),
                'template' => $template ? $template->toApiArray() : null,
                'uploaded_by' => $uploadedBy ? $uploadedBy->toApiArray() : null,
                'reviewed_by' => $reviewedBy ? $reviewedBy->toApiArray() : null,
                'previous_versions' => array_map(fn($d) => $d->toApiArray(), $previousVersions),
                'can_review' => $canReview
            ]);
            return;
        }
        
        $data = [
            'title' => "Documento: {$document->document_name}",
            'document' => $document,
            'project' => $project,
            'template' => $template,
            'uploaded_by' => $uploadedBy,
            'reviewed_by' => $reviewedBy,
            'previous_versions' => $previousVersions,
            'can_review' => $canReview,
            'is_admin' => $isAdmin,
            'is_owner' => $isOwner,
            'file_info' => $document->getFileInfo()
        ];
        
        $this->addBreadcrumbs($data, [
            ['title' => 'Inicio', 'url' => '/dashboard.php'],
            ['title' => 'Proyectos', 'url' => '/projects/'],
            ['title' => $project->project_code, 'url' => "/projects/{$project->id}"],
            ['title' => 'Documentos', 'url' => "/documents/?project_id={$project->id}"],
            ['title' => $document->document_name]
        ]);
        
        $viewPath = $isAdmin ? 'admin/documents/show' : 'client/documents/show';
        $this->view($viewPath, $data);
    }
    
    /**
     * Descargar documento
     */
    public function download(): void 
    {
        $documentId = $this->getInput('document_id');
        $token = $this->getInput('token');
        
        if (!$documentId || !$token) {
            $this->notFound('Parámetros inválidos');
            return;
        }
        
        // Validar token
        $tokenData = Document::validateDownloadToken($token);
        
        if (!$tokenData || $tokenData['document_id'] != $documentId) {
            $this->forbidden('Token de descarga inválido o expirado');
            return;
        }
        
        $document = Document::find($documentId);
        
        if (!$document) {
            $this->notFound('Documento no encontrado');
            return;
        }
        
        // Verificar integridad del archivo
        if (!$document->verifyIntegrity()) {
            Logger::error('Integridad de archivo comprometida', [
                'document_id' => $document->id,
                'file_path' => $document->file_path,
                'expected_checksum' => $document->checksum
            ]);
            
            $this->internalError('El archivo ha sido modificado o está corrupto');
            return;
        }
        
        // Verificar permisos adicionales si es necesario
        if (Session::isAuthenticated()) {
            $project = $document->project();
            $userId = $this->getCurrentUserId();
            $isAdmin = Session::isAdmin();
            $isOwner = $project && $project->user_id === $userId;
            
            if (!$isAdmin && !$isOwner) {
                $this->forbidden('No tiene permisos para descargar este documento');
                return;
            }
        }
        
        // Log de descarga
        Logger::info('Documento descargado', [
            'document_id' => $document->id,
            'file_name' => $document->document_name,
            'user_id' => Session::get('user_id'),
            'ip' => $this->request['ip']
        ]);
        
        Session::logActivity('document_downloaded', [
            'document_id' => $document->id,
            'file_name' => $document->document_name
        ]);
        
        // Descargar archivo
        $this->fileService->downloadFile($document->file_path, $document->original_filename);
    }
    
    /**
     * Subir nueva versión del documento
     */
    public function newVersion(): void 
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
        
        $documentId = (int) $this->getInput('document_id');
        $file = $this->getFile('document');
        
        if (!$file) {
            $this->jsonError('Archivo requerido', [], 400);
            return;
        }
        
        $document = Document::find($documentId);
        
        if (!$document) {
            $this->jsonError('Documento no encontrado', [], 404);
            return;
        }
        
        $project = $document->project();
        
        // Verificar permisos
        $userId = $this->getCurrentUserId();
        $isOwner = $project && $project->user_id === $userId;
        
        if (!$isOwner) {
            $this->jsonError('Solo el propietario puede subir nuevas versiones', [], 403);
            return;
        }
        
        try {
            // Subir nueva versión del archivo
            $uploadResult = $this->fileService->uploadFile(
                $file, 
                $document->area_name, 
                $document->project_id, 
                $document->template_id
            );
            
            if (!$uploadResult['success']) {
                $this->jsonError($uploadResult['error'], [], 400);
                return;
            }
            
            // Crear nueva versión del documento
            $newVersion = $document->createNewVersion($uploadResult['file_info'], $userId);
            
            if (!$newVersion) {
                // Eliminar archivo si falla la creación
                $this->fileService->deleteFile($uploadResult['file_info']['relative_path']);
                throw new \Exception('Error creando nueva versión del documento');
            }
            
            Session::logActivity('document_version_uploaded', [
                'document_id' => $newVersion->id,
                'previous_version_id' => $document->id,
                'project_id' => $document->project_id,
                'version' => $newVersion->version
            ]);
            
            $this->jsonSuccess($newVersion->toApiArray(), 'Nueva versión subida exitosamente');
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Aprobar documento (admin only)
     */
    public function approve(): void 
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
        
        $documentId = (int) $this->getInput('id');
        $notes = $this->getInput('notes', '');
        
        $document = Document::find($documentId);
        
        if (!$document) {
            $this->jsonError('Documento no encontrado', [], 404);
            return;
        }
        
        // Verificar permisos de área
        $this->requireAreaAccess($document->area_name);
        
        try {
            $adminId = $this->getCurrentUserId();
            
            $approved = $document->approve($adminId, $notes);
            
            if (!$approved) {
                throw new \Exception('Error aprobando documento');
            }
            
            Session::logActivity('document_approved', [
                'document_id' => $document->id,
                'project_id' => $document->project_id,
                'area_name' => $document->area_name,
                'notes' => $notes
            ]);
            
            $this->jsonSuccess($document->toApiArray(), 'Documento aprobado exitosamente');
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Rechazar documento (admin only)
     */
    public function reject(): void 
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
        
        $documentId = (int) $this->getInput('id');
        $notes = $this->getInput('notes');
        
        if (!$notes) {
            $this->jsonError('Notas de rechazo requeridas', [], 400);
            return;
        }
        
        $document = Document::find($documentId);
        
        if (!$document) {
            $this->jsonError('Documento no encontrado', [], 404);
            return;
        }
        
        // Verificar permisos de área
        $this->requireAreaAccess($document->area_name);
        
        try {
            $adminId = $this->getCurrentUserId();
            
            $rejected = $document->reject($adminId, $notes);
            
            if (!$rejected) {
                throw new \Exception('Error rechazando documento');
            }
            
            Session::logActivity('document_rejected', [
                'document_id' => $document->id,
                'project_id' => $document->project_id,
                'area_name' => $document->area_name,
                'notes' => $notes
            ]);
            
            $this->jsonSuccess($document->toApiArray(), 'Documento rechazado');
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Marcar documento como requiere cambios
     */
    public function requireChanges(): void 
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
        
        $documentId = (int) $this->getInput('id');
        $notes = $this->getInput('notes');
        
        if (!$notes) {
            $this->jsonError('Notas explicativas requeridas', [], 400);
            return;
        }
        
        $document = Document::find($documentId);
        
        if (!$document) {
            $this->jsonError('Documento no encontrado', [], 404);
            return;
        }
        
        // Verificar permisos de área
        $this->requireAreaAccess($document->area_name);
        
        try {
            $adminId = $this->getCurrentUserId();
            
            $updated = $document->requireChanges($adminId, $notes);
            
            if (!$updated) {
                throw new \Exception('Error marcando documento como requiere cambios');
            }
            
            Session::logActivity('document_requires_changes', [
                'document_id' => $document->id,
                'project_id' => $document->project_id,
                'area_name' => $document->area_name,
                'notes' => $notes
            ]);
            
            $this->jsonSuccess($document->toApiArray(), 'Documento marcado como requiere cambios');
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Eliminar documento
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
        
        $documentId = (int) $this->getInput('id');
        $document = Document::find($documentId);
        
        if (!$document) {
            $this->jsonError('Documento no encontrado', [], 404);
            return;
        }
        
        $project = $document->project();
        
        // Verificar permisos
        $userId = $this->getCurrentUserId();
        $isAdmin = Session::isAdmin();
        $isOwner = $project && $project->user_id === $userId;
        
        if (!$isOwner && !$isAdmin) {
            $this->jsonError('No tiene permisos para eliminar este documento', [], 403);
            return;
        }
        
        // Solo se pueden eliminar documentos no aprobados
        if ($document->isApproved()) {
            $this->jsonError('No se puede eliminar un documento aprobado', [], 400);
            return;
        }
        
        try {
            $filePath = $document->file_path;
            $documentName = $document->document_name;
            $projectId = $document->project_id;
            
            // Eliminar registro de base de datos
            $deleted = $document->delete();
            
            if (!$deleted) {
                throw new \Exception('Error eliminando documento de base de datos');
            }
            
            // Eliminar archivo físico
            $this->fileService->deleteFile($filePath);
            
            Session::logActivity('document_deleted', [
                'document_name' => $documentName,
                'project_id' => $projectId,
                'deleted_by' => $userId
            ]);
            
            if ($this->isJsonRequest()) {
                $this->jsonSuccess([], 'Documento eliminado exitosamente');
            } else {
                $this->redirectWithMessage("/documents/?project_id={$projectId}", 
                    'Documento eliminado exitosamente', 'success');
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Obtener estadísticas de documentos
     */
    public function stats(): void 
    {
        $this->requireAdmin();
        
        $stats = Document::getStats();
        
        $this->jsonSuccess($stats);
    }
    
    /**
     * Listar documentos recientes
     */
    public function recent(): void 
    {
        $this->requireAdmin();
        
        $limit = min(50, (int) $this->getInput('limit', 10));
        $documents = Document::getRecent($limit);
        
        $this->jsonSuccess([
            'documents' => $documents,
            'limit' => $limit
        ]);
    }
    
    /**
     * Documentos que requieren atención
     */
    public function requiresAttention(): void 
    {
        $this->requireAdmin();
        
        $documents = Document::requiresAttention();
        
        $this->jsonSuccess([
            'documents' => array_map(function($doc) {
                return array_merge($doc->toApiArray(), [
                    'days_pending' => $doc->days_pending,
                    'project_code' => $doc->project_code,
                    'project_title' => $doc->project_title
                ]);
            }, $documents),
            'total' => count($documents)
        ]);
    }
    
    /**
     * Buscar documentos
     */
    public function search(): void 
    {
        $this->requireAuth();
        
        $query = $this->getInput('q');
        $projectId = $this->getInput('project_id');
        $areaName = $this->getInput('area');
        $status = $this->getInput('status');
        
        if (!$query) {
            $this->jsonError('Parámetro de búsqueda requerido', [], 400);
            return;
        }
        
        $isAdmin = Session::isAdmin();
        $userId = $this->getCurrentUserId();
        
        $searchQuery = Document::query()
                              ->where('document_name', 'LIKE', "%{$query}%")
                              ->orWhere('original_filename', 'LIKE', "%{$query}%")
                              ->where('is_latest', true);
        
        // Filtros adicionales
        if ($projectId) {
            $searchQuery->where('project_id', $projectId);
        }
        
        if ($areaName) {
            $searchQuery->where('area_name', $areaName);
        }
        
        if ($status) {
            $searchQuery->where('status', $status);
        }
        
        // Restricción de permisos para usuarios no admin
        if (!$isAdmin) {
            $searchQuery->whereIn('project_id', function($q) use ($userId) {
                $q->select('id')
                  ->from('projects')
                  ->where('user_id', $userId);
            });
        }
        
        $documents = $searchQuery->limit(20)->get();
        
        $results = array_map(function($document) {
            $project = $document->project();
            return [
                'id' => $document->id,
                'document_name' => $document->document_name,
                'area_name' => $document->area_name,
                'status' => $document->status,
                'version' => $document->version,
                'created_at' => $document->created_at,
                'project_code' => $project ? $project->project_code : null,
                'project_title' => $project ? $project->title : null,
                'url' => "/documents/{$document->id}"
            ];
        }, $documents);
        
        $this->jsonSuccess([
            'query' => $query,
            'results' => $results,
            'total' => count($results),
            'filters' => [
                'project_id' => $projectId,
                'area' => $areaName,
                'status' => $status
            ]
        ]);
    }
    
    /**
     * Limpiar versiones antiguas de documentos
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
        
        $versionsToKeep = (int) $this->getInput('versions_to_keep', 5);
        
        try {
            $deletedCount = Document::cleanOldVersions($versionsToKeep);
            
            Session::logActivity('documents_cleanup', [
                'versions_kept' => $versionsToKeep,
                'deleted_count' => $deletedCount
            ]);
            
            $this->jsonSuccess([
                'deleted_count' => $deletedCount,
                'versions_kept' => $versionsToKeep
            ], "Se eliminaron {$deletedCount} versiones antiguas de documentos");
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Verificar integridad de documentos
     */
    public function verifyIntegrity(): void 
    {
        $this->requireAdmin();
        
        $documentId = $this->getInput('document_id');
        
        if ($documentId) {
            // Verificar documento específico
            $document = Document::find($documentId);
            
            if (!$document) {
                $this->jsonError('Documento no encontrado', [], 404);
                return;
            }
            
            $isValid = $document->verifyIntegrity();
            
            $this->jsonSuccess([
                'document_id' => $document->id,
                'integrity_valid' => $isValid,
                'checksum' => $document->checksum
            ]);
            
        } else {
            // Verificar todos los documentos (operación costosa)
            $documents = Document::where('is_latest', true)->limit(100)->get();
            $results = [];
            
            foreach ($documents as $document) {
                $results[] = [
                    'document_id' => $document->id,
                    'document_name' => $document->document_name,
                    'integrity_valid' => $document->verifyIntegrity(),
                    'checksum' => $document->checksum
                ];
            }
            
            $validCount = count(array_filter($results, fn($r) => $r['integrity_valid']));
            
            $this->jsonSuccess([
                'total_checked' => count($results),
                'valid_count' => $validCount,
                'invalid_count' => count($results) - $validCount,
                'documents' => $results
            ]);
        }
    }
}