-- ========================================
-- Schema de Base de Datos
-- Sistema de Aprobación Multi-Área - Universidad Católica
-- ========================================

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS approval_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE approval_system;

-- ========================================
-- Tabla de usuarios del sistema (clientes que envían proyectos)
-- ========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    department VARCHAR(100),
    title VARCHAR(100),
    phone VARCHAR(50),
    employee_id VARCHAR(50),
    student_id VARCHAR(50),
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    login_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_department (department),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de administradores y revisores por área
-- ========================================
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'supervisor', 'reviewer') DEFAULT 'reviewer',
    areas JSON, -- ['arquitectura', 'infraestructura', etc.]
    permissions JSON, -- Permisos específicos
    status ENUM('active', 'inactive') DEFAULT 'active',
    notification_preferences JSON, -- Preferencias de notificación
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ========================================
-- Tabla principal de proyectos
-- ========================================
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_code VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    status ENUM('draft', 'submitted', 'in_review', 'approved', 'rejected', 'on_hold') DEFAULT 'draft',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    current_stage VARCHAR(50), -- Etapa actual del proyecto
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    estimated_completion_date DATE,
    actual_completion_date DATE,
    budget DECIMAL(15,2),
    department VARCHAR(100),
    technical_lead VARCHAR(255),
    business_owner VARCHAR(255),
    tags JSON, -- Etiquetas del proyecto
    metadata JSON, -- Información adicional
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_code (project_code),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_current_stage (current_stage),
    INDEX idx_created_at (created_at),
    INDEX idx_department (department)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de etapas del proyecto por área
-- ========================================
CREATE TABLE project_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    area_name VARCHAR(50) NOT NULL, -- arquitectura, infraestructura, etc.
    stage_name VARCHAR(100) NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'failed', 'skipped') DEFAULT 'pending',
    assigned_to INT, -- ID del admin/revisor asignado
    order_sequence INT DEFAULT 0,
    estimated_hours DECIMAL(8,2),
    actual_hours DECIMAL(8,2),
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    due_date TIMESTAMP NULL,
    reviewer_notes TEXT,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    required_documents JSON, -- Lista de documentos requeridos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES admins(id) ON SET NULL,
    INDEX idx_project_id (project_id),
    INDEX idx_area_name (area_name),
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_order_sequence (order_sequence),
    INDEX idx_due_date (due_date),
    UNIQUE KEY unique_project_area_stage (project_id, area_name, stage_name)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de feedback y comentarios
-- ========================================
CREATE TABLE project_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    stage_id INT,
    admin_id INT NOT NULL,
    feedback_text TEXT NOT NULL,
    feedback_type ENUM('comment', 'requirement', 'suggestion', 'warning', 'error') DEFAULT 'comment',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    resolved_by INT,
    parent_feedback_id INT, -- Para respuestas a comentarios
    attachments JSON, -- Archivos adjuntos al feedback
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (stage_id) REFERENCES project_stages(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES admins(id) ON SET NULL,
    FOREIGN KEY (parent_feedback_id) REFERENCES project_feedback(id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_stage_id (stage_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_feedback_type (feedback_type),
    INDEX idx_priority (priority),
    INDEX idx_is_resolved (is_resolved),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de plantillas de documentos
-- ========================================
CREATE TABLE document_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(50) NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    version VARCHAR(20) DEFAULT '1.0',
    is_required BOOLEAN DEFAULT TRUE,
    order_sequence INT DEFAULT 0,
    instructions TEXT, -- Instrucciones para llenar el documento
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_area_name (area_name),
    INDEX idx_template_name (template_name),
    INDEX idx_is_required (is_required),
    INDEX idx_order_sequence (order_sequence),
    UNIQUE KEY unique_area_template (area_name, template_name)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de documentos subidos por usuarios
-- ========================================
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    template_id INT, -- Referencia a la plantilla si aplica
    area_name VARCHAR(50) NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    checksum VARCHAR(64), -- Para verificar integridad
    version INT DEFAULT 1, -- Control de versiones
    is_latest BOOLEAN DEFAULT TRUE,
    uploaded_by_user_id INT NOT NULL,
    upload_ip VARCHAR(45),
    status ENUM('uploaded', 'under_review', 'approved', 'rejected', 'requires_changes') DEFAULT 'uploaded',
    review_notes TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES document_templates(id) ON SET NULL,
    FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON SET NULL,
    INDEX idx_project_id (project_id),
    INDEX idx_template_id (template_id),
    INDEX idx_area_name (area_name),
    INDEX idx_uploaded_by_user_id (uploaded_by_user_id),
    INDEX idx_status (status),
    INDEX idx_is_latest (is_latest),
    INDEX idx_version (version),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de historial de cambios
-- ========================================
CREATE TABLE project_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT, -- Usuario que hizo el cambio (puede ser admin o user)
    user_type ENUM('user', 'admin') NOT NULL,
    action VARCHAR(100) NOT NULL, -- 'created', 'updated', 'status_changed', etc.
    description TEXT NOT NULL,
    old_values JSON, -- Valores anteriores
    new_values JSON, -- Valores nuevos
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_user_type (user_type),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de notificaciones
-- ========================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_type ENUM('user', 'admin') NOT NULL,
    notification_type VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    template_used VARCHAR(100),
    status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    opened_at TIMESTAMP NULL,
    clicked_at TIMESTAMP NULL,
    error_message TEXT,
    retry_count INT DEFAULT 0,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_project_id (project_id),
    INDEX idx_recipient_email (recipient_email),
    INDEX idx_recipient_type (recipient_type),
    INDEX idx_notification_type (notification_type),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de configuraciones del sistema
-- ========================================
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json', 'text') DEFAULT 'string',
    category VARCHAR(50) DEFAULT 'general',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE, -- Si el setting es visible públicamente
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de logs del sistema (opcional, complementa al archivo de logs)
-- ========================================
CREATE TABLE system_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    user_id INT,
    session_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_level (level),