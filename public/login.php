<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
use UC\ApprovalSystem\Controllers\AuthController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new AuthController())->login($_POST);
    exit;
}

include __DIR__ . '/../views/auth/login.php';
