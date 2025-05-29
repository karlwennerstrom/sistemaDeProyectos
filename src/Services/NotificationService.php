<?php
/**
 * Servicio de Notificaciones
 * Sistema de Aprobación Multi-Área - Universidad Católica
 */

namespace UC\ApprovalSystem\Services;

use UC\ApprovalSystem\Utils\Logger;
use UC\ApprovalSystem\Utils\Database;
use UC\ApprovalSystem\Models\Project;
use UC\ApprovalSystem\Models\User;
use UC\ApprovalSystem\Models\Admin;

class NotificationService 
{
    private $emailService;
    private $db;
    private $config;
    
    public function __construct() 
    {
        $this->emailService = new EmailService();
        $this->db = Database::getInstance();
        $this->config = include __DIR__ . '/../../config/app.php';
    }
    
    /**
     * Notificar proyecto enviado
     */
    public function notifyProjectSubmitted(Project $project): bool 
    {
        try {
            $user = $project->user();
            if (!$user) {
                Logger::error('Usuario no encontrado para proyecto', ['project_id' => $project->id]);
                return false;
            }
            
            // Enviar email
            $emailSent = $this->emailService->sendProjectSubmitted(
                $project->id,
                $project->project_code,
                $project->title,
                $user->getFullName(),
                $user->email
            );
            
            // Registrar notificación
            $this->recordNotification([
                'project_id' => $project->id,
                'recipient_email' => $user->email,
                'recipient_type' => 'user',
                'notification_type' => 'project_submitted',
                'subject' => "Proyecto Recibido - {$project->project_code}",
                'message' => "Tu proyecto '{$project->title}' ha sido recibido y está en proceso de revisión.",
                'status' => $emailSent ? 'sent' : 'failed'
            ]);
            
            // Notificar a administradores del área correspondiente
            $this->notifyAreaReviewers($project, 'project_submitted');
            
            return $emailSent;
            
        } catch (\Exception $e) {
            Logger::error('Error notificando proyecto enviado: ' . $e->getMessage(), [
                'project_id' => $project->id
            ]);
            return false;
        }
    }
    
