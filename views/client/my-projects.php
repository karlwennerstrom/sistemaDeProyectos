<?php
// Configurar variables para el layout
$page_header = true;
$page_description = 'Gestiona y da seguimiento a todos tus proyectos';
$additional_css = ['/public/assets/css/projects.css', '/public/assets/css/client.css'];
$additional_js = ['/public/assets/js/client-projects.js'];

$breadcrumb = [
    ['text' => 'Dashboard', 'url' => '/client/dashboard'],
    ['text' => 'Mis Proyectos', 'url' => '/client/my-projects']
];

$page_actions = '
    <div class="btn-group">
        <a href="/client/projects/new" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Nuevo Proyecto
        </a>
        <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
            <span class="visually-hidden">Más opciones</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#" onclick="exportMyProjects()">
                <i class="fas fa-download me-2"></i>Exportar Lista
            </a></li>
            <li><a class="dropdown-item" href="/client/help/project-workflow">
                <i class="fas fa-question-circle me-2"></i>Guía de Proyectos
            </a></li>
        </ul>
    </div>
';
?>

<?php include '../layouts/header.php'; ?>

<div class="container-fluid py-4">
    
    <!-- Resumen rápido -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card bg-gradient-primary text-white">
                <div class="stat-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number"><?= count($projects) ?></h4>
                    <p class="stat-label">Total</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card bg-gradient-info text-white">
                <div class="stat-icon">
                    <i class="fas fa-play"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number">
                        <?= count(array_filter($projects, function($p) { 
                            return in_array($p->status, ['in_progress', 'under_review']); 
                        })) ?>
                    </h4>
                    <p class="stat-label">Activos</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card bg-gradient-warning text-white">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number">
                        <?= count(array_filter($projects, function($p) { 
                            return $p->status === 'under_review'; 
                        })) ?>
                    </h4>
                    <p class="stat-label">En Revisión</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card bg-gradient-success text-white">
                <div class="stat-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number">
                        <?= count(array_filter($projects, function($p) { 
                            return $p->status === 'approved'; 
                        })) ?>
                    </h4>
                    <p class="stat-label">Aprobados</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card bg-gradient-secondary text-white">
                <div class="stat-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number">
                        <?= count(array_filter($projects, function($p) { 
                            return $p->status === 'draft'; 
                        })) ?>
                    </h4>
                    <p class="stat-label">Borradores</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card bg-gradient-danger text-white">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number"><?= $pending_feedback_count ?></h4>
                    <p class="stat-label">Feedback</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros de vista -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="filter-tabs">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="statusFilter" id="filter-all" value="all" checked>
                                <label class="btn btn-outline-primary" for="filter-all">
                                    <i class="fas fa-list me-1"></i>Todos
                                </label>
                                
                                <input type="radio" class="btn-check" name="statusFilter" id="filter-active" value="active">
                                <label class="btn btn-outline-info" for="filter-active">
                                    <i class="fas fa-play me-1"></i>Activos
                                </label>
                                
                                <input type="radio" class="btn-check" name="statusFilter" id="filter-review" value="under_review">
                                <label class="btn btn-outline-warning" for="filter-review">
                                    <i class="fas fa-clock me-1"></i>En Revisión
                                </label>
                                
                                <input type="radio" class="btn-check" name="statusFilter" id="filter-approved" value="approved">
                                <label class="btn btn-outline-success" for="filter-approved">
                                    <i class="fas fa-check me-1"></i>Aprobados
                                </label>
                                
                                <input type="radio" class="btn-check" name="statusFilter" id="filter-draft" value="draft">
                                <label class="btn btn-outline-secondary" for="filter-draft">
                                    <i class="fas fa-edit me-1"></i>Borradores
                                </label>
                            </div>
                        </div>
                        
                        <div class="view-controls">
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchProjects" placeholder="Buscar proyectos...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lista de proyectos -->
    <div class="row" id="projectsContainer">
        <?php if (!empty($projects)): ?>
            <?php foreach ($projects as $project): ?>
                <div class="col-xl-6 col-lg-12 mb-4 project-item" 
                     data-status="<?= $project->status ?>" 
                     data-name="<?= strtolower($project->name) ?>"
                     data-description="<?= strtolower($project->description) ?>">
                    <div class="project-card">
                        
                        <!-- Header del proyecto -->
                        <div class="project-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="project-title-section">
                                    <h5 class="project-title mb-1">
                                        <a href="/client/projects/<?= $project->id ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($project->name) ?>
                                        </a>
                                    </h5>
                                    <div class="project-meta">
                                        <span class="project-id text-muted">#<?= $project->id ?></span>
                                        <span class="project-date text-muted ms-2">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d/m/Y', strtotime($project->created_at)) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="project-status">
                                    <span class="badge status-badge bg-<?= getStatusColor($project->status) ?> fs-6">
                                        <i class="fas fa-<?= getStatusIcon($project->status) ?> me-1"></i>
                                        <?= getStatusText($project->status) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="project-description">
                            <p class="text-muted mb-0">
                                <?= htmlspecialchars(substr($project->description, 0, 150)) ?><?= strlen($project->description) > 150 ? '...' : '' ?>
                            </p>
                        </div>
                        
                        <!-- Progreso general -->
                        <div class="project-progress mb-3">
                            <?php $overall_progress = $project->getOverallProgress(); ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted fw-semibold">Progreso General</small>
                                <small class="text-primary fw-bold"><?= $overall_progress ?>%</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?= $overall_progress < 30 ? 'danger' : ($overall_progress < 70 ? 'warning' : 'success') ?>" 
                                     style="width: <?= $overall_progress ?>%"
                                     data-bs-toggle="tooltip" 
                                     title="<?= $overall_progress ?>% completado"></div>
                            </div>
                        </div>
                        
                        <!-- Progreso por áreas -->
                        <div class="areas-progress mb-3">
                            <h6 class="areas-title">Estado por Áreas</h6>
                            <div class="row">
                                <?php 
                                $project_areas = json_decode($project->areas, true) ?? [];
                                $area_progress = $project->getAreaProgress();
                                
                                foreach ($project_areas as $area_key): 
                                    $area_name = \UC\ApprovalSystem\Utils\Helper::getAreaName($area_key);
                                    $progress = $area_progress[$area_key] ?? ['status' => 'pending', 'progress' => 0];
                                ?>
                                    <div class="col-6 mb-2">
                                        <div class="area-progress-item">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="area-name"><?= htmlspecialchars($area_name) ?></small>
                                                <span class="badge badge-sm bg-<?= getAreaStatusColor($progress['status']) ?>">
                                                    <?= getAreaStatusText($progress['status']) ?>
                                                </span>
                                            </div>
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar bg-<?= getAreaStatusColor($progress['status']) ?>" 
                                                     style="width: <?= $progress['progress'] ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="project-info">
                            <div class="row">
                                <div class="col-6">
                                    <div class="info-item">
                                        <i class="fas fa-file-alt text-primary me-2"></i>
                                        <span class="text-muted">Documentos:</span>
                                        <strong class="ms-1"><?= $project->getDocumentCount() ?></strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-item">
                                        <i class="fas fa-comments text-warning me-2"></i>
                                        <span class="text-muted">Feedback:</span>
                                        <strong class="ms-1"><?= $project->getFeedbackCount() ?></strong>
                                        <?php if ($project->getUnreadFeedbackCount() > 0): ?>
                                            <span class="badge bg-danger ms-1"><?= $project->getUnreadFeedbackCount() ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Última actividad -->
                        <div class="last-activity">
                            <div class="d-flex align-items-center text-muted">
                                <i class="fas fa-clock me-2"></i>
                                <small>Última actividad: <?= \UC\ApprovalSystem\Utils\Helper::timeAgo($project->updated_at) ?></small>
                            </div>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="project-actions">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="quick-actions">
                                    <?php if ($project->status === 'draft'): ?>
                                        <a href="/client/projects/<?= $project->id ?>/edit" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($project->status, ['in_progress', 'under_review'])): ?>
                                        <a href="/client/projects/<?= $project->id ?>/documents" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-upload me-1"></i>Subir Docs
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($project->getUnreadFeedbackCount() > 0): ?>
                                        <a href="/client/projects/<?= $project->id ?>/feedback" class="btn btn-sm btn-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Ver Feedback (<?= $project->getUnreadFeedbackCount() ?>)
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="main-actions">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/client/projects/<?= $project->id ?>" class="btn btn-primary">
                                            <i class="fas fa-eye me-1"></i>Ver Detalles
                                        </a>
                                        <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                            <span class="visually-hidden">Más opciones</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php if ($project->status === 'draft'): ?>
                                                <li><a class="dropdown-item" href="/client/projects/<?= $project->id ?>/edit">
                                                    <i class="fas fa-edit me-2"></i>Editar Proyecto
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="submitProject(<?= $project->id ?>)">
                                                    <i class="fas fa-paper-plane me-2"></i>Enviar a Revisión
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                            <?php endif; ?>
                                            <li><a class="dropdown-item" href="/client/projects/<?= $project->id ?>/documents">
                                                <i class="fas fa-file-alt me-2"></i>Ver Documentos
                                            </a></li>
                                            <li><a class="dropdown-item" href="/client/projects/<?= $project->id ?>/feedback">
                                                <i class="fas fa-comments me-2"></i>Ver Feedback
                                            </a></li>
                                            <li><a class="dropdown-item" href="/client/projects/<?= $project->id ?>/timeline">
                                                <i class="fas fa-history me-2"></i>Ver Historial
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="duplicateProject(<?= $project->id ?>)">
                                                <i class="fas fa-copy me-2"></i>Duplicar Proyecto
                                            </a></li>
                                            <?php if ($project->status === 'draft'): ?>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteProject(<?= $project->id ?>, '<?= htmlspecialchars($project->name) ?>')">
                                                    <i class="fas fa-trash me-2"></i>Eliminar
                                                </a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="col-12">
                <div class="empty-state text-center py-5">
                    <div class="empty-icon mb-4">
                        <i class="fas fa-project-diagram text-muted" style="font-size: 4rem; opacity: 0.5;"></i>
                    </div>
                    <h4 class="text-muted mb-3">No tienes proyectos aún</h4>
                    <p class="text-muted mb-4">
                        Crea tu primer proyecto para comenzar con el proceso de aprobación.<br>
                        ¡Es fácil y rápido!
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="/client/projects/new" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Crear Mi Primer Proyecto
                        </a>
                        <a href="/client/help/getting-started" class="btn btn-outline-info btn-lg">
                            <i class="fas fa-question-circle me-2"></i>¿Cómo empiezo?
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Estado de filtros vacío -->
    <div class="row d-none" id="emptyFilterState">
        <div class="col-12">
            <div class="empty-filter-state text-center py-4">
                <div class="empty-icon mb-3">
                    <i class="fas fa-filter text-muted" style="font-size: 2.5rem; opacity: 0.5;"></i>
                </div>
                <h6 class="text-muted mb-2">No se encontraron proyectos</h6>
                <p class="text-muted mb-3">Prueba cambiando los filtros o busca con otros términos</p>
                <button class="btn btn-outline-primary" onclick="clearFilters()">
                    <i class="fas fa-times me-1"></i>Limpiar Filtros
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para enviar proyecto a revisión -->
<div class="modal fade" id="submitProjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar Proyecto a Revisión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Importante:</strong> Una vez enviado a revisión, no podrás editar el proyecto hasta recibir feedback.
                </div>
                <p>¿Estás seguro de que deseas enviar este proyecto a revisión?</p>
                <p class="text-muted">Se notificará automáticamente a todas las áreas involucradas para que comiencen el proceso de revisión.</p>
                <form id="submitProjectForm">
                    <input type="hidden" id="submitProjectId" name="project_id">
                    <div class="mb-3">
                        <label for="submitMessage" class="form-label">Mensaje Adicional (Opcional)</label>
                        <textarea class="form-control" id="submitMessage" name="message" rows="3" 
                                  placeholder="Agrega cualquier información adicional para los revisores..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="executeProjectSubmit()">
                    <i class="fas fa-paper-plane me-1"></i>Enviar a Revisión
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Funciones auxiliares para la vista
function getStatusColor($status) {
    $colors = [
        'draft' => 'secondary',
        'in_progress' => 'info',
        'under_review' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'cancelled' => 'dark'
    ];
    return $colors[$status] ?? 'secondary';
}

