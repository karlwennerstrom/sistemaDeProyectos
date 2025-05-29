<?php
/**
 * Configuración Principal de la Aplicación
 * Sistema de Aprobación Multi-Área - Universidad Católica
 * app.php
 */

// Cargar variables de entorno
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configuración de la aplicación
return [
    
    /*
    |--------------------------------------------------------------------------
    | Configuración General
    |--------------------------------------------------------------------------
    */
    'name' => $_ENV['APP_NAME'] ?? 'Sistema de Aprobación UC',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => 'America/Santiago',
    'locale' => 'es',
    'charset' => 'UTF-8',
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Sesión
    |--------------------------------------------------------------------------
    */
    'session' => [
        'name' => $_ENV['SESSION_NAME'] ?? 'approval_system_session',
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200), // 2 horas
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Archivos
    |--------------------------------------------------------------------------
    */
    'files' => [
        'max_size' => (int)($_ENV['MAX_FILE_SIZE'] ?? 10485760), // 10MB
        'allowed_extensions' => explode(',', $_ENV['ALLOWED_EXTENSIONS'] ?? 'pdf,doc,docx,xls,xlsx,ppt,pptx,zip'),
        'upload_path' => $_ENV['UPLOAD_PATH'] ?? 'uploads/documents/',
        'template_path' => $_ENV['TEMPLATE_PATH'] ?? 'uploads/templates/',
        'temp_path' => 'uploads/temp/',
        'allowed_mime_types' => [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad
    |--------------------------------------------------------------------------
    */
    'security' => [
        'csrf_token_name' => $_ENV['CSRF_TOKEN_NAME'] ?? 'csrf_token',
        'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? '',
        'password_min_length' => 8,
        'session_regenerate_interval' => 300, // 5 minutos
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Logs
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'default' => 'file',
        'level' => $_ENV['LOG_LEVEL'] ?? 'info',
        'path' => $_ENV['LOG_PATH'] ?? 'logs/',
        'channels' => [
            'file' => [
                'driver' => 'file',
                'path' => 'logs/app.log',
                'level' => 'debug',
                'max_files' => 30
            ],
            'error' => [
                'driver' => 'file', 
                'path' => 'logs/error.log',
                'level' => 'error',
                'max_files' => 90
            ],
            'auth' => [
                'driver' => 'file',
                'path' => 'logs/auth.log', 
                'level' => 'info',
                'max_files' => 60
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => filter_var($_ENV['CACHE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'lifetime' => (int)($_ENV['CACHE_LIFETIME'] ?? 3600), // 1 hora
        'path' => 'cache/',
        'prefix' => 'approval_system_'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | URLs y Rutas de la Universidad
    |--------------------------------------------------------------------------
    */
    'university' => [
        'name' => 'Universidad Católica',
        'website' => $_ENV['UC_WEBSITE'] ?? 'https://www.uc.cl',
        'logo_url' => $_ENV['UC_LOGO_URL'] ?? 'https://www.uc.cl/images/logo-uc.png',
        'support_email' => 'soporte@uc.cl',
        'architecture_email' => 'arquitectura@uc.cl'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Áreas del Sistema
    |--------------------------------------------------------------------------
    */
    'areas' => [
        'arquitectura' => [
            'name' => 'Arquitectura',
            'description' => 'Validaciones de arquitectura de software',
            'icon' => '🏗️',
            'color' => '#dc2626',
            'order' => 1
        ],
        'infraestructura' => [
            'name' => 'Infraestructura', 
            'description' => 'Validaciones de infraestructura y despliegue',
            'icon' => '🔧',
            'color' => '#2563eb',
            'order' => 2
        ],
        'seguridad' => [
            'name' => 'Seguridad',
            'description' => 'Validaciones de seguridad y cumplimiento',
            'icon' => '🛡️',
            'color' => '#059669',
            'order' => 3
        ],
        'basedatos' => [
            'name' => 'Base de Datos',
            'description' => 'Validaciones de diseño y optimización de BD',
            'icon' => '📊',
            'color' => '#ea580c',
            'order' => 4
        ],
        'integraciones' => [
            'name' => 'Integraciones',
            'description' => 'Validaciones de APIs e integraciones',
            'icon' => '🔗',
            'color' => '#7c3aed',
            'order' => 5
        ],
        'ambientes' => [
            'name' => 'Ambientes',
            'description' => 'Configuración y administración de ambientes',
            'icon' => '🌐',
            'color' => '#0891b2',
            'order' => 6
        ],
        'jcps' => [
            'name' => 'JCPS',
            'description' => 'Validación de escenarios de prueba',
            'icon' => '🔍',
            'color' => '#be185d',
            'order' => 7
        ],
        'monitoreo' => [
            'name' => 'Monitoreo',
            'description' => 'Implementación de monitoreo y observabilidad',
            'icon' => '📈',
            'color' => '#16a34a',
            'order' => 8
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Estados de Proyectos
    |--------------------------------------------------------------------------
    */
    'project_statuses' => [
        'draft' => [
            'name' => 'Borrador',
            'description' => 'Proyecto en preparación',
            'color' => '#6b7280',
            'icon' => '📝'
        ],
        'submitted' => [
            'name' => 'Enviado',
            'description' => 'Proyecto enviado para revisión',
            'color' => '#3b82f6',
            'icon' => '📤'
        ],
        'in_review' => [
            'name' => 'En Revisión',
            'description' => 'Siendo evaluado por las áreas',
            'color' => '#f59e0b',
            'icon' => '🔄'
        ],
        'approved' => [
            'name' => 'Aprobado',
            'description' => 'Proyecto aprobado para implementación',
            'color' => '#10b981',
            'icon' => '✅'
        ],
        'rejected' => [
            'name' => 'Rechazado',
            'description' => 'Proyecto rechazado, requiere cambios',
            'color' => '#ef4444',
            'icon' => '❌'
        ],
        'on_hold' => [
            'name' => 'En Pausa',
            'description' => 'Proyecto pausado temporalmente',
            'color' => '#8b5cf6',
            'icon' => '⏸️'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Prioridades
    |--------------------------------------------------------------------------
    */
    'priorities' => [
        'low' => [
            'name' => 'Baja',
            'color' => '#10b981',
            'weight' => 1
        ],
        'medium' => [
            'name' => 'Media', 
            'color' => '#f59e0b',
            'weight' => 2
        ],
        'high' => [
            'name' => 'Alta',
            'color' => '#ef4444',
            'weight' => 3
        ],
        'critical' => [
            'name' => 'Crítica',
            'color' => '#7c2d12',
            'weight' => 4
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Notificaciones
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => true,
        'types' => [
            'project_submitted' => [
                'name' => 'Proyecto Enviado',
                'template' => 'project_submitted',
                'to' => ['admin', 'area_reviewer']
            ],
            'project_approved' => [
                'name' => 'Proyecto Aprobado',
                'template' => 'project_approved', 
                'to' => ['client', 'next_area']
            ],
            'project_rejected' => [
                'name' => 'Proyecto Rechazado',
                'template' => 'project_rejected',
                'to' => ['client']
            ],
            'document_uploaded' => [
                'name' => 'Documento Subido',
                'template' => 'document_uploaded',
                'to' => ['area_reviewer']
            ],
            'feedback_added' => [
                'name' => 'Feedback Agregado',
                'template' => 'feedback_added',
                'to' => ['client']
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Plantillas de Documentos
    |--------------------------------------------------------------------------
    */
    'document_templates' => [
        'formalizacion' => [
            'ficha_formalizacion.docx' => 'Ficha de Formalización de Proyecto',
            'presupuesto_template.xlsx' => 'Plantilla de Presupuesto'
        ],
        'arquitectura' => [
            'requerimientos_tecnicos.docx' => 'Requerimientos Técnicos y Operacionales',
            'especificacion_funcional.docx' => 'Documento de Especificación Funcional',
            'planificacion_definitiva.docx' => 'Planificación Definitiva'
        ],
        'infraestructura' => [
            'arquitectura_infraestructura.docx' => 'Documento de Arquitectura de Infraestructura',
            'plan_despliegue.docx' => 'Plan de Despliegue'
        ],
        'seguridad' => [
            'analisis_seguridad.docx' => 'Análisis de Seguridad',
            'politicas_acceso.docx' => 'Políticas de Acceso y Autenticación'
        ],
        'basedatos' => [
            'diseno_bd.docx' => 'Diseño de Base de Datos',
            'plan_migracion.docx' => 'Plan de Migración de Datos'
        ],
        'ambientes' => [
            'solicitud_ambientes.docx' => 'Solicitud de Creación de Ambientes',
            'configuracion_ambientes.docx' => 'Configuración de Ambientes'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Desarrollo
    |--------------------------------------------------------------------------
    */
    'development' => [
        'show_errors' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'log_queries' => false,
        'mock_cas' => filter_var($_ENV['MOCK_CAS'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'fake_users' => [
            'admin@uc.cl' => 'Administrador Sistema',
            'arquitecto@uc.cl' => 'Jefe de Arquitectura',
            'test@uc.cl' => 'Usuario de Prueba'
        ]
    ]
];