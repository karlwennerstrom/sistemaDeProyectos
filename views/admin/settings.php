<?php
/**
 * Vista de configuración del sistema para administradores
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

require_once '../config/app.php';
require_once '../src/Controllers/AuthController.php';
require_once '../src/Controllers/AdminController.php';

use Controllers\AuthController;
use Controllers\AdminController;

// Verificar autenticación y permisos de admin
$authController = new AuthController();
if (!$authController->isAuthenticated() || !$authController->hasRole(['admin'])) {
    header('Location: /auth/login.php');
    exit;
}

$user = $authController->getCurrentUser();
$adminController = new AdminController();

// Obtener configuraciones actuales
$settings = $adminController->getSystemSettings();

// Obtener estadísticas del sistema
$systemStats = $adminController->getSystemStats();

// Obtener logs recientes
$recentLogs = $adminController->getRecentLogs(50);

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'update_general_settings':
            $result = $adminController->updateGeneralSettings($_POST);
            break;
        case 'update_email_settings':
            $result = $adminController->updateEmailSettings($_POST);
            break;
        case 'update_cas_settings':
            $result = $adminController->updateCASSettings($_POST);
            break;
        case 'update_file_settings':
            $result = $adminController->updateFileSettings($_POST);
            break;
        case 'update_notification_settings':
            $result = $adminController->updateNotificationSettings($_POST);
            break;
        case 'test_email':
            $result = $adminController->testEmailConfiguration($_POST['test_email']);
            break;
        case 'test_cas':
            $result = $adminController->testCASConnection();
            break;
        case 'clear_cache':
            $result = $adminController->clearSystemCache();
            break;
        case 'backup_database':
            $result = $adminController->createDatabaseBackup();
            break;
        case 'clear_logs':
            $result = $adminController->clearSystemLogs($_POST['log_level'] ?? 'all');
            break;
        case 'regenerate_tokens':
            $result = $adminController->regenerateAPITokens();
            break;
    }
    
    if ($result['success']) {
        $successMessage = $result['message'];
        // Recargar configuraciones
        $settings = $adminController->getSystemSettings();
        $systemStats = $adminController->getSystemStats();
        $recentLogs = $adminController->getRecentLogs(50);
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
                    <i class="fas fa-cog me-2"></i>
                    Configuración del Sistema
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="exportSettings()">
                            <i class="fas fa-download me-1"></i>
                            Exportar Config
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#backupModal">
                            <i class="fas fa-database me-1"></i>
                            Backup
                        </button>
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

            <!-- Estado del sistema -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-white bg-<?= $systemStats['status'] === 'healthy' ? 'success' : 'warning' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= ucfirst($systemStats['status']) ?></h4>
                                    <p class="mb-0">Estado del Sistema</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-server fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= $systemStats['total_users'] ?></h4>
                                    <p class="mb-0">Usuarios Registrados</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= $systemStats['active_projects'] ?></h4>
                                    <p class="mb-0">Proyectos Activos</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-project-diagram fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-white bg-secondary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?= formatFileSize($systemStats['storage_used']) ?></h4>
                                    <p class="mb-0">Almacenamiento</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-hdd fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs de configuración -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="settingsTabs">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab">
                                <i class="fas fa-cogs me-1"></i>
                                General
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="email-tab" data-bs-toggle="tab" href="#email" role="tab">
                                <i class="fas fa-envelope me-1"></i>
                                Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="cas-tab" data-bs-toggle="tab" href="#cas" role="tab">
                                <i class="fas fa-key me-1"></i>
                                CAS UC
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="files-tab" data-bs-toggle="tab" href="#files" role="tab">
                                <i class="fas fa-file me-1"></i>
                                Archivos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="notifications-tab" data-bs-toggle="tab" href="#notifications" role="tab">
                                <i class="fas fa-bell me-1"></i>
                                Notificaciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="system-tab" data-bs-toggle="tab" href="#system" role="tab">
                                <i class="fas fa-server me-1"></i>
                                Sistema
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="settingsTabContent">
                        
                        <!-- Tab General -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_general_settings">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="app_name" class="form-label">Nombre de la aplicación</label>
                                            <input type="text" class="form-control" id="app_name" name="app_name" 
                                                   value="<?= htmlspecialchars($settings['app_name'] ?? 'Sistema de Aprobación UC') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="app_version" class="form-label">Versión</label>
                                            <input type="text" class="form-control" id="app_version" name="app_version" 
                                                   value="<?= htmlspecialchars($settings['app_version'] ?? '1.0.0') ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="app_description" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="app_description" name="app_description" rows="3"><?= htmlspecialchars($settings['app_description'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="timezone" class="form-label">Zona horaria</label>
                                            <select class="form-select" id="timezone" name="timezone">
                                                <option value="America/Santiago" <?= ($settings['timezone'] ?? '') === 'America/Santiago' ? 'selected' : '' ?>>Santiago (Chile)</option>
                                                <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                                <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>New York</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="language" class="form-label">Idioma</label>
                                            <select class="form-select" id="language" name="language">
                                                <option value="es" <?= ($settings['language'] ?? '') === 'es' ? 'selected' : '' ?>>Español</option>
                                                <option value="en" <?= ($settings['language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" 
                                                   <?= ($settings['maintenance_mode'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="maintenance_mode">
                                                Modo mantenimiento
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="debug_mode" name="debug_mode" 
                                                   <?= ($settings['debug_mode'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="debug_mode">
                                                Modo debug
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="registration_enabled" name="registration_enabled" 
                                                   <?= ($settings['registration_enabled'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="registration_enabled">
                                                Registro habilitado
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="maintenance_message" class="form-label">Mensaje de mantenimiento</label>
                                    <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="2"><?= htmlspecialchars($settings['maintenance_message'] ?? '') ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Guardar Configuración General
                                </button>
                            </form>
                        </div>

                        <!-- Tab Email -->
                        <div class="tab-pane fade" id="email" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_email_settings">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="mail_host" class="form-label">Servidor SMTP</label>
                                            <input type="text" class="form-control" id="mail_host" name="mail_host" 
                                                   value="<?= htmlspecialchars($settings['mail_host'] ?? 'smtp.gmail.com') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="mail_port" class="form-label">Puerto</label>
                                            <input type="number" class="form-control" id="mail_port" name="mail_port" 
                                                   value="<?= htmlspecialchars($settings['mail_port'] ?? '587') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="mail_encryption" class="form-label">Encriptación</label>
                                            <select class="form-select" id="mail_encryption" name="mail_encryption">
                                                <option value="tls" <?= ($settings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                                <option value="ssl" <?= ($settings['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                                <option value="" <?= ($settings['mail_encryption'] ?? '') === '' ? 'selected' : '' ?>>Sin encriptación</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="mail_username" class="form-label">Usuario SMTP</label>
                                            <input type="email" class="form-control" id="mail_username" name="mail_username" 
                                                   value="<?= htmlspecialchars($settings['mail_username'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="mail_password" class="form-label">Contraseña SMTP</label>
                                            <input type="password" class="form-control" id="mail_password" name="mail_password" 
                                                   value="<?= htmlspecialchars($settings['mail_password'] ?? '') ?>" 
                                                   placeholder="Dejar vacío para no cambiar">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="mail_from_address" class="form-label">Email remitente</label>
                                            <input type="email" class="form-control" id="mail_from_address" name="mail_from_address" 
                                                   value="<?= htmlspecialchars($settings['mail_from_address'] ?? 'sistema@uc.cl') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="mail_from_name" class="form-label">Nombre remitente</label>
                                            <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" 
                                                   value="<?= htmlspecialchars($settings['mail_from_name'] ?? 'Sistema de Aprobación UC') ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="mail_enabled" name="mail_enabled" 
                                           <?= ($settings['mail_enabled'] ?? true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="mail_enabled">
                                        Habilitar envío de emails
                                    </label>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="test_email" class="form-label">Probar configuración</label>
                                    <div class="input-group">
                                        <input type="email" class="form-control" id="test_email" name="test_email" 
                                               placeholder="email@ejemplo.com">
                                        <button type="button" class="btn btn-outline-info" onclick="testEmail()">
                                            <i class="fas fa-paper-plane me-1"></i>
                                            Enviar Prueba
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Guardar Configuración Email
                                </button>
                            </form>
                        </div>

                        <!-- Tab CAS UC -->
                        <div class="tab-pane fade" id="cas" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_cas_settings">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cas_server" class="form-label">Servidor CAS</label>
                                            <input type="text" class="form-control" id="cas_server" name="cas_server" 
                                                   value="<?= htmlspecialchars($settings['cas_server'] ?? 'sso-lib.uc.cl') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="cas_port" class="form-label">Puerto</label>
                                            <input type="number" class="form-control" id="cas_port" name="cas_port" 
                                                   value="<?= htmlspecialchars($settings['cas_port'] ?? '443') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="cas_uri" class="form-label">URI</label>
                                            <input type="text" class="form-control" id="cas_uri" name="cas_uri" 
                                                   value="<?= htmlspecialchars($settings['cas_uri'] ?? '/cas') ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cas_service_url" class="form-label">URL del servicio</label>
                                            <input type="url" class="form-control" id="cas_service_url" name="cas_service_url" 
                                                   value="<?= htmlspecialchars($settings['cas_service_url'] ?? '') ?>"
                                                   placeholder="https://tusitio.uc.cl/auth/callback">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cas_logout_url" class="form-label">URL de logout</label>
                                            <input type="url" class="form-control" id="cas_logout_url" name="cas_logout_url" 
                                                   value="<?= htmlspecialchars($settings['cas_logout_url'] ?? '') ?>"
                                                   placeholder="https://tusitio.uc.cl/auth/logout">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="cas_enabled" name="cas_enabled" 
                                                   <?= ($settings['cas_enabled'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="cas_enabled">
                                                Habilitar autenticación CAS
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="cas_debug" name="cas_debug" 
                                                   <?= ($settings['cas_debug'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="cas_debug">
                                                Debug CAS
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="cas_ssl_verify" name="cas_ssl_verify" 
                                                   <?= ($settings['cas_ssl_verify'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="cas_ssl_verify">
                                                Verificar SSL
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-info me-2" onclick="testCAS()">
                                        <i class="fas fa-link me-1"></i>
                                        Probar Conexión CAS
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        Guardar Configuración CAS
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Tab Archivos -->
                        <div class="tab-pane fade" id="files" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_file_settings">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_file_size" class="form-label">Tamaño máximo de archivo (MB)</label>
                                            <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                                   value="<?= htmlspecialchars($settings['max_file_size'] ?? '10') ?>" min="1" max="100">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="storage_path" class="form-label">Ruta de almacenamiento</label>
                                            <input type="text" class="form-control" id="storage_path" name="storage_path" 
                                                   value="<?= htmlspecialchars($settings['storage_path'] ?? '/uploads') ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="allowed_extensions" class="form-label">Extensiones permitidas</label>
                                    <input type="text" class="form-control" id="allowed_extensions" name="allowed_extensions" 
                                           value="<?= htmlspecialchars($settings['allowed_extensions'] ?? 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar') ?>"
                                           placeholder="pdf,doc,docx,xls,xlsx">
                                    <div class="form-text">Separar con comas, sin espacios</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="virus_scan_enabled" name="virus_scan_enabled" 
                                                   <?= ($settings['virus_scan_enabled'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="virus_scan_enabled">
                                                Escaneo de virus
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="file_versioning" name="file_versioning" 
                                                   <?= ($settings['file_versioning'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="file_versioning">
                                                Versionado de archivos
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="file_compression" name="file_compression" 
                                                   <?= ($settings['file_compression'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="file_compression">
                                                Compresión automática
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="retention_days" class="form-label">Retención de archivos (días)</label>
                                            <input type="number" class="form-control" id="retention_days" name="retention_days" 
                                                   value="<?= htmlspecialchars($settings['retention_days'] ?? '365') ?>" min="30">
                                            <div class="form-text">0 = sin límite</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="backup_frequency" class="form-label">Frecuencia de backup</label>
                                            <select class="form-select" id="backup_frequency" name="backup_frequency">
                                                <option value="daily" <?= ($settings['backup_frequency'] ?? 'weekly') === 'daily' ? 'selected' : '' ?>>Diario</option>
                                                <option value="weekly" <?= ($settings['backup_frequency'] ?? 'weekly') === 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                                <option value="monthly" <?= ($settings['backup_frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>Mensual</option>
                                                <option value="never" <?= ($settings['backup_frequency'] ?? '') === 'never' ? 'selected' : '' ?>>Nunca</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Guardar Configuración de Archivos
                                </button>
                            </form>
                        </div>

                        <!-- Tab Notificaciones -->
                        <div class="tab-pane fade" id="notifications" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_notification_settings">
                                
                                <h6 class="mb-3">Notificaciones por Email</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="notify_new_project" name="notify_new_project" 
                                                   <?= ($settings['notify_new_project'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notify_new_project">
                                                Nuevos proyectos
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="notify_document_uploaded" name="notify_document_uploaded" 
                                                   <?= ($settings['notify_document_uploaded'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notify_document_uploaded">
                                                Documentos subidos
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="notify_project_approved" name="notify_project_approved" 
                                                   <?= ($settings['notify_project_approved'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notify_project_approved">
                                                Proyectos aprobados
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="notify_project_rejected" name="notify_project_rejected" 
                                                   <?= ($settings['notify_project_rejected'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notify_project_rejected">
                                                Proyectos rechazados
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="notify_deadline_approaching" name="notify_deadline_approaching" 
                                                   <?= ($settings['notify_deadline_approaching'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notify_deadline_approaching">
                                                Fechas límite próximas
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="notify_system_alerts" name="notify_system_alerts" 
                                                   <?= ($settings['notify_system_alerts'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notify_system_alerts">
                                                Alertas del sistema
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                <h6 class="mb-3">Configuración de Frecuencia</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="digest_frequency" class="form-label">Resumen de notificaciones</label>
                                            <select class="form-select" id="digest_frequency" name="digest_frequency">
                                                <option value="immediate" <?= ($settings['digest_frequency'] ?? 'daily') === 'immediate' ? 'selected' : '' ?>>Inmediato</option>
                                                <option value="hourly" <?= ($settings['digest_frequency'] ?? 'daily') === 'hourly' ? 'selected' : '' ?>>Cada hora</option>
                                                <option value="daily" <?= ($settings['digest_frequency'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>Diario</option>
                                                <option value="weekly" <?= ($settings['digest_frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="admin_emails" class="form-label">Emails de administradores</label>
                                            <input type="text" class="form-control" id="admin_emails" name="admin_emails" 
                                                   value="<?= htmlspecialchars($settings['admin_emails'] ?? '') ?>"
                                                   placeholder="admin1@uc.cl,admin2@uc.cl">
                                            <div class="form-text">Separar con comas</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Guardar Configuración de Notificaciones
                                </button>
                            </form>
                        </div>

                        <!-- Tab Sistema -->
                        <div class="tab-pane fade" id="system" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-broom me-1"></i>
                                                Mantenimiento
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-outline-warning" onclick="clearCache()">
                                                    <i class="fas fa-trash me-1"></i>
                                                    Limpiar Caché
                                                </button>
                                                <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                                                    <i class="fas fa-file-alt me-1"></i>
                                                    Limpiar Logs
                                                </button>
                                                <button type="button" class="btn btn-outline-primary" onclick="regenerateTokens()">
                                                    <i class="fas fa-key me-1"></i>
                                                    Regenerar Tokens API
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Información del Sistema
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tr>
                                                    <td><strong>PHP:</strong></td>
                                                    <td><?= PHP_VERSION ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>MySQL:</strong></td>
                                                    <td><?= $systemStats['mysql_version'] ?? 'N/A' ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Servidor:</strong></td>
                                                    <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Memoria PHP:</strong></td>
                                                    <td><?= ini_get('memory_limit') ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Tiempo máximo:</strong></td>
                                                    <td><?= ini_get('max_execution_time') ?>s</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Subida máxima:</strong></td>
                                                    <td><?= ini_get('upload_max_filesize') ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Logs del sistema -->
                            <div class="card mt-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-list-alt me-1"></i>
                                        Logs Recientes
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshLogs()">
                                        <i class="fas fa-sync me-1"></i>
                                        Actualizar
                                    </button>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <?php if (empty($recentLogs)): ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No hay logs recientes</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Nivel</th>
                                                        <th>Mensaje</th>
                                                        <th>Usuario</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recentLogs as $log): ?>
                                                        <tr>
                                                            <td><small><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></small></td>
                                                            <td>
                                                                <span class="badge bg-<?= $log['level'] === 'error' ? 'danger' : 
                                                                                         ($log['level'] === 'warning' ? 'warning' : 
                                                                                         ($log['level'] === 'info' ? 'info' : 'secondary')) ?>">
                                                                    <?= ucfirst($log['level']) ?>
                                                                </span>
                                                            </td>
                                                            <td><small><?= htmlspecialchars($log['message']) ?></small></td>
                                                            <td><small><?= htmlspecialchars($log['user_name'] ?? 'Sistema') ?></small></td>
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
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal backup -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Deseas crear un backup completo de la base de datos?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    El backup incluirá todos los datos del sistema pero no los archivos subidos.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="createBackup()">
                    <i class="fas fa-database me-1"></i>
                    Crear Backup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal limpiar logs -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Limpiar Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="clear_logs">
                    <div class="mb-3">
                        <label for="log_level" class="form-label">Nivel de logs a limpiar</label>
                        <select class="form-select" id="log_level" name="log_level">
                            <option value="all">Todos los logs</option>
                            <option value="debug">Solo Debug</option>
                            <option value="info">Solo Info</option>
                            <option value="warning">Solo Warning</option>
                            <option value="error">Solo Error</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Esta acción no se puede deshacer. Los logs eliminados no se pueden recuperar.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>
                        Limpiar Logs
                    </button>
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

// Función para probar configuración de email
function testEmail() {
    const email = document.getElementById('test_email').value;
    if (!email) {
        alert('Por favor ingresa un email para la prueba');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="test_email">
        <input type="hidden" name="test_email" value="${email}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Función para probar conexión CAS
function testCAS() {
    if (confirm('¿Probar la conexión con el servidor CAS?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="test_cas">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Función para limpiar caché
function clearCache() {
    if (confirm('¿Limpiar el caché del sistema?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="clear_cache">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Función para crear backup
function createBackup() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('backupModal'));
    modal.hide();
    
    if (confirm('¿Crear backup de la base de datos? Este proceso puede tomar varios minutos.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="backup_database">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Función para regenerar tokens API
function regenerateTokens() {
    if (confirm('¿Regenerar todos los tokens de API? Esto invalidará los tokens existentes.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="regenerate_tokens">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Función para refrescar logs
function refreshLogs() {
    location.reload();
}

// Función para exportar configuraciones
function exportSettings() {
    const settings = {
        general: {
            app_name: document.getElementById('app_name')?.value,
            app_version: document.getElementById('app_version')?.value,
            timezone: document.getElementById('timezone')?.value,
            language: document.getElementById('language')?.value
        },
        email: {
            mail_host: document.getElementById('mail_host')?.value,
            mail_port: document.getElementById('mail_port')?.value,
            mail_encryption: document.getElementById('mail_encryption')?.value,
            mail_from_address: document.getElementById('mail_from_address')?.value,
            mail_from_name: document.getElementById('mail_from_name')?.value
        },
        cas: {
            cas_server: document.getElementById('cas_server')?.value,
            cas_port: document.getElementById('cas_port')?.value,
            cas_uri: document.getElementById('cas_uri')?.value,
            cas_service_url: document.getElementById('cas_service_url')?.value
        },
        files: {
            max_file_size: document.getElementById('max_file_size')?.value,
            storage_path: document.getElementById('storage_path')?.value,
            allowed_extensions: document.getElementById('allowed_extensions')?.value
        },
        export_date: new Date().toISOString()
    };
    
    const dataStr = JSON.stringify(settings, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `configuracion_sistema_${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    URL.revokeObjectURL(url);
}

// Validación de formularios
document.addEventListener('DOMContentLoaded', function() {
    // Validar puertos
    const portInputs = document.querySelectorAll('input[type="number"][id$="_port"]');
    portInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value < 1 || value > 65535) {
                this.setCustomValidity('El puerto debe estar entre 1 y 65535');
            } else {
                this.setCustomValidity('');
            }
        });
    });
    
    // Validar tamaño máximo de archivo
    const maxFileSizeInput = document.getElementById('max_file_size');
    if (maxFileSizeInput) {
        maxFileSizeInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value < 1 || value > 100) {
                this.setCustomValidity('El tamaño debe estar entre 1 y 100 MB');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Validar emails múltiples
    const adminEmailsInput = document.getElementById('admin_emails');
    if (adminEmailsInput) {
        adminEmailsInput.addEventListener('input', function() {
            const emails = this.value.split(',').map(email => email.trim());
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            for (let email of emails) {
                if (email && !emailRegex.test(email)) {
                    this.setCustomValidity(`Email inválido: ${email}`);
                    return;
                }
            }
            this.setCustomValidity('');
        });
    }
    
    // Validar extensiones de archivo
    const allowedExtensionsInput = document.getElementById('allowed_extensions');
    if (allowedExtensionsInput) {
        allowedExtensionsInput.addEventListener('input', function() {
            const extensions = this.value.split(',').map(ext => ext.trim().toLowerCase());
            const invalidExtensions = extensions.filter(ext => 
                ext && (ext.includes(' ') || ext.includes('.') || ext.length > 10)
            );
            
            if (invalidExtensions.length > 0) {
                this.setCustomValidity(`Extensiones inválidas: ${invalidExtensions.join(', ')}`);
            } else {
                this.setCustomValidity('');
            }
        });
    }
});

// Confirmaciones para acciones críticas
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const action = this.querySelector('input[name="action"]')?.value;
        
        // Acciones que requieren confirmación
        const criticalActions = [
            'clear_cache', 
            'backup_database', 
            'clear_logs', 
            'regenerate_tokens',
            'update_cas_settings'
        ];
        
        if (criticalActions.includes(action)) {
            const actionNames = {
                'clear_cache': 'limpiar el caché',
                'backup_database': 'crear el backup',
                'clear_logs': 'limpiar los logs',
                'regenerate_tokens': 'regenerar los tokens',
                'update_cas_settings': 'actualizar la configuración CAS'
            };
            
            if (!confirm(`¿Estás seguro de ${actionNames[action] || 'realizar esta acción'}?`)) {
                e.preventDefault();
            }
        }
    });
});

// Auto-guardado de configuraciones (cada 30 segundos)
let autoSaveTimeout;
document.querySelectorAll('input, select, textarea').forEach(input => {
    input.addEventListener('change', function() {
        clearTimeout(autoSaveTimeout);
        
        // Mostrar indicador de cambios pendientes
        const saveBtn = this.closest('form')?.querySelector('button[type="submit"]');
        if (saveBtn && !saveBtn.classList.contains('btn-warning')) {
            saveBtn.classList.remove('btn-primary');
            saveBtn.classList.add('btn-warning');
            saveBtn.innerHTML = saveBtn.innerHTML.replace('Guardar', 'Guardar Cambios');
        }
        
        // Auto-guardar después de 30 segundos de inactividad
        autoSaveTimeout = setTimeout(() => {
            if (saveBtn) {
                saveBtn.click();
            }
        }, 30000);
    });
});

// Indicador de estado de conexión
function checkSystemHealth() {
    fetch('/api/system/health')
        .then(response => response.json())
        .then(data => {
            const statusCard = document.querySelector('.bg-success, .bg-warning, .bg-danger');
            if (statusCard) {
                statusCard.className = statusCard.className.replace(/bg-(success|warning|danger)/, 
                    `bg-${data.status === 'healthy' ? 'success' : 'warning'}`);
                statusCard.querySelector('h4').textContent = data.status === 'healthy' ? 'Healthy' : 'Issues';
            }
        })
        .catch(error => {
            console.log('Error checking system health:', error);
        });
}

// Verificar estado del sistema cada 5 minutos
setInterval(checkSystemHealth, 300000);

// Notificación de cambios no guardados
window.addEventListener('beforeunload', function(e) {
    const hasUnsavedChanges = document.querySelector('.btn-warning[type="submit"]');
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '';
        return 'Tienes cambios sin guardar. ¿Estás seguro de salir?';
    }
});

// Tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Función para mostrar/ocultar contraseñas
document.querySelectorAll('input[type="password"]').forEach(input => {
    const toggleBtn = document.createElement('button');
    toggleBtn.type = 'button';
    toggleBtn.className = 'btn btn-outline-secondary';
    toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
    toggleBtn.onclick = function() {
        if (input.type === 'password') {
            input.type = 'text';
            this.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            input.type = 'password';
            this.innerHTML = '<i class="fas fa-eye"></i>';
        }
    };
    
    // Crear input group si no existe
    if (!input.parentElement.classList.contains('input-group')) {
        const inputGroup = document.createElement('div');
        inputGroup.className = 'input-group';
        input.parentElement.insertBefore(inputGroup, input);
        inputGroup.appendChild(input);
        inputGroup.appendChild(toggleBtn);
    }
});
</script>

<?php include '../views/layouts/footer.php'; ?>