function getStatusIcon($status) {
    $icons = [
        'draft' => 'edit',
        'in_progress' => 'play',
        'under_review' => 'clock',
        'approved' => 'check',
        'rejected' => 'times',
        'cancelled' => 'ban'
    ];
    return $icons[$status] ?? 'question';
}

function getStatusText($status) {
    $texts = [
        'draft' => 'Borrador',
        'in_progress' => 'En Progreso',
        'under_review' => 'En Revisión',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'cancelled' => 'Cancelado'
    ];
    return $texts[$status] ?? ucfirst($status);
}

function getAreaStatusColor($status) {
    $colors = [
        'pending' => 'secondary',
        'in_review' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'on_hold' => 'info'
    ];
    return $colors[$status] ?? 'secondary';
}

function getAreaStatusText($status) {
    $texts = [
        'pending' => 'Pendiente',
        'in_review' => 'Revisando',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'on_hold' => 'En Espera'
    ];
    return $texts[$status] ?? ucfirst($status);
}
?>

<script>
// Variables globales
let currentFilter = 'all';
let searchTerm = '';

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeProjectsPage();
    setupFilters();
    setupSearch();
    initializeTooltips();
});

function initializeProjectsPage() {
    // Configurar listeners para filtros
    const filterButtons = document.querySelectorAll('input[name="statusFilter"]');
    filterButtons.forEach(button => {
        button.addEventListener('change', function() {
            currentFilter = this.value;
            applyFilters();
        });
    });
    
    // Auto-refresh cada 2 minutos
    setInterval(function() {
        updateProjectCounts();
    }, 120000);
}

