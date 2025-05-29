<?php
// Obtener la ruta actual para marcar el elemento activo
$current_path = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim($current_path, '/'));
$current_section = $path_parts[1] ?? '';

// Función helper para verificar si un menú está activo
function isActive($path, $current_path) {
    return strpos($current_path, $path) === 0 ? 'active' : '';
}
?>

<!-- Dashboard -->
<li class="nav-item">
    <a class="nav-link <?= isActive('/admin/dashboard', $current_path) ?>" href="/admin/dashboard">
        <i class="fas fa-tachometer-alt me-1"></i>
        Dashboard
    </a>
</li>

<!-- Proyectos -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/admin/projects', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-project-diagram me-1"></i>
        Proyectos
        <?php 
        // Mostrar badge con proyectos pendientes
        $pending_projects = \UC\ApprovalSystem\Models\Project::count(['status' => 'under_review']);
        if ($pending_projects > 0): 
        ?>
            <span class="badge bg-warning text-dark ms-1"><?= $pending_projects ?></span>
        <?php endif; ?>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/admin/projects">
            <i class="fas fa-list me-2"></i>Todos los Proyectos
        </a></li>
        <li><a class="dropdown-item" href="/admin/projects?status=under_review">
            <i class="fas fa-clock me-2"></i>Pendientes de Revisión
            <?php if ($pending_projects > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $pending_projects ?></span>
            <?php endif; ?>
        </a></li>
        <li><a class="dropdown-item" href="/admin/projects?status=in_progress">
            <i class="fas fa-play me-2"></i>En Progreso
        </a></li>
        <li><a class="dropdown-item" href="/admin/projects?status=approved">
            <i class="fas fa-check me-2"></i>Aprobados
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/admin/projects/stats">
            <i class="fas fa-chart-bar me-2"></i>Estadísticas
        </a></li>
    </ul>
</li>

<!-- Documentos -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/admin/documents', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-file-alt me-1"></i>
        Documentos
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/admin/documents">
            <i class="fas fa-folder me-2"></i>Todos los Documentos
        </a></li>
        <li><a class="dropdown-item" href="/admin/documents?status=under_review">
            <i class="fas fa-search me-2"></i>Pendientes de Revisión
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/admin/document-templates">
            <i class="fas fa-copy me-2"></i>Plantillas
        </a></li>
        <li><a class="dropdown-item" href="/admin/document-templates/new">
            <i class="fas fa-plus me-2"></i>Nueva Plantilla
        </a></li>
    </ul>
</li>

<!-- Usuarios -->
<?php if ($current_user->role === 'admin'): ?>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/admin/users', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-users me-1"></i>
        Usuarios
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/admin/users">
            <i class="fas fa-list me-2"></i>Todos los Usuarios
        </a></li>
        <li><a class="dropdown-item" href="/admin/users/new">
            <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/admin/users?role=admin">
            <i class="fas fa-user-shield me-2"></i>Administradores
        </a></li>
        <li><a class="dropdown-item" href="/admin/users?role=area_admin">
            <i class="fas fa-user-cog me-2"></i>Administradores de Área
        </a></li>
        <li><a class="dropdown-item" href="/admin/users?role=reviewer">
            <i class="fas fa-user-check me-2"></i>Revisores
        </a></li>
        <li><a class="dropdown-item" href="/admin/users?role=client">
            <i class="fas fa-user me-2"></i>Clientes
        </a></li>
    </ul>
</li>
<?php endif; ?>

<!-- Áreas del Sistema -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/admin/areas', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-sitemap me-1"></i>
        Áreas
    </a>
    <ul class="dropdown-menu">
        <?php 
        $areas = \UC\ApprovalSystem\Utils\Helper::getAreas();
        foreach ($areas as $area_key => $area_name): 
        ?>
            <li><a class="dropdown-item" href="/admin/areas/<?= $area_key ?>">
                <i class="fas fa-cube me-2"></i><?= htmlspecialchars($area_name) ?>
            </a></li>
        <?php endforeach; ?>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/admin/areas/overview">
            <i class="fas fa-chart-pie me-2"></i>Vista General
        </a></li>
    </ul>
</li>

<!-- Feedback y Revisiones -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/admin/feedback', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-comments me-1"></i>
        Feedback
        <?php 
        $critical_feedback = \UC\ApprovalSystem\Models\ProjectFeedback::count([
            'status' => 'open',
            'priority' => 'critical'
        ]);
        if ($critical_feedback > 0): 
        ?>
            <span class="badge bg-danger ms-1"><?= $critical_feedback ?></span>
        <?php endif; ?>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/admin/feedback">
            <i class="fas fa-list me-2"></i>Todo el Feedback
        </a></li>
        <li><a class="dropdown-item" href="/admin/feedback?status=open">
            <i class="fas fa-exclamation-circle me-2"></i>Abierto
        </a></li>
        <li><a class="dropdown-item" href="/admin/feedback?priority=critical">
            <i class="fas fa-fire me-2"></i>Crítico
            <?php if ($critical_feedback > 0): ?>
                <span class="badge bg-danger ms-1"><?= $critical_feedback ?></span>
            <?php endif; ?>
        </a></li>
        <li><a class="dropdown-item" href="/admin/feedback?priority=high">
            <i class="fas fa-arrow-up me-2"></i>Alta Prioridad
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/admin/feedback/stats">
            <i class="fas fa-analytics me-2"></i>Estadísticas
        </a></li>
    </ul>
</li>

<!-- Reportes -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/admin/reports', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-chart-line me-1"></i>
        Reportes
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/admin/reports">
            <i class="fas fa-chart-bar me-2"></i>Dashboard de Reportes
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/admin/reports?type=projects">
            <i class="fas fa-project-diagram me-2"></i>Reporte de Proyectos
        </a></li>
        <li><a class="dropdown-item" href="/admin/reports?type=users">
            <i class="fas fa-users me-2"></i>Reporte de Usuarios
        </a></li>
        <li><a class="dropdown-item" href="/admin/reports?type=documents">
            <i class="fas fa-file-alt me-2"></i>Reporte de Documentos
        </a></li>
        <li><a class="dropdown-item" href="/admin/reports?type=performance">
            <i class="fas fa-tachometer-alt me-2"></i>Reporte de Rendimiento
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/admin/reports/export">
            <i class="fas fa-download me-2"></i>Exportar Datos
        </a></li>
    </ul>
</li>

<!-- Sistema -->
<?php if ($current_user->role === 'admin'): ?>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/admin/system', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-cogs me-1"></i>
        Sistema
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/admin/settings">
            <i class="fas fa-cog me-2"></i>Configuración
        </a></li>
        <li><a class="dropdown-item" href="/admin/system/health">
            <i class="fas fa-heartbeat me-2"></i>Estado del Sistema
        </a></li>
        <li><a class="dropdown-item" href="/admin/system/logs">
            <i class="fas fa-file-text me-2"></i>Logs del Sistema
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/admin/system/cleanup">
            <i class="fas fa-broom me-2"></i>Limpieza del Sistema
        </a></li>
        <li><a class="dropdown-item" href="/admin/system/backup">
            <i class="fas fa-database me-2"></i>Respaldos
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/admin/system/maintenance">
            <i class="fas fa-tools me-2"></i>Modo Mantenimiento
        </a></li>
    </ul>
</li>
<?php endif; ?>

<!-- Vista rápida del estado del sistema -->
<?php if ($current_user->role === 'admin'): ?>
<li class="nav-item">
    <a class="nav-link position-relative" href="/admin/system/health" title="Estado del Sistema">
        <i class="fas fa-heartbeat me-1"></i>
        <span class="system-health-indicator position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle">
            <span class="visually-hidden">Sistema saludable</span>
        </span>
    </a>
</li>
<?php endif; ?>

<style>
/* Estilos adicionales para la navegación de admin */
.nav-link.active {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 0.375rem;
}

.dropdown-menu {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border: none;
}

.dropdown-item {
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: rgba(0, 123, 255, 0.1);
    transform: translateX(2px);
}

.system-health-indicator {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
    }
    
    70% {
        transform: scale(1);
        box-shadow: 0 0 0 6px rgba(40, 167, 69, 0);
    }
    
    100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
    }
}

/* Badge styling */
.badge {
    font-size: 0.65em;
    font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dropdown-item {
        padding: 0.75rem 1rem;
    }
    
    .dropdown-item i {
        width: 20px;
    }
}
</style>