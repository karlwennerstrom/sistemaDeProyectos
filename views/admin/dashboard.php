<?php
// Configurar variables para el layout
$page_header = true;
$page_description = 'Panel de control de mis proyectos y documentos';
$additional_css = ['/public/assets/css/dashboard.css', '/public/assets/css/client.css'];
$additional_js = ['/public/assets/js/charts.js', '/public/assets/js/client-dashboard.js'];

$breadcrumb = [
    ['text' => 'Dashboard', 'url' => '/client/dashboard']
];

$page_actions = '
    <a href="/client/projects/new" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Proyecto
    </a>
';
?>

<?php include '../layouts/header.php'; ?>

<div class="container-fluid py-4">
    
    <!-- Saludo personalizado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="welcome-section bg-gradient-primary text-white rounded-3 p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="welcome-title mb-2">
                            ¡Bienvenido, <?= htmlspecialchars($current_user->first_name) ?>!
                        </h2>
                        <p class="welcome-subtitle mb-0 opacity-90">
                            Gestiona tus proyectos y da seguimiento a su proceso de aprobación
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="welcome-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?= $user_stats['total_projects'] ?></span>
                                <span class="stat-label">Proyectos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resumen de proyectos -->
    <div class="row mb-4">
        <!-- Proyectos Activos -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 project-card active">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Proyectos Activos</h6>
                            <h2 class="mb-0 text-primary"><?= $user_stats['active_projects'] ?></h2>
                            <small class="text-muted">
                                <i class="fas fa-play me-1"></i>
                                En progreso y revisión
                            </small>
                        </div>
                        <div class="icon-circle bg-primary bg-opacity-10">
                            <i class="fas fa-rocket text-primary"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="/client/projects?status=in_progress,under_review" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>Ver activos
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Borradores -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 project-card draft">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Borradores</h6>
                            <h2 class="mb-0 text-secondary"><?= $user_stats['draft_projects'] ?></h2>
                            <small class="text-muted">
                                <i class="fas fa-edit me-1"></i>
                                Pendientes de envío
                            </small>
                        </div>
                        <div class="icon-circle bg-secondary bg-opacity-10">
                            <i class="fas fa-file-alt text-secondary"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="/client/projects?status=draft" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye me-1"></i>Ver borradores
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Feedback Pendiente -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 project-card feedback">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Feedback Pendiente</h6>
                            <h2 class="mb-0 text-warning"><?= $user_stats['pending_feedback'] ?></h2>
                            <small class="text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Requiere atención
                            </small>
                        </div>
                        <div class="icon-circle bg-warning bg-opacity-10">
                            <i class="fas fa-comments text-warning"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="/client/feedback?status=open" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-eye me-1"></i>Ver feedback
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Proyectos Aprobados -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 project-card approved">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Aprobados</h6>
                            <h2 class="mb-0 text-success"><?= $user_stats['approved_projects'] ?></h2>
                            <small class="text-success">
                                <i class="fas fa-check-circle me-1"></i>
                                Completados
                            </small>
                        </div>
                        <div class="icon-circle bg-success bg-opacity-10">
                            <i class="fas fa-check text-success"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="/client/projects?status=approved" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-eye me-1"></i>Ver aprobados
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Proyectos recientes y progreso -->
    <div class="row mb-4">
        <!-- Mis Proyectos Recientes -->
        <div class="col-xl-8 col-lg-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Mis Proyectos Recientes</h5>
                        <a href="/client/projects" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list me-1"></i>Ver todos
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recent_projects)): ?>
                        <div class="projects-list">
                            <?php foreach ($recent_projects as $project): ?>
                                <div class="project-item">
                                    <div class="project-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="project-info">
                                                <h6 class="project-title">
                                                    <a href="/client/projects/<?= $project->id ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($project->name) ?>
                                                    </a>
                                                </h6>
                                                <p class="project-description text-muted mb-2">
                                                    <?= htmlspecialchars(substr($project->description, 0, 100)) ?>...
                                                </p>
                                                <div class="project-meta">
                                                    <span class="badge bg-<?= $project->status === 'approved' ? 'success' : ($project->status === 'rejected' ? 'danger' : ($project->status === 'under_review' ? 'warning' : 'secondary')) ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $project->status)) ?>
                                                    </span>
                                                    <small class="text-muted ms-2">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('d/m/Y', strtotime($project->updated_at)) ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="project-actions">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/client/projects/<?= $project->id ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($project->status === 'draft'): ?>
                                                        <a href="/client/projects/<?= $project->id ?>/edit" 
                                                           class="btn btn-outline-secondary" 
                                                           title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Progreso por áreas -->
                                        <div class="project-progress mt-3">
                                            <div class="row">
                                                <?php 
                                                $project_areas = json_decode($project->areas, true) ?? [];
                                                $progress_data = $project->getAreaProgress();
                                                ?>
                                                <?php foreach ($project_areas as $area): ?>
                                                    <?php 
                                                    $area_name = \UC\ApprovalSystem\Utils\Helper::getAreaName($area);
                                                    $area_progress = $progress_data[$area] ?? ['status' => 'pending', 'progress' => 0];
                                                    ?>
                                                    <div class="col-md-3 col-6 mb-2">
                                                        <div class="area-progress-item">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <small class="text-muted"><?= htmlspecialchars($area_name) ?></small>
                                                                <small class="progress-percentage"><?= $area_progress['progress'] ?>%</small>
                                                            </div>
                                                            <div class="progress" style="height: 4px;">
                                                                <div class="progress-bar bg-<?= $area_progress['status'] === 'approved' ? 'success' : ($area_progress['status'] === 'rejected' ? 'danger' : ($area_progress['status'] === 'in_review' ? 'warning' : 'secondary')) ?>" 
                                                                     style="width: <?= $area_progress['progress'] ?>%"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state text-center py-5">
                            <div class="empty-icon mb-3">
                                <i class="fas fa-project-diagram text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h6 class="text-muted">No tienes proyectos aún</h6>
                            <p class="text-muted mb-3">Crea tu primer proyecto para comenzar</p>
                            <a href="/client/projects/new" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Crear Proyecto
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Panel de acciones y tips -->
        <div class="col-xl-4 col-lg-12 mb-4">
            <!-- Acciones rápidas -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/client/projects/new" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nuevo Proyecto
                        </a>
                        <a href="/client/documents/upload" class="btn btn-outline-primary">
                            <i class="fas fa-upload me-2"></i>Subir Documento
                        </a>
                        <a href="/client/feedback" class="btn btn-outline-warning">
                            <i class="fas fa-comments me-2"></i>Ver Feedback
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Tips y guías -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Tips Útiles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="tips-list">
                        <div class="tip-item mb-3">
                            <div class="tip-icon">
                                <i class="fas fa-file-check text-success"></i>
                            </div>
                            <div class="tip-content">
                                <h6 class="tip-title">Documenta bien tu proyecto</h6>
                                <p class="tip-text text-muted mb-0">
                                    Asegúrate de incluir todos los documentos requeridos por cada área.
                                </p>
                            </div>
                        </div>
                        
                        <div class="tip-item mb-3">
                            <div class="tip-icon">
                                <i class="fas fa-clock text-info"></i>
                            </div>
                            <div class="tip-content">
                                <h6 class="tip-title">Responde el feedback rápido</h6>
                                <p class="tip-text text-muted mb-0">
                                    Atiende los comentarios de los revisores para acelerar la aprobación.
                                </p>
                            </div>
                        </div>
                        
                        <div class="tip-item">
                            <div class="tip-icon">
                                <i class="fas fa-users text-primary"></i>
                            </div>
                            <div class="tip-content">
                                <h6 class="tip-title">Comunícate con las áreas</h6>
                                <p class="tip-text text-muted mb-0">
                                    Mantén comunicación fluida con los responsables de cada área.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="/client/help/getting-started" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-book me-1"></i>Guía Completa
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estado por áreas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Estado de Proyectos por Área</h5>
                        <a href="/client/areas/progress" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-chart-line me-1"></i>Ver progreso completo
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        $areas = \UC\ApprovalSystem\Utils\Helper::getAreas();
                        foreach ($areas as $area_key => $area_name): 
                            $area_stats = $area_statistics[$area_key] ?? [
                                'total' => 0,
                                'pending' => 0,
                                'approved' => 0,
                                'rejected' => 0
                            ];
                        ?>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="area-card">
                                    <div class="area-header">
                                        <h6 class="area-name"><?= htmlspecialchars($area_name) ?></h6>
                                        <span class="area-total"><?= $area_stats['total'] ?> proyectos</span>
                                    </div>
                                    <div class="area-stats">
                                        <div class="stat-row">
                                            <span class="stat-label">Pendientes</span>
                                            <span class="stat-value text-warning"><?= $area_stats['pending'] ?></span>
                                        </div>
                                        <div class="stat-row">
                                            <span class="stat-label">Aprobados</span>
                                            <span class="stat-value text-success"><?= $area_stats['approved'] ?></span>
                                        </div>
                                        <div class="stat-row">
                                            <span class="stat-label">Rechazados</span>
                                            <span class="stat-value text-danger"><?= $area_stats['rejected'] ?></span>
                                        </div>
                                    </div>
                                    <?php if ($area_stats['total'] > 0): ?>
                                        <div class="area-progress mt-3">
                                            <?php $approval_rate = ($area_stats['approved'] / $area_stats['total']) * 100; ?>
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="text-muted">Tasa de Aprobación</small>
                                                <small class="text-muted"><?= round($approval_rate) ?>%</small>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: <?= $approval_rate ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="area-actions mt-3">
                                        <a href="/client/areas/<?= $area_key ?>" class="btn btn-sm btn-outline-primary w-100">
                                            Ver detalles
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notificaciones y alertas importantes -->
    <?php if (!empty($important_notifications)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bell text-warning me-2"></i>
                            Notificaciones Importantes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($important_notifications as $notification): ?>
                            <div class="notification-item alert alert-<?= $notification['type'] === 'urgent' ? 'danger' : ($notification['type'] === 'warning' ? 'warning' : 'info') ?> border-0">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon me-3">
                                        <i class="fas fa-<?= $notification['icon'] ?? 'info-circle' ?>"></i>
                                    </div>
                                    <div class="notification-content flex-grow-1">
                                        <h6 class="notification-title"><?= htmlspecialchars($notification['title']) ?></h6>
                                        <p class="notification-message mb-2"><?= htmlspecialchars($notification['message']) ?></p>
                                        <?php if (isset($notification['action_url'])): ?>
                                            <a href="<?= $notification['action_url'] ?>" class="btn btn-sm btn-<?= $notification['type'] === 'urgent' ? 'danger' : ($notification['type'] === 'warning' ? 'warning' : 'info') ?>">
                                                <?= htmlspecialchars($notification['action_text'] ?? 'Ver más') ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-time">
                                        <small class="text-muted"><?= date('d/m H:i', strtotime($notification['created_at'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Datos para gráficos del cliente
const userStats = <?= json_encode($user_stats) ?>;
const areaStats = <?= json_encode($area_statistics) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar actualizaciones automáticas
    startClientUpdates();
    
    // Inicializar tooltips para las barras de progreso
    initializeProgressTooltips();
});

// Función para iniciar actualizaciones del cliente
function startClientUpdates() {
    // Actualizar estadísticas cada 2 minutos
    setInterval(function() {
        updateClientStats();
    }, 120000);
}

// Función para actualizar estadísticas del cliente
function updateClientStats() {
    fetch('/api/client/dashboard-stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsCards(data.stats);
                updateNotifications(data.notifications);
            }
        })
        .catch(error => {
            console.warn('Error updating client stats:', error);
        });
}

