<?php
// Configurar variables para el layout
$page_header = true;
$page_description = 'Gestión completa de proyectos del sistema';
$additional_css = ['/public/assets/css/datatables.css', '/public/assets/css/projects.css'];
$additional_js = ['/public/assets/js/datatables.js', '/public/assets/js/project-management.js'];

$breadcrumb = [
    ['text' => 'Administración', 'url' => '/admin'],
    ['text' => 'Proyectos', 'url' => '/admin/projects']
];

$page_actions = '
    <div class="btn-group">
        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-plus me-1"></i>Acciones
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#" onclick="exportProjects()">
                <i class="fas fa-download me-2"></i>Exportar Proyectos
            </a></li>
            <li><a class="dropdown-item" href="#" onclick="generateReport()">
                <i class="fas fa-chart-bar me-2"></i>Generar Reporte
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" onclick="bulkActions()">
                <i class="fas fa-cog me-2"></i>Acciones en Lote
            </a></li>
        </ul>
    </div>
';
?>

<?php include '../layouts/header.php'; ?>

<div class="container-fluid py-4">
    
    <!-- Estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary bg-opacity-10">
                    <i class="fas fa-project-diagram text-primary"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number"><?= number_format($project_stats['total']) ?></h4>
                    <p class="stat-label">Total</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-info bg-opacity-10">
                    <i class="fas fa-play text-info"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number"><?= number_format($project_stats['in_progress']) ?></h4>
                    <p class="stat-label">En Progreso</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning bg-opacity-10">
                    <i class="fas fa-clock text-warning"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number"><?= number_format($project_stats['under_review']) ?></h4>
                    <p class="stat-label">En Revisión</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-success bg-opacity-10">
                    <i class="fas fa-check text-success"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number"><?= number_format($project_stats['approved']) ?></h4>
                    <p class="stat-label">Aprobados</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger bg-opacity-10">
                    <i class="fas fa-times text-danger"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number"><?= number_format($project_stats['rejected']) ?></h4>
                    <p class="stat-label">Rechazados</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-secondary bg-opacity-10">
                    <i class="fas fa-edit text-secondary"></i>
                </div>
                <div class="stat-content">
                    <h4 class="stat-number"><?= number_format($project_stats['draft']) ?></h4>
                    <p class="stat-label">Borradores</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros avanzados -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-filter me-2"></i>Filtros
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllFilters()">
                            <i class="fas fa-times me-1"></i>Limpiar Filtros
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Buscar</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="Nombre, descripción, ID..."
                                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="status">
                                <option value="">Todos los estados</option>
                                <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>
                                    Borrador
                                </option>
                                <option value="in_progress" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>
                                    En Progreso
                                </option>
                                <option value="under_review" <?= ($filters['status'] ?? '') === 'under_review' ? 'selected' : '' ?>>
                                    En Revisión
                                </option>
                                <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>
                                    Aprobado
                                </option>
                                <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>
                                    Rechazado
                                </option>
                                <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>
                                    Cancelado
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Área</label>
                            <select class="form-select" name="area">
                                <option value="">Todas las áreas</option>
                                <?php foreach ($areas as $area_key => $area_name): ?>
                                    <option value="<?= $area_key ?>" 
                                            <?= ($filters['area'] ?? '') === $area_key ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($area_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Propietario</label>
                            <select class="form-select" name="user_id">
                                <option value="">Todos los usuarios</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user->id ?>" 
                                            <?= ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Fecha</label>
                            <select class="form-select" name="date_filter">
                                <option value="">Cualquier fecha</option>
                                <option value="today" <?= ($filters['date_filter'] ?? '') === 'today' ? 'selected' : '' ?>>
                                    Hoy
                                </option>
                                <option value="week" <?= ($filters['date_filter'] ?? '') === 'week' ? 'selected' : '' ?>>
                                    Esta semana
                                </option>
                                <option value="month" <?= ($filters['date_filter'] ?? '') === 'month' ? 'selected' : '' ?>>
                                    Este mes
                                </option>
                                <option value="quarter" <?= ($filters['date_filter'] ?? '') === 'quarter' ? 'selected' : '' ?>>
                                    Este trimestre
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla de proyectos -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            Lista de Proyectos
                            <span class="badge bg-secondary ms-2"><?= number_format($total_projects) ?> total</span>
                        </h5>
                        <div class="card-actions">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" onclick="selectAllProjects()">
                                    <i class="fas fa-check-square me-1"></i>Seleccionar
                                </button>
                                <button type="button" class="btn btn-outline-primary" onclick="refreshProjects()">
                                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                                </button>
                                <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-eye me-1"></i>Vista
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="changeView('table')">
                                        <i class="fas fa-table me-2"></i>Tabla
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="changeView('cards')">
                                        <i class="fas fa-th-large me-2"></i>Tarjetas
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="changeView('timeline')">
                                        <i class="fas fa-timeline me-2"></i>Timeline
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    
                    <!-- Vista de tabla -->
                    <div id="tableView" class="table-view">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="projectsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                            </div>
                                        </th>
                                        <th>Proyecto</th>
                                        <th>Propietario</th>
                                        <th>Estado</th>
                                        <th>Progreso</th>
                                        <th>Áreas</th>
                                        <th>Última Actividad</th>
                                        <th width="140">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($projects)): ?>
                                        <?php foreach ($projects as $project): ?>
                                            <tr data-project-id="<?= $project->id ?>" class="project-row">
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input project-checkbox" 
                                                               type="checkbox" 
                                                               value="<?= $project->id ?>"
                                                               onchange="updateBulkActions()">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="project-info">
                                                        <div class="project-title">
                                                            <a href="/admin/projects/<?= $project->id ?>" 
                                                               class="text-decoration-none fw-semibold">
                                                                <?= htmlspecialchars($project->name) ?>
                                                            </a>
                                                            <span class="project-id text-muted ms-2">#<?= $project->id ?></span>
                                                        </div>
                                                        <div class="project-description text-muted">
                                                            <?= htmlspecialchars(substr($project->description, 0, 80)) ?><?= strlen($project->description) > 80 ? '...' : '' ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="user-name"><?= htmlspecialchars($project->user_name) ?></div>
                                                        <div class="user-email text-muted small"><?= htmlspecialchars($project->user_email) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge status-badge bg-<?= getStatusColor($project->status) ?>">
                                                        <i class="fas fa-<?= getStatusIcon($project->status) ?> me-1"></i>
                                                        <?= ucfirst(str_replace('_', ' ', $project->status)) ?>
                                                    </span>
                                                    <?php if ($project->status === 'under_review'): ?>
                                                        <?php 
                                                        $days_in_review = floor((time() - strtotime($project->status_changed_at)) / 86400);
                                                        if ($days_in_review > 7): 
                                                        ?>
                                                            <div class="text-warning small">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                                <?= $days_in_review ?> días
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $progress = $project->getOverallProgress();
                                                    $progress_color = $progress < 30 ? 'danger' : ($progress < 70 ? 'warning' : 'success');
                                                    ?>
                                                    <div class="progress-container">
                                                        <div class="progress" style="height: 8px;">
                                                            <div class="progress-bar bg-<?= $progress_color ?>" 
                                                                 style="width: <?= $progress ?>%"
                                                                 data-bs-toggle="tooltip" 
                                                                 title="<?= $progress ?>% completado"></div>
                                                        </div>
                                                        <small class="text-muted"><?= $progress ?>%</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="areas-list">
                                                        <?php 
                                                        $project_areas = json_decode($project->areas, true) ?? [];
                                                        $visible_areas = array_slice($project_areas, 0, 2);
                                                        $remaining_areas = count($project_areas) - 2;
                                                        ?>
                                                        <?php foreach ($visible_areas as $area): ?>
                                                            <span class="badge bg-light text-dark me-1 mb-1">
                                                                <?= htmlspecialchars($areas[$area] ?? $area) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                        <?php if ($remaining_areas > 0): ?>
                                                            <span class="badge bg-secondary me-1 mb-1" 
                                                                  data-bs-toggle="tooltip" 
                                                                  title="<?= implode(', ', array_map(function($area) use ($areas) { 
                                                                      return $areas[$area] ?? $area; 
                                                                  }, array_slice($project_areas, 2))) ?>">
                                                                +<?= $remaining_areas ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="activity-info">
                                                        <div class="activity-time">
                                                            <?= \UC\ApprovalSystem\Utils\Helper::timeAgo($project->updated_at) ?>
                                                        </div>
                                                        <div class="activity-action text-muted small">
                                                            <?= getLastActivityText($project) ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="/admin/projects/<?= $project->id ?>" 
                                                           class="btn btn-outline-primary" 
                                                           title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-outline-info dropdown-toggle dropdown-toggle-split" 
                                                                data-bs-toggle="dropdown">
                                                            <span class="visually-hidden">Más opciones</span>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="/admin/projects/<?= $project->id ?>/edit">
                                                                    <i class="fas fa-edit me-2"></i>Editar
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="/admin/projects/<?= $project->id ?>/documents">
                                                                    <i class="fas fa-file-alt me-2"></i>Documentos
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="/admin/projects/<?= $project->id ?>/feedback">
                                                                    <i class="fas fa-comments me-2"></i>Feedback
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="changeProjectStatus(<?= $project->id ?>, '<?= $project->status ?>')">
                                                                    <i class="fas fa-exchange-alt me-2"></i>Cambiar Estado
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="assignProject(<?= $project->id ?>)">
                                                                    <i class="fas fa-user-plus me-2"></i>Asignar Usuario
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="duplicateProject(<?= $project->id ?>)">
                                                                    <i class="fas fa-copy me-2"></i>Duplicar
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" 
                                                                   href="#" 
                                                                   onclick="deleteProject(<?= $project->id ?>, '<?= htmlspecialchars($project->name) ?>')">
                                                                    <i class="fas fa-trash me-2"></i>Eliminar
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="fas fa-project-diagram text-muted mb-3" style="font-size: 3rem;"></i>
                                                    <h6 class="text-muted">No se encontraron proyectos</h6>
                                                    <p class="text-muted">Ajusta los filtros para ver diferentes proyectos</p>
                                                    <button class="btn btn-outline-primary" onclick="clearAllFilters()">
                                                        <i class="fas fa-times me-1"></i>Limpiar Filtros
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Vista de tarjetas (oculta por defecto) -->
                    <div id="cardsView" class="cards-view d-none">
                        <div class="row">
                            <?php foreach ($projects as $project): ?>
                                <div class="col-xl-4 col-lg-6 mb-4">
                                    <div class="project-card">
                                        <div class="project-card-header">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="project-card-title">
                                                    <h6 class="mb-1">
                                                        <a href="/admin/projects/<?= $project->id ?>" class="text-decoration-none">
                                                            <?= htmlspecialchars($project->name) ?>
                                                        </a>
                                                    </h6>
                                                    <small class="text-muted">#<?= $project->id ?></small>
                                                </div>
                                                <span class="badge bg-<?= getStatusColor($project->status) ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $project->status)) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="project-card-body">
                                            <p class="project-card-description">
                                                <?= htmlspecialchars(substr($project->description, 0, 120)) ?><?= strlen($project->description) > 120 ? '...' : '' ?>
                                            </p>
                                            <div class="project-card-meta">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <small class="text-muted">Progreso</small>
                                                    <small class="fw-bold"><?= $project->getOverallProgress() ?>%</small>
                                                </div>
                                                <div class="progress mb-3" style="height: 6px;">
                                                    <div class="progress-bar bg-<?= $project->getOverallProgress() < 30 ? 'danger' : ($project->getOverallProgress() < 70 ? 'warning' : 'success') ?>" 
                                                         style="width: <?= $project->getOverallProgress() ?>%"></div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="project-owner">
                                                        <small class="text-muted">Por <?= htmlspecialchars($project->user_name) ?></small>
                                                    </div>
                                                    <div class="project-date">
                                                        <small class="text-muted"><?= \UC\ApprovalSystem\Utils\Helper::timeAgo($project->updated_at) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="project-card-actions">
                                            <div class="btn-group btn-group-sm w-100">
                                                <a href="/admin/projects/<?= $project->id ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>Ver
                                                </a>
                                                <a href="/admin/projects/<?= $project->id ?>/edit" class="btn btn-outline-secondary">
                                                    <i class="fas fa-edit me-1"></i>Editar
                                                </a>
                                                <button type="button" class="btn btn-outline-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                                    <span class="visually-hidden">Más</span>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="/admin/projects/<?= $project->id ?>/documents">
                                                        <i class="fas fa-file-alt me-2"></i>Documentos
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="/admin/projects/<?= $project->id ?>/feedback">
                                                        <i class="fas fa-comments me-2"></i>Feedback
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-transparent border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info">
                                <small class="text-muted">
                                    Mostrando <?= count($projects) ?> de <?= number_format($total_projects) ?> proyectos
                                </small>
                            </div>
                            <nav aria-label="Paginación de proyectos">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($current_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $current_page - 1 ?><?= http_build_query($filters) ? '&' . http_build_query($filters) : '' ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    ?>
                                    
                                    <?php if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1<?= http_build_query($filters) ? '&' . http_build_query($filters) : '' ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= http_build_query($filters) ? '&' . http_build_query($filters) : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $total_pages ?><?= http_build_query($filters) ? '&' . http_build_query($filters) : '' ?>">
                                                <?= $total_pages ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($current_page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $current_page + 1 ?><?= http_build_query($filters) ? '&' . http_build_query($filters) : '' ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar estado -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado del Proyecto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changeStatusForm">
                    <input type="hidden" id="projectId" name="project_id">
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">Nuevo Estado</label>
                        <select class="form-select" id="newStatus" name="status" required>
                            <option value="">Seleccionar estado</option>
                            <option value="draft">Borrador</option>
                            <option value="in_progress">En Progreso</option>
                            <option value="under_review">En Revisión</option>
                            <option value="approved">Aprobado</option>
                            <option value="rejected">Rechazado</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="statusReason" class="form-label">Motivo del Cambio</label>
                        <textarea class="form-control" id="statusReason" name="reason" rows="3" 
                                  placeholder="Explica el motivo del cambio de estado..."></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifyUser" name="notify_user" checked>
                        <label class="form-check-label" for="notifyUser">
                            Notificar al propietario del proyecto
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="executeStatusChange()">
                    <i class="fas fa-save me-1"></i>Cambiar Estado
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para acciones en lote -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acciones en Lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Has seleccionado <span id="selectedProjectsCount">0</span> proyecto(s).</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-info" onclick="bulkChangeStatus()">
                        <i class="fas fa-exchange-alt me-2"></i>Cambiar Estado
                    </button>
                    <button type="button" class="btn btn-warning" onclick="bulkAssignUser()">
                        <i class="fas fa-user-plus me-2"></i>Asignar Usuario
                    </button>
                    <button type="button" class="btn btn-success" onclick="bulkExport()">
                        <i class="fas fa-download me-2"></i>Exportar Seleccionados
                    </button>
                    <button type="button" class="btn btn-danger" onclick="bulkDelete()">
                        <i class="fas fa-trash me-2"></i>Eliminar Proyectos
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para asignar usuario -->
<div class="modal fade" id="assignUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asignar Usuario al Proyecto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignUserForm">
                    <input type="hidden" id="assignProjectId" name="project_id">
                    <div class="mb-3">
                        <label for="assignUserId" class="form-label">Seleccionar Usuario</label>
                        <select class="form-select" id="assignUserId" name="user_id" required>
                            <option value="">Seleccionar usuario</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user->id ?>">
                                    <?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?> 
                                    (<?= htmlspecialchars($user->email) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assignReason" class="form-label">Motivo de la Asignación</label>
                        <textarea class="form-control" id="assignReason" name="reason" rows="3" 
                                  placeholder="Explica el motivo de la asignación..."></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifyNewUser" name="notify_new_user" checked>
                        <label class="form-check-label" for="notifyNewUser">
                            Notificar al nuevo usuario asignado
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifyOldUser" name="notify_old_user" checked>
                        <label class="form-check-label" for="notifyOldUser">
                            Notificar al usuario anterior
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="executeUserAssignment()">
                    <i class="fas fa-save me-1"></i>Asignar Usuario
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

function getLastActivityText($project) {
    // Esta función debería obtener la última actividad real del proyecto
    // Por ahora, simulamos basándonos en el estado
    switch ($project->status) {
        case 'draft': return 'Proyecto creado';
        case 'in_progress': return 'En desarrollo';
        case 'under_review': return 'Enviado a revisión';
        case 'approved': return 'Proyecto aprobado';
        case 'rejected': return 'Proyecto rechazado';
        case 'cancelled': return 'Proyecto cancelado';
        default: return 'Actualizado';
    }
}
?>

<script>
// Variables globales
let selectedProjects = [];
let currentView = 'table';

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeProjectsPage();
    setupAutoFilters();
    initializeTooltips();
});

function initializeProjectsPage() {
    // Configurar DataTables si está disponible
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#projectsTable').DataTable({
            pageLength: 25,
            order: [[7, 'desc']], // Ordenar por última actividad
            columnDefs: [
                { orderable: false, targets: [0, 6, 7] } // Checkboxes y acciones no ordenables
            ],
            language: {
                url: '/public/assets/js/datatables-es.json'
            }
        });
    }
    
    // Configurar actualización automática cada 30 segundos
    setInterval(function() {
        updateProjectCounts();
    }, 30000);
}

