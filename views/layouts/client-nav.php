<?php
// Obtener la ruta actual para marcar el elemento activo
$current_path = $_SERVER['REQUEST_URI'];

// Función helper para verificar si un menú está activo
function isActive($path, $current_path) {
    return strpos($current_path, $path) === 0 ? 'active' : '';
}

// Obtener estadísticas del usuario actual
$user_stats = [
    'active_projects' => \UC\ApprovalSystem\Models\Project::count([
        'user_id' => $current_user->id,
        'status' => ['in_progress', 'under_review']
    ]),
    'pending_feedback' => \UC\ApprovalSystem\Models\ProjectFeedback::count([
        'project_user_id' => $current_user->id,
        'status' => 'open',
        'type' => ['requirement', 'error']
    ])
];
?>

<!-- Dashboard -->
<li class="nav-item">
    <a class="nav-link <?= isActive('/client/dashboard', $current_path) ?>" href="/client/dashboard">
        <i class="fas fa-tachometer-alt me-1"></i>
        Dashboard
    </a>
</li>

<!-- Mis Proyectos -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/client/projects', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-project-diagram me-1"></i>
        Mis Proyectos
        <?php if ($user_stats['active_projects'] > 0): ?>
            <span class="badge bg-primary ms-1"><?= $user_stats['active_projects'] ?></span>
        <?php endif; ?>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/client/projects">
            <i class="fas fa-list me-2"></i>Todos mis Proyectos
        </a></li>
        <li><a class="dropdown-item" href="/client/projects/new">
            <i class="fas fa-plus me-2"></i>Crear Nuevo Proyecto
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/client/projects?status=draft">
            <i class="fas fa-edit me-2"></i>Borradores
        </a></li>
        <li><a class="dropdown-item" href="/client/projects?status=in_progress">
            <i class="fas fa-play me-2"></i>En Progreso
            <?php 
            $in_progress = \UC\ApprovalSystem\Models\Project::count([
                'user_id' => $current_user->id,
                'status' => 'in_progress'
            ]);
            if ($in_progress > 0): 
            ?>
                <span class="badge bg-info ms-1"><?= $in_progress ?></span>
            <?php endif; ?>
        </a></li>
        <li><a class="dropdown-item" href="/client/projects?status=under_review">
            <i class="fas fa-clock me-2"></i>En Revisión
            <?php 
            $under_review = \UC\ApprovalSystem\Models\Project::count([
                'user_id' => $current_user->id,
                'status' => 'under_review'
            ]);
            if ($under_review > 0): 
            ?>
                <span class="badge bg-warning text-dark ms-1"><?= $under_review ?></span>
            <?php endif; ?>
        </a></li>
        <li><a class="dropdown-item" href="/client/projects?status=approved">
            <i class="fas fa-check me-2"></i>Aprobados
        </a></li>
        <li><a class="dropdown-item" href="/client/projects?status=rejected">
            <i class="fas fa-times me-2"></i>Rechazados
        </a></li>
    </ul>
</li>

<!-- Documentos -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/client/documents', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-file-alt me-1"></i>
        Documentos
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/client/documents">
            <i class="fas fa-folder me-2"></i>Mis Documentos
        </a></li>
        <li><a class="dropdown-item" href="/client/documents/upload">
            <i class="fas fa-upload me-2"></i>Subir Documento
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/client/documents?status=uploaded">
            <i class="fas fa-cloud-upload-alt me-2"></i>Subidos
        </a></li>
        <li><a class="dropdown-item" href="/client/documents?status=under_review">
            <i class="fas fa-search me-2"></i>En Revisión
        </a></li>
        <li><a class="dropdown-item" href="/client/documents?status=approved">
            <i class="fas fa-check-circle me-2"></i>Aprobados
        </a></li>
        <li><a class="dropdown-item" href="/client/documents?status=rejected">
            <i class="fas fa-times-circle me-2"></i>Rechazados
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/client/documents/templates">
            <i class="fas fa-copy me-2"></i>Plantillas Disponibles
        </a></li>
    </ul>
</li>

