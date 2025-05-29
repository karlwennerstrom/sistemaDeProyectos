<?php
/**
 * Vista detallada de proyecto para administradores
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

require_once '../config/app.php';
require_once '../src/Controllers/AuthController.php';
require_once '../src/Controllers/AdminController.php';
require_once '../src/Controllers/ProjectController.php';
require_once '../src/Controllers/DocumentController.php';

use Controllers\AuthController;
use Controllers\AdminController;
use Controllers\ProjectController;
use Controllers\DocumentController;

// Verificar autenticación y permisos de admin
$authController = new AuthController();
if (!$authController->isAuthenticated() || !$authController->hasRole(['admin', 'area_admin'])) {
    header('Location: /auth/login.php');
    exit;
}

$user = $authController->getCurrentUser();
$adminController = new AdminController();
$projectController = new ProjectController();
$documentController = new DocumentController();

// Obtener ID del proyecto
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$projectId) {
    header('Location: /admin/projects.php?error=proyecto_no_encontrado');
    exit;
}

// Obtener información completa del proyecto
$project = $adminController->getProjectDetails($projectId);
if (!$project) {
    header('Location: /admin/projects.php?error=proyecto_no_encontrado');
    exit;
}

// Obtener etapas del proyecto
$stages = $adminController->getProjectStages($projectId);

// Obtener documentos del proyecto
$documents = $documentController->getProjectDocuments($projectId);

// Obtener feedback/comentarios
$feedback = $adminController->getProjectFeedback($projectId);

// Obtener historial de actividad
$activity = $adminController->getProjectActivity($projectId);

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'update_status':
            $result = $adminController->updateProjectStatus($projectId, $_POST);
            break;
        case 'update_priority':
            $result = $adminController->updateProjectPriority($projectId, $_POST['priority']);
            break;
        case 'add_feedback':
            $result = $adminController->addProjectFeedback($projectId, $_POST);
            break;
        case 'approve_stage':
            $result = $adminController->approveProjectStage($projectId, $_POST['stage_area']);
            break;
        case 'reject_stage':
            $result = $adminController->rejectProjectStage($projectId, $_POST['stage_area'], $_POST['rejection_reason']);
            break;
        case 'assign_reviewer':
            $result = $adminController->assignReviewer($projectId, $_POST['stage_area'], $_POST['reviewer_id']);
            break;
    }
    
    if ($result['success']) {
        $successMessage = $result['message'];
        // Recargar datos
        $project = $adminController->getProjectDetails($projectId);
        $stages = $adminController->getProjectStages($projectId);
        $feedback = $adminController->getProjectFeedback($projectId);
        $activity = $adminController->getProjectActivity($projectId);
    } else {
        $errorMessage = $result['message'];
    }
}

// Obtener lista de revisores para asignación
$reviewers = $adminController->getReviewers();

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
                    <i class="fas fa-project-diagram me-2"></i>
                    <?= htmlspecialchars($project['name']) ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
                            <i class="fas fa-edit me-1"></i>
                            Cambiar Estado
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#priorityModal">
                            <i class="fas fa-flag me-1"></i>
                            Cambiar Prioridad
                        </button>
                    </div>
                    <a href="/admin/projects.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver a Proyectos
                    </a>
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

            <!-- Información general del proyecto -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Información General
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Cliente:</strong> <?= htmlspecialchars($project['client_name']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($project['client_email']) ?></p>
                                    <p><strong>Descripción:</strong></p>
                                    <p class="text-muted"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Estado:</strong> 
                                        <span class="badge bg-<?= $project['status'] === 'draft' ? 'secondary' : 
                                                                ($project['status'] === 'in_progress' ? 'warning' : 
                                                                ($project['status'] === 'in_review' ? 'info' : 
                                                                ($project['status'] === 'approved' ? 'success' : 'danger'))) ?> fs-6">
                                            <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                                        </span>
                                    </p>
                                    <p><strong>Prioridad:</strong> 
                                        <span class="badge bg-<?= $project['priority'] === 'high' ? 'danger' : 
                                                               ($project['priority'] === 'medium' ? 'warning' : 'info') ?> fs-6">
                                            <?= ucfirst($project['priority']) ?>
                                        </span>
                                    </p>
                                    <p><strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($project['created_at'])) ?></p>
                                    <p><strong>Última actualización:</strong> <?= date('d/m/Y H:i', strtotime($project['updated_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Estadísticas rápidas -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>
                                Estadísticas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-primary"><?= count($documents) ?></h4>
                                        <small class="text-muted">Documentos</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success"><?= count(array_filter($stages, fn($s) => $s['status'] === 'approved')) ?></h4>
                                    <small class="text-muted">Áreas aprobadas</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-warning"><?= count(array_filter($stages, fn($s) => $s['status'] === 'in_review')) ?></h4>
                                        <small class="text-muted">En revisión</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-info"><?= count($feedback) ?></h4>
                                    <small class="text-muted">Comentarios</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progreso por áreas -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>
                                Progreso por Áreas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                $areas = [
                                    'arquitectura' => ['icon' => '🏗️', 'name' => 'Arquitectura'],
                                    'infraestructura' => ['icon' => '🔧', 'name' => 'Infraestructura'],
                                    'seguridad' => ['icon' => '🛡️', 'name' => 'Seguridad'],
                                    'base_datos' => ['icon' => '📊', 'name' => 'Base de Datos'],
                                    'integraciones' => ['icon' => '🔗', 'name' => 'Integraciones'],
                                    'ambientes' => ['icon' => '🌐', 'name' => 'Ambientes'],
                                    'jcps' => ['icon' => '🔍', 'name' => 'JCPS'],
                                    'monitoreo' => ['icon' => '📈', 'name' => 'Monitoreo']
                                ];
                                
                                foreach ($areas as $areaKey => $areaInfo):
                                    $stage = array_filter($stages, fn($s) => $s['area'] === $areaKey);
                                    $stage = reset($stage) ?: ['status' => 'pending', 'progress' => 0, 'reviewer_name' => null, 'updated_at' => null];
                                ?>
                                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                    <div class="card h-100 border-<?= $stage['status'] === 'approved' ? 'success' : 
                                                                    ($stage['status'] === 'rejected' ? 'danger' : 
                                                                    ($stage['status'] === 'in_review' ? 'warning' : 'light')) ?>">
                                        <div class="card-body text-center">
                                            <div class="mb-2">
                                                <span style="font-size: 2rem;"><?= $areaInfo['icon'] ?></span>
                                            </div>
                                            <h6 class="card-title"><?= $areaInfo['name'] ?></h6>
                                            
                                            <div class="progress mb-2" style="height: 8px;">
                                                <div class="progress-bar bg-<?= $stage['status'] === 'approved' ? 'success' : 
                                                                              ($stage['status'] === 'rejected' ? 'danger' : 'info') ?>" 
                                                     style="width: <?= $stage['progress'] ?>%"></div>
                                            </div>
                                            
                                            <small class="badge bg-<?= $stage['status'] === 'approved' ? 'success' : 
                                                                     ($stage['status'] === 'rejected' ? 'danger' : 
                                                                     ($stage['status'] === 'in_review' ? 'warning' : 'secondary')) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $stage['status'])) ?>
                                            </small>
                                            
                                            <?php if ($stage['reviewer_name']): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?= htmlspecialchars($stage['reviewer_name']) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mt-2">
                                                <div class="btn-group" role="group">
                                                    <?php if ($stage['status'] === 'in_review'): ?>
                                                        <button class="btn btn-sm btn-success" 
                                                                onclick="approveStage('<?= $areaKey ?>')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" 
                                                                onclick="rejectStage('<?= $areaKey ?>')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="assignReviewer('<?= $areaKey ?>')">
                                                        <i class="fas fa-user-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Documentos del proyecto -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i>
                                Documentos
                            </h5>
                            <span class="badge bg-secondary"><?= count($documents) ?></span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($documents)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No hay documentos subidos</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tbody>
                                            <?php foreach ($documents as $doc): ?>
                                                <tr>
                                                    <td style="width: 40px;">
                                                        <i class="fas fa-file-<?= $doc['file_extension'] === 'pdf' ? 'pdf text-danger' : 
                                                                                ($doc['file_extension'] === 'doc' || $doc['file_extension'] === 'docx' ? 'word text-primary' : 
                                                                                'alt text-secondary') ?>"></i>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold"><?= htmlspecialchars($doc['title']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($doc['area']) ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= $doc['status'] === 'approved' ? 'success' : 
                                                                               ($doc['status'] === 'rejected' ? 'danger' : 'warning') ?> badge-sm">
                                                            <?= ucfirst($doc['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="/api/documents/<?= $doc['id'] ?>/download" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Comentarios y feedback -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-comments me-2"></i>
                                Comentarios
                            </h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                <i class="fas fa-plus me-1"></i>
                                Agregar
                            </button>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($feedback)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-comment-slash fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No hay comentarios</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($feedback as $comment): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong><?= htmlspecialchars($comment['author_name']) ?></strong>
                                                <span class="badge bg-light text-dark ms-2"><?= htmlspecialchars($comment['area']) ?></span>
                                            </div>
                                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></small>
                                        </div>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($comment['message'])) ?></p>
                                        <?php if ($comment['type'] === 'rejection'): ?>
                                            <span class="badge bg-danger mt-1">Rechazo</span>
                                        <?php elseif ($comment['type'] === 'approval'): ?>
                                            <span class="badge bg-success mt-1">Aprobación</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de actividad -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Historial de Actividad
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($activity)): ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No hay actividad registrada</p>
                                </div>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($activity as $item): ?>
                                        <div class="timeline-item border-bottom pb-3 mb-3">
                                            <div class="d-flex align-items-start">
                                                <div class="timeline-marker me-3">
                                                    <i class="fas fa-<?= $item['action'] === 'created' ? 'plus-circle text-success' : 
                                                                       ($item['action'] === 'updated' ? 'edit text-warning' : 
                                                                       ($item['action'] === 'approved' ? 'check-circle text-success' : 
                                                                       ($item['action'] === 'rejected' ? 'times-circle text-danger' : 'info-circle text-info'))) ?>"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong><?= htmlspecialchars($item['user_name']) ?></strong>
                                                            <span class="text-muted"><?= htmlspecialchars($item['description']) ?></span>
                                                        </div>
                                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modales -->

<!-- Modal cambiar estado -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Estado del Proyecto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <div class="mb-3">
                        <label for="new_status" class="form-label">Nuevo Estado</label>
                        <select class="form-select" name="status" id="new_status" required>
                            <option value="draft" <?= $project['status'] === 'draft' ? 'selected' : '' ?>>Borrador</option>
                            <option value="in_progress" <?= $project['status'] === 'in_progress' ? 'selected' : '' ?>>En Progreso</option>
                            <option value="in_review" <?= $project['status'] === 'in_review' ? 'selected' : '' ?>>En Revisión</option>
                            <option value="approved" <?= $project['status'] === 'approved' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="rejected" <?= $project['status'] === 'rejected' ? 'selected' : '' ?>>Rechazado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status_reason" class="form-label">Motivo del cambio</label>
                        <textarea class="form-control" name="reason" id="status_reason" rows="3" 
                                  placeholder="Explica el motivo del cambio de estado..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Estado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal cambiar prioridad -->
<div class="modal fade" id="priorityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Prioridad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_priority">
                    <div class="mb-3">
                        <label for="new_priority" class="form-label">Nueva Prioridad</label>
                        <select class="form-select" name="priority" id="new_priority" required>
                            <option value="low" <?= $project['priority'] === 'low' ? 'selected' : '' ?>>Baja</option>
                            <option value="medium" <?= $project['priority'] === 'medium' ? 'selected' : '' ?>>Media</option>
                            <option value="high" <?= $project['priority'] === 'high' ? 'selected' : '' ?>>Alta</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Prioridad</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal agregar comentario -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Comentario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_feedback">
                    <div class="mb-3">
                        <label for="feedback_area" class="form-label">Área</label>
                        <select class="form-select" name="area" id="feedback_area" required>
                            <option value="general">General</option>
                            <option value="arquitectura">Arquitectura</option>
                            <option value="infraestructura">Infraestructura</option>
                            <option value="seguridad">Seguridad</option>
                            <option value="base_datos">Base de Datos</option>
                            <option value="integraciones">Integraciones</option>
                            <option value="ambientes">Ambientes</option>
                            <option value="jcps">JCPS</option>
                            <option value="monitoreo">Monitoreo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="feedback_type" class="form-label">Tipo</label>
                        <select class="form-select" name="type" id="feedback_type" required>
                            <option value="comment">Comentario</option>
                            <option value="suggestion">Sugerencia</option>
                            <option value="issue">Problema</option>
                            <option value="approval">Aprobación</option>
                            <option value="rejection">Rechazo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="feedback_message" class="form-label">Mensaje</label>
                        <textarea class="form-control" name="message" id="feedback_message" rows="4" 
                                  placeholder="Escribe tu comentario..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar Comentario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funciones para acciones rápidas en áreas
function approveStage(area) {
    if (confirm('¿Aprobar esta área?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="approve_stage">
            <input type="hidden" name="stage_area" value="${area}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectStage(area) {
    const reason = prompt('Motivo del rechazo:');
    if (reason) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="reject_stage">
            <input type="hidden" name="stage_area" value="${area}">
            <input type="hidden" name="rejection_reason" value="${reason}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function assignReviewer(area) {
    // Crear modal dinámico para asignar revisor
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Asignar Revisor - ${area.charAt(0).toUpperCase() + area.slice(1)}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="assign_reviewer">
                        <input type="hidden" name="stage_area" value="${area}">
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Revisor</label>
                            <select class="form-select" name="reviewer_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($reviewers as $reviewer): ?>
                                    <option value="<?= $reviewer['id'] ?>"><?= htmlspecialchars($reviewer['name']) ?> (<?= htmlspecialchars($reviewer['area']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Asignar</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Limpiar el modal cuando se cierre
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

// Auto-refresh de la página cada 5 minutos para mantener datos actualizados
setInterval(function() {
    // Solo si no hay modales abiertos
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 300000); // 5 minutos

// Confirmar acciones críticas
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const action = this.querySelector('input[name="action"]')?.value;
        if (['update_status', 'approve_stage', 'reject_stage'].includes(action)) {
            if (!confirm('¿Estás seguro de realizar esta acción?')) {
                e.preventDefault();
            }
        }
    });
});

// Tooltips para elementos con título
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Función para exportar información del proyecto
function exportProjectInfo() {
    const projectData = {
        id: <?= $projectId ?>,
        name: '<?= addslashes($project['name']) ?>',
        status: '<?= $project['status'] ?>',
        priority: '<?= $project['priority'] ?>',
        client: '<?= addslashes($project['client_name']) ?>',
        documents_count: <?= count($documents) ?>,
        approved_stages: <?= count(array_filter($stages, fn($s) => $s['status'] === 'approved')) ?>,
        export_date: new Date().toISOString()
    };
    
    const dataStr = JSON.stringify(projectData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `proyecto_${<?= $projectId ?>}_${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    URL.revokeObjectURL(url);
}

// Agregar botón de exportación al toolbar si no existe
document.addEventListener('DOMContentLoaded', function() {
    const toolbar = document.querySelector('.btn-toolbar');
    if (toolbar && !document.getElementById('exportBtn')) {
        const exportBtn = document.createElement('button');
        exportBtn.id = 'exportBtn';
        exportBtn.className = 'btn btn-sm btn-outline-info me-2';
        exportBtn.innerHTML = '<i class="fas fa-download me-1"></i>Exportar';
        exportBtn.onclick = exportProjectInfo;
        toolbar.insertBefore(exportBtn, toolbar.lastElementChild);
    }
});
</script>

<?php include '../views/layouts/footer.php'; ?>