function setupAutoFilters() {
    const filterForm = document.getElementById('filterForm');
    const filterInputs = filterForm.querySelectorAll('select');
    
    // Aplicar filtros automáticamente al cambiar selects
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Para búsqueda, esperar un poco antes de filtrar
    const searchInput = filterForm.querySelector('[name="search"]');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterForm.submit();
        }, 500);
    });
}

function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Funciones de selección
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const projectCheckboxes = document.querySelectorAll('.project-checkbox');
    
    projectCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const projectCheckboxes = document.querySelectorAll('.project-checkbox:checked');
    selectedProjects = Array.from(projectCheckboxes).map(cb => cb.value);
    
    // Actualizar UI según selección
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const totalCheckboxes = document.querySelectorAll('.project-checkbox').length;
    const checkedCheckboxes = selectedProjects.length;
    
    if (checkedCheckboxes === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (checkedCheckboxes === totalCheckboxes) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
    }
}

function selectAllProjects() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    selectAllCheckbox.checked = true;
    toggleSelectAll();
}

// Funciones de vista
function changeView(viewType) {
    const tableView = document.getElementById('tableView');
    const cardsView = document.getElementById('cardsView');
    
    currentView = viewType;
    
    switch (viewType) {
        case 'table':
            tableView.classList.remove('d-none');
            cardsView.classList.add('d-none');
            break;
        case 'cards':
            tableView.classList.add('d-none');
            cardsView.classList.remove('d-none');
            break;
        case 'timeline':
            // Implementar vista timeline
            showAlert('Vista timeline en desarrollo', 'info');
            break;
    }
    
    // Guardar preferencia en localStorage
    localStorage.setItem('projects_view', viewType);
}

