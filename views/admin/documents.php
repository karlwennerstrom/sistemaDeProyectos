<?php
/**
 * Vista de gestión de documentos y plantillas para administradores
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

require_once '../config/app.php';
require_once '../src/Controllers/AuthController.php';
require_once '../src/Controllers/AdminController.php';
require_once '../src/Controllers/DocumentController.php';

use Controllers\AuthController;
use Controllers\AdminController;
use Controllers\DocumentController;

// Verificar autenticación y permisos de admin
$authController = new AuthController();
if (!$authController->isAuthenticated() || !$authController->hasRole(['admin', 'area_admin'])) {
    header('Location: /auth/login.php');
    exit;
}

$user = $authController->getCurrentUser();
$adminController = new AdminController();
$documentController = new DocumentController();

// Obtener parámetros de filtrado y paginación
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$search = $_GET['search'] ?? '';
$areaFilter = $_GET['area'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? 'documents'; // 'documents' o 'templates'

// Obtener documentos con filtros
if ($typeFilter === 'templates') {
    $documents = $documentController->getDocumentTemplates($areaFilter, $search, $page, $perPage);
    $totalDocuments = $documentController->countDocumentTemplates($areaFilter, $search);
} else {
    $documents = $adminController->getAllDocuments($search, $areaFilter, $statusFilter, $page, $perPage);
    $totalDocuments = $adminController->countAllDocuments($search, $areaFilter, $statusFilter);
}

$totalPages = ceil($totalDocuments / $perPage);

// Obtener estadísticas
$stats = $adminController->getDocumentStats();

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'approve_document':
            $result = $adminController->approveDocument($_POST['document_id'], $_POST['comments'] ?? '');
            break;
        case 'reject_document':
            $result = $adminController->rejectDocument($_POST['document_id'], $_POST['comments'] ?? '');
            break;
        case 'delete_document':
            $result = $adminController->deleteDocument($_POST['document_id']);
            break;
        case 'create_template':
            $result = $documentController->createDocumentTemplate($_POST, $_FILES);
            break;
        case 'update_template':
            $result = $documentController->updateDocumentTemplate($_POST['template_id'], $_POST, $_FILES);
            break;
        case 'delete_template':
            $result = $documentController->deleteDocumentTemplate($_POST['template_id']);
            break;
        case 'bulk_action':
            $documentIds = $_POST['document_ids'] ?? [];
            $bulkAction = $_POST['bulk_action'] ?? '';
            $result = $adminController->bulkDocumentAction($documentIds, $bulkAction, $_POST);
            break;
    }
    
    if ($result['success']) {
        $successMessage = $result['message'];
        // Recargar datos
        if ($typeFilter === 'templates') {
            $documents = $documentController->getDocumentTemplates($areaFilter, $search, $page, $perPage);
            $totalDocuments = $documentController->countDocumentTemplates($areaFilter, $search);
        } else {
            $documents = $adminController->getAllDocuments($search, $areaFilter, $statusFilter, $page, $perPage);
            $totalDocuments = $adminController->countAllDocuments($search, $areaFilter, $statusFilter);
        }
        $stats = $adminController->getDocumentStats();
    } else {
        $errorMessage = $result['message'];
    }
}

// Incluir header
include '../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../views/layouts/admin-nav.php'; ?>
        
        <!-- Contenido principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-file-alt me-2"></i>
                    Gestión de Documentos
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="?type=documents" class="btn btn-sm btn-<?= $typeFilter === 'documents' ? 'primary' : 'outline-primary' ?>">
                            <i class="fas fa-file-alt me-1"></i>
                            Documentos
                        </a>
                        <a href="?type=templates" class="btn btn-sm btn-<?= $typeFilter === 'templates' ? 'primary' : 'outline-primary' ?>">
                            <i class="fas fa-file-contract me-1"></i>
                            Plantillas
                        </a>
                    </div>
                    <?php if ($typeFilter === 'templates'): ?>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                            <i class="fas fa-plus me-1"></i>
                            Nueva Plantilla
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if (isset($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= $stats['total_documents'] ?></h4>
                                    <p class="mb-0">Total Documentos</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= $stats['pending_documents'] ?></h4>
                                    <p class="mb-0">Pendientes</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= $stats['approved_documents'] ?></h4>
                                    <p class="mb-0">Aprobados</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= $stats['rejected_documents'] ?></h4>
                                    <p class="mb-0">Rechazados</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros y búsqueda -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-search me-2"></i>
                        Filtros y Búsqueda
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($typeFilter) ?>">
                        
                        <div class="col-md-4">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Nombre, descripción, archivo...">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="area" class="form-label">Área</label>
                            <select class="form-select" id="area" name="area">
                                <option value="">Todas las áreas</option>
                                <option value="arquitectura" <?= $areaFilter === 'arquitectura' ? 'selected' : '' ?>>🏗️ Arquitectura</option>
                                <option value="infraestructura" <?= $areaFilter === 'infraestructura' ? 'selected' : '' ?>>🔧 Infraestructura</option>
                                <option value="seguridad" <?= $areaFilter === 'seguridad' ? 'selected' : '' ?>>🛡️ Seguridad</option>
                                <option value="base_datos" <?= $areaFilter === 'base_datos' ? 'selected' : '' ?>>📊 Base de Datos</option>
                                <option value="integraciones" <?= $areaFilter === 'integraciones' ? 'selected' : '' ?>>🔗 Integraciones</option>
                                <option value="ambientes" <?= $areaFilter === 'ambientes' ? 'selected' : '' ?>>🌐 Ambientes</option>
                                <option value="jcps" <?= $areaFilter === 'jcps' ? 'selected' : '' ?>>🔍 JCPS</option>
                                <option value="monitoreo" <?= $areaFilter === 'monitoreo' ? 'selected' : '' ?>>📈 Monitoreo</option>
                            </select>
                        </div>
                        
                        <?php if ($typeFilter === 'documents'): ?>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Estado</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos los estados</option>
                                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Aprobado</option>
                                    <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>
                                    Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de documentos/plantillas -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $typeFilter === 'templates' ? 'file-contract' : 'file-alt' ?> me-2"></i>
                        <?= $typeFilter === 'templates' ? 'Plantillas' : 'Documentos' ?>
                        <span class="badge bg-secondary ms-2"><?= $totalDocuments ?></span>
                    </h5>
                    
                    <?php if ($typeFilter === 'documents' && !empty($documents)): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                <i class="fas fa-check-square me-1"></i>
                                Seleccionar Todo
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkAction()" disabled id="bulkActionBtn">
                                <i class="fas fa-tasks me-1"></i>
                                Acciones Masivas
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No se encontraron <?= $typeFilter === 'templates' ? 'plantillas' : 'documentos' ?></h5>
                            <p class="text-muted">
                                <?php if ($typeFilter === 'templates'): ?>
                                    Crea tu primera plantilla usando el botón "Nueva Plantilla".
                                <?php else: ?>
                                    Ajusta los filtros o espera a que los usuarios suban documentos.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <?php if ($typeFilter === 'documents'): ?>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll()">
                                            </th>
                                        <?php endif; ?>
                                        <th><?= $typeFilter === 'templates' ? 'Plantilla' : 'Documento' ?></th>
                                        <th>Proyecto/Área</th>
                                        <?php if ($typeFilter === 'documents'): ?>
                                            <th>Cliente</th>
                                            <th>Estado</th>
                                        <?php endif; ?>
                                        <th>Fecha</th>
                                        <th>Tamaño</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <?php if ($typeFilter === 'documents'): ?>
                                                <td>
                                                    <input type="checkbox" class="document-checkbox" value="<?= $doc['id'] ?>" onchange="updateBulkButton()">
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file-<?= $doc['file_extension'] === 'pdf' ? 'pdf text-danger' : 
                                                                            ($doc['file_extension'] === 'doc' || $doc['file_extension'] === 'docx' ? 'word text-primary' : 
                                                                            ($doc['file_extension'] === 'xls' || $doc['file_extension'] === 'xlsx' ? 'excel text-success' : 
                                                                            'alt text-secondary')) ?> me-2"></i>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($doc['title'] ?? $doc['name']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($doc['filename']) ?></small>
                                                        <?php if (isset($doc['is_final']) && $doc['is_final']): ?>
                                                            <span class="badge bg-success ms-2">Final</span>
                                                        <?php endif; ?>
                                                        <?php if (isset($doc['is_required']) && $doc['is_required']): ?>
                                                            <span class="badge bg-warning ms-2">Requerido</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($typeFilter === 'templates'): ?>
                                                    <span class="badge bg-light text-dark">
                                                        <?= ucfirst(str_replace('_', ' ', $doc['area'])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($doc['project_name']) ?></div>
                                                        <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $doc['area'])) ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($typeFilter === 'documents'): ?>
                                                <td>
                                                    <small><?= htmlspecialchars($doc['client_name']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $doc['status'] === 'approved' ? 'success' : 
                                                                           ($doc['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                                        <?= ucfirst($doc['status']) ?>
                                                    </span>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <small><?= date('d/m/Y H:i', strtotime($doc['uploaded_at'] ?? $doc['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <small><?= isset($doc['file_size']) ? formatFileSize($doc['file_size']) : 'N/A' ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/api/<?= $typeFilter === 'templates' ? 'templates' : 'documents' ?>/<?= $doc['id'] ?>/download" 
                                                       class="btn btn-sm btn-outline-primary" title="Descargar">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    
                                                    <?php if ($typeFilter === 'documents'): ?>
                                                        <?php if ($doc['status'] === 'pending'): ?>
                                                            <button class="btn btn-sm btn-outline-success" 
                                                                    onclick="reviewDocument(<?= $doc['id'] ?>, 'approve')"
                                                                    title="Aprobar">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger" 
                                                                    onclick="reviewDocument(<?= $doc['id'] ?>, 'reject')"
                                                                    title="Rechazar">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-warning" 
                                                                onclick="editTemplate(<?= $doc['id'] ?>)"
                                                                title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteItem(<?= $doc['id'] ?>, '<?= $typeFilter ?>')"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Paginación de documentos">
                                <ul class="pagination justify-content-center mt-4">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal crear plantilla -->
<div class="modal fade" id="createTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nueva Plantilla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_template">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="template_name" class="form-label">Nombre de la plantilla *</label>
                                <input type="text" class="form-control" id="template_name" name="name" 
                                       placeholder="Ej: Checklist de Seguridad" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="template_area" class="form-label">Área *</label>
                                <select class="form-select" id="template_area" name="area" required>
                                    <option value="">Seleccionar área...</option>
                                    <option value="arquitectura">🏗️ Arquitectura</option>
                                    <option value="infraestructura">🔧 Infraestructura</option>
                                    <option value="seguridad">🛡️ Seguridad</option>
                                    <option value="base_datos">📊 Base de Datos</option>
                                    <option value="integraciones">🔗 Integraciones</option>
                                    <option value="ambientes">🌐 Ambientes</option>
                                    <option value="jcps">🔍 JCPS</option>
                                    <option value="monitoreo">📈 Monitoreo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="template_description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="template_description" name="description" rows="3"
                                  placeholder="Describe el propósito y contenido de esta plantilla..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="template_file" class="form-label">Archivo de plantilla *</label>
                        <input type="file" class="form-control" id="template_file" name="template_file" 
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" required>
                        <div class="form-text">
                            <small>Formatos: PDF, Word, Excel, PowerPoint. Máximo 5MB.</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="template_required" name="is_required">
                                <label class="form-check-label" for="template_required">
                                    Plantilla obligatoria
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="template_active" name="is_active" checked>
                                <label class="form-check-label" for="template_active">
                                    Plantilla activa
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="template_instructions" class="form-label">Instrucciones de uso</label>
                        <textarea class="form-control" id="template_instructions" name="instructions" rows="4"
                                  placeholder="Instrucciones para los usuarios sobre cómo usar esta plantilla..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Plantilla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal revisar documento -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="reviewForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalTitle">Revisar Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="reviewAction">
                    <input type="hidden" name="document_id" id="reviewDocumentId">
                    
                    <div class="mb-3">
                        <label for="review_comments" class="form-label">Comentarios</label>
                        <textarea class="form-control" id="review_comments" name="comments" rows="4"
                                  placeholder="Agrega comentarios sobre la revisión..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="reviewSubmitBtn">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal acciones masivas -->
<div class="modal fade" id="bulkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="bulkForm">
                <div class="modal-header">
                    <h5 class="modal-title">Acciones Masivas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="bulk_action">
                    <input type="hidden" name="document_ids" id="bulkDocumentIds">
                    
                    <div class="mb-3">
                        <label for="bulk_action_select" class="form-label">Acción a realizar</label>
                        <select class="form-select" id="bulk_action_select" name="bulk_action" required>
                            <option value="">Seleccionar acción...</option>
                            <option value="approve">Aprobar documentos</option>
                            <option value="reject">Rechazar documentos</option>
                            <option value="delete">Eliminar documentos</option>
                            <option value="change_area">Cambiar área</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="bulk_area_div" style="display: none;">
                        <label for="bulk_new_area" class="form-label">Nueva área</label>
                        <select class="form-select" id="bulk_new_area" name="new_area">
                            <option value="arquitectura">🏗️ Arquitectura</option>
                            <option value="infraestructura">🔧 Infraestructura</option>
                            <option value="seguridad">🛡️ Seguridad</option>
                            <option value="base_datos">📊 Base de Datos</option>
                            <option value="integraciones">🔗 Integraciones</option>
                            <option value="ambientes">🌐 Ambientes</option>
                            <option value="jcps">🔍 JCPS</option>
                            <option value="monitoreo">📈 Monitoreo</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulk_comments" class="form-label">Comentarios</label>
                        <textarea class="form-control" id="bulk_comments" name="bulk_comments" rows="3"
                                  placeholder="Comentarios para la acción masiva..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="bulk_count">0</span> documentos seleccionados
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning" id="bulkSubmitBtn">Ejecutar Acción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Función auxiliar para formatear tamaño de archivo
<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

// Funciones de selección múltiple
function toggleAll() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.document-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkButton();
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.document-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    
    selectAllCheckbox.checked = true;
    updateBulkButton();
}

function updateBulkButton() {
    const checkboxes = document.querySelectorAll('.document-checkbox:checked');
    const bulkBtn = document.getElementById('bulkActionBtn');
    
    if (bulkBtn) {
        if (checkboxes.length > 0) {
            bulkBtn.disabled = false;
            bulkBtn.innerHTML = `<i class="fas fa-tasks me-1"></i>Acciones Masivas (${checkboxes.length})`;
        } else {
            bulkBtn.disabled = true;
            bulkBtn.innerHTML = '<i class="fas fa-tasks me-1"></i>Acciones Masivas';
        }
    }
}

// Función para revisar documento individual
function reviewDocument(documentId, action) {
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    const form = document.getElementById('reviewForm');
    const title = document.getElementById('reviewModalTitle');
    const actionInput = document.getElementById('reviewAction');
    const documentIdInput = document.getElementById('reviewDocumentId');
    const submitBtn = document.getElementById('reviewSubmitBtn');
    
    actionInput.value = action === 'approve' ? 'approve_document' : 'reject_document';
    documentIdInput.value = documentId;
    
    if (action === 'approve') {
        title.textContent = 'Aprobar Documento';
        submitBtn.className = 'btn btn-success';
        submitBtn.textContent = 'Aprobar';
    } else {
        title.textContent = 'Rechazar Documento';
        submitBtn.className = 'btn btn-danger';
        submitBtn.textContent = 'Rechazar';
    }
    
    modal.show();
}

// Función para acciones masivas
function bulkAction() {
    const checkboxes = document.querySelectorAll('.document-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Selecciona al menos un documento');
        return;
    }
    
    const documentIds = Array.from(checkboxes).map(cb => cb.value);
    document.getElementById('bulkDocumentIds').value = documentIds.join(',');
    document.getElementById('bulk_count').textContent = documentIds.length;
    
    const modal = new bootstrap.Modal(document.getElementById('bulkModal'));
    modal.show();
}

// Función para eliminar documento/plantilla
function deleteItem(itemId, type) {
    const itemType = type === 'templates' ? 'plantilla' : 'documento';
    
    if (confirm(`¿Estás seguro de que deseas eliminar este ${itemType}?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_${type === 'templates' ? 'template' : 'document'}">
            <input type="hidden" name="${type === 'templates' ? 'template_id' : 'document_id'}" value="${itemId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Función para editar plantilla
function editTemplate(templateId) {
    // Redirigir a página de edición o abrir modal de edición
    window.location.href = `?type=templates&edit=${templateId}`;
}

// Mostrar/ocultar campo de área en acciones masivas
document.addEventListener('DOMContentLoaded', function() {
    const bulkActionSelect = document.getElementById('bulk_action_select');
    const bulkAreaDiv = document.getElementById('bulk_area_div');
    
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', function() {
            if (this.value === 'change_area') {
                bulkAreaDiv.style.display = 'block';
            } else {
                bulkAreaDiv.style.display = 'none';
            }
        });
    }
    
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Validación del formulario de plantilla
document.addEventListener('DOMContentLoaded', function() {
    const templateForm = document.querySelector('#createTemplateModal form');
    
    if (templateForm) {
        templateForm.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('template_file');
            const file = fileInput.files[0];
            
            if (file) {
                // Validar tamaño (5MB para plantillas)
                if (file.size > 5 * 1024 * 1024) {
                    e.preventDefault();
                    alert('El archivo es demasiado grande. El tamaño máximo para plantillas es 5MB.');
                    return;
                }
                
                // Validar tipo de archivo
                const allowedTypes = ['application/pdf', 'application/msword', 
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
                    
                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Tipo de archivo no permitido. Use PDF, Word, Excel o PowerPoint.');
                    return;
                }
            }
        });
    }
});

// Confirmar acciones críticas
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const action = this.querySelector('input[name="action"]')?.value;
        
        if (['bulk_action', 'delete_document', 'delete_template'].includes(action)) {
            const confirmMessage = action === 'bulk_action' ? 
                '¿Confirmas ejecutar esta acción masiva?' :
                '¿Estás seguro de eliminar este elemento?';
                
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        }
    });
});

// Auto-refresh estadísticas cada 2 minutos
setInterval(function() {
    if (!document.querySelector('.modal.show')) {
        fetch(window.location.href + '&ajax=stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar estadísticas sin recargar toda la página
                    document.querySelector('.bg-primary .card-body h4').textContent = data.stats.total_documents;
                    document.querySelector('.bg-warning .card-body h4').textContent = data.stats.pending_documents;
                    document.querySelector('.bg-success .card-body h4').textContent = data.stats.approved_documents;
                    document.querySelector('.bg-danger .card-body h4').textContent = data.stats.rejected_documents;
                }
            })
            .catch(error => console.log('Error actualizando estadísticas:', error));
    }
}, 120000); // 2 minutos

// Funciones de exportación
function exportDocuments() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = window.location.pathname + '?' + params.toString();
}

function exportTemplates() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'templates');
    window.location.href = window.location.pathname + '?' + params.toString();
}

// Agregar botones de exportación
document.addEventListener('DOMContentLoaded', function() {
    const toolbar = document.querySelector('.btn-toolbar');
    if (toolbar) {
        const exportBtn = document.createElement('button');
        exportBtn.className = 'btn btn-sm btn-outline-info me-2';
        exportBtn.innerHTML = '<i class="fas fa-file-export me-1"></i>Exportar';
        exportBtn.onclick = function() {
            const type = '<?= $typeFilter ?>';
            if (type === 'templates') {
                exportTemplates();
            } else {
                exportDocuments();
            }
        };
        toolbar.insertBefore(exportBtn, toolbar.lastElementChild);
    }
});

// Drag and drop para subir plantillas
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('template_file');
    const modalBody = document.querySelector('#createTemplateModal .modal-body');
    
    if (modalBody && fileInput) {
        modalBody.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-primary');
        });
        
        modalBody.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-primary');
        });
        
        modalBody.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-primary');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                // Trigger change event
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }
});

// Filtrado en tiempo real (opcional)
let searchTimeout;
document.getElementById('search')?.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        // Auto-submit del formulario después de 1 segundo de inactividad
        this.form.submit();
    }, 1000);
});
</script>

<?php include '../views/layouts/footer.php'; ?>