function setupFilters() {
    // Los filtros ya están configurados en initializeProjectsPage
}

function setupSearch() {
    const searchInput = document.getElementById('searchProjects');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTerm = this.value.toLowerCase().trim();
        
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 300);
    });
}

function applyFilters() {
    const projectItems = document.querySelectorAll('.project-item');
    const emptyState = document.getElementById('emptyFilterState');
    let visibleCount = 0;
    
    projectItems.forEach(item => {
        const status = item.dataset.status;
        const name = item.dataset.name;
        const description = item.dataset.description;
        
        let showByStatus = true;
        let showBySearch = true;
        
        // Filtro por estado
        if (currentFilter !== 'all') {
            if (currentFilter === 'active') {
                showByStatus = ['in_progress', 'under_review'].includes(status);
            } else {
                showByStatus = status === currentFilter;
            }
        }
        
        // Filtro por búsqueda
        if (searchTerm) {
            showBySearch = name.includes(searchTerm) || description.includes(searchTerm);
        }
        
        // Mostrar/ocultar elemento
        if (showByStatus && showBySearch) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Mostrar estado vacío si no hay resultados
    if (visibleCount === 0 && projectItems.length > 0) {
        emptyState.classList.remove('d-none');
    } else {
        emptyState.classList.add('d-none');
    }
}

function clearFilters() {
    // Resetear filtro de estado
    document.getElementById('filter-all').checked = true;
    currentFilter = 'all';
    
    // Limpiar búsqueda
    document.getElementById('searchProjects').value = '';
    searchTerm = '';
    
    // Aplicar filtros
    applyFilters();
}

function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Funciones de acciones
function submitProject(projectId) {
    document.getElementById('submitProjectId').value = projectId;
    document.getElementById('submitMessage').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('submitProjectModal'));
    modal.show();
}

function executeProjectSubmit() {
    const form = document.getElementById('submitProjectForm');
    const formData = new FormData(form);
    
    showLoader(true);
    
    fetch('/client/projects/submit', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Proyecto enviado a revisión correctamente', 'success');
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('submitProjectModal'));
            modal.hide();
            
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(data.message || 'Error al enviar proyecto', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al conectar con el servidor', 'error');
    })
    .finally(() => {
        showLoader(false);
    });
}