// Funciones de acciones individuales
function changeProjectStatus(projectId, currentStatus) {
    document.getElementById('projectId').value = projectId;
    document.getElementById('newStatus').value = '';
    document.getElementById('statusReason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
    modal.show();
}

function executeStatusChange() {
    const form = document.getElementById('changeStatusForm');
    const formData = new FormData(form);
    
    if (!formData.get('status')) {
        showAlert('Selecciona un nuevo estado', 'warning');
        return;
    }
    
    showLoader(true);
    
    fetch('/admin/projects/change-status', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Estado cambiado correctamente', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('changeStatusModal'));
            modal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Error al cambiar estado', 'error');
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

function assignProject(projectId) {
    document.getElementById('assignProjectId').value = projectId;
    document.getElementById('assignUserId').value = '';
    document.getElementById('assignReason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('assignUserModal'));
    modal.show();
}

function executeUserAssignment() {
    const form = document.getElementById('assignUserForm');
    const formData = new FormData(form);
    
    if (!formData.get('user_id')) {
        showAlert('Selecciona un usuario', 'warning');
        return;
    }
    
    showLoader(true);
    
    fetch('/admin/projects/assign-user', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Usuario asignado correctamente', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('assignUserModal'));
            modal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Error al asignar usuario', 'error');
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
        
        fetch(`/admin/projects/${projectId}/duplicate`, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Proyecto duplicado correctamente', 'success');
                setTimeout(() => location.reload(), 1000);
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
        
        fetch(`/admin/projects/${projectId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Proyecto eliminado correctamente', 'success');
                document.querySelector(`tr[data-project-id="${projectId}"]`).remove();
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

// Funciones de acciones en lote
function bulkActions() {
    if (selectedProjects.length === 0) {
        showAlert('Selecciona al menos un proyecto', 'warning');
        return;
    }
    
    document.getElementById('selectedProjectsCount').textContent = selectedProjects.length;
    const modal = new bootstrap.Modal(document.getElementById('bulkActionsModal'));
    modal.show();
}

function bulkChangeStatus() {
    showAlert('Cambio de estado en lote en desarrollo', 'info');
}

function bulkAssignUser() {
    showAlert('Asignación de usuario en lote en desarrollo', 'info');
}

function bulkExport() {
    if (selectedProjects.length === 0) return;
    
    const params = new URLSearchParams();
    params.set('export', 'csv');
    params.set('project_ids', selectedProjects.join(','));
    
    const link = document.createElement('a');
    link.href = `/admin/projects/export?${params.toString()}`;
    link.download = '';
    link.click();
    
    showAlert('Exportación iniciada', 'success');
}

function bulkDelete() {
    if (confirm(`¿Estás seguro de eliminar ${selectedProjects.length} proyecto(s)?\n\nEsta acción no se puede deshacer.`)) {
        showLoader(true);
        
        fetch('/admin/projects/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
            },
            body: JSON.stringify({
                project_ids: selectedProjects
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Proyectos eliminados correctamente', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('bulkActionsModal'));
                modal.hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(data.message || 'Error al eliminar proyectos', 'error');
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

// Funciones de utilidad
function refreshProjects() {
    showLoader(true);
    location.reload();
}

function clearAllFilters() {
    const form = document.getElementById('filterForm');
    form.reset();
    window.location.href = '/admin/projects';
}

function exportProjects() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    const link = document.createElement('a');
    link.href = `/admin/projects/export?${params.toString()}`;
    link.download = '';
    link.click();
    
    showAlert('Exportación iniciada', 'success');
}

function generateReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('type', 'projects');
    
    window.open(`/admin/reports?${params.toString()}`, '_blank');
}

function updateProjectCounts() {
    fetch('/api/admin/project-stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar contadores en las tarjetas de estadísticas
                Object.keys(data.stats).forEach(status => {
                    const element = document.querySelector(`[data-stat="${status}"]`);
                    if (element) {
                        element.textContent = number_format(data.stats[status]);
                    }
                });
            }
        })
        .catch(error => {
            console.warn('Error updating project counts:', error);
        });
}

// Función auxiliar para formatear números
function number_format(number) {
    return new Intl.NumberFormat().format(number);
}

// Restaurar vista preferida
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('projects_view');
    if (savedView && savedView !== 'table') {
        changeView(savedView);
    }
});
</script>

<style>
/* Estilos específicos para la lista de proyectos */
.stat-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 1.25rem;
}

.stat-content {
    flex-grow: 1;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.project-info .project-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.project-info .project-id {
    font-size: 0.8rem;
    font-weight: normal;
}

.project-info .project-description {
    font-size: 0.875rem;
    line-height: 1.3;
}

.user-info .user-name {
    font-weight: 500;
    margin-bottom: 0.125rem;
}

.user-info .user-email {
    font-size: 0.8rem;
}

.status-badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.375rem 0.75rem;
}

.progress-container {
    min-width: 80px;
}

.progress-container .progress {
    margin-bottom: 0.25rem;
}

.areas-list .badge {
    font-size: 0.7rem;
    font-weight: 500;
}

.activity-info .activity-time {
    font-weight: 500;
    font-size: 0.875rem;
}

.activity-info .activity-action {
    font-size: 0.8rem;
}

.project-card {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    background: white;
    transition: all 0.2s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.project-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.project-card-header {
    padding: 1rem 1rem 0.5rem;
}

.project-card-title h6 {
    margin-bottom: 0.25rem;
}

.project-card-title a {
    color: #495057;
    font-weight: 600;
}

.project-card-title a:hover {
    color: #0d6efd;
}

.project-card-body {
    padding: 0.5rem 1rem;
    flex-grow: 1;
}

.project-card-description {
    color: #6c757d;
    font-size: 0.875rem;
    line-height: 1.4;
    margin-bottom: 1rem;
}

.project-card-meta {
    margin-top: auto;
}

.project-card-actions {
    padding: 0.75rem 1rem 1rem;
    border-top: 1px solid #e9ecef;
    margin-top: auto;
}

.empty-state {
    padding: 3rem 1rem;
    text-align: center;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stat-card {
        margin-bottom: 0.75rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
        margin-right: 0.5rem;
    }
    
    .stat-number {
        font-size: 1.25rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.4rem;
    }
    
    .project-card {
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .stat-icon {
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
    
    .card-actions {
        margin-top: 1rem;
    }
    
    .btn-group {
        width: 100%;
    }
    
    .btn-group .btn {
        flex: 1;
    }
}

/* Animaciones */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.project-row {
    animation: fadeIn 0.3s ease;
}

.project-card {
    animation: fadeIn 0.3s ease;
}

/* Estados de hover mejorados */
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.dropdown-item:hover {
    background-color: rgba(0, 123, 255, 0.1);
    transform: translateX(2px);
}

/* Mejoras de accesibilidad */
.btn:focus,
.form-control:focus,
.form-select:focus {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

.dropdown-item:focus {
    outline: 2px solid #0d6efd;
    outline-offset: -2px;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .stat-card {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .project-card {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .project-card:hover {
        background-color: #4a5568;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
}
</style>

<?php include '../layouts/footer.php'; ?>