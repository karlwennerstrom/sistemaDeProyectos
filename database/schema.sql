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
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de sesiones de usuario (para persistencia de sesiones)
-- ========================================
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    user_email VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB;

-- ========================================
-- Tabla de estadísticas y métricas
-- ========================================
CREATE TABLE system_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,4),
    metric_type ENUM('counter', 'gauge', 'histogram') DEFAULT 'gauge',
    tags JSON, -- Etiquetas adicionales
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_metric_name (metric_name),
    INDEX idx_recorded_at (recorded_at),
    INDEX idx_metric_type (metric_type)
) ENGINE=InnoDB;

-- ========================================
-- DATOS INICIALES
-- ========================================

-- Insertar administradores por defecto
INSERT INTO admins (email, name, role, areas, permissions, status) VALUES 
('admin@uc.cl', 'Administrador Sistema', 'admin', '["all"]', '{"all": true}', 'active'),
('arquitecto@uc.cl', 'Carlos Mendoza', 'reviewer', '["arquitectura"]', '{"arquitectura": {"review": true, "approve": true}}', 'active'),
('infraestructura@uc.cl', 'Ana Torres', 'reviewer', '["infraestructura"]', '{"infraestructura": {"review": true, "approve": true}}', 'active'),
('seguridad@uc.cl', 'Luis García', 'reviewer', '["seguridad"]', '{"seguridad": {"review": true, "approve": true}}', 'active'),
('dba@uc.cl', 'Carmen López', 'reviewer', '["basedatos"]', '{"basedatos": {"review": true, "approve": true}}', 'active'),
('integraciones@uc.cl', 'Mario Vargas', 'reviewer', '["integraciones"]', '{"integraciones": {"review": true, "approve": true}}', 'active'),
('ambientes@uc.cl', 'David Chen', 'reviewer', '["ambientes"]', '{"ambientes": {"review": true, "approve": true}}', 'active'),
('jcps@uc.cl', 'Patricia Soto', 'reviewer', '["jcps"]', '{"jcps": {"review": true, "approve": true}}', 'active'),
('monitoreo@uc.cl', 'Ricardo Flores', 'reviewer', '["monitoreo"]', '{"monitoreo": {"review": true, "approve": true}}', 'active');

