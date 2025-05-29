<?php
/**
 * Página de cierre de sesión
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

require_once '../config/app.php';
require_once '../src/Controllers/AuthController.php';
require_once '../src/Services/CASService.php';
require_once '../src/Utils/Logger.php';

use Controllers\AuthController;
use Services\CASService;
use Utils\Logger;

// Inicializar controladores
$authController = new AuthController();
$casService = new CASService();
$logger = new Logger();

// Obtener información del usuario antes del logout (para logs)
$user = $authController->getCurrentUser();
$userName = $user ? $user['name'] : 'Usuario desconocido';
$userId = $user ? $user['id'] : null;

// Determinar tipo de logout
$logoutType = $_GET['type'] ?? 'normal';
$redirect = $_GET['redirect'] ?? '';
$force = isset($_GET['force']) && $_GET['force'] === '1';

// Validar tipos de logout permitidos
$allowedLogoutTypes = ['normal', 'cas', 'session_expired', 'admin_forced', 'security'];
if (!in_array($logoutType, $allowedLogoutTypes)) {
    $logoutType = 'normal';
}

// Procesar logout
try {
    // Log del evento de logout
    $logger->info("Logout iniciado", [
        'user_id' => $userId,
        'user_name' => $userName,
        'logout_type' => $logoutType,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);

    // Realizar logout local
    $authController->logout();
    
    // Limpiar variables de sesión específicas
    if (isset($_SESSION)) {
        $_SESSION = array();
        
        // Destruir cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    // Limpiar cookies adicionales del sistema
    $cookiesToClear = ['remember_token', 'user_preferences', 'last_activity'];
    foreach ($cookiesToClear as $cookie) {
        if (isset($_COOKIE[$cookie])) {
            setcookie($cookie, '', time() - 3600, '/');
        }
    }
    
    // Determinar URL de redirección
    $finalRedirectUrl = '/auth/login.php?message=logout_success';
    
    // Si hay una URL de redirección específica, validarla
    if ($redirect) {
        $decodedRedirect = urldecode($redirect);
        // Validar que sea una URL interna (seguridad)
        if (filter_var($decodedRedirect, FILTER_VALIDATE_URL) && 
            (strpos($decodedRedirect, $_SERVER['HTTP_HOST']) !== false || 
             strpos($decodedRedirect, '/') === 0)) {
            $finalRedirectUrl = $decodedRedirect;
        }
    }
    
    // Para logout CAS o forzado, hacer logout completo del CAS
    if ($logoutType === 'cas' || $force) {
        try {
            $casLogoutUrl = $casService->getLogoutUrl($finalRedirectUrl);
            
            $logger->info("Logout CAS iniciado", [
                'user_id' => $userId,
                'cas_logout_url' => $casLogoutUrl
            ]);
            
            // Redirigir al logout de CAS
            header('Location: ' . $casLogoutUrl);
            exit;
            
        } catch (Exception $e) {
            $logger->error("Error en logout CAS", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            // Si falla CAS, continuar con logout local
        }
    }
    
    // Log exitoso
    $logger->info("Logout completado exitosamente", [
        'user_id' => $userId,
        'logout_type' => $logoutType
    ]);
    
    // Redirigir a login con mensaje de éxito
    header('Location: ' . $finalRedirectUrl);
    exit;
    
} catch (Exception $e) {
    // Log del error
    $logger->error("Error durante logout", [
        'user_id' => $userId,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // En caso de error, intentar logout básico y redirigir
    if (isset($_SESSION)) {
        session_destroy();
    }
    
    header('Location: /auth/login.php?error=logout_error');
    exit;
}

// Esta parte del código normalmente no se ejecuta debido a las redirecciones,
// pero se incluye como fallback para casos especiales

// Obtener configuraciones para mostrar página intermedia si es necesario
$settings = [
    'app_name' => $_ENV['APP_NAME'] ?? 'Sistema de Aprobación UC',
    'cas_enabled' => $_ENV['CAS_ENABLED'] ?? true
];

// Mensajes según tipo de logout
$logoutMessages = [
    'normal' => 'Cerrando sesión...',
    'cas' => 'Cerrando sesión en CAS UC...',
    'session_expired' => 'Tu sesión ha expirado',
    'admin_forced' => 'Un administrador ha cerrado tu sesión',
    'security' => 'Sesión cerrada por razones de seguridad'
];

$message = $logoutMessages[$logoutType] ?? $logoutMessages['normal'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando Sesión - <?= htmlspecialchars($settings['app_name']) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }
        
        .logout-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .logout-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 3rem 2rem;
            text-align: center;
            max-width: 400px;
            width: 100%;
            animation: fadeIn 0.6s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
            }
        }
        
        .logout-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .logout-title {
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .logout-message {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
        }
        
        .progress-bar {
            animation: progressFill 3s ease-in-out;
        }
        
        @keyframes progressFill {
            from {
                width: 0%;
            }
            to {
                width: 100%;
            }
        }
        
        .logout-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 0.9rem;
        }
        
        .emergency-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.8rem;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .emergency-link:hover {
            background: rgba(220, 53, 69, 1);
            color: white;
            transform: scale(1.05);
        }
        
        .logout-steps {
            text-align: left;
            margin: 1.5rem 0;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
        }
        
        .logout-step {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }
        
        .logout-step:last-child {
            margin-bottom: 0;
        }
        
        .step-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #28a745;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 0.7rem;
        }
        
        .step-pending {
            background: #6c757d;
        }
        
        .step-current {
            background: #007bff;
            animation: pulse 1s infinite;
        }
        
        @media (max-width: 768px) {
            .logout-card {
                padding: 2rem 1.5rem;
                margin: 10px;
            }
            
            .logout-icon {
                width: 60px;
                height: 60px;
            }
            
            .logout-icon i {
                font-size: 1.5rem;
            }
            
            .logout-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-card">
            <!-- Icono principal -->
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            
            <!-- Título y mensaje -->
            <h1 class="logout-title"><?= htmlspecialchars($message) ?></h1>
            <p class="logout-message">
                <?php if ($logoutType === 'session_expired'): ?>
                    Por tu seguridad, tu sesión ha sido cerrada automáticamente.
                <?php elseif ($logoutType === 'admin_forced'): ?>
                    Un administrador ha finalizado tu sesión.
                <?php elseif ($logoutType === 'security'): ?>
                    Se detectó actividad sospechosa en tu cuenta.
                <?php else: ?>
                    Estamos cerrando tu sesión de forma segura.
                <?php endif; ?>
            </p>
            
            <!-- Barra de progreso -->
            <div class="progress mb-3" style="height: 6px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
            </div>
            
            <!-- Spinner de carga -->
            <div class="mb-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cerrando sesión...</span>
                </div>
            </div>
            
            <!-- Pasos del logout -->
            <div class="logout-steps">
                <div class="logout-step">
                    <div class="step-icon step-current" id="step1">
                        <i class="fas fa-check"></i>
                    </div>
                    <span>Cerrando sesión local</span>
                </div>
                <?php if ($logoutType === 'cas' || $force): ?>
                <div class="logout-step">
                    <div class="step-icon step-pending" id="step2">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span>Desconectando de CAS UC</span>
                </div>
                <?php endif; ?>
                <div class="logout-step">
                    <div class="step-icon step-pending" id="step3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span>Limpiando datos de sesión</span>
                </div>
                <div class="logout-step">
                    <div class="step-icon step-pending" id="step4">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span>Redirigiendo...</span>
                </div>
            </div>
            
            <!-- Footer informativo -->
            <div class="logout-footer">
                <i class="fas fa-shield-alt me-1"></i>
                Logout seguro - <?= htmlspecialchars($settings['app_name']) ?>
            </div>
        </div>
    </div>
    
    <!-- Enlace de emergencia -->
    <a href="/auth/login.php" class="emergency-link">
        <i class="fas fa-home me-1"></i>
        Ir al Login
    </a>
    
    <script>
        // Simular progreso de logout
        let progress = 0;
        let currentStep = 1;
        const progressBar = document.querySelector('.progress-bar');
        const totalSteps = document.querySelectorAll('.logout-step').length;
        
        function updateProgress() {
            progress += Math.random() * 20 + 10;
            if (progress > 100) progress = 100;
            
            progressBar.style.width = progress + '%';
            
            // Actualizar pasos
            const stepProgress = Math.floor((progress / 100) * totalSteps) + 1;
            if (stepProgress > currentStep) {
                // Marcar paso anterior como completado
                const prevStep = document.getElementById(`step${currentStep}`);
                if (prevStep) {
                    prevStep.className = 'step-icon';
                    prevStep.innerHTML = '<i class="fas fa-check"></i>';
                }
                
                // Marcar paso actual
                currentStep = stepProgress;
                const currentStepEl = document.getElementById(`step${currentStep}`);
                if (currentStepEl) {
                    currentStepEl.className = 'step-icon step-current';
                    currentStepEl.innerHTML = '<i class="fas fa-sync fa-spin"></i>';
                }
            }
            
            if (progress < 100) {
                setTimeout(updateProgress, 300 + Math.random() * 500);
            } else {
                // Completar último paso
                const lastStep = document.getElementById(`step${totalSteps}`);
                if (lastStep) {
                    lastStep.className = 'step-icon';
                    lastStep.innerHTML = '<i class="fas fa-check"></i>';
                }
                
                // Redirigir después de completar
                setTimeout(() => {
                    window.location.href = '<?= $finalRedirectUrl ?>';
                }, 1000);
            }
        }
        
        // Iniciar progreso
        setTimeout(updateProgress, 500);
        
        // Fallback: redirigir después de 10 segundos máximo
        setTimeout(() => {
            window.location.href = '<?= $finalRedirectUrl ?>';
        }, 10000);
        
        // Prevenir navegación hacia atrás
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };
        
        // Limpiar localStorage y sessionStorage
        try {
            localStorage.clear();
            sessionStorage.clear();
            
            // Limpiar cookies específicas del sistema
            const cookiesToClear = ['remember_token', 'user_preferences', 'last_activity'];
            cookiesToClear.forEach(cookie => {
                document.cookie = `${cookie}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
            });
        } catch (e) {
            console.log('Error limpiando almacenamiento local:', e);
        }
        
        // Mensaje específico según tipo de logout
        const logoutType = '<?= $logoutType ?>';
        let customMessage = '';
        
        switch (logoutType) {
            case 'session_expired':
                customMessage = 'Tu sesión expiró después de un período de inactividad.';
                break;
            case 'admin_forced':
                customMessage = 'Un administrador ha terminado tu sesión remotamente.';
                break;
            case 'security':
                customMessage = 'Se cerró tu sesión por motivos de seguridad.';
                break;
            case 'cas':
                customMessage = 'Desconectando también del sistema CAS UC.';
                break;
            default:
                customMessage = 'Gracias por usar el sistema.';
        }
        
        // Actualizar mensaje si es necesario
        if (customMessage) {
            const messageEl = document.querySelector('.logout-message');
            if (messageEl && logoutType !== 'normal') {
                messageEl.innerHTML = customMessage;
            }
        }
        
        // Deshabilitar contexto menú y teclas de desarrollador
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                (e.ctrlKey && e.shiftKey && e.key === 'C') ||
                (e.ctrlKey && e.key === 'u')) {
                e.preventDefault();
            }
        });
        
        // Log de evento de logout en el cliente (para analytics)
        try {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'logout', {
                    'event_category': 'authentication',
                    'event_label': logoutType
                });
            }
        } catch (e) {
            // Ignore analytics errors
        }
    </script>
</body>
</html>