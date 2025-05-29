<?php
// Configurar variables para el layout
$page_header = true;
$page_description = 'Panel de control y estadísticas del sistema de aprobación';
$additional_css = ['/public/assets/css/dashboard.css', '/public/assets/css/charts.css'];
$additional_js = ['/public/assets/js/charts.js', '/public/assets/js/dashboard.js'];

$breadcrumb = [
    ['text' => 'Administración', 'url' => '/admin'],
    ['text' => 'Dashboard', 'url' => '/admin/dashboard']
];
?>

<?php include '../layouts/header.php'; ?>

<div class="container-fluid py-4">
    
    <!-- Alertas del sistema -->
    <?php if (!empty($alerts)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert-section">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="alert alert-<?= $alert['type'] === 'error' ? 'danger' : $alert['type'] ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?= $alert['type'] === 'warning' ? 'exclamation-triangle' : ($alert['type'] === 'error' ? 'times-circle' : 'info-circle') ?> me-2"></i>
                            <strong><?= htmlspecialchars($alert['message']) ?></strong>
                            <?php if (isset($alert['action'])): ?>
                                <a href="<?= $alert['action'] ?>" class="alert-link ms-2">Ver detalles</a>
                            <?php endif; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Métricas principales -->
    <div class="row mb-4">
        <!-- Total de Usuarios -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Total Usuarios</h6>
                            <h2 class="mb-0 text-primary"><?= number_format($stats['total_users']) ?></h2>
                            <small class="text-success">
                                <i class="fas fa-arrow-up me-1"></i>
                                +<?= $stats['recent_registrations'] ?> esta semana
                            </small>
                        </div>
                        <div class="icon-circle bg-primary bg-opacity-10">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="/admin/users" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>Ver todos
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total de Proyectos -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Total Proyectos</h6>
                            <h2 class="mb-0 text-info"><?= number_format($stats['total_projects']) ?></h2>
                            <small class="text-info">
                                <i class="fas fa-play me-1"></i>
                                <?= $stats['active_projects'] ?> activos
                            </small>
                        </div>
                        <div class="icon-circle bg-info bg-opacity-10">
                            <i class="fas fa-project-diagram text-info"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="/admin/projects" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-eye me-1"></i>Ver todos
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Pendientes de Revisión -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Pendientes Revisión</h6>
                            <h2 class="mb-0 text-warning"><?= number_format($stats['pending_reviews']) ?></h2>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Requieren atención
                            </small>
                        </div>
                        <div class="icon-circle bg-warning bg-opacity-10">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="/admin/projects?status=under_review" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-eye me-1"></i>Revisar
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total de Documentos -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Total Documentos</h6>
                            <h2 class="mb-0 text-success"><?= number_format($stats['total_documents']) ?></h2>
                            <small class="text-muted">
                                <i class="fas fa-comments me-1"></i>
                                <?= $stats['total_feedback'] ?> comentarios
                            </small>
                        </div>
                        <div class="icon-circle bg-success bg-opacity-10">
                            <i class="fas fa-file-alt text-success"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="/admin/documents" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-eye me-1"></i>Ver todos
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos y estadísticas -->
    <div class="row mb-4">
        <!-- Proyectos por Estado -->
        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Proyectos por Estado</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/admin/reports?type=projects">Ver reporte completo</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportChart('projects-status-chart')">Exportar gráfico</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="projects-status-chart" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Proyectos por Área -->
        <div class="col-xl-6 col-lg-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Distribución por Área</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/admin/areas/overview">Ver detalles por área</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportChart('projects-area-chart')">Exportar gráfico</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="projects-area-chart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actividad Reciente y Estado del Sistema -->
    <div class="row mb-4">
        <!-- Actividad Reciente -->
        <div class="col-xl-8 col-lg-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Actividad Reciente</h5>
                        <a href="/admin/activity" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-history me-1"></i>Ver todo
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="activity-feed">
                        
                        <!-- Proyectos recientes -->
                        <?php if (!empty($recent_activity['recent_projects'])): ?>
                            <div class="activity-section">
                                <h6 class="activity-section-title">
                                    <i class="fas fa-project-diagram me-2"></i>Proyectos Recientes
                                </h6>
                                <?php foreach (array_slice($recent_activity['recent_projects'], 0, 3) as $project): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-primary bg-opacity-10">
                                            <i class="fas fa-plus text-primary"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <a href="/admin/projects/<?= $project->id ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($project->name) ?>
                                                </a>
                                            </div>
                                            <div class="activity-meta">
                                                <span class="badge bg-<?= $project->status === 'approved' ? 'success' : ($project->status === 'rejected' ? 'danger' : 'warning') ?>">
                                                    <?= ucfirst($project->status) ?>
                                                </span>
                                                <small class="text-muted ms-2">
                                                    <?= date('d/m/Y H:i', strtotime($project->created_at)) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Documentos recientes -->
                        <?php if (!empty($recent_activity['recent_documents'])): ?>
                            <div class="activity-section">
                                <h6 class="activity-section-title">
                                    <i class="fas fa-file-upload me-2"></i>Documentos Recientes
                                </h6>
                                <?php foreach (array_slice($recent_activity['recent_documents'], 0, 3) as $document): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-info bg-opacity-10">
                                            <i class="fas fa-file text-info"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <?= htmlspecialchars($document->original_name) ?>
                                            </div>
                                            <div class="activity-meta">
                                                <span class="badge bg-<?= $document->status === 'approved' ? 'success' : ($document->status === 'rejected' ? 'danger' : 'secondary') ?>">
                                                    <?= ucfirst($document->status) ?>
                                                </span>
                                                <small class="text-muted ms-2">
                                                    <?= date('d/m/Y H:i', strtotime($document->created_at)) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Feedback reciente -->
                        <?php if (!empty($recent_activity['recent_feedback'])): ?>
                            <div class="activity-section">
                                <h6 class="activity-section-title">
                                    <i class="fas fa-comments me-2"></i>Feedback Reciente
                                </h6>
                                <?php foreach (array_slice($recent_activity['recent_feedback'], 0, 3) as $feedback): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-warning bg-opacity-10">
                                            <i class="fas fa-comment text-warning"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <?= htmlspecialchars(substr($feedback->message, 0, 50)) ?>...
                                            </div>
                                            <div class="activity-meta">
                                                <span class="badge bg-<?= $feedback->priority === 'critical' ? 'danger' : ($feedback->priority === 'high' ? 'warning' : 'info') ?>">
                                                    <?= ucfirst($feedback->priority) ?>
                                                </span>
                                                <small class="text-muted ms-2">
                                                    <?= date('d/m/Y H:i', strtotime($feedback->created_at)) ?>
                                                </small>
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
        
        <!-- Estado del Sistema -->
        <div class="col-xl-4 col-lg-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Estado del Sistema</h5>
                        <button class="btn btn-sm btn-outline-secondary" onclick="refreshSystemHealth()">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="system-health-items">
                        
                        <!-- Base de Datos -->
                        <div class="health-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="health-indicator bg-<?= $system_health['database']['status'] === 'ok' ? 'success' : 'danger' ?>"></div>
                                    <span class="ms-2">Base de Datos</span>
                                </div>
                                <small class="text-<?= $system_health['database']['status'] === 'ok' ? 'success' : 'danger' ?>">
                                    <?= $system_health['database']['status'] === 'ok' ? 'Operativa' : 'Error' ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Sistema de Archivos -->
                        <div class="health-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="health-indicator bg-<?= $system_health['file_system']['status'] === 'ok' ? 'success' : 'danger' ?>"></div>
                                    <span class="ms-2">Sistema de Archivos</span>
                                </div>
                                <small class="text-<?= $system_health['file_system']['status'] === 'ok' ? 'success' : 'danger' ?>">
                                    <?= $system_health['file_system']['status'] === 'ok' ? 'Operativo' : 'Error' ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Servicio de Email -->
                        <div class="health-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="health-indicator bg-<?= $system_health['email_service']['status'] === 'ok' ? 'success' : 'danger' ?>"></div>
                                    <span class="ms-2">Servicio de Email</span>
                                </div>
                                <small class="text-<?= $system_health['email_service']['status'] === 'ok' ? 'success' : 'danger' ?>">
                                    <?= $system_health['email_service']['status'] === 'ok' ? 'Operativo' : 'Error' ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Espacio en Disco -->
                        <div class="health-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="health-indicator bg-<?= $system_health['disk_space']['usage_percent'] < 85 ? 'success' : 'warning' ?>"></div>
                                    <span class="ms-2">Espacio en Disco</span>
                                </div>
                                <small class="text-<?= $system_health['disk_space']['usage_percent'] < 85 ? 'success' : 'warning' ?>">
                                    <?= $system_health['disk_space']['usage_percent'] ?>% usado
                                </small>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-<?= $system_health['disk_space']['usage_percent'] < 85 ? 'success' : 'warning' ?>" 
                                     style="width: <?= $system_health['disk_space']['usage_percent'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Métricas adicionales -->
                    <div class="mt-4">
                        <h6 class="text-muted mb-3">Métricas de Rendimiento</h6>
                        
                        <!-- Tiempo promedio de aprobación -->
                        <div class="metric-item mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Tiempo Promedio de Aprobación</span>
                                <span class="fw-bold"><?= $stats['avg_approval_time'] ?> días</span>
                            </div>
                        </div>
                        
                        <!-- Usuarios activos hoy -->
                        <div class="metric-item mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Usuarios Activos Hoy</span>
                                <span class="fw-bold text-success">
                                    <?= $stats['active_users_today'] ?? 0 ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Última actualización -->
                        <div class="metric-item">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Última Actualización</span>
                                <span class="fw-bold" id="last-update-time">
                                    <?= date('H:i:s') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="/admin/system/health" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-heartbeat me-1"></i>Ver Estado Completo
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Acciones rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="/admin/users/new" class="quick-action-card">
                                <div class="quick-action-icon bg-primary bg-opacity-10">
                                    <i class="fas fa-user-plus text-primary"></i>
                                </div>
                                <div class="quick-action-content">
                                    <h6>Nuevo Usuario</h6>
                                    <p class="text-muted mb-0">Crear cuenta de usuario</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="/admin/document-templates/new" class="quick-action-card">
                                <div class="quick-action-icon bg-success bg-opacity-10">
                                    <i class="fas fa-file-plus text-success"></i>
                                </div>
                                <div class="quick-action-content">
                                    <h6>Nueva Plantilla</h6>
                                    <p class="text-muted mb-0">Crear plantilla de documento</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="/admin/reports" class="quick-action-card">
                                <div class="quick-action-icon bg-info bg-opacity-10">
                                    <i class="fas fa-chart-bar text-info"></i>
                                </div>
                                <div class="quick-action-content">
                                    <h6>Generar Reporte</h6>
                                    <p class="text-muted mb-0">Crear reporte personalizado</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="/admin/system/cleanup" class="quick-action-card">
                                <div class="quick-action-icon bg-warning bg-opacity-10">
                                    <i class="fas fa-broom text-warning"></i>
                                </div>
                                <div class="quick-action-content">
                                    <h6>Limpieza Sistema</h6>
                                    <p class="text-muted mb-0">Optimizar rendimiento</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para exportar datos -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exportar Datos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="mb-3">
                        <label class="form-label">Tipo de Reporte</label>
                        <select class="form-select" name="report_type" required>
                            <option value="">Seleccionar tipo</option>
                            <option value="projects">Proyectos</option>
                            <option value="users">Usuarios</option>
                            <option value="documents">Documentos</option>
                            <option value="feedback">Feedback</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Formato</label>
                        <select class="form-select" name="format" required>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" name="date_from">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" name="date_to">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="executeExport()">
                    <i class="fas fa-download me-1"></i>Exportar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Datos para los gráficos
const projectsStatusData = <?= json_encode($stats['projects_by_status']) ?>;
const projectsAreaData = <?= json_encode($stats['projects_by_area']) ?>;

// Inicializar gráficos cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    startRealTimeUpdates();
});

