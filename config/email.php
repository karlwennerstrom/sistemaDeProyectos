<?php
/**
 * Configuración de Email
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Configuración SMTP
    |--------------------------------------------------------------------------
    */
    'smtp' => [
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls', // tls, ssl, null
        'auth' => true,
        'timeout' => 30,
        'keepalive' => false
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración del Remitente
    |--------------------------------------------------------------------------
    */
    'from' => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'sistema@uc.cl',
        'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Sistema de Aprobación UC'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Plantillas de Email
    |--------------------------------------------------------------------------
    */
    'templates' => [
        
        'project_submitted' => [
            'subject' => 'Proyecto Recibido - {{project_code}}',
            'template' => 'emails/project_submitted.html',
            'variables' => ['project_code', 'project_title', 'client_name', 'dashboard_url']
        ],
        
        'project_approved' => [
            'subject' => 'Proyecto Aprobado - {{project_code}}',
            'template' => 'emails/project_approved.html',
            'variables' => ['project_code', 'project_title', 'area_name', 'next_stage', 'dashboard_url']
        ],
        
        'project_rejected' => [
            'subject' => 'Proyecto Requiere Modificaciones - {{project_code}}',
            'template' => 'emails/project_rejected.html',
            'variables' => ['project_code', 'project_title', 'rejection_reason', 'feedback_list', 'dashboard_url']
        ],
        
        'document_uploaded' => [
            'subject' => 'Documento Subido - {{project_code}}',
            'template' => 'emails/document_uploaded.html',
            'variables' => ['project_code', 'project_title', 'document_name', 'client_name', 'admin_url']
        ],
        
        'feedback_added' => [
            'subject' => 'Nuevo Comentario en Proyecto - {{project_code}}',
            'template' => 'emails/feedback_added.html',
            'variables' => ['project_code', 'project_title', 'area_name', 'feedback_text', 'dashboard_url']
        ],
        
        'stage_completed' => [
            'subject' => 'Etapa Completada - {{project_code}}',
            'template' => 'emails/stage_completed.html',
            'variables' => ['project_code', 'project_title', 'stage_name', 'area_name', 'dashboard_url']
        ],
        
        'project_assigned' => [
            'subject' => 'Proyecto Asignado para Revisión - {{project_code}}',
            'template' => 'emails/project_assigned.html',
            'variables' => ['project_code', 'project_title', 'area_name', 'reviewer_name', 'admin_url']
        ],
        
        'reminder_pending_review' => [
            'subject' => 'Recordatorio: Proyecto Pendiente de Revisión - {{project_code}}',
            'template' => 'emails/reminder_pending.html',
            'variables' => ['project_code', 'project_title', 'days_pending', 'area_name', 'admin_url']
        ],
        
        'project_deadline_warning' => [
            'subject' => 'Advertencia: Plazo de Proyecto Próximo a Vencer - {{project_code}}',
            'template' => 'emails/deadline_warning.html',
            'variables' => ['project_code', 'project_title', 'deadline_date', 'days_remaining', 'dashboard_url']
        ],
        
        'bulk_action_completed' => [
            'subject' => 'Acción Masiva Completada - {{action_type}}',
            'template' => 'emails/bulk_action.html',
            'variables' => ['action_type', 'affected_projects', 'admin_name', 'completion_time']
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Colas de Email
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled' => false, // Para futuras implementaciones
        'driver' => 'database', // database, redis, file
        'table' => 'email_queue',
        'retry_attempts' => 3,
        'retry_delay' => 300, // 5 minutos
        'batch_size' => 50
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Notificaciones por Área
    |--------------------------------------------------------------------------
    */
    'area_notifications' => [
        'arquitectura' => [
            'emails' => [
                'arquitecto@uc.cl',
                'carlos.mendoza@uc.cl'
            ],
            'notifications' => [
                'project_assigned',
                'document_uploaded',
                'reminder_pending_review'
            ]
        ],
        'infraestructura' => [
            'emails' => [
                'infraestructura@uc.cl',
                'ana.torres@uc.cl'
            ],
            'notifications' => [
                'project_assigned',
                'document_uploaded',
                'reminder_pending_review'
            ]
        ],
        'seguridad' => [
            'emails' => [
                'seguridad@uc.cl',
                'luis.garcia@uc.cl'
            ],
            'notifications' => [
                'project_assigned',
                'document_uploaded',
                'reminder_pending_review'
            ]
        ],
        'basedatos' => [
            'emails' => [
                'dba@uc.cl',
                'carmen.lopez@uc.cl'
            ],
            'notifications' => [
                'project_assigned',
                'document_uploaded',
                'reminder_pending_review'
            ]
        ],
        'integraciones' => [
            'emails' => [
                'integraciones@uc.cl',
                'mario.vargas@uc.cl'
            ],
            'notifications' => [
                'project_assigned',
                'document_uploaded',
                'reminder_pending_review'
            ]
        ],
        'ambientes' => [
            'emails' => [
                'ambientes@uc.cl',
                'david.chen@uc.cl'
            ],
            'notifications' => [
                'project_assigned',
                'document_uploaded',
                'reminder_pending_review'
            ]
        ],
        'jcps' => [
            'emails' => [
                'jcps@uc.cl',
                'patricia.soto@uc.cl'
            ],
            'notifications' => [
                'project_assigned',
                'document_uploaded',
                'reminder_pending_review'
            ]
        ],
        'monitoreo' => [
            'emails' => [
                'monitoreo@uc.cl',
                'ricardo.flores@uc.cl'
            ],
            'notifications' => [
                'project_assigned',
                'document_uploaded',
                'reminder_pending_review'
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Recordatorios Automáticos
    |--------------------------------------------------------------------------
    */
    'reminders' => [
        'enabled' => true,
        'schedules' => [
            'daily_pending' => [
                'time' => '09:00',
                'template' => 'reminder_pending_review',
                'condition' => 'pending_days >= 3'
            ],
            'weekly_summary' => [
                'day' => 'monday',
                'time' => '08:00',
                'template' => 'weekly_summary',
                'recipients' => 'area_supervisors'
            ],
            'deadline_warning' => [
                'time' => '10:00',
                'template' => 'project_deadline_warning',
                'condition' => 'deadline_days <= 3'
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Límites y Throttling
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_emails_per_hour' => 100,
        'max_emails_per_day' => 1000,
        'throttle_enabled' => true,
        'throttle_delay' => 2, // segundos entre emails
        'max_recipients_per_email' => 10,
        'max_attachments' => 5,
        'max_attachment_size' => 5242880 // 5MB
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Logs de Email
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'log_level' => 'info',
        'log_file' => 'logs/email.log',
        'log_sent_emails' => true,
        'log_failed_emails' => true,
        'log_attachments' => false,
        'retention_days' => 30
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Desarrollo y Testing
    |--------------------------------------------------------------------------
    */
    'development' => [
        'catch_all_emails' => filter_var($_ENV['MAIL_CATCH_ALL'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'catch_all_address' => $_ENV['MAIL_CATCH_ALL_ADDRESS'] ?? 'test@uc.cl',
        'fake_send' => filter_var($_ENV['MAIL_FAKE_SEND'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'preview_mode' => false, // Mostrar emails en el navegador en lugar de enviarlos
        'save_to_file' => false, // Guardar emails como archivos HTML
        'file_path' => 'storage/emails/'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Firma de Email
    |--------------------------------------------------------------------------
    */
    'signature' => [
        'enabled' => true,
        'template' => '
            <br><br>
            <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 20px;">
                <table style="width: 100%; font-family: Arial, sans-serif;">
                    <tr>
                        <td style="width: 60px;">
                            <img src="{{logo_url}}" alt="UC Logo" style="width: 50px; height: auto;">
                        </td>
                        <td style="vertical-align: top;">
                            <strong style="color: #1e40af;">Sistema de Aprobación Multi-Área</strong><br>
                            <span style="color: #6b7280;">Universidad Católica</span><br>
                            <a href="{{website_url}}" style="color: #3b82f6;">www.uc.cl</a>
                        </td>
                    </tr>
                </table>
                <p style="font-size: 12px; color: #9ca3af; margin-top: 15px;">
                    Este es un mensaje automático del Sistema de Aprobación UC. 
                    Para consultas, contacta a: <a href="mailto:soporte@uc.cl">soporte@uc.cl</a>
                </p>
            </div>
        ',
        'variables' => [
            'logo_url' => $_ENV['UC_LOGO_URL'] ?? 'https://www.uc.cl/images/logo-uc.png',
            'website_url' => $_ENV['UC_WEBSITE'] ?? 'https://www.uc.cl'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Plantillas por Defecto
    |--------------------------------------------------------------------------
    */
    'default_template' => [
        'header' => '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{{subject}}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                    .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; padding: 30px; text-align: center; }
                    .content { padding: 30px; line-height: 1.6; }
                    .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; }
                    .btn { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                    .status-approved { color: #059669; font-weight: bold; }
                    .status-rejected { color: #dc2626; font-weight: bold; }
                    .status-pending { color: #d97706; font-weight: bold; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>🏛️ Universidad Católica</h1>
                        <p>Sistema de Aprobación de Arquitectura</p>
                    </div>
                    <div class="content">
        ',
        'footer' => '
                    </div>
                    {{signature}}
                </div>
            </body>
            </html>
        '
    ]
];