// Función para actualizar las tarjetas de estadísticas
function updateStatsCards(stats) {
    // Actualizar contadores
    document.querySelector('[data-stat="active_projects"]').textContent = stats.active_projects;
    document.querySelector('[data-stat="draft_projects"]').textContent = stats.draft_projects;
    document.querySelector('[data-stat="pending_feedback"]').textContent = stats.pending_feedback;
    document.querySelector('[data-stat="approved_projects"]').textContent = stats.approved_projects;
}

// Función para actualizar notificaciones
function updateNotifications(notifications) {
    // Actualizar badge de notificaciones en el header
    const badge = document.getElementById('notification-count');
    const unreadCount = notifications.filter(n => !n.is_read).length;
    
    if (unreadCount > 0) {
        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }
}

// Función para inicializar tooltips de progreso
function initializeProgressTooltips() {
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const tooltip = new bootstrap.Tooltip(bar, {
            title: function() {
                return `Progreso: ${bar.style.width}`;
            }
        });
    });
}

// Función para mostrar/ocultar detalles de proyecto
function toggleProjectDetails(projectId) {
    const details = document.getElementById(`project-details-${projectId}`);
    if (details.style.display === 'none') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
}
</script>

<style>
/* Estilos específicos del dashboard de cliente */
.welcome-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.welcome-stats .stat-item {
    text-align: center;
}

