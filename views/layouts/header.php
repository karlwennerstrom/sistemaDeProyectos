<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Sistema de Aprobación UC' ?></title>
    
    <!-- Meta tags de seguridad -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/public/assets/img/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS personalizado -->
    <link href="/public/assets/css/style.css" rel="stylesheet">
    
    <?php if (isset($current_user) && $current_user->role === 'admin'): ?>
        <link href="/public/assets/css/admin.css" rel="stylesheet">
    <?php else: ?>
        <link href="/public/assets/css/client.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- CSS adicional específico de página -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link href="<?= $css_file ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Variables globales para JavaScript -->
    <script>
        window.APP_CONFIG = {
            BASE_URL: '<?= $_ENV['APP_URL'] ?? 'http://localhost' ?>',
            API_URL: '<?= $_ENV['APP_URL'] ?? 'http://localhost' ?>/api',
            CSRF_TOKEN: '<?= $_SESSION['csrf_token'] ?? '' ?>',
            USER_ROLE: '<?= $current_user->role ?? 'guest' ?>',
            USER_AREAS: <?= json_encode($current_user->getAreas() ?? []) ?>,
            MAX_FILE_SIZE: <?= $_ENV['MAX_FILE_SIZE'] ?? 10485760 ?>,
            ALLOWED_EXTENSIONS: <?= json_encode(explode(',', $_ENV['ALLOWED_EXTENSIONS'] ?? 'pdf,doc,docx')) ?>
        };
    </script>