    /**
     * Notificar proyecto aprobado por área
     */
    public function notifyProjectApproved(Project $project, string $areaName, Admin $approver): bool 
    {
        try {
            $user = $project->user();
            if (!$user) {
                return false;
            }
            
            // Enviar email al cliente
            $emailSent = $this->emailService->sendProjectApproved(
                $project->id,
                $project->project_code,
                $project->title,
                $areaName,
                $user->email,
                $user->getFullName()
            );
            
            // Registrar notificación
            $this->recordNotification([
                'project_id' => $project->id,
                'recipient_email' => $user->email,
                'recipient_type' => 'user',
                'notification_type' => 'project_approved',
                'subject' => "Proyecto Aprobado - {$project->project_code}",
                'message' => "Tu proyecto ha sido aprobado por el área de {$areaName}.",
                'status' => $emailSent ? 'sent' : 'failed'
            ]);
            
            Logger::info('Proyecto aprobado - notificación enviada', [
                'project_id' => $project->id,
                'area' => $areaName,
                'approver_id' => $approver->id,
                'client_email' => $user->email
            ]);
            
            return $emailSent;
            
        } catch (\Exception $e) {
            Logger::error('Error notificando aprobación: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'area' => $areaName
            ]);
            return false;
        }
    }
    
    /**
     * Notificar proyecto rechazado
     */
    public function notifyProjectRejected(Project $project, string $rejectionReason, array $feedbackList): bool 
    {
        try {
            $user = $project->user();
            if (!$user) {
                return false;
            }
            
            // Enviar email
            $emailSent = $this->emailService->sendProjectRejected(
                $project->id,
                $project->project_code,
                $project->title,
                $rejectionReason,
                $feedbackList,
                $user->email,
                $user->getFullName()
            );
            
            // Registrar notificación
            $this->recordNotification([
                'project_id' => $project->id,
                'recipient_email' => $user->email,
                'recipient_type' => 'user',
                'notification_type' => 'project_rejected',
                'subject' => "Proyecto Requiere Modificaciones - {$project->project_code}",
                'message' => "Tu proyecto requiere modificaciones: {$rejectionReason}",
                'status' => $emailSent ? 'sent' : 'failed',
                'metadata' => json_encode(['feedback_count' => count($feedbackList)])
            ]);
            
            return $emailSent;
            
        } catch (\Exception $e) {
            Logger::error('Error notificando rechazo: ' . $e->getMessage(), [
                'project_id' => $project->id
            ]);
            return false;
        }
    }
    
    /**
     * Notificar documento subido
     */
    public function notifyDocumentUploaded(Project $project, string $documentName, string $areaName): bool 
    {
        try {
            $user = $project->user();
            if (!$user) {
                return false;
            }
            
            // Enviar email a revisores del área
            $emailSent = $this->emailService->sendDocumentUploaded(
                $project->id,
                $project->project_code,
                $project->title,
                $documentName,
                $areaName,
                $user->getFullName()
            );
            
            // Notificar a revisores específicos del área
            $this->notifyAreaReviewers($project, 'document_uploaded', [
                'document_name' => $documentName,
                'area_name' => $areaName
            ]);
            
            return $emailSent;
            
        } catch (\Exception $e) {
            Logger::error('Error notificando documento subido: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'document' => $documentName,
                'area' => $areaName
            ]);
            return false;
        }
    }
    
    /**
     * Notificar feedback agregado
     */
    public function notifyFeedbackAdded(Project $project, string $areaName, string $feedbackText, Admin $reviewer): bool 
    {
        try {
            $user = $project->user();
            if (!$user) {
                return false;
            }
            
            // Enviar email al cliente
            $emailSent = $this->emailService->sendFeedbackAdded(
                $project->id,
                $project->project_code,
                $project->title,
                $areaName,
                $feedbackText,
                $user->email,
                $user->getFullName()
            );
            
            // Registrar notificación
            $this->recordNotification([
                'project_id' => $project->id,
                'recipient_email' => $user->email,
                'recipient_type' => 'user',
                'notification_type' => 'feedback_added',
                'subject' => "Nuevo Comentario en Proyecto - {$project->project_code}",
                'message' => "Se ha agregado un comentario en tu proyecto desde el área de {$areaName}.",
                'status' => $emailSent ? 'sent' : 'failed',
                'metadata' => json_encode(['reviewer_id' => $reviewer->id])
            ]);
            
            return $emailSent;
            
        } catch (\Exception $e) {
            Logger::error('Error notificando feedback: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'area' => $areaName
            ]);
            return false;
        }
    }
    
    /**
     * Notificar asignación de proyecto
     */
    public function notifyProjectAssigned(Project $project, Admin $reviewer, string $areaName): bool 
    {
        try {
            // Verificar si el revisor quiere recibir esta notificación
            if (!$reviewer->shouldReceiveNotification('project_assigned')) {
                return true; // No es error, simplemente no quiere la notificación
            }
            
            // Enviar email
            $emailSent = $this->emailService->sendProjectAssigned(
                $project->id,
                $project->project_code,
                $project->title,
                $areaName,
                $reviewer->email,
                $reviewer->name
            );
            
            // Registrar notificación
            $this->recordNotification([
                'project_id' => $project->id,
                'recipient_email' => $reviewer->email,
                'recipient_type' => 'admin',
                'notification_type' => 'project_assigned',
                'subject' => "Proyecto Asignado para Revisión - {$project->project_code}",
                'message' => "Se te ha asignado un proyecto para revisión en el área de {$areaName}.",
                'status' => $emailSent ? 'sent' : 'failed'
            ]);
            
            return $emailSent;
            
        } catch (\Exception $e) {
            Logger::error('Error notificando asignación: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'reviewer_id' => $reviewer->id,
                'area' => $areaName
            ]);
            return false;
        }
    }
    
    /**
     * Enviar recordatorios de proyectos pendientes
     */
    public function sendPendingReminders(): int 
    {
        try {
            $remindersSent = 0;
            
            // Obtener proyectos pendientes por más de 3 días
            $query = "SELECT p.*, ps.area_name, ps.assigned_to, ps.due_date,
                        DATEDIFF(NOW(), ps.start_date) as days_pending
                      FROM projects p
                      JOIN project_stages ps ON p.id = ps.project_id
                      WHERE ps.status = 'in_progress'
                      AND ps.start_date <= DATE_SUB(NOW(), INTERVAL 3 DAY)
                      AND (ps.due_date IS NULL OR ps.due_date > NOW())";
            
            $pendingProjects = $this->db->select($query);
            
            // Agrupar por área y revisor
            $groupedProjects = [];
            foreach ($pendingProjects as $projectData) {
                $areaName = $projectData['area_name'];
                $reviewerId = $projectData['assigned_to'];
                
                if (!isset($groupedProjects[$areaName][$reviewerId])) {
                    $groupedProjects[$areaName][$reviewerId] = [];
                }
                
                $groupedProjects[$areaName][$reviewerId][] = $projectData;
            }
            
            // Enviar recordatorios agrupados
            foreach ($groupedProjects as $areaName => $reviewers) {
                foreach ($reviewers as $reviewerId => $projects) {
                    if ($reviewerId) {
                        $reviewer = Admin::find($reviewerId);
                        if ($reviewer && $reviewer->shouldReceiveNotification('reminder_pending')) {
                            $this->sendPendingReminderToReviewer($reviewer, $areaName, $projects);
                            $remindersSent++;
                        }
                    }
                }
            }
            
            Logger::info('Recordatorios de proyectos pendientes enviados', [
                'reminders_sent' => $remindersSent
            ]);
            
            return $remindersSent;
            
        } catch (\Exception $e) {
            Logger::error('Error enviando recordatorios: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Enviar recordatorio a revisor específico
     */
    private function sendPendingReminderToReviewer(Admin $reviewer, string $areaName, array $projects): bool 
    {
        try {
            $projectList = [];
            foreach ($projects as $project) {
                $projectList[] = [
                    'code' => $project['project_code'],
                    'title' => $project['title'],
                    'days_pending' => $project['days_pending']
                ];
            }
            
            // Enviar email con lista de proyectos pendientes
            $emailSent = $this->emailService->sendPendingReminder(
                $projects[0]['id'], // Usar primer proyecto como referencia
                $projects[0]['project_code'],
                $projects[0]['title'],
                $projects[0]['days_pending'],
                $areaName,
                [$reviewer->email]
            );
            
            // Registrar notificaciones para cada proyecto
            foreach ($projects as $project) {
                $this->recordNotification([
                    'project_id' => $project['id'],
                    'recipient_email' => $reviewer->email,
                    'recipient_type' => 'admin',
                    'notification_type' => 'reminder_pending_review',
                    'subject' => "Recordatorio: Proyecto Pendiente - {$project['project_code']}",
                    'message' => "Tienes proyectos pendientes de revisión en {$areaName}.",
                    'status' => $emailSent ? 'sent' : 'failed',
                    'metadata' => json_encode(['projects_count' => count($projects)])
                ]);
            }
            
            return $emailSent;
            
        } catch (\Exception $e) {
            Logger::error('Error enviando recordatorio a revisor: ' . $e->getMessage(), [
                'reviewer_id' => $reviewer->id,
                'area' => $areaName
            ]);
            return false;
        }
    }
    
    /**
     * Enviar advertencias de fechas límite
     */
    public function sendDeadlineWarnings(): int 
    {
        try {
            $warningsSent = 0;
            
            // Obtener proyectos que vencen en 3 días o menos
            $query = "SELECT p.*, u.email as user_email, u.name as user_name
                      FROM projects p
                      JOIN users u ON p.user_id = u.id
                      WHERE p.estimated_completion_date IS NOT NULL
                      AND p.estimated_completion_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
                      AND p.status IN ('submitted', 'in_review')";
            
            $expiringProjects = $this->db->select($query);
            
            foreach ($expiringProjects as $projectData) {
                $daysRemaining = ceil((strtotime($projectData['estimated_completion_date']) - time()) / (24 * 60 * 60));
                
                $emailSent = $this->emailService->sendDeadlineWarning(
                    $projectData['id'],
                    $projectData['project_code'],
                    $projectData['title'],
                    $projectData['estimated_completion_date'],
                    $daysRemaining,
                    $projectData['user_email'],
                    $projectData['user_name']
                );
                
                if ($emailSent) {
                    $warningsSent++;
                }
                
                // Registrar notificación
                $this->recordNotification([
                    'project_id' => $projectData['id'],
                    'recipient_email' => $projectData['user_email'],
                    'recipient_type' => 'user',
                    'notification_type' => 'project_deadline_warning',
                    'subject' => "Advertencia: Plazo Próximo a Vencer - {$projectData['project_code']}",
                    'message' => "Tu proyecto vence en {$daysRemaining} días.",
                    'status' => $emailSent ? 'sent' : 'failed',
                    'metadata' => json_encode(['days_remaining' => $daysRemaining])
                ]);
            }
            
            Logger::info('Advertencias de fecha límite enviadas', [
                'warnings_sent' => $warningsSent
            ]);
            
            return $warningsSent;
            
        } catch (\Exception $e) {
            Logger::error('Error enviando advertencias de fecha límite: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Enviar resúmenes semanales
     */
    public function sendWeeklySummaries(): int 
    {
        try {
            $summariesSent = 0;
            $areas = array_keys($this->config['areas']);
            
            foreach ($areas as $areaName) {
                $summaryData = $this->getWeeklySummaryData($areaName);
                $supervisors = $this->getSupervisorEmails($areaName);
                
                if (!empty($supervisors)) {
                    $emailSent = $this->emailService->sendWeeklySummary(
                        $areaName,
                        $summaryData,
                        $supervisors
                    );
                    
                    if ($emailSent) {
                        $summariesSent++;
                    }
                    
                    // Registrar notificaciones
                    foreach ($supervisors as $email) {
                        $this->recordNotification([
                            'recipient_email' => $email,
                            'recipient_type' => 'admin',
                            'notification_type' => 'weekly_summary',
                            'subject' => "Resumen Semanal - {$areaName}",
                            'message' => "Resumen semanal de actividades del área {$areaName}.",
                            'status' => $emailSent ? 'sent' : 'failed',
                            'metadata' => json_encode($summaryData)
                        ]);
                    }
                }
            }
            
            Logger::info('Resúmenes semanales enviados', [
                'summaries_sent' => $summariesSent
            ]);
            
            return $summariesSent;
            
        } catch (\Exception $e) {
            Logger::error('Error enviando resúmenes semanales: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Notificar a revisores de área específica
     */
    private function notifyAreaReviewers(Project $project, string $notificationType, array $additionalData = []): void 
    {
        try {
            $currentStage = $project->getCurrentStage();
            if (!$currentStage) {
                return;
            }
            
            $areaName = $currentStage->area_name;
            $reviewers = Admin::findReviewersByArea($areaName);
            
            foreach ($reviewers as $reviewer) {
                if ($reviewer->shouldReceiveNotification($notificationType)) {
                    $this->recordNotification([
                        'project_id' => $project->id,
                        'recipient_email' => $reviewer->email,
                        'recipient_type' => 'admin',
                        'notification_type' => $notificationType,
                        'subject' => $this->getNotificationSubject($notificationType, $project),
                        'message' => $this->getNotificationMessage($notificationType, $project, $areaName),
                        'status' => 'sent',
                        'metadata' => json_encode($additionalData)
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Logger::error('Error notificando a revisores de área: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'notification_type' => $notificationType
            ]);
        }
    }
    
    /**
     * Registrar notificación en base de datos
     */
    private function recordNotification(array $data): bool 
    {
        try {
            $query = "INSERT INTO notifications (
                        project_id, recipient_email, recipient_type, notification_type,
                        subject, message, status, metadata, created_at
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $this->db->insert($query, [
                $data['project_id'] ?? null,
                $data['recipient_email'],
                $data['recipient_type'],
                $data['notification_type'],
                $data['subject'],
                $data['message'],
                $data['status'],
                $data['metadata'] ?? null
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Logger::error('Error registrando notificación: ' . $e->getMessage(), $data);
            return false;
        }
    }
    
    /**
     * Obtener datos de resumen semanal para un área
     */
    private function getWeeklySummaryData(string $areaName): array 
    {
        $startWeek = date('Y-m-d', strtotime('last monday'));
        $endWeek = date('Y-m-d', strtotime('next sunday'));
        
        // Proyectos completados esta semana
        $completedQuery = "SELECT COUNT(*) as count FROM project_stages 
                          WHERE area_name = ? AND status = 'completed' 
                          AND end_date BETWEEN ? AND ?";
        $completed = $this->db->selectOne($completedQuery, [$areaName, $startWeek, $endWeek]);
        
        // Proyectos iniciados esta semana
        $startedQuery = "SELECT COUNT(*) as count FROM project_stages 
                        WHERE area_name = ? AND status IN ('in_progress', 'completed')
                        AND start_date BETWEEN ? AND ?";
        $started = $this->db->selectOne($startedQuery, [$areaName, $startWeek, $endWeek]);
        
        // Proyectos pendientes
        $pendingQuery = "SELECT COUNT(*) as count FROM project_stages 
                        WHERE area_name = ? AND status = 'pending'";
        $pending = $this->db->selectOne($pendingQuery, [$areaName]);
        
        // Proyectos atrasados
        $overdueQuery = "SELECT COUNT(*) as count FROM project_stages 
                        WHERE area_name = ? AND status = 'in_progress' 
                        AND due_date < NOW()";
        $overdue = $this->db->selectOne($overdueQuery, [$areaName]);
        
        return [
            'completed_this_week' => $completed['count'] ?? 0,
            'started_this_week' => $started['count'] ?? 0,
            'pending_projects' => $pending['count'] ?? 0,
            'overdue_projects' => $overdue['count'] ?? 0,
            'week_start' => $startWeek,
            'week_end' => $endWeek
        ];
    }
    
    /**
     * Obtener emails de supervisores para un área
     */
    private function getSupervisorEmails(string $areaName): array 
    {
        $query = "SELECT email FROM admins 
                  WHERE role IN ('supervisor', 'admin') 
                  AND status = 'active'
                  AND (JSON_CONTAINS(areas, JSON_QUOTE(?)) OR JSON_CONTAINS(areas, JSON_QUOTE('all')))";
        
        $supervisors = $this->db->select($query, [$areaName]);
        
        return array_column($supervisors, 'email');
    }
    
    /**
     * Obtener asunto de notificación
     */
    private function getNotificationSubject(string $type, Project $project): string 
    {
        $subjects = [
            'project_submitted' => "Nuevo Proyecto - {$project->project_code}",
            'document_uploaded' => "Documento Subido - {$project->project_code}",
            'project_assigned' => "Proyecto Asignado - {$project->project_code}"
        ];
        
        return $subjects[$type] ?? "Notificación - {$project->project_code}";
    }
    
    /**
     * Obtener mensaje de notificación
     */
    private function getNotificationMessage(string $type, Project $project, string $areaName): string 
    {
        $messages = [
            'project_submitted' => "Un nuevo proyecto '{$project->title}' ha sido enviado para revisión.",
            'document_uploaded' => "Se ha subido un nuevo documento para el proyecto '{$project->title}' en tu área.",
            'project_assigned' => "Se te ha asignado el proyecto '{$project->title}' para revisión en {$areaName}."
        ];
        
        return $messages[$type] ?? "Notificación sobre el proyecto '{$project->title}'.";
    }
    
    /**
     * Obtener estadísticas de notificaciones
     */
    public function getNotificationStats(int $days = 30): array 
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Notificaciones por tipo
        $typeQuery = "SELECT notification_type, COUNT(*) as count 
                     FROM notifications 
                     WHERE created_at >= ? 
                     GROUP BY notification_type";
        $typeStats = $this->db->select($typeQuery, [$startDate]);
        
        // Notificaciones por estado
        $statusQuery = "SELECT status, COUNT(*) as count 
                       FROM notifications 
                       WHERE created_at >= ? 
                       GROUP BY status";
        $statusStats = $this->db->select($statusQuery, [$startDate]);
        
        // Notificaciones por día
        $dailyQuery = "SELECT DATE(created_at) as date, COUNT(*) as count 
                      FROM notifications 
                      WHERE created_at >= ? 
                      GROUP BY DATE(created_at)";
        $dailyStats = $this->db->select($dailyQuery, [$startDate]);
        
        return [
            'total_notifications' => array_sum(array_column($statusStats, 'count')),
            'by_type' => $typeStats,
            'by_status' => $statusStats,
            'daily_breakdown' => $dailyStats,
            'period_days' => $days
        ];
    }
    
    /**
     * Obtener notificaciones recientes
     */
    public function getRecentNotifications(int $limit = 50): array 
    {
        $query = "SELECT n.*, p.project_code, p.title as project_title 
                  FROM notifications n
                  LEFT JOIN projects p ON n.project_id = p.id
                  ORDER BY n.created_at DESC 
                  LIMIT ?";
        
        return $this->db->select($query, [$limit]);
    }
    
    /**
     * Marcar notificación como abierta
     */
    public function markAsOpened(int $notificationId): bool 
    {
        try {
            $query = "UPDATE notifications SET opened_at = NOW() WHERE id = ? AND opened_at IS NULL";
            $this->db->update($query, [$notificationId]);
            return true;
        } catch (\Exception $e) {
            Logger::error('Error marcando notificación como abierta: ' . $e->getMessage(), [
                'notification_id' => $notificationId
            ]);
            return false;
        }
    }
    
    /**
     * Marcar notificación como clickeada
     */
    public function markAsClicked(int $notificationId): bool 
    {
        try {
            $query = "UPDATE notifications SET clicked_at = NOW() WHERE id = ?";
            $this->db->update($query, [$notificationId]);
            return true;
        } catch (\Exception $e) {
            Logger::error('Error marcando notificación como clickeada: ' . $e->getMessage(), [
                'notification_id' => $notificationId
            ]);
            return false;
        }
    }
    
    /**
     * Procesar notificaciones fallidas para reintento
     */
    public function retryFailedNotifications(): int 
    {
        try {
            $maxRetries = 3;
            $retryDelay = 300; // 5 minutos
            
            $query = "SELECT * FROM notifications 
                     WHERE status = 'failed' 
                     AND retry_count < ? 
                     AND created_at <= DATE_SUB(NOW(), INTERVAL ? SECOND)";
            
            $failedNotifications = $this->db->select($query, [$maxRetries, $retryDelay]);
            $retriedCount = 0;
            
            foreach ($failedNotifications as $notification) {
                // Intentar reenviar
                $success = $this->resendNotification($notification);
                
                if ($success) {
                    $this->updateNotificationStatus($notification['id'], 'sent');
                    $retriedCount++;
                } else {
                    $this->incrementRetryCount($notification['id']);
                }
            }
            
            Logger::info('Notificaciones fallidas reintentadas', [
                'processed' => count($failedNotifications),
                'successful_retries' => $retriedCount
            ]);
            
            return $retriedCount;
            
        } catch (\Exception $e) {
            Logger::error('Error reintentando notificaciones fallidas: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Reenviar notificación
     */
    private function resendNotification(array $notification): bool 
    {
        try {
            return $this->emailService->sendEmail(
                [['email' => $notification['recipient_email']]],
                $notification['subject'],
                $notification['message']
            );
        } catch (\Exception $e) {
            Logger::error('Error reenviando notificación: ' . $e->getMessage(), [
                'notification_id' => $notification['id']
            ]);
            return false;
        }
    }
    
    /**
     * Actualizar estado de notificación
     */
    private function updateNotificationStatus(int $notificationId, string $status): void 
    {
        $query = "UPDATE notifications SET status = ?, sent_at = NOW() WHERE id = ?";
        $this->db->update($query, [$status, $notificationId]);
    }
    
    /**
     * Incrementar contador de reintentos
     */
    private function incrementRetryCount(int $notificationId): void 
    {
        $query = "UPDATE notifications SET retry_count = retry_count + 1 WHERE id = ?";
        $this->db->update($query, [$notificationId]);
    }
    
    /**
     * Limpiar notificaciones antiguas
     */
    public function cleanOldNotifications(int $daysToKeep = 90): int 
    {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
            
            $query = "DELETE FROM notifications WHERE created_at < ?";
            $deletedCount = $this->db->delete($query, [$cutoffDate]);
            
            Logger::info('Notificaciones antiguas limpiadas', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate
            ]);
            
            return $deletedCount;
            
        } catch (\Exception $e) {
            Logger::error('Error limpiando notificaciones antiguas: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verificar salud del servicio de notificaciones
     */
    public function healthCheck(): array 
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'email_service' => false,
            'database_connection' => false,
            'recent_notifications' => 0,
            'failed_notifications' => 0
        ];
        
        try {
            // Verificar servicio de email
            $emailHealth = $this->emailService->healthCheck();
            $health['email_service'] = $emailHealth['status'] === 'healthy';
            
            if (!$health['email_service']) {
                $health['status'] = 'unhealthy';
                $health['issues'][] = 'Servicio de email no disponible';
            }
            
            // Verificar conexión a base de datos
            $this->db->select("SELECT 1");
            $health['database_connection'] = true;
            
            // Verificar notificaciones recientes
            $recentQuery = "SELECT COUNT(*) as count FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $recentResult = $this->db->selectOne($recentQuery);
            $health['recent_notifications'] = $recentResult['count'] ?? 0;
            
            // Verificar notificaciones fallidas
            $failedQuery = "SELECT COUNT(*) as count FROM notifications WHERE status = 'failed'";
            $failedResult = $this->db->selectOne($failedQuery);
            $health['failed_notifications'] = $failedResult['count'] ?? 0;
            
            if ($health['failed_notifications'] > 10) {
                $health['status'] = 'warning';
                $health['issues'][] = 'Muchas notificaciones fallidas';
            }
            
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = 'Error verificando salud del servicio: ' . $e->getMessage();
            $health['database_connection'] = false;
        }
        
        return $health;
    }
}