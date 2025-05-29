<?php
// views/layouts/nav.php

// Asegúrate de que la variable $current_user esté disponible
// (p. ej. la defines en tu BaseController y la pasas a la vista)

if (!isset($current_user)) {
    // Si no hay usuario, podrías redirigir o mostrar un menú genérico
    return;
}

switch ($current_user->role) {
    case 'admin':
        // Menú de administración
        include __DIR__ . '/admin-nav.php';
        break;

    default:
        // Menú de cliente u otros roles
        include __DIR__ . '/client-nav.php';
        break;
}
