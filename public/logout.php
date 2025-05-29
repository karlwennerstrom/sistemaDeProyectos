<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
use UC\ApprovalSystem\Controllers\AuthController;

(new AuthController())->logout();