.welcome-stats .stat-number {
    display: block;
    font-size: 2rem;
    font-weight: bold;
}

.welcome-stats .stat-label {
    display: block;
    font-size: 0.875rem;
    opacity: 0.9;
}

.project-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.project-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
}

.projects-list {
    max-height: 600px;
    overflow-y: auto;
}

.project-item {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s ease;
}

.project-item:hover {
    background-color: #f8f9fa;
}

.project-item:last-child {
    border-bottom: none;
}

.project-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.project-title a {
    color: inherit;
    transition: color 0.2s ease;
}

.project-title a:hover {
    color: #0d6efd;
}

.project-description {
    line-height: 1.5;
}

.project-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.area-progress-item {
    padding: 0.75rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border: 1px solid #e9ecef;
}

.progress-percentage {
    font-weight: 600;
    color: #495057;
}

.empty-state {
    padding: 3rem 1rem;
}

.empty-icon {
    opacity: 0.5;
}

.tips-list {
    space-y: 1rem;
}

.tip-item {
    display: flex;
    align-items: flex-start;
}

.tip-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.tip-content {
    flex-grow: 1;
}

.tip-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.tip-text {
    font-size: 0.8rem;
    line-height: 1.4;
}

.area-card {
    padding: 1.25rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    height: 100%;
    transition: all 0.2s ease;
}