// Función para inicializar gráficos
function initializeCharts() {
    // Gráfico de proyectos por estado
    const statusCtx = document.getElementById('projects-status-chart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(projectsStatusData),
            datasets: [{
                data: Object.values(projectsStatusData),
                backgroundColor: [
                    '#6c757d', // draft
                    '#0dcaf0', // in_progress
                    '#ffc107', // under_review
                    '#198754', // approved
                    '#dc3545', // rejected
                    '#6f42c1'  // cancelled
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });
    
    // Gráfico de proyectos por área
    const areaCtx = document.getElementById('projects-area-chart').getContext('2d');
    new Chart(areaCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(projectsAreaData),
            datasets: [{
                label: 'Proyectos',
                data: Object.values(projectsAreaData),
                backgroundColor: '#0d6efd',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Función para actualizar el estado del sistema
function refreshSystemHealth() {
    showLoader(true);
    
    fetch('/api/health')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateSystemHealthUI(data.checks);
                showAlert('Estado del sistema actualizado', 'success');
            } else {
                showAlert('Error al actualizar estado del sistema', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al conectar con el servidor', 'error');
        })
        .finally(() => {
            showLoader(false);
            updateLastUpdateTime();
        });
}

// Función para actualizar la UI del estado del sistema
function updateSystemHealthUI(checks) {
    Object.keys(checks).forEach(key => {
        const indicator = document.querySelector(`[data-health="${key}"] .health-indicator`);
        if (indicator) {
            indicator.className = `health-indicator bg-${checks[key].status === 'ok' ? 'success' : 'danger'}`;
        }
    });
}

// Función para actualizar el tiempo de última actualización
function updateLastUpdateTime() {
    const timeElement = document.getElementById('last-update-time');
    if (timeElement) {
        timeElement.textContent = new Date().toLocaleTimeString();
    }
}

// Función para iniciar actualizaciones en tiempo real
function startRealTimeUpdates() {
    // Actualizar cada 5 minutos
    setInterval(function() {
        refreshSystemHealth();
    }, 300000);
    
    // Actualizar tiempo cada segundo
    setInterval(updateLastUpdateTime, 1000);
}

// Función para exportar gráfico
function exportChart(chartId) {
    const canvas = document.getElementById(chartId);
    const url = canvas.toDataURL('image/png');
    
    const link = document.createElement('a');
    link.download = chartId + '_' + new Date().toISOString().slice(0, 10) + '.png';
    link.href = url;
    link.click();
}

// Función para ejecutar exportación
function executeExport() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    
    // Validar formulario
    if (!formData.get('report_type') || !formData.get('format')) {
        showAlert('Por favor completa todos los campos requeridos', 'warning');
        return;
    }
    
    showLoader(true);
    
    // Crear URL de descarga
    const params = new URLSearchParams(formData);
    const url = `/admin/reports/export?${params.toString()}`;
    
    // Crear descarga
    const link = document.createElement('a');
    link.href = url;
    link.download = '';
    link.click();
    
    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
    modal.hide();
    
    showLoader(false);
    showAlert('Exportación iniciada', 'success');
}
</script>

<style>
/* Estilos específicos del dashboard */
.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.activity-feed {
    max-height: 500px;
    overflow-y: auto;
}

.activity-section {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.activity-section:last-child {
    border-bottom: none;
}

.activity-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 0.75rem;
    padding: 0.5rem;
    border-radius: 0.375rem;
    transition: background-color 0.2s ease;
}

.activity-item:hover {
    background-color: #f8f9fa;
}

.activity-item:last-child {
    margin-bottom: 0;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.activity-content {
    flex-grow: 1;
    min-width: 0;
}

.activity-title {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.activity-title a {
    color: inherit;
}

.activity-title a:hover {
    color: #0d6efd;
}

.activity-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.system-health-items {
    space-y: 1rem;
}

.health-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.health-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.health-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.metric-item {
    padding: 0.5rem 0;
}

.quick-action-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    height: 100%;
}

.quick-action-card:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    color: inherit;
    text-decoration: none;
}

.quick-action-icon {
    width: 50px;
    height: 50px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.25rem;
}

.quick-action-content h6 {
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.quick-action-content p {
    font-size: 0.875rem;
    line-height: 1.3;
}

/* Animaciones */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(var(--bs-success-rgb), 0.7);
    }
    70% {
        box-shadow: 0 0 0 6px rgba(var(--bs-success-rgb), 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(var(--bs-success-rgb), 0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .quick-action-card {
        flex-direction: column;
        text-align: center;
    }
    
    .quick-action-icon {
        margin-right: 0;
        margin-bottom: 0.75rem;
    }
    
    .activity-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .activity-icon {
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .activity-item:hover {
        background-color: #2d3748;
    }
    
    .quick-action-card {
        border-color: #4a5568;
    }
    
    .quick-action-card:hover {
        border-color: #0d6efd;
        background-color: #2d3748;
    }
}
</style>

<?php include '../layouts/footer.php'; ?>