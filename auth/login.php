<?php
/**
 * Página de autenticación CAS
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

// Configuración básica
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Autoloader básico para cargar las clases
spl_autoload_register(function ($className) {
    $className = str_replace('\\', '/', $className);
    $className = str_replace('UC/ApprovalSystem/', '', $className);
    
    $paths = [
        __DIR__ . '/../src/' . $className . '.php',
        __DIR__ . '/../src/Services/' . basename($className) . '.php',
        __DIR__ . '/../src/Utils/' . basename($className) . '.php',
        __DIR__ . '/../src/Models/' . basename($className) . '.php',
    ];
    
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Cargar configuración de CAS
if (!function_exists('getCASConfig')) {
    function getCASConfig() {
        return [
            'server' => [
                'hostname' => 'sso-lib.uc.cl',
                'port' => 443,
                'uri' => '/cas'
            ],
            'client' => [
                'service_url' => 'http://localhost/aprobacionArquitectura/auth/callback.php'
            ],
            'urls' => [
                'login' => 'https://sso-lib.uc.cl:443/cas/login',
                'logout' => 'https://sso-lib.uc.cl:443/cas/logout',
                'validate' => 'https://sso-lib.uc.cl:443/cas/serviceValidate'
            ],
            'development' => [
                'mock_enabled' => true, // Cambiar a false para usar CAS real
                'mock_users' => [
                    'admin' => [
                        'email' => 'karl.wennerstrom@uc.cl',
                        'name' => 'Karl Wennerstrom',
                        'first_name' => 'Karl',
                        'last_name' => 'Wennerstrom',
                        'department' => 'TI',
                        'title' => 'Administrador Sistema'
                    ],
                    'cliente' => [
                        'email' => 'cliente@uc.cl',
                        'name' => 'Juan Pérez',
                        'first_name' => 'Juan',
                        'last_name' => 'Pérez',
                        'department' => 'Ingeniería',
                        'title' => 'Usuario'
                    ]
                ]
            ],
            'attributes' => [
                'name' => 'displayName',
                'first_name' => 'givenName',
                'last_name' => 'sn',
                'department' => 'department',
                'title' => 'title'
            ]
        ];
    }
}

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'approval_system');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDBConnection() {
    try {
        return new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Verificar si ya está autenticado
if (isset($_SESSION['user_id'])) {
    $userType = $_SESSION['user_type'] ?? 'client';
    if ($userType === 'admin') {
        header('Location: /aprobacionArquitectura/admin/dashboard.php');
    } else {
        header('Location: /aprobacionArquitectura/client/dashboard.php');
    }
    exit;
}

$error = '';
$message = '';
$config = getCASConfig();

// Procesar callback CAS si viene ticket
if (isset($_GET['ticket'])) {
    $ticket = $_GET['ticket'];
    $service = $_GET['service'] ?? $config['client']['service_url'];
    
    try {
        $userData = validateCASTicket($ticket, $service, $config);
        
        if ($userData) {
            $loginResult = processSuccessfulLogin($userData);
            
            if ($loginResult['success']) {
                header('Location: ' . $loginResult['redirect_url']);
                exit;
            } else {
                $error = $loginResult['error'];
            }
        } else {
            $error = 'Error validando ticket CAS';
        }
    } catch (Exception $e) {
        $error = 'Error en autenticación: ' . $e->getMessage();
    }
}

// Funciones de validación CAS
function validateCASTicket($ticket, $service, $config) {
    // Si está en modo mock, simular validación
    if ($config['development']['mock_enabled']) {
        return validateMockTicket($ticket, $config);
    }
    
    // Validación real con CAS
    $validateUrl = $config['urls']['validate'] . '?' . http_build_query([
        'service' => $service,
        'ticket' => $ticket
    ]);
    
    $response = @file_get_contents($validateUrl);
    if (!$response) {
        return null;
    }
    
    return parseValidationResponse($response);
}

function validateMockTicket($ticket, $config) {
    $mockUsers = $config['development']['mock_users'];
    
    // Simular validación exitosa para tickets que empiecen con ST-
    if (strpos($ticket, 'ST-') === 0) {
        // Determinar qué usuario mock usar basado en el ticket
        if (strpos($ticket, 'admin') !== false) {
            return $mockUsers['admin'];
        } else {
            return $mockUsers['cliente'];
        }
    }
    
    return null;
}

function parseValidationResponse($response) {
    $xml = simplexml_load_string($response);
    if (!$xml) return null;
    
    $xml->registerXPathNamespace('cas', 'http://www.yale.edu/tp/cas');
    $success = $xml->xpath('//cas:authenticationSuccess');
    
    if (empty($success)) {
        return null;
    }
    
    $user = $xml->xpath('//cas:user')[0] ?? null;
    if (!$user) return null;
    
    return [
        'email' => (string)$user,
        'name' => (string)$user, // Se puede mejorar extrayendo atributos
        'first_name' => '',
        'last_name' => '',
        'department' => '',
        'title' => ''
    ];
}

function processSuccessfulLogin($casUserData) {
    try {
        $pdo = getDBConnection();
        
        // Buscar en tabla admins primero
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? AND status = 'active'");
        $stmt->execute([$casUserData['email']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            // Login como admin
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['name'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_role'] = $admin['role'];
            $_SESSION['user_areas'] = json_decode($admin['areas'], true);
            
            return [
                'success' => true,
                'user_type' => 'admin',
                'redirect_url' => '/aprobacionArquitectura/admin/dashboard.php'
            ];
        }
        
        // Buscar en tabla users
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$casUserData['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Crear nuevo usuario
            $stmt = $pdo->prepare("
                INSERT INTO users (email, name, first_name, last_name, department, status, email_verified) 
                VALUES (?, ?, ?, ?, ?, 'active', 1)
            ");
            $stmt->execute([
                $casUserData['email'],
                $casUserData['name'],
                $casUserData['first_name'],
                $casUserData['last_name'],
                $casUserData['department']
            ]);
            
            $userId = $pdo->lastInsertId();
            $user = ['id' => $userId] + $casUserData;
        }
        
        // Login como cliente
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = 'client';
        $_SESSION['user_role'] = 'client';
        
        return [
            'success' => true,
            'user_type' => 'client',
            'redirect_url' => '/aprobacionArquitectura/client/dashboard.php'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Error procesando login: ' . $e->getMessage()
        ];
    }
}

// Obtener mensajes de URL
if (isset($_GET['error'])) {
    $errors = [
        'auth_failed' => 'Error de autenticación CAS',
        'cas_error' => 'Error de conexión con CAS',
        'ticket_invalid' => 'Ticket CAS inválido'
    ];
    $error = $errors[$_GET['error']] ?? 'Error desconocido';
}

if (isset($_GET['message']) && $_GET['message'] === 'logout_success') {
    $message = 'Has cerrado sesión correctamente';
}

// URLs de CAS
$casLoginUrl = $config['urls']['login'] . '?service=' . urlencode($config['client']['service_url']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login CAS - Sistema de Aprobación UC</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 300;
            margin-bottom: 0.5rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .cas-login-btn {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            border-radius: 12px;
            padding: 15px 25px;
            color: white;
            font-weight: 500;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
            width: 100%;
        }
        
        .cas-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.4);
            color: white;
        }
        
        .university-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #1e3c72;
            font-weight: bold;
        }
        
        .mock-mode {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .mock-users {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .loading-spinner {
            display: none;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="university-logo">UC</div>
                <h1>Sistema de Aprobación</h1>
                <div>Universidad Católica de Chile</div>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Login CAS Principal -->
                <div class="text-center">
                    <h5 class="mb-3">Autenticación Centralizada</h5>
                    <p class="text-muted mb-4">
                        Usa tu cuenta institucional UC para acceder
                    </p>
                    
                    <a href="<?= htmlspecialchars($casLoginUrl) ?>" 
                       class="btn cas-login-btn d-flex align-items-center justify-content-center"
                       id="casLoginBtn">
                        <i class="fas fa-university me-2"></i>
                        Ingresar con CAS UC
                        <div class="loading-spinner">
                            <div class="spinner-border spinner-border-sm text-light" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Modo Mock -->
                <?php if ($config['development']['mock_enabled']): ?>
                    <div class="mock-mode">
                        <h6 class="mb-2">
                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                            Modo Desarrollo
                        </h6>
                        <p class="mb-2">El sistema está configurado en modo mock. La autenticación CAS es simulada.</p>
                        
                        <div class="d-flex gap-2">
                            <a href="<?= $casLoginUrl ?>&mockuser=admin" class="btn btn-sm btn-outline-primary">
                                Login Admin (Karl)
                            </a>
                            <a href="<?= $casLoginUrl ?>&mockuser=cliente" class="btn btn-sm btn-outline-secondary">
                                Login Cliente
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Información del sistema -->
                <div class="mock-users">
                    <h6 class="mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Información del Sistema
                    </h6>
                    
                    <div class="row">
                        <div class="col-6">
                            <small>
                                <strong>CAS Server:</strong><br>
                                <?= $config['server']['hostname'] ?>
                            </small>
                        </div>
                        <div class="col-6">
                            <small>
                                <strong>Modo:</strong><br>
                                <?= $config['development']['mock_enabled'] ? 'Desarrollo (Mock)' : 'Producción (Real)' ?>
                            </small>
                        </div>
                    </div>
                    
                    <?php if ($config['development']['mock_enabled']): ?>
                        <hr>
                        <small class="text-muted">
                            <strong>Usuarios Mock Disponibles:</strong><br>
                            • karl.wennerstrom@uc.cl (Admin)<br>
                            • cliente@uc.cl (Cliente)
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar spinner al hacer clic en login
        document.getElementById('casLoginBtn').addEventListener('click', function() {
            this.querySelector('.loading-spinner').style.display = 'inline-block';
            this.style.pointerEvents = 'none';
        });
        
        // Verificar si hay errores de CAS en la URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('ticket') && urlParams.get('error')) {
            console.log('Error CAS detectado:', urlParams.get('error'));
        }
        
        // Auto-focus en modo desarrollo
        <?php if ($config['development']['mock_enabled']): ?>
        console.log('Sistema en modo desarrollo - CAS simulado');
        <?php endif; ?>
    </script>
</body>
</html>