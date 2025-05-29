<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

\phpCAS::client(
    CAS_VERSION_2_0,
    'sso-lib.uc.cl',
    443,
    '/cas',
    false
);

// en desarrollo, deshabilita validación SSL
\phpCAS::setNoCasServerValidation();

// fuerza login
\phpCAS::forceAuthentication();

// 3) Fuerza la autenticación (te redirige al CAS si no has hecho login)
\phpCAS::forceAuthentication();

// 4) Usuario y atributos
$email      = \phpCAS::getUser();          // e.g. karl.wennerstrom@uc.cl
$attributes = \phpCAS::getAttributes();    // array de atributos extra

// 5) Conecta a tu base de datos
try {
    $pdo = new PDO(
      "mysql:host=localhost;dbname=approval_system;charset=utf8mb4",
      'root', 'Dracula241988.',
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Error de conexión BD: " . $e->getMessage());
}

// 6) Comprueba si es admin
$stmt = $pdo->prepare("
    SELECT * FROM admins 
     WHERE email = ? AND status = 'active'
");
$stmt->execute([$email]);

if ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Login como admin
    $_SESSION['user_id']    = $admin['id'];
    $_SESSION['user_email'] = $admin['email'];
    $_SESSION['user_name']  = $admin['name'];
    $_SESSION['user_type']  = 'admin';
    $_SESSION['user_role']  = $admin['role'];
    $_SESSION['user_areas'] = json_decode($admin['areas'], true);
    
    // Actualiza último login
    $upd = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
    $upd->execute([$admin['id']]);
    
    header('Location: /aprobacionArquitectura/admin/dashboard.php');
    exit;
}

// 7) Si no es admin, busca o crea en users
$stmt = $pdo->prepare("
    SELECT * FROM users 
     WHERE email = ? AND status = 'active'
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Crear nuevo usuario
    $ins = $pdo->prepare("
      INSERT INTO users 
        (email, name, first_name, last_name, department, status, email_verified)
      VALUES (?, ?, ?, ?, ?, 'active', 1)
    ");
    // Puedes extraer first_name, last_name, dept desde $attributes si CAS los provee
    $ins->execute([
      $email,
      $attributes['displayName']   ?? $email,
      $attributes['givenName']     ?? '',
      $attributes['surname']       ?? '',
      $attributes['department']    ?? ''
    ]);
    $userId = $pdo->lastInsertId();
} else {
    $userId = $user['id'];
}

// 8) Login como cliente
$_SESSION['user_id']    = $userId;
$_SESSION['user_email'] = $email;
$_SESSION['user_name']  = $user['name'] ?? $email;
$_SESSION['user_type']  = 'client';
$_SESSION['user_role']  = 'client';

// Actualiza último login
$upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$upd->execute([$userId]);

header('Location: /aprobacionArquitectura/client/dashboard.php');
exit;
