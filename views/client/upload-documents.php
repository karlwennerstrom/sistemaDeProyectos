<?php
/**
 * Vista de carga de documentos para clientes
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

require_once '../config/app.php';
require_once '../src/Controllers/AuthController.php';
require_once '../src/Controllers/DocumentController.php';
require_once '../src/Controllers/ProjectController.php';

use Controllers\AuthController;
use Controllers\DocumentController;
use Controllers\ProjectController;

// Verificar autenticación
$authController = new AuthController();
if (!$authController->isAuthenticated() || !$authController->hasRole(['client', 'admin'])) {
    header('Location: /auth/login.php');
    exit;
}

$user = $authController->getCurrentUser();
$documentController = new DocumentController();
$projectController = new ProjectController();

// Obtener ID del proyecto
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if (!$projectId) {
    header('Location: /client/my-projects.php?error=proyecto_no_especificado');
    exit;
}

// Verificar que el proyecto pertenece al usuario
$project = $projectController->getProject($projectId);
if (!$project || ($project['user_id'] != $user['id'] && !$authController->hasRole(['admin']))) {
    header('Location: /client/my-projects.php?error=acceso_denegado');
    exit;
}

// Obtener documentos del proyecto
$documents = $documentController->getProjectDocuments($projectId);

// Obtener plantillas de documentos por área
$templates = $documentController->getDocumentTemplates();

// Procesar subida de archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    $result = $documentController->uploadDocument($_POST, $_FILES);
    if ($result['success']) {
        $successMessage = 'Documento subido correctamente';
        // Recargar documentos
        $documents = $documentController->getProjectDocuments($projectId);
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
        <?php include '../views/layouts/client-nav.php'; ?>
        
        <!-- Contenido principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-upload me-2"></i>
                    Gestión de Documentos
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/client/my-projects.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver a Mis Proyectos
                    </a>
                </div>
            </div>

            <!-- Información del proyecto -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-project-diagram me-2"></i>
                                <?= htmlspecialchars($project['name']) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Estado:</strong> 
                                        <span class="badge bg-<?= $project['status'] === 'draft' ? 'secondary' : 
                                                                ($project['status'] === 'in_progress' ? 'warning' : 
                                                                ($project['status'] === 'in_review' ? 'info' : 
                                                                ($project['status'] === 'approved' ? 'success' : 'danger'))) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                                        </span>
                                    </p>
                                    <p><strong>Prioridad:</strong> 
                                        <span class="badge bg-<?= $project['priority'] === 'high' ? 'danger' : 
                                                               ($project['priority'] === 'medium' ? 'warning' : 'info') ?>">
                                            <?= ucfirst($project['priority']) ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Fecha de creación:</strong> <?= date('d/m/Y', strtotime($project['created_at'])) ?></p>
                                    <p><strong>Documentos subidos:</strong> <?= count($documents) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
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

            <div class="row">
                <!-- Formulario de subida -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-cloud-upload-alt me-2"></i>
                                Subir Documento
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                
                                <div class="mb-3">
                                    <label for="document_area" class="form-label">Área *</label>
                                    <select class="form-select" id="document_area" name="area" required>
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

                                <div class="mb-3">
                                    <label for="document_template" class="form-label">Plantilla</label>
                                    <select class="form-select" id="document_template" name="template_id">
                                        <option value="">Sin plantilla</option>
                                        <!-- Las opciones se cargarán dinámicamente -->
                                    </select>
                                    <div class="form-text">
                                        <small>Las plantillas ayudan a asegurar que incluyes toda la información necesaria.</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="document_title" class="form-label">Título del documento *</label>
                                    <input type="text" class="form-control" id="document_title" name="title" 
                                           placeholder="Ej: Diagrama de arquitectura v1.0" required>
                                </div>

                                <div class="mb-3">
                                    <label for="document_description" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="document_description" name="description" 
                                              rows="3" placeholder="Describe el contenido del documento..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="document_file" class="form-label">Archivo *</label>
                                    <input type="file" class="form-control" id="document_file" name="document_file" 
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.zip,.rar" required>
                                    <div class="form-text">
                                        <small>Formatos permitidos: PDF, Word, Excel, PowerPoint, imágenes, ZIP. Máximo 10MB.</small>
                                    </div>
                                    <div class="progress mt-2" id="uploadProgress" style="display: none;">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_final" name="is_final">
                                    <label class="form-check-label" for="is_final">
                                        Marcar como versión final
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="upload_document" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>
                                        Subir Documento
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Ayuda -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Consejos
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <small>Usa nombres descriptivos para tus archivos</small>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <small>Agrupa documentos por área de revisión</small>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <small>Las plantillas te guían con los requisitos</small>
                                </li>
                                <li class="mb-0">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <small>Marca como final solo cuando esté listo</small>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Lista de documentos -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i>
                                Documentos del Proyecto
                            </h5>
                            <span class="badge bg-secondary"><?= count($documents) ?> documentos</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($documents)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No hay documentos subidos</h5>
                                    <p class="text-muted">Comienza subiendo tu primer documento usando el formulario de la izquierda.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Documento</th>
                                                <th>Área</th>
                                                <th>Estado</th>
                                                <th>Fecha</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($documents as $doc): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-file-<?= $doc['file_extension'] === 'pdf' ? 'pdf text-danger' : 
                                                                                    ($doc['file_extension'] === 'doc' || $doc['file_extension'] === 'docx' ? 'word text-primary' : 
                                                                                    'alt text-secondary') ?> me-2"></i>
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($doc['title']) ?></div>
                                                                <small class="text-muted"><?= htmlspecialchars($doc['filename']) ?></small>
                                                                <?php if ($doc['is_final']): ?>
                                                                    <span class="badge bg-success ms-2">Final</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark">
                                                            <?= ucfirst(str_replace('_', ' ', $doc['area'])) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= $doc['status'] === 'approved' ? 'success' : 
                                                                               ($doc['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                                            <?= ucfirst($doc['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small><?= date('d/m/Y H:i', strtotime($doc['uploaded_at'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="/api/documents/<?= $doc['id'] ?>/download" 
                                                               class="btn btn-sm btn-outline-primary" title="Descargar">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <?php if ($doc['status'] === 'pending'): ?>
                                                                <button class="btn btn-sm btn-outline-danger" 
                                                                        onclick="deleteDocument(<?= $doc['id'] ?>)"
                                                                        title="Eliminar">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
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
            </div>
        </main>
    </div>
</div>

<script>
// Cargar plantillas cuando se selecciona un área
document.getElementById('document_area').addEventListener('change', function() {
    const area = this.value;
    const templateSelect = document.getElementById('document_template');
    
    // Limpiar opciones
    templateSelect.innerHTML = '<option value="">Sin plantilla</option>';
    
    if (area) {
        // Simular carga de plantillas (reemplazar con llamada AJAX real)
        const templates = <?= json_encode($templates) ?>;
        const areaTemplates = templates.filter(t => t.area === area);
        
        areaTemplates.forEach(template => {
            const option = document.createElement('option');
            option.value = template.id;
            option.textContent = template.name;
            templateSelect.appendChild(option);
        });
    }
});

// Validación del formulario
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('document_file');
    const file = fileInput.files[0];
    
    if (file) {
        // Validar tamaño (10MB)
        if (file.size > 10 * 1024 * 1024) {
            e.preventDefault();
            alert('El archivo es demasiado grande. El tamaño máximo es 10MB.');
            return;
        }
        
        // Mostrar barra de progreso
        document.getElementById('uploadProgress').style.display = 'block';
    }
});

// Función para eliminar documento
function deleteDocument(documentId) {
    if (confirm('¿Estás seguro de que deseas eliminar este documento?')) {
        fetch(`/api/documents/${documentId}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al eliminar el documento: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el documento');
        });
    }
}

// Drag and drop para el input de archivo
const fileInput = document.getElementById('document_file');
const formBody = document.querySelector('#uploadForm .card-body');

formBody.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('border-primary');
});

formBody.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary');
});

formBody.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        // Trigger change event
        fileInput.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include '../views/layouts/footer.php'; ?>