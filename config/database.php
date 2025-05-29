<?php
/**
 * Configuración de Base de Datos
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */
//database.php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Conexión por Defecto
    |--------------------------------------------------------------------------
    */
    'default' => 'mysql',
    
    /*
    |--------------------------------------------------------------------------
    | Configuraciones de Conexiones
    |--------------------------------------------------------------------------
    */
    'connections' => [
        
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_NAME'] ?? 'approval_system',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? 'Dracula241988.',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]
        ],
        
        'testing' => [
            'driver' => 'mysql',
            'host' => $_ENV['TEST_DB_HOST'] ?? 'localhost',
            'port' => $_ENV['TEST_DB_PORT'] ?? '3306',
            'database' => $_ENV['TEST_DB_NAME'] ?? 'approval_system_test',
            'username' => $_ENV['TEST_DB_USER'] ?? 'root',
            'password' => $_ENV['TEST_DB_PASS'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Pool de Conexiones
    |--------------------------------------------------------------------------
    */
    'pool' => [
        'enabled' => false,
        'max_connections' => 10,
        'min_connections' => 2,
        'timeout' => 30
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Migraciones
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'table' => 'migrations',
        'path' => 'database/migrations'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Backup
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => true,
        'path' => 'backups/',
        'frequency' => 'daily', // daily, weekly, monthly
        'retention_days' => 30,
        'tables' => [
            'users',
            'admins', 
            'projects',
            'project_stages',
            'project_feedback',
            'documents',
            'document_templates',
            'project_history',
            'notifications'
        ]
    ]
];