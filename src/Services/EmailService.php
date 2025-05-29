<?php
/**
 * Servicio de Email
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Services;

use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Session;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService 
{
    private $config;
    private $templates;
    private $signature;
    private $defaultTemplate;
    private $areaNotifications;
    
    public function __construct() 
    {
        $this->config = include __DIR__ . '/../../config/email.php';
        $this->templates = $this->config['templates'];
        $this->signature = $this->config['signature'];
        $this->defaultTemplate = $this->config['default_template'];
        $this->areaNotifications = $this->config['area_notifications'];
    }
    
    /**
     * Enviar email usando plantilla
     */
    public function sendTemplateEmail(string $templateName, array $recipients, array $variables = []): bool 
    {
        if (!isset($this->templates[$templateName])) {
            Logger::error('Plantilla de email no encontrada', [
                'template' => $templateName,
                'available_templates' => array_keys($this->templates)
            ]);
            return false;
        }
        
        $template = $this->templates[$templateName];
        
        // Reemplazar variables en el asunto
        $subject = $this->replaceVariables($template['subject'], $variables);
        
        // Cargar contenido de la plantilla
        $body = $this->loadTemplateContent($template['template'], $variables);
        
        if (!$body) {
            Logger::error('Error cargando contenido de plantilla', [
                'template' => $templateName,
                'template_file' => $template['template']
            ]);
            return false;
        }
        
        return $this->sendEmail($recipients, $subject, $body);
    }
    
    /**
     * Enviar email directo
     */
    public function sendEmail(array $recipients, string $subject, string $body, array $attachments = []): bool 
    {
        try {
            $mail = $this->createMailer();
            
            // Configurar remitente
            $fromConfig = $this->config['from'];
            $mail->setFrom('contacto@puc.cl', $fromConfig['name']);
            $mail->addReplyTo('contacto@puc.cl', $fromConfig['name']);
            
            // Agregar destinatarios
            foreach ($recipients as $recipient) {
                if (is_array($recipient)) {
                    $mail->addAddress($recipient['email'], $recipient['name'] ?? '');
                } else {
                    $mail->addAddress($recipient);
                }
            }
            
            // Configurar contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $this->wrapInDefaultTemplate($body, $subject);
            $mail->AltBody = strip_tags($body);
            
            // Agregar archivos adjuntos
            foreach ($attachments as $attachment) {
                if (is_array($attachment)) {
                    $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                } else {
                    $mail->addAttachment($attachment);
                }
            }
            
            // Enviar email
            $sent = $mail->send();
            
            if ($sent) {
                Logger::info('Email enviado exitosamente', [
                    'recipients' => $this->getRecipientEmails($recipients),
                    'subject' => $subject,
                    'attachments_count' => count($attachments),
                    'sent_by' => Session::get('user_id')
                ]);
                
                // Registrar en base de datos si es necesario
                $this->logEmailSent($recipients, $subject, $body);
                
                return true;
            } else {
                Logger::error('Error enviando email', [
                    'recipients' => $this->getRecipientEmails($recipients),
                    'subject' => $subject,
                    'error' => $mail->ErrorInfo
                ]);
                
                return false;
            }
            
        } catch (Exception $e) {
            Logger::error('Excepción enviando email: ' . $e->getMessage(), [
                'recipients' => $this->getRecipientEmails($recipients),
                'subject' => $subject
            ]);
            
            return false;
        }
    }
    
    /**
     * Crear instancia de PHPMailer configurada
     */
    private function createMailer(): PHPMailer 
    {
        $mail = new PHPMailer(true);
        
        // Configuración del servidor SMTP UC
        $mail->isSMTP();
        $mail->Host = 'smtp.puc.cl';
        $mail->Port = 25;
        $mail->SMTPAuth = false; // Sin autenticación
        $mail->SMTPSecure = false; // Sin STARTTLS
        $mail->SMTPAutoTLS = false; // Deshabilitar TLS automático
        
        // Configuración de caracteres
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Configuración de timeout
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        
        // Configuración de debug (solo en desarrollo)
        if ($this->config['development']['fake_send']) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) {
                Logger::debug('SMTP Debug: ' . $str, ['level' => $level]);
            };
        }
        
        return $mail;
    }
    
    /**
     * Cargar contenido de plantilla
     */
    private function loadTemplateContent(string $templateFile, array $variables): ?string 
    {
        $templatePath = __DIR__ . '/../../views/emails/' . $templateFile;
        
        if (!file_exists($templatePath)) {
            Logger::error('Archivo de plantilla no encontrado', [
                'template_path' => $templatePath
            ]);
            return null;
        }
        
        $content = file_get_contents($templatePath);
        
        if ($content === false) {
            Logger::error('Error leyendo archivo de plantilla', [
                'template_path' => $templatePath
            ]);
            return null;
        }
        
        return $this->replaceVariables($content, $variables);
    }
    
    /**
     * Reemplazar variables en contenido
     */
    private function replaceVariables(string $content, array $variables): string 
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Envolver contenido en plantilla por defecto
     */
    private function wrapInDefaultTemplate(string $body, string $subject): string 
    {
        $header = $this->defaultTemplate['header'];
        $footer = $this->defaultTemplate['footer'];
        
        // Reemplazar variables de la plantilla por defecto
        $header = str_replace('{{subject}}', $subject, $header);
        
        // Reemplazar variables de la firma
        $signatureHtml = $this->signature['template'];
        foreach ($this->signature['variables'] as $key => $value) {
            $signatureHtml = str_replace('{{' . $key . '}}', $value, $signatureHtml);
        }
        
        $footer = str_replace('{{signature}}', $signatureHtml, $footer);
        
        return $header . $body . $footer;
    }
    
    /**
     * Obtener emails de lista de destinatarios
     */
    private function getRecipientEmails(array $recipients): array 
    {
        return array_map(function($recipient) {
            return is_array($recipient) ? $recipient['email'] : $recipient;
        }, $recipients);
    }
    
    /**
     * Enviar notificación de proyecto enviado
     */
    public function sendProjectSubmitted(int $projectId, string $projectCode, string $projectTitle, string $clientName, string $clientEmail): bool 
    {
        $variables = [
            'project_code' => $projectCode,
            'project_title' => $projectTitle,
            'client_name' => $clientName,
            'dashboard_url' => $this->getConfig('app_url') . '/dashboard.php'
        ];
        
        // Enviar al cliente
        $clientSent = $this->sendTemplateEmail(
            'project_submitted',
            [['email' => $clientEmail, 'name' => $clientName]],
            $variables
        );
        
        // Enviar a administradores
        $adminEmails = $this->config['authorization']['admin_emails'];
        $adminSent = $this->sendTemplateEmail(
            'project_submitted',
            array_map(fn($email) => ['email' => $email], $adminEmails),
            $variables
        );
        
        return $clientSent && $adminSent;
    }
    
    /**
     * Enviar notificación de proyecto aprobado
     */
    public function sendProjectApproved(int $projectId, string $projectCode, string $projectTitle, string $areaName, string $clientEmail, string $clientName): bool 
    {
        $variables = [
            'project_code' => $projectCode,
            'project_title' => $projectTitle,
            'area_name' => $areaName,
            'next_stage' => $this->getNextStage($areaName),
            'dashboard_url' => $this->getConfig('app_url') . '/dashboard.php'
        ];
        
        return $this->sendTemplateEmail(
            'project_approved',
            [['email' => $clientEmail, 'name' => $clientName]],
            $variables
        );
    }
    
    /**
     * Enviar notificación de proyecto rechazado
     */
    public function sendProjectRejected(int $projectId, string $projectCode, string $projectTitle, string $rejectionReason, array $feedbackList, string $clientEmail, string $clientName): bool 
    {
        $variables = [
            'project_code' => $projectCode,
            'project_title' => $projectTitle,
            'rejection_reason' => $rejectionReason,
            'feedback_list' => $this->formatFeedbackList($feedbackList),
            'dashboard_url' => $this->getConfig('app_url') . '/dashboard.php'
        ];
        
        return $this->sendTemplateEmail(
            'project_rejected',
            [['email' => $clientEmail, 'name' => $clientName]],
            $variables
        );
    }
    
    /**
     * Enviar notificación de documento subido
     */
    public function sendDocumentUploaded(int $projectId, string $projectCode, string $projectTitle, string $documentName, string $areaName, string $clientName): bool 
    {
        if (!isset($this->areaNotifications[$areaName])) {
            Logger::warning('Área no configurada para notificaciones', [
                'area' => $areaName,
                'project_id' => $projectId
            ]);
            return false;
        }
        
        $variables = [
            'project_code' => $projectCode,
            'project_title' => $projectTitle,
            'document_name' => $documentName,
            'client_name' => $clientName,
            'admin_url' => $this->getConfig('app_url') . '/admin/project_detail.php?id=' . $projectId
        ];
        
        $areaEmails = $this->areaNotifications[$areaName]['emails'];
        
        return $this->sendTemplateEmail(
            'document_uploaded',
            array_map(fn($email) => ['email' => $email], $areaEmails),
            $variables
        );
    }
    
    /**
     * Enviar notificación de feedback agregado
     */
    public function sendFeedbackAdded(int $projectId, string $projectCode, string $projectTitle, string $areaName, string $feedbackText, string $clientEmail, string $clientName): bool 
    {
        $variables = [
            'project_code' => $projectCode,
            'project_title' => $projectTitle,
            'area_name' => $areaName,
            'feedback_text' => $feedbackText,
            'dashboard_url' => $this->getConfig('app_url') . '/dashboard.php'
        ];
        
        return $this->sendTemplateEmail(
            'feedback_added',
            [['email' => $clientEmail, 'name' => $clientName]],
            $variables
        );
    }
    
    /**
     * Enviar notificación de asignación de proyecto
     */
    public function sendProjectAssigned(int $projectId, string $projectCode, string $projectTitle, string $areaName, string $reviewerEmail, string $reviewerName): bool 
    {
        $variables = [
            'project_code' => $projectCode,
            'project_title' => $projectTitle,
            'area_name' => $areaName,
            'reviewer_name' => $reviewerName,
            'admin_url' => $this->getConfig('app_url') . '/admin/project_detail.php?id=' . $projectId
        ];
        
        return $this->sendTemplateEmail(
            'project_assigned',
            [['email' => $reviewerEmail, 'name' => $reviewerName]],
            $variables
        );
    }
    
    /**
     * Enviar recordatorio de proyecto pendiente
     */
    public function sendPendingReminder(int $projectId, string $projectCode, string $projectTitle, int $daysPending, string $areaName, array $reviewerEmails): bool 
    {
        $variables = [
            'project_code' => $projectCode,
            'project_title' => $projectTitle,
            'days_pending' => $daysPending,
            'area_name' => $areaName,
            'admin_url' => $this->getConfig('app_url') . '/admin/project_detail.php?id=' . $projectId
        ];
        
        return $this->sendTemplateEmail(
            'reminder_pending_review',
            array_map(fn($email) => ['email' => $email], $reviewerEmails),
            $variables
        );
    }
    
    /**
     * Enviar advertencia de fecha límite
     */
    public function sendDeadlineWarning(int $projectId, string $projectCode, string $projectTitle, string $deadlineDate, int $daysRemaining, string $clientEmail, string $clientName): bool 
    {
        $variables = [
            'project_code' => $projectCode,
            'project_title' => $projectTitle,
            'deadline_date' => $deadlineDate,
            'days_remaining' => $daysRemaining,
            'dashboard_url' => $this->getConfig('app_url') . '/dashboard.php'
        ];
        
        return $this->sendTemplateEmail(
            'project_deadline_warning',
            [['email' => $clientEmail, 'name' => $clientName]],
            $variables
        );
    }
    
    /**
     * Enviar resumen semanal
     */
    public function sendWeeklySummary(string $areaName, array $summaryData, array $supervisorEmails): bool 
    {
        $variables = array_merge($summaryData, [
            'area_name' => $areaName,
            'week_start' => date('Y-m-d', strtotime('last monday')),
            'week_end' => date('Y-m-d', strtotime('next sunday')),
            'admin_url' => $this->getConfig('app_url') . '/admin/dashboard.php'
        ]);
        
        return $this->sendTemplateEmail(
            'weekly_summary',
            array_map(fn($email) => ['email' => $email], $supervisorEmails),
            $variables
        );
    }
    
    /**
     * Enviar notificación de acción masiva
     */
    public function sendBulkActionCompleted(string $actionType, array $affectedProjects, string $adminName, array $adminEmails): bool 
    {
        $variables = [
            'action_type' => $actionType,
            'affected_projects' => $this->formatProjectList($affectedProjects),
            'admin_name' => $adminName,
            'completion_time' => date('Y-m-d H:i:s')
        ];
        
        return $this->sendTemplateEmail(
            'bulk_action_completed',
            array_map(fn($email) => ['email' => $email], $adminEmails),
            $variables
        );
    }
    
    /**
     * Procesar cola de emails (para implementación futura)
     */
    public function processEmailQueue(): int 
    {
        // Esta función podría implementarse para procesar emails en cola
        // Por ahora, los emails se envían directamente
        Logger::info('Procesamiento de cola de emails - no implementado');
        return 0;
    }
    
    /**
     * Enviar email de prueba
     */
    public function sendTestEmail(string $recipient): bool 
    {
        $subject = 'Email de Prueba - Sistema de Aprobación UC';
        $body = '
            <h2>Email de Prueba</h2>
            <p>Este es un email de prueba del Sistema de Aprobación Multi-Área de la Universidad Católica.</p>
            <p><strong>Fecha:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Enviado por:</strong> ' . (Session::get('user_email') ?: 'Sistema') . '</p>
            <p>Si recibes este email, la configuración SMTP está funcionando correctamente.</p>
        ';
        
        return $this->sendEmail([['email' => $recipient]], $subject, $body);
    }
    
    /**
     * Formatear lista de feedback
     */
    private function formatFeedbackList(array $feedbackList): string 
    {
        $html = '<ul>';
        foreach ($feedbackList as $feedback) {
            $html .= '<li><strong>' . $feedback['area'] . ':</strong> ' . $feedback['message'] . '</li>';
        }
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * Formatear lista de proyectos
     */
    private function formatProjectList(array $projects): string 
    {
        $html = '<ul>';
        foreach ($projects as $project) {
            $html .= '<li>' . $project['code'] . ' - ' . $project['title'] . '</li>';
        }
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * Obtener siguiente etapa
     */
    private function getNextStage(string $currentArea): string 
    {
        $appConfig = include __DIR__ . '/../../config/app.php';
        $areas = array_keys($appConfig['areas']);
        
        $currentIndex = array_search($currentArea, $areas);
        $nextIndex = $currentIndex + 1;
        
        if ($nextIndex < count($areas)) {
            return $appConfig['areas'][$areas[$nextIndex]]['name'];
        }
        
        return 'Finalización del proyecto';
    }
    
    /**
     * Registrar email enviado en base de datos
     */
    private function logEmailSent(array $recipients, string $subject, string $body): void 
    {
        // Esta función podría implementarse para registrar emails en la BD
        // Por ahora solo hacemos log
        Logger::info('Email enviado registrado', [
            'recipients_count' => count($recipients),
            'subject' => $subject,
            'body_length' => strlen($body)
        ]);
    }
    
    /**
     * Obtener configuración
     */
    private function getConfig(string $key): string 
    {
        $appConfig = include __DIR__ . '/../../config/app.php';
        return $appConfig[$key] ?? '';
    }
    
    /**
     * Verificar si un email está en modo catch-all (desarrollo)
     */
    private function shouldCatchAll(): bool 
    {
        return $this->config['development']['catch_all_emails'];
    }
    
    /**
     * Obtener estadísticas de email
     */
    public function getEmailStats(): array 
    {
        // Esta función podría expandirse para incluir estadísticas de la BD
        return [
            'smtp_config' => [
                'host' => 'smtp.puc.cl',
                'port' => 25,
                'auth' => false,
                'encryption' => 'none'
            ],
            'from_address' => 'contacto@puc.cl',
            'templates_available' => count($this->templates),
            'areas_configured' => count($this->areaNotifications)
        ];
    }
    
    /**
     * Verificar salud del servicio de email
     */
    public function healthCheck(): array 
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'smtp_connection' => false,
            'templates' => [],
            'configuration' => [
                'host' => 'smtp.puc.cl',
                'port' => 25,
                'auth' => false,
                'from_address' => 'contacto@puc.cl'
            ]
        ];
        
        // Verificar conexión SMTP
        try {
            $mail = $this->createMailer();
            $mail->smtpConnect();
            $health['smtp_connection'] = true;
            $mail->smtpClose();
        } catch (Exception $e) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = 'No se puede conectar al servidor SMTP: ' . $e->getMessage();
        }
        
        // Verificar plantillas
        foreach ($this->templates as $name => $template) {
            $templatePath = __DIR__ . '/../../views/emails/' . $template['template'];
            $exists = file_exists($templatePath);
            
            $health['templates'][$name] = [
                'file' => $template['template'],
                'exists' => $exists,
                'readable' => $exists ? is_readable($templatePath) : false
            ];
            
            if (!$exists) {
                $health['status'] = 'warning';
                $health['issues'][] = "Plantilla faltante: {$name}";
            }
        }
        
        return $health;
    }
}