.area-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.area-header {
    text-align: center;
    margin-bottom: 1rem;
}

.area-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #495057;
}

.area-total {
    font-size: 0.8rem;
    color: #6c757d;
}

.area-stats {
    space-y: 0.5rem;
}

.stat-row {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.25rem 0;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
}

.stat-value {
    font-weight: 600;
    font-size: 0.9rem;
}

.notification-item {
    margin-bottom: 1rem;
}

.notification-item:last-child {
    margin-bottom: 0;
}

.notification-icon {
    width: 40px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 0.25rem;
}

.notification-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.notification-message {
    line-height: 1.5;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .welcome-section {
        text-align: center;
    }
    
    .welcome-stats {
        margin-top: 1rem;
    }
    
    .project-actions {
        margin-top: 1rem;
    }
    
    .area-progress-item {
        margin-bottom: 0.5rem;
    }
    
    .tip-item {
        flex-direction: column;
        text-align: center;
    }
    
    .tip-icon {
        margin-right: 0;
        margin-bottom: 0.75rem;
        align-self: center;
    }
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.project-item {
    animation: fadeInUp 0.3s ease;
}

.area-card {
    animation: fadeInUp 0.3s ease;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .project-item:hover {
        background-color: #2d3748;
    }
    
    .area-progress-item {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .area-card {
        border-color: #4a5568;
    }
    
    .area-card:hover {
        background-color: #2d3748;
    }
}
</style>

<?php include '../layouts/footer.php'; ?>