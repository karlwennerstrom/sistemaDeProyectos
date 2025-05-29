<?php
/**
 * Autoloader para el sistema
 */

spl_autoload_register(function ($class) {
    // Mapeo de namespaces a directorios
    $prefix_mapping = [
        'UC\\ApprovalSystem\\Controllers\\' => __DIR__ . '/../src/Controllers/',
        'UC\\ApprovalSystem\\Models\\' => __DIR__ . '/../src/Models/',
        'UC\\ApprovalSystem\\Services\\' => __DIR__ . '/../src/Services/',
        'UC\\ApprovalSystem\\Utils\\' => __DIR__ . '/../src/Utils/',
        'Controllers\\' => __DIR__ . '/../src/Controllers/',
        'Models\\' => __DIR__ . '/../src/Models/',
        'Services\\' => __DIR__ . '/../src/Services/',
        'Utils\\' => __DIR__ . '/../src/Utils/',
    ];
    
    foreach ($prefix_mapping as $prefix => $base_dir) {
        if (strpos($class, $prefix) === 0) {
            $relative_class = substr($class, strlen($prefix));
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
    
    // Fallback: buscar en toda la estructura src/
    $class_parts = explode('\\', $class);
    $class_name = end($class_parts);
    
    $search_paths = [
        __DIR__ . '/../src/Controllers/' . $class_name . '.php',
        __DIR__ . '/../src/Models/' . $class_name . '.php',
        __DIR__ . '/../src/Services/' . $class_name . '.php',
        __DIR__ . '/../src/Utils/' . $class_name . '.php',
    ];
    
    foreach ($search_paths as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});