-- Insertar plantillas de documentos por área
INSERT INTO document_templates (area_name, template_name, display_name, description, file_path, mime_type, is_required, order_sequence, instructions) VALUES 
-- Formalización
('formalizacion', 'ficha_formalizacion.docx', 'Ficha de Formalización de Proyecto', 'Documento principal para formalizar el proyecto', 'uploads/templates/formalizacion/ficha_formalizacion.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 1, 'Complete todos los campos requeridos con la información del proyecto.'),
('formalizacion', 'presupuesto_template.xlsx', 'Plantilla de Presupuesto', 'Plantilla para detallar el presupuesto del proyecto', 'uploads/templates/formalizacion/presupuesto_template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', TRUE, 2, 'Incluya todos los costos estimados divididos por categorías.'),

-- Arquitectura
('arquitectura', 'requerimientos_tecnicos.docx', 'Requerimientos Técnicos y Operacionales', 'Especificación de requerimientos técnicos del sistema', 'uploads/templates/arquitectura/requerimientos_tecnicos.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 1, 'Detalle los requerimientos funcionales y no funcionales del sistema.'),
('arquitectura', 'especificacion_funcional.docx', 'Documento de Especificación Funcional', 'Especificación detallada de funcionalidades', 'uploads/templates/arquitectura/especificacion_funcional.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 2, 'Describa cada funcionalidad con casos de uso detallados.'),
('arquitectura', 'planificacion_definitiva.docx', 'Planificación Definitiva', 'Plan de proyecto definitivo con cronograma', 'uploads/templates/arquitectura/planificacion_definitiva.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 3, 'Incluya cronograma detallado, hitos y dependencias.'),
('arquitectura', 'diagrama_arquitectura.vsdx', 'Diagrama de Arquitectura', 'Diagrama técnico de la arquitectura del sistema', 'uploads/templates/arquitectura/diagrama_arquitectura.vsdx', 'application/vnd.visio', FALSE, 4, 'Use notación estándar para diagramas de arquitectura.'),

-- Infraestructura
('infraestructura', 'arquitectura_infraestructura.docx', 'Documento de Arquitectura de Infraestructura', 'Diseño de infraestructura tecnológica', 'uploads/templates/infraestructura/arquitectura_infraestructura.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 1, 'Especifique servidores, redes, balanceadores y componentes de infraestructura.'),
('infraestructura', 'plan_despliegue.docx', 'Plan de Despliegue', 'Estrategia y procedimientos de despliegue', 'uploads/templates/infraestructura/plan_despliegue.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 2, 'Incluya procedimientos paso a paso para el despliegue.'),
('infraestructura', 'matriz_ambientes.xlsx', 'Matriz de Ambientes', 'Definición de ambientes de desarrollo, testing y producción', 'uploads/templates/infraestructura/matriz_ambientes.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', TRUE, 3, 'Complete la matriz con las características de cada ambiente.'),

-- Seguridad
('seguridad', 'analisis_seguridad.docx', 'Análisis de Seguridad', 'Evaluación de riesgos y medidas de seguridad', 'uploads/templates/seguridad/analisis_seguridad.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 1, 'Identifique vulnerabilidades potenciales y controles de seguridad.'),
('seguridad', 'politicas_acceso.docx', 'Políticas de Acceso y Autenticación', 'Definición de políticas de acceso al sistema', 'uploads/templates/seguridad/politicas_acceso.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 2, 'Defina roles, permisos y procedimientos de autenticación.'),
('seguridad', 'plan_contingencia.docx', 'Plan de Contingencia', 'Procedimientos ante incidentes de seguridad', 'uploads/templates/seguridad/plan_contingencia.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', FALSE, 3, 'Establezca procedimientos de respuesta ante incidentes.'),

-- Base de Datos
('basedatos', 'diseno_bd.docx', 'Diseño de Base de Datos', 'Modelo de datos y estructura de base de datos', 'uploads/templates/basedatos/diseno_bd.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 1, 'Incluya modelo entidad-relación y scripts de creación.'),
('basedatos', 'plan_migracion.docx', 'Plan de Migración de Datos', 'Estrategia para migración de datos existentes', 'uploads/templates/basedatos/plan_migracion.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 2, 'Detalle el proceso de migración y validación de datos.'),
('basedatos', 'politica_backup.docx', 'Política de Backup y Recuperación', 'Procedimientos de respaldo y recuperación', 'uploads/templates/basedatos/politica_backup.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 3, 'Establezca frecuencia y procedimientos de backup.'),

-- Integraciones
('integraciones', 'matriz_integraciones.xlsx', 'Matriz de Integraciones', 'Mapeo de sistemas e integraciones requeridas', 'uploads/templates/integraciones/matriz_integraciones.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', TRUE, 1, 'Liste todos los sistemas externos y sus interfaces.'),
('integraciones', 'especificacion_apis.docx', 'Especificación de APIs', 'Documentación detallada de interfaces de programación', 'uploads/templates/integraciones/especificacion_apis.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 2, 'Documente endpoints, parámetros y formatos de respuesta.'),

-- Ambientes
('ambientes', 'solicitud_ambientes.docx', 'Solicitud de Creación de Ambientes', 'Formulario para solicitar nuevos ambientes', 'uploads/templates/ambientes/solicitud_ambientes.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 1, 'Especifique recursos necesarios para cada ambiente.'),
('ambientes', 'configuracion_ambientes.docx', 'Configuración de Ambientes', 'Documentación de configuración de ambientes', 'uploads/templates/ambientes/configuracion_ambientes.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 2, 'Documente variables de entorno y configuraciones específicas.'),

-- JCPS (Pruebas)
('jcps', 'plan_pruebas.docx', 'Plan de Pruebas', 'Estrategia y casos de prueba del sistema', 'uploads/templates/jcps/plan_pruebas.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 1, 'Incluya casos de prueba funcionales y no funcionales.'),
('jcps', 'matriz_pruebas.xlsx', 'Matriz de Casos de Prueba', 'Detalle de todos los casos de prueba', 'uploads/templates/jcps/matriz_pruebas.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', TRUE, 2, 'Complete cada caso con pasos detallados y resultados esperados.'),

-- Monitoreo
('monitoreo', 'plan_monitoreo.docx', 'Plan de Monitoreo', 'Estrategia de monitoreo y alertas del sistema', 'uploads/templates/monitoreo/plan_monitoreo.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', TRUE, 1, 'Defina métricas, umbrales y procedimientos de alerta.'),
('monitoreo', 'dashboard_metricas.xlsx', 'Dashboard de Métricas', 'Definición de métricas y KPIs a monitorear', 'uploads/templates/monitoreo/dashboard_metricas.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', FALSE, 2, 'Liste todas las métricas con sus valores objetivo.');

-- Insertar configuraciones del sistema
INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) VALUES 
('app_name', 'Sistema de Aprobación UC', 'string', 'general', 'Nombre de la aplicación', TRUE),
('app_version', '1.0.0', 'string', 'general', 'Versión actual del sistema', TRUE),
('maintenance_mode', 'false', 'boolean', 'general', 'Modo de mantenimiento activo', FALSE),
('max_file_size', '10485760', 'integer', 'files', 'Tamaño máximo de archivo en bytes (10MB)', FALSE),
('allowed_extensions', '["pdf","doc","docx","xls","xlsx","ppt","pptx","zip","vsdx"]', 'json', 'files', 'Extensiones de archivo permitidas', FALSE),
('email_notifications_enabled', 'true', 'boolean', 'notifications', 'Notificaciones por email habilitadas', FALSE),
('default_project_priority', 'medium', 'string', 'projects', 'Prioridad por defecto de proyectos', FALSE),
('auto_assign_reviewers', 'true', 'boolean', 'workflow', 'Asignación automática de revisores', FALSE),
('session_timeout', '7200', 'integer', 'security', 'Timeout de sesión en segundos', FALSE),
('backup_enabled', 'true', 'boolean', 'system', 'Backups automáticos habilitados', FALSE),
('log_retention_days', '90', 'integer', 'system', 'Días de retención de logs', FALSE);

-- ========================================
-- PROCEDIMIENTOS ALMACENADOS
-- ========================================

DELIMITER //

-- Procedimiento para obtener estadísticas de proyectos por área
CREATE PROCEDURE GetProjectStatsByArea(IN area_name VARCHAR(50))
BEGIN
    SELECT 
        ps.status,
        COUNT(*) as count,
        AVG(ps.completion_percentage) as avg_completion,
        COUNT(CASE WHEN ps.due_date < NOW() AND ps.status != 'completed' THEN 1 END) as overdue_count
    FROM project_stages ps
    WHERE ps.area_name = area_name
    GROUP BY ps.status;
END //

-- Procedimiento para obtener carga de trabajo por revisor
CREATE PROCEDURE GetReviewerWorkload()
BEGIN
    SELECT 
        a.name as reviewer_name,
        a.email,
        JSON_UNQUOTE(JSON_EXTRACT(a.areas, '$[0]')) as primary_area,
        COUNT(ps.id) as assigned_projects,
        COUNT(CASE WHEN ps.status = 'in_progress' THEN 1 END) as active_projects,
        COUNT(CASE WHEN ps.due_date < NOW() AND ps.status != 'completed' THEN 1 END) as overdue_projects
    FROM admins a
    LEFT JOIN project_stages ps ON ps.assigned_to = a.id
    WHERE a.role = 'reviewer' AND a.status = 'active'
    GROUP BY a.id, a.name, a.email;
END //

-- Procedimiento para limpiar datos antiguos
CREATE PROCEDURE CleanOldData(IN retention_days INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE table_name VARCHAR(64);
    DECLARE cur CURSOR FOR 
        SELECT TABLE_NAME 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME IN ('system_logs', 'notifications', 'user_sessions');
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    START TRANSACTION;
    
    -- Limpiar logs antiguos
    DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);
    
    -- Limpiar notificaciones antiguas
    DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL retention_days DAY) AND status = 'sent';
    
    -- Limpiar sesiones inactivas
    DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Limpiar métricas antiguas (mantener solo 30 días)
    DELETE FROM system_metrics WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    COMMIT;
END //

DELIMITER ;

-- ========================================
-- TRIGGERS
-- ========================================

-- Trigger para actualizar el progreso del proyecto cuando cambia una etapa
DELIMITER //
CREATE TRIGGER update_project_progress 
    AFTER UPDATE ON project_stages
    FOR EACH ROW
BEGIN
    DECLARE total_stages INT;
    DECLARE completed_stages INT;
    DECLARE new_progress DECIMAL(5,2);
    
    SELECT COUNT(*) INTO total_stages 
    FROM project_stages 
    WHERE project_id = NEW.project_id;
    
    SELECT COUNT(*) INTO completed_stages 
    FROM project_stages 
    WHERE project_id = NEW.project_id AND status = 'completed';
    
    SET new_progress = (completed_stages / total_stages) * 100;
    
    UPDATE projects 
    SET progress_percentage = new_progress,
        updated_at = NOW()
    WHERE id = NEW.project_id;
END //
DELIMITER ;

-- Trigger para registrar cambios en el historial
DELIMITER //
CREATE TRIGGER log_project_changes 
    AFTER UPDATE ON projects
    FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO project_history (
            project_id, user_id, user_type, action, description, 
            old_values, new_values, ip_address, created_at
        ) VALUES (
            NEW.id, 
            COALESCE(@current_user_id, 0),
            COALESCE(@current_user_type, 'system'),
            'status_changed',
            CONCAT('Estado cambiado de ', OLD.status, ' a ', NEW.status),
            JSON_OBJECT('status', OLD.status),
            JSON_OBJECT('status', NEW.status),
            COALESCE(@current_ip, '127.0.0.1'),
            NOW()
        );
    END IF;
END //
DELIMITER ;

-- ========================================
-- VISTAS
-- ========================================

-- Vista para dashboard de administrador
CREATE VIEW admin_dashboard_view AS
SELECT 
    p.id,
    p.project_code,
    p.title,
    p.status,
    p.priority,
    p.current_stage,
    p.progress_percentage,
    u.name as client_name,
    u.email as client_email,
    u.department,
    p.created_at,
    p.updated_at,
    COUNT(d.id) as document_count,
    COUNT(CASE WHEN pf.is_resolved = 0 THEN 1 END) as pending_feedback_count
FROM projects p
JOIN users u ON p.user_id = u.id
LEFT JOIN documents d ON p.id = d.project_id AND d.is_latest = 1
LEFT JOIN project_feedback pf ON p.id = pf.project_id
GROUP BY p.id, p.project_code, p.title, p.status, p.priority, p.current_stage, 
         p.progress_percentage, u.name, u.email, u.department, p.created_at, p.updated_at;

-- Vista para estadísticas del sistema
CREATE VIEW system_stats_view AS
SELECT 
    'projects' as metric_name,
    COUNT(*) as metric_value,
    'total' as metric_type
FROM projects
UNION ALL
SELECT 
    'active_projects' as metric_name,
    COUNT(*) as metric_value,
    'active' as metric_type
FROM projects WHERE status IN ('submitted', 'in_review')
UNION ALL
SELECT 
    'completed_projects' as metric_name,
    COUNT(*) as metric_value,
    'completed' as metric_type
FROM projects WHERE status = 'approved'
UNION ALL
SELECT 
    'total_users' as metric_name,
    COUNT(*) as metric_value,
    'users' as metric_type
FROM users WHERE status = 'active';

-- ========================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ========================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_projects_status_priority ON projects(status, priority);
CREATE INDEX idx_projects_user_status ON projects(user_id, status);
CREATE INDEX idx_project_stages_area_status ON project_stages(area_name, status);
CREATE INDEX idx_documents_project_area ON documents(project_id, area_name);
CREATE INDEX idx_feedback_project_resolved ON project_feedback(project_id, is_resolved);
CREATE INDEX idx_notifications_recipient_status ON notifications(recipient_email, status);

-- ========================================
-- COMENTARIOS FINALES
-- ========================================

-- Este esquema proporciona:
-- 1. Gestión completa de usuarios y administradores
-- 2. Sistema de proyectos con etapas por área
-- 3. Control de documentos con versionado
-- 4. Sistema de feedback y comentarios
-- 5. Historial completo de cambios
-- 6. Sistema de notificaciones
-- 7. Configuración flexible del sistema
-- 8. Logs y métricas del sistema
-- 9. Procedimientos almacenados para operaciones comunes
-- 10. Triggers para automatización
-- 11. Vistas para consultas optimizadas

-- Para mantenimiento regular, ejecutar:
-- CALL CleanOldData(90); -- Limpiar datos de más de 90 días