</head>
<body class="<?= $body_class ?? '' ?>">
    
    <!-- Loader para acciones asíncronas -->
    <div id="global-loader" class="d-none">
        <div class="loader-overlay">
            <div class="loader-content">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Procesando...</p>
            </div>
        </div>
    </div>
    
    <!-- Alertas globales -->
    <div id="global-alerts" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>
    
    <!-- Skip navigation para accesibilidad -->
    <a href="#main-content" class="visually-hidden-focusable btn btn-primary position-absolute top-0 start-0 m-2">
        Saltar al contenido principal
    </a>
    
    <?php if (isset($current_user)): ?>
        <!-- Header principal para usuarios autenticados -->
        <header class="uc-header bg-primary text-white shadow-sm">
            <nav class="navbar navbar-expand-lg navbar-dark">
                <div class="container-fluid">
                    
                    <!-- Logo UC -->
                    <a class="navbar-brand d-flex align-items-center" href="<?= $current_user->role === 'admin' ? '/admin/dashboard' : '/client/dashboard' ?>">
                        <img src="<?= $_ENV['UC_LOGO_URL'] ?? '/public/assets/img/logo-uc.png' ?>" 
                             alt="Universidad Católica" 
                             height="40" 
                             class="me-2">
                        <div class="brand-text">
                            <div class="fw-bold">Sistema de Aprobación</div>
                            <small class="opacity-75">Universidad Católica</small>
                        </div>
                    </a>
                    
                    <!-- Toggle para móvil -->
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                    <!-- Navegación principal -->
                    <div class="collapse navbar-collapse" id="navbarMain">
                        
                        <!-- Menú de navegación -->
                        <ul class="navbar-nav me-auto">
                            <?php if ($current_user->role === 'admin' || $current_user->role === 'area_admin'): ?>
                                <!-- Menú administración -->
                                <?php include 'admin-nav.php'; ?>
                            <?php else: ?>
                                <!-- Menú cliente -->
                                <?php include 'client-nav.php'; ?>
                            <?php endif; ?>
                        </ul>
                        
                        <!-- Utilidades del header -->
                        <ul class="navbar-nav">
                            
                            <!-- Notificaciones -->
                            <li class="nav-item dropdown">
                                <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" id="notificationsDropdown">
                                    <i class="fas fa-bell"></i>
                                    <span class="notification-badge badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" 
                                          id="notification-count" style="font-size: 0.7em; display: none;">
                                        0
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px;">
                                    <li class="dropdown-header d-flex justify-content-between align-items-center">
                                        <span>Notificaciones</span>
                                        <button class="btn btn-sm btn-outline-primary" onclick="markAllNotificationsRead()">
                                            Marcar todas como leídas
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <div id="notifications-list" class="notifications-container">
                                        <li class="dropdown-item text-muted text-center py-3">
                                            <i class="fas fa-bell-slash me-2"></i>
                                            No hay notificaciones
                                        </li>
                                    </div>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-item text-center">
                                        <a href="/notifications" class="text-decoration-none">Ver todas las notificaciones</a>
                                    </li>
                                </ul>
                            </li>
                            
                            <!-- Ayuda -->
                            <li class="nav-item dropdown">
                                <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-question-circle"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/help/user-guide"><i class="fas fa-book me-2"></i>Guía de Usuario</a></li>
                                    <li><a class="dropdown-item" href="/help/faq"><i class="fas fa-question me-2"></i>Preguntas Frecuentes</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/help/contact"><i class="fas fa-envelope me-2"></i>Contactar Soporte</a></li>
                                    <?php if ($current_user->role === 'admin'): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="/admin/system-info"><i class="fas fa-info-circle me-2"></i>Info del Sistema</a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            
                            <!-- Perfil de usuario -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                    <div class="user-avatar me-2">
                                        <?php 
                                        $initials = strtoupper(substr($current_user->first_name, 0, 1) . substr($current_user->last_name, 0, 1));
                                        ?>
                                        <div class="avatar-circle bg-light text-primary d-flex align-items-center justify-content-center rounded-circle" 
                                             style="width: 32px; height: 32px; font-size: 14px; font-weight: bold;">
                                            <?= $initials ?>
                                        </div>
                                    </div>
                                    <div class="user-info d-none d-md-block">
                                        <div class="user-name"><?= htmlspecialchars($current_user->first_name . ' ' . $current_user->last_name) ?></div>
                                        <small class="user-role opacity-75"><?= ucfirst(str_replace('_', ' ', $current_user->role)) ?></small>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li class="dropdown-header">
                                        <div class="fw-bold"><?= htmlspecialchars($current_user->first_name . ' ' . $current_user->last_name) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($current_user->email) ?></small>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/profile"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                                    <li><a class="dropdown-item" href="/profile/settings"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                                    <li><a class="dropdown-item" href="/profile/security"><i class="fas fa-shield-alt me-2"></i>Seguridad</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="/logout" class="d-inline w-100">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <!-- Breadcrumb -->
            <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
                <div class="container-fluid bg-light border-top">
                    <nav aria-label="breadcrumb" class="py-2">
                        <ol class="breadcrumb mb-0">
                            <?php foreach ($breadcrumb as $index => $item): ?>
                                <?php if ($index === count($breadcrumb) - 1): ?>
                                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($item['text']) ?></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?= $item['url'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($item['text']) ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                </div>
            <?php endif; ?>
        </header>
        
    <?php else: ?>
        <!-- Header simple para páginas públicas -->
        <header class="uc-header-simple bg-white shadow-sm">
            <nav class="navbar navbar-light">
                <div class="container">
                    <a class="navbar-brand d-flex align-items-center" href="/">
                        <img src="<?= $_ENV['UC_LOGO_URL'] ?? '/public/assets/img/logo-uc.png' ?>" 
                             alt="Universidad Católica" 
                             height="50" 
                             class="me-3">
                        <div class="brand-text">
                            <h1 class="h4 mb-0 text-primary">Sistema de Aprobación</h1>
                            <small class="text-muted">Universidad Católica</small>
                        </div>
                    </a>
                </div>
            </nav>
        </header>
    <?php endif; ?>
    
    <!-- Contenido principal -->
    <main id="main-content" class="main-content">
        <?php if (isset($page_header) && $page_header): ?>
            <!-- Header de página -->
            <div class="page-header bg-light border-bottom">
                <div class="container-fluid py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1 class="page-title h3 mb-1"><?= htmlspecialchars($title ?? 'Página') ?></h1>
                            <?php if (isset($page_description)): ?>
                                <p class="page-description text-muted mb-0"><?= htmlspecialchars($page_description) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($page_actions)): ?>
                            <div class="col-auto">
                                <div class="page-actions">
                                    <?= $page_actions ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Alertas de sesión (flash messages) -->
        <?php if (isset($_SESSION['flash_messages'])): ?>
            <div class="container-fluid mt-3">
                <?php foreach ($_SESSION['flash_messages'] as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php unset($_SESSION['flash_messages']); ?>
        <?php endif; ?>