function duplicateProject(projectId) {
    if (confirm('¿Crear una copia de este proyecto?')) {
        showLoader(true);
        
        fetch(`/client/projects/${projectId}/duplicate`, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Proyecto duplicado correctamente', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert(data.message || 'Error al duplicar proyecto', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al conectar con el servidor', 'error');
        })
        .finally(() => {
            showLoader(false);
        });
    }
}

function deleteProject(projectId, projectName) {
    if (confirm(`¿Estás seguro de eliminar el proyecto "${projectName}"?\n\nEsta acción no se puede deshacer.`)) {
        showLoader(true);
        
        fetch(`/client/projects/${projectId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Proyecto eliminado correctamente', 'success');
                
                // Remover elemento del DOM
                const projectElement = document.querySelector(`[data-project-id="${projectId}"]`);
                if (projectElement) {
                    projectElement.remove();
                }
                
                // Actualizar contadores
                updateProjectCounts();
            } else {
                showAlert(data.message || 'Error al eliminar proyecto', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al conectar con el servidor', 'error');
        })
        .finally(() => {
            showLoader(false);
        });
    }
}

function exportMyProjects() {
    showLoader(true);
    
    const link = document.createElement('a');
    link.href = '/client/projects/export?format=csv';
    link.download = '';
    link.click();
    
    showLoader(false);
    showAlert('Exportación iniciada', 'success');
}

function updateProjectCounts() {
    fetch('/api/client/project-stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar contadores en las tarjetas de estadísticas
                const stats = data.stats;
                
                // Actualizar cada contador
                document.querySelectorAll('.stat-number').forEach((element, index) => {
                    switch (index) {
                        case 0: // Total
                            element.textContent = stats.total || 0;
                            break;
                        case 1: // Activos
                            element.textContent = stats.active || 0;
                            break;
                        case 2: // En Revisión
                            element.textContent = stats.under_review || 0;
                            break;
                        case 3: // Aprobados
                            element.textContent = stats.approved || 0;
                            break;
                        case 4: // Borradores
                            element.textContent = stats.draft || 0;
                            break;
                        case 5: // Feedback
                            element.textContent = stats.pending_feedback || 0;
                            break;
                    }
                });
            }
        })
        .catch(error => {
            console.warn('Error updating project counts:', error);
        });
}

// Funciones de utilidad
function refreshProjects() {
    showLoader(true);
    location.reload();
}

// Animaciones de entrada para nuevos elementos
function animateNewProject(element) {
    element.style.opacity = '0';
    element.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        element.style.transition = 'all 0.3s ease';
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
    }, 100);
}

// Configurar animaciones de hover para las tarjetas
document.addEventListener('DOMContentLoaded', function() {
    const projectCards = document.querySelectorAll('.project-card');
    
    projectCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<style>
/* Estilos específicos para mis proyectos */
.stat-card {
    padding: 1.5rem;
    border-radius: 1rem;
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.bg-gradient-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.stat-icon {
    font-size: 2rem;
    opacity: 0.8;
    margin-bottom: 0.5rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    margin-bottom: 0;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-tabs .btn-check:checked + .btn {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}

.project-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.project-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-color: #0d6efd;
}

.project-header {
    margin-bottom: 1rem;
}

.project-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.project-title a {
    color: #495057;
    transition: color 0.2s ease;
}

.project-title a:hover {
    color: #0d6efd;
}

.project-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.project-id {
    font-size: 0.8rem;
    font-weight: 500;
}

.project-date {
    font-size: 0.8rem;
}

.status-badge {
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
}

.project-description {
    margin-bottom: 1.5rem;
    flex-grow: 1;
}

.project-description p {
    line-height: 1.6;
    color: #6c757d;
}

.project-progress .progress {
    border-radius: 0.5rem;
    background-color: #e9ecef;
}

.project-progress .progress-bar {
    border-radius: 0.5rem;
}

.areas-progress {
    background-color: #f8f9fa;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.areas-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.75rem;
}

.area-progress-item {
    background-color: white;
    border-radius: 0.5rem;
    padding: 0.75rem;
    border: 1px solid #e9ecef;
}

.area-name {
    font-weight: 500;
    color: #495057;
    font-size: 0.8rem;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

.project-info {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
}

.last-activity {
    margin-bottom: 1rem;
    padding: 0.5rem 0;
    border-top: 1px solid #e9ecef;
}

.project-actions {
    margin-top: auto;
}

.quick-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.empty-state {
    padding: 4rem 2rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 1rem;
    border: 2px dashed #dee2e6;
}

.empty-filter-state {
    padding: 2rem;
    background-color: #f8f9fa;
    border-radius: 0.75rem;
    border: 1px solid #e9ecef;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stat-card {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .stat-icon {
        font-size: 1.5rem;
    }
    
    .filter-tabs {
        margin-bottom: 1rem;
    }
    
    .filter-tabs .btn {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
    }
    
    .project-card {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .quick-actions {
        margin-bottom: 1rem;
    }
    
    .main-actions {
        width: 100%;
    }
    
    .main-actions .btn-group {
        width: 100%;
    }
    
    .main-actions .btn {
        flex: 1;
    }
}

@media (max-width: 576px) {
    .project-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .project-status {
        margin-top: 0.5rem;
        align-self: flex-start;
    }
    
    .areas-progress .row .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .project-info .row .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .project-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .view-controls {
        margin-top: 1rem;
    }
    
    .view-controls .input-group {
        width: 100% !important;
    }
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.project-item {
    animation: fadeInUp 0.3s ease;
}

.project-item:nth-child(odd) {
    animation-delay: 0.1s;
}

.project-item:nth-child(even) {
    animation-delay: 0.2s;
}

/* Estados de hover mejorados */
.project-card .btn {
    transition: all 0.2s ease;
}

.project-card .btn:hover {
    transform: translateY(-1px);
}

.dropdown-item:hover {
    background-color: rgba(0, 123, 255, 0.1);
    transform: translateX(3px);
}

/* Mejoras de accesibilidad */
.btn:focus,
.form-control:focus,
.btn-check:focus + .btn {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

.dropdown-item:focus {
    outline: 2px solid #0d6efd;
    outline-offset: -2px;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .project-card {
        background-color: #2d3748;
        border-color: #4a5568;
        color: #e2e8f0;
    }
    
    .project-card:hover {
        background-color: #4a5568;
    }
    
    .areas-progress {
        background-color: #4a5568;
    }
    
    .area-progress-item {
        background-color: #2d3748;
        border-color: #718096;
    }
    
    .project-info {
        background-color: #4a5568;
    }
    
    .empty-state,
    .empty-filter-state {
        background-color: #2d3748;
        border-color: #4a5568;
        color: #e2e8f0;
    }
}

/* Efectos especiales */
.stat-card:hover::before {
    opacity: 0.2;
}

.project-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: linear-gradient(to bottom, #0d6efd, #6610f2);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.project-card:hover::before {
    opacity: 1;
}

/* Loading states */
.project-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

.project-card.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #0d6efd;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<?php include '../layouts/footer.php'; ?>