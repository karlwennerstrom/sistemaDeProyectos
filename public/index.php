<?php
// public/index.php
session_start();
require __DIR__ . '/../vendor/autoload.php';

use UC\ApprovalSystem\Controllers\AuthController;
use UC\ApprovalSystem\Controllers\AdminController;
use UC\ApprovalSystem\Controllers\ProjectController;

// Ruta via ?route=…
$route = $_GET['route'] ?? 'login';

switch ($route) {
    case 'login':
        (new AuthController())->showLoginForm();
        break;
    case 'login.submit':
        (new AuthController())->login($_POST);
        break;
    case 'logout':
        (new AuthController())->logout();
        break;
    case 'callback':
        (new AuthController())->handleCallback();
        break;
    case 'admin/dashboard':
        (new AdminController())->dashboard();
        break;
    case 'client/dashboard':
        (new ProjectController())->clientDashboard();
        break;
    default:
        http_response_code(404);
        include __DIR__ . '/errors/404.php';
        break;
}
