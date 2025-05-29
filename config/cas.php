<?php
/**
 * Configuración CAS (Central Authentication Service)
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Configuración del Servidor CAS
    |--------------------------------------------------------------------------
    */
    'server' => [
        'hostname' => $_ENV['CAS_SERVER'] ?? 'sso-lib.uc.cl',
        'port' => (int)($_ENV['CAS_PORT'] ?? 443),
        'uri' => $_ENV['CAS_URI'] ?? '/cas',
        'version' => $_ENV['CAS_VERSION'] ?? '2.0',
        'debug' => filter_var($_ENV['CAS_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)
    ],
    
    /*
    |--------------------------------------------------------------------------
    | URLs del Sistema CAS
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'login' => 'https://sso-lib.uc.cl/cas/login',
        'logout' => 'https://sso-lib.uc.cl/cas/logout',
        'validate' => 'https://sso-lib.uc.cl/cas/serviceValidate',
        'proxy' => 'https://sso-lib.uc.cl/cas/proxy',
        'proxy_validate' => 'https://sso-lib.uc.cl/cas/proxyValidate'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración del Cliente
    |--------------------------------------------------------------------------
    */
    'client' => [
        'service_url' => $_ENV['APP_URL'] . '/public/callback.php',
        'callback_url' => $_ENV['APP_URL'] . '/public/callback.php',
        'logout_url' => $_ENV['APP_URL'] . '/public/logout.php',
        'certificate_path' => null, // Ruta al certificado SSL si es necesario
        'ca_certificate_path' => null // Ruta al certificado CA si es necesario
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Atributos
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'email' => 'mail',
        'name' => 'displayName',
        'first_name' => 'givenName',
        'last_name' => 'sn',
        'department' => 'department',
        'title' => 'title',
        'phone' => 'telephoneNumber',
        'employee_id' => 'employeeNumber',
        'student_id' => 'studentId',
        'groups' => 'memberOf'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Sesión CAS
    |--------------------------------------------------------------------------
    */
    'session' => [
        'name' => 'cas_session',
        'lifetime' => 7200, // 2 horas
        'check_interval' => 300, // 5 minutos - verificar validez de sesión
        'auto_renew' => true,
        'single_logout' => true
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad
    |--------------------------------------------------------------------------
    */
    'security' => [
        'ssl_verify_peer' => true,
        'ssl_verify_host' => 2,
        'curl_timeout' => 30,
        'curl_connect_timeout' => 10,
        'allowed_proxy_chains' => [],
        'proxy_granting_ticket_storage' => 'file', // file, database, memcache
        'proxy_granting_ticket_path' => 'cache/pgt/'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Logs CAS
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'file' => 'logs/cas.log',
        'log_requests' => true,
        'log_responses' => false // No loggear respuestas por seguridad
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración para Desarrollo
    |--------------------------------------------------------------------------
    */
    'development' => [
        'mock_enabled' => filter_var($_ENV['MOCK_CAS'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'mock_users' => [
            'karl.wennerstrom@uc.cl' => [
                'email' => 'karl.wennerstrom@uc.cl',
                'name' => 'Administrador Sistema',
                'first_name' => 'Admin',
                'last_name' => 'Sistema',
                'department' => 'TI',
                'title' => 'Administrador'
            ],
            'karl.wennerstrom@uc.cl' => [
                'email' => 'karl.wennerstrom@uc.cl',
                'name' => 'Carlos Mendoza',
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza',
                'department' => 'Arquitectura',
                'title' => 'Jefe de Arquitectura'
            ],
            'karl.wennerstrom@uc.cl' => [
                'email' => 'karl.wennerstrom@uc.cl',
                'name' => 'Ana Torres',
                'first_name' => 'Ana',
                'last_name' => 'Torres', 
                'department' => 'Infraestructura',
                'title' => 'Jefa de Infraestructura'
            ],
            'karl.wennerstrom@uc.cl' => [
                'email' => 'karl.wennerstrom@uc.cl',
                'name' => 'Luis García',
                'first_name' => 'Luis',
                'last_name' => 'García',
                'department' => 'Seguridad',
                'title' => 'Especialista en Seguridad'
            ],
            'karl.wennerstrom@uc.cl' => [
                'email' => 'karl.wennerstrom@uc.cl',
                'name' => 'Usuario Prueba',
                'first_name' => 'Usuario',
                'last_name' => 'Prueba',
                'department' => 'Pruebas',
                'title' => 'Tester'
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Roles y Permisos
    |--------------------------------------------------------------------------
    */
    'authorization' => [
        'admin_emails' => [
            'admin@uc.cl',
            'sistema@uc.cl',
            'arquitectura@uc.cl'
        ],
        'area_reviewers' => [
            'arquitectura' => [
                'arquitecto@uc.cl',
                'carlos.mendoza@uc.cl'
            ],
            'infraestructura' => [
                'infraestructura@uc.cl',
                'ana.torres@uc.cl'
            ],
            'seguridad' => [
                'seguridad@uc.cl',
                'luis.garcia@uc.cl'
            ],
            'basedatos' => [
                'dba@uc.cl',
                'carmen.lopez@uc.cl'
            ],
            'integraciones' => [
                'integraciones@uc.cl',
                'mario.vargas@uc.cl'
            ],
            'ambientes' => [
                'ambientes@uc.cl',
                'david.chen@uc.cl'
            ],
            'jcps' => [
                'jcps@uc.cl',
                'patricia.soto@uc.cl'
            ],
            'monitoreo' => [
                'monitoreo@uc.cl',
                'ricardo.flores@uc.cl'
            ]
        ],
        'supervisor_emails' => [
            'supervisor@uc.cl',
            'jefe.ti@uc.cl'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Error Handling
    |--------------------------------------------------------------------------
    */
    'error_handling' => [
        'on_auth_failure' => 'redirect', // redirect, exception, json
        'auth_failure_url' => '/public/login.php?error=auth_failed',
        'on_logout' => 'redirect',
        'logout_redirect_url' => '/public/login.php?logged_out=1',
        'on_session_expired' => 'redirect',
        'session_expired_url' => '/public/login.php?error=session_expired'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'validation_cache_time' => 300, // 5 minutos
        'user_info_cache_time' => 1800, // 30 minutos
        'cache_prefix' => 'cas_'
    ]
];