<!-- Feedback y Comentarios -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/client/feedback', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-comments me-1"></i>
        Feedback
        <?php if ($user_stats['pending_feedback'] > 0): ?>
            <span class="badge bg-warning text-dark ms-1"><?= $user_stats['pending_feedback'] ?></span>
        <?php endif; ?>
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/client/feedback">
            <i class="fas fa-list me-2"></i>Todo el Feedback
        </a></li>
        <li><a class="dropdown-item" href="/client/feedback?status=open">
            <i class="fas fa-exclamation-circle me-2"></i>Pendiente
            <?php if ($user_stats['pending_feedback'] > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $user_stats['pending_feedback'] ?></span>
            <?php endif; ?>
        </a></li>
        <li><a class="dropdown-item" href="/client/feedback?status=resolved">
            <i class="fas fa-check me-2"></i>Resuelto
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/client/feedback?type=requirement">
            <i class="fas fa-list-check me-2"></i>Requerimientos
        </a></li>
        <li><a class="dropdown-item" href="/client/feedback?type=suggestion">
            <i class="fas fa-lightbulb me-2"></i>Sugerencias
        </a></li>
        <li><a class="dropdown-item" href="/client/feedback?priority=high">
            <i class="fas fa-exclamation-triangle me-2"></i>Alta Prioridad
        </a></li>
    </ul>
</li>

<!-- Estado por Áreas -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/client/areas', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-sitemap me-1"></i>
        Estado por Áreas
    </a>
    <ul class="dropdown-menu">
        <?php 
        $areas = \UC\ApprovalSystem\Utils\Helper::getAreas();
        foreach ($areas as $area_key => $area_name): 
            // Obtener estadísticas por área para el usuario
            $area_stats = \UC\ApprovalSystem\Models\ProjectStage::getStatsForUserByArea($current_user->id, $area_key);
        ?>
            <li><a class="dropdown-item d-flex justify-content-between align-items-center" 
                   href="/client/areas/<?= $area_key ?>">
                <span>
                    <i class="fas fa-cube me-2"></i><?= htmlspecialchars($area_name) ?>
                </span>
                <?php if (isset($area_stats['pending']) && $area_stats['pending'] > 0): ?>
                    <span class="badge bg-warning text-dark"><?= $area_stats['pending'] ?></span>
                <?php elseif (isset($area_stats['approved']) && $area_stats['approved'] > 0): ?>
                    <span class="badge bg-success"><?= $area_stats['approved'] ?></span>
                <?php endif; ?>
            </a></li>
        <?php endforeach; ?>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/client/areas/progress">
            <i class="fas fa-chart-line me-2"></i>Progreso General
        </a></li>
    </ul>
</li>

<!-- Guías y Ayuda -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?= isActive('/client/help', $current_path) ?>" 
       href="#" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-question-circle me-1"></i>
        Ayuda
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/client/help/getting-started">
            <i class="fas fa-play-circle me-2"></i>Comenzar
        </a></li>
        <li><a class="dropdown-item" href="/client/help/project-workflow">
            <i class="fas fa-route me-2"></i>Flujo de Proyectos
        </a></li>
        <li><a class="dropdown-item" href="/client/help/document-requirements">
            <i class="fas fa-file-check me-2"></i>Requisitos de Documentos
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/client/help/faq">
            <i class="fas fa-question me-2"></i>Preguntas Frecuentes
        </a></li>
        <li><a class="dropdown-item" href="/client/help/contact">
            <i class="fas fa-envelope me-2"></i>Contactar Soporte
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/client/help/video-tutorials">
            <i class="fas fa-video me-2"></i>Video Tutoriales
        </a></li>
        <li><a class="dropdown-item" href="/client/help/downloads">
            <i class="fas fa-download me-2"></i>Descargas
        </a></li>
    </ul>
</li>

<!-- Acceso rápido a acciones importantes -->
<li class="nav-item d-none d-lg-block">
    <a class="nav-link btn btn-success btn-sm text-white ms-2" 
       href="/client/projects/new" 
       title="Crear nuevo proyecto">
        <i class="fas fa-plus me-1"></i>
        Nuevo Proyecto
    </a>
</li>

<style>
/* Estilos específicos para navegación de cliente */
.nav-link.active {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 0.375rem;
}

.dropdown-menu {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border: none;
    min-width: 250px;
}

.dropdown-item {
    transition: all 0.2s ease;
    padding: 0.5rem 1rem;
}

.dropdown-item:hover {
    background-color: rgba(0, 123, 255, 0.1);
    transform: translateX(2px);
}

.dropdown-item i {
    width: 20px;
    text-align: center;
}

.badge {
    font-size: 0.65em;
    font-weight: 600;
}

/* Estilo para el botón de acción rápida */
.nav-link.btn {
    border-radius: 20px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-link.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Indicadores de estado por área */
.dropdown-item .badge.bg-warning {
    animation: pulse-warning 2s infinite;
}

.dropdown-item .badge.bg-success {
    animation: pulse-success 2s infinite;
}

@keyframes pulse-warning {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

@keyframes pulse-success {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .nav-item.d-none.d-lg-block {
        display: block !important;
    }
    
    .nav-link.btn {
        margin: 0.5rem 0;
        display: inline-block;
        width: auto;
    }
}

@media (max-width: 768px) {
    .dropdown-menu {
        min-width: 220px;
    }
    
    .dropdown-item {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    
    .badge {
        font-size: 0.6em;
    }
}

/* Estados de proyectos con colores específicos */
.dropdown-item[href*="status=draft"] .badge {
    background-color: #6c757d !important;
}

.dropdown-item[href*="status=in_progress"] .badge {
    background-color: #0dcaf0 !important;
}

.dropdown-item[href*="status=under_review"] .badge {
    background-color: #ffc107 !important;
    color: #000 !important;
}

.dropdown-item[href*="status=approved"] .badge {
    background-color: #198754 !important;
}

.dropdown-item[href*="status=rejected"] .badge {
    background-color: #dc3545 !important;
}

/* Mejoras de accesibilidad */
.dropdown-item:focus {
    outline: 2px solid #0d6efd;
    outline-offset: -2px;
}

.nav-link:focus {
    outline: 2px solid #ffffff;
    outline-offset: -2px;
}
</style>