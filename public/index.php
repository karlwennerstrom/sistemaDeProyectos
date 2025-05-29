<?php
/**
 * Punto de entrada del sistema
 */

require_once '../config/app.php';

// Redirigir a login por defecto
header('Location: /aprobacionArquitectura/auth/login.php');
exit;