<?php
// Configurar variables para el layout
$page_header = true;
$is_edit = isset($user) && $user->id;
$page_title = $is_edit ? 'Editar Usuario' : 'Nuevo Usuario';
$page_description = $is_edit ? 'Modificar información del usuario' : 'Crear nueva cuenta de usuario';

$additional_css = ['/public/assets/css/forms.css'];
$additional_js = ['/public/assets/js/user-form.js', '/public/assets/js/form-validation.js'];

$breadcrumb = [
    ['text' => 'Administración', 'url' => '/admin'],
    ['text' => 'Usuarios', 'url' => '/admin/users'],
    ['text' => $page_title, 'url' => '']
];

$page_actions = '
    <div class="btn-group">
        <a href="/admin/users" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver a Lista
        </a>
        ' . ($is_edit ? '
        <a href="/admin/users/' . $user->id . '" class="btn btn-outline-info">
            <i class="fas fa-eye me-1"></i>Ver Perfil
        </a>
        ' : '') . '
    </div>
';
?>

<?php include '../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            
            <!-- Información del usuario (solo en edición) -->
            <?php if ($is_edit): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="user-avatar">
                                            <?php 
                                            $initials = strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));
                                            ?>
                                            <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 80px; height: 80px; border-radius: 50%; font-size: 1.5rem; font-weight: bold;">
                                                <?= $initials ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <h4 class="mb-1"><?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?></h4>
                                        <p class="text-muted mb-2"><?= htmlspecialchars($user->email) ?></p>
                                        <div class="user-meta">
                                            <span class="badge bg-<?= $user->role === 'admin' ? 'danger' : ($user->role === 'area_admin' ? 'warning' : ($user->role === 'reviewer' ? 'info' : 'secondary')) ?> me-2">
                                                <?= ucfirst(str_replace('_', ' ', $user->role)) ?>
                                            </span>
                                            <?php if ($user->is_active): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Activo
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-times me-1"></i>Inactivo
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="text-end">
                                            <small class="text-muted d-block">Creado</small>
                                            <strong><?= date('d/m/Y', strtotime($user->created_at)) ?></strong>
                                        </div>
                                        <?php if ($user->last_login): ?>
                                            <div class="text-end mt-2">
                                                <small class="text-muted d-block">Último acceso</small>
                                                <strong><?= \UC\ApprovalSystem\Utils\Helper::timeAgo($user->last_login) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Formulario principal -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-<?= $is_edit ? 'edit' : 'user-plus' ?> me-2"></i>
                        <?= $page_title ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form id="userForm" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="user_id" value="<?= $user->id ?>">
                        <?php endif; ?>
                        
                        <!-- Información Personal -->
                        <div class="form-section mb-5">
                            <h6 class="form-section-title">
                                <i class="fas fa-user me-2"></i>
                                Información Personal
                            </h6>
                            <div class="form-section-content">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label required">Nombre</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="first_name" 
                                                   name="first_name" 
                                                   value="<?= htmlspecialchars($user->first_name ?? '') ?>"
                                                   required
                                                   maxlength="50">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label required">Apellido</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="last_name" 
                                                   name="last_name" 
                                                   value="<?= htmlspecialchars($user->last_name ?? '') ?>"
                                                   required
                                                   maxlength="50">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="email" class="form-label required">Email</label>
                                            <input type="email" 
                                                   class="form-control" 
                                                   id="email" 
                                                   name="email" 
                                                   value="<?= htmlspecialchars($user->email ?? '') ?>"
                                                   required
                                                   maxlength="255">
                                            <div class="form-text">
                                                <?= $is_edit ? 'Este email será usado para notificaciones del sistema' : 'Debe ser un email válido de la universidad' ?>
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Teléfono</label>
                                            <input type="tel" 
                                                   class="form-control" 
                                                   id="phone" 
                                                   name="phone" 
                                                   value="<?= htmlspecialchars($user->phone ?? '') ?>"
                                                   maxlength="20">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Credenciales -->
                        <div class="form-section mb-5">
                            <h6 class="form-section-title">
                                <i class="fas fa-key me-2"></i>
                                Credenciales de Acceso
                            </h6>
                            <div class="form-section-content">
                                <?php if (!$is_edit): ?>
                                    <!-- Contraseña solo en creación -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password" class="form-label required">Contraseña</label>
                                                <div class="input-group">
                                                    <input type="password" 
                                                           class="form-control" 
                                                           id="password" 
                                                           name="password" 
                                                           required
                                                           minlength="8">
                                                    <button class="btn btn-outline-secondary" 
                                                            type="button" 
                                                            onclick="togglePassword('password')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text">
                                                    Mínimo 8 caracteres. Se recomienda incluir mayúsculas, minúsculas y números.
                                                </div>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password_confirmation" class="form-label required">Confirmar Contraseña</label>
                                                <div class="input-group">
                                                    <input type="password" 
                                                           class="form-control" 
                                                           id="password_confirmation" 
                                                           name="password_confirmation" 
                                                           required
                                                           minlength="8">
                                                    <button class="btn btn-outline-secondary" 
                                                            type="button" 
                                                            onclick="togglePassword('password_confirmation')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Opciones de contraseña en edición -->
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Contraseña actual:</strong> La contraseña se mantendrá sin cambios a menos que especifiques una nueva.
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="change_password" 
                                               name="change_password"
                                               onchange="togglePasswordFields()">
                                        <label class="form-check-label" for="change_password">
                                            Cambiar contraseña
                                        </label>
                                    </div>
                                    
                                    <div id="passwordFields" class="d-none">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label">Nueva Contraseña</label>
                                                    <div class="input-group">
                                                        <input type="password" 
                                                               class="form-control" 
                                                               id="new_password" 
                                                               name="new_password" 
                                                               minlength="8">
                                                        <button class="btn btn-outline-secondary" 
                                                                type="button" 
                                                                onclick="togglePassword('new_password')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="new_password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                                                    <div class="input-group">
                                                        <input type="password" 
                                                               class="form-control" 
                                                               id="new_password_confirmation" 
                                                               name="new_password_confirmation" 
                                                               minlength="8">
                                                        <button class="btn btn-outline-secondary" 
                                                                type="button" 
                                                                onclick="togglePassword('new_password_confirmation')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="button" 
                                                class="btn btn-outline-warning btn-sm" 
                                                onclick="sendPasswordReset()">
                                            <i class="fas fa-envelope me-1"></i>
                                            Enviar Email de Restablecimiento
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-info btn-sm" 
                                                onclick="generateRandomPassword()">
                                            <i class="fas fa-random me-1"></i>
                                            Generar Contraseña Aleatoria
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Rol y Permisos -->
                        <div class="form-section mb-5">
                            <h6 class="form-section-title">
                                <i class="fas fa-shield-alt me-2"></i>
                                Rol y Permisos
                            </h6>
                            <div class="form-section-content">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="role" class="form-label required">Rol del Usuario</label>
                                            <select class="form-select" 
                                                    id="role" 
                                                    name="role" 
                                                    required
                                                    onchange="updateRoleDescription()">
                                                <option value="">Seleccionar rol</option>
                                                <?php foreach ($roles as $role_key => $role_name): ?>
                                                    <option value="<?= $role_key ?>" 
                                                            <?= ($user->role ?? '') === $role_key ? 'selected' : '' ?>
                                                            data-description="<?= htmlspecialchars($role_descriptions[$role_key] ?? '') ?>">
                                                        <?= htmlspecialchars($role_name) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text" id="roleDescription">
                                                Selecciona un rol para ver su descripción
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Estado del Usuario</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="is_active" 
                                                       name="is_active" 
                                                       <?= ($user->is_active ?? true) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_active">
                                                    Usuario Activo
                                                </label>
                                            </div>
                                            <div class="form-text">
                                                Los usuarios inactivos no pueden acceder al sistema
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Áreas asignadas -->
                                <div class="mb-3" id="areasSection">
                                    <label class="form-label">Áreas Asignadas</label>
                                    <div class="areas-grid">
                                        <?php 
                                        $user_areas = $user ? json_decode($user->areas, true) ?? [] : [];
                                        foreach ($areas as $area_key => $area_name): 
                                        ?>
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="area_<?= $area_key ?>" 
                                                       name="areas[]" 
                                                       value="<?= $area_key ?>"
                                                       <?= in_array($area_key, $user_areas) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="area_<?= $area_key ?>">
                                                    <?= htmlspecialchars($area_name) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="form-text">
                                        Selecciona las áreas donde este usuario tendrá permisos de trabajo
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Configuración Adicional -->
                        <div class="form-section mb-5">
                            <h6 class="form-section-title">
                                <i class="fas fa-cog me-2"></i>
                                Configuración Adicional
                            </h6>
                            <div class="form-section-content">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="language" class="form-label">Idioma Preferido</label>
                                            <select class="form-select" id="language" name="language">
                                                <option value="es" <?= ($user->language ?? 'es') === 'es' ? 'selected' : '' ?>>
                                                    Español
                                                </option>
                                                <option value="en" <?= ($user->language ?? 'es') === 'en' ? 'selected' : '' ?>>
                                                    English
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="timezone" class="form-label">Zona Horaria</label>
                                            <select class="form-select" id="timezone" name="timezone">
                                                <option value="America/Santiago" <?= ($user->timezone ?? 'America/Santiago') === 'America/Santiago' ? 'selected' : '' ?>>
                                                    Santiago (UTC-3)
                                                </option>
                                                <option value="America/New_York" <?= ($user->timezone ?? 'America/Santiago') === 'America/New_York' ? 'selected' : '' ?>>
                                                    New York (UTC-5)
                                                </option>
                                                <option value="UTC" <?= ($user->timezone ?? 'America/Santiago') === 'UTC' ? 'selected' : '' ?>>
                                                    UTC
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Notificaciones -->
                                <div class="mb-3">
                                    <label class="form-label">Preferencias de Notificación</label>
                                    <div class="notification-preferences">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="email_notifications" 
                                                   name="email_notifications" 
                                                   <?= ($user->email_notifications ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="email_notifications">
                                                <strong>Notificaciones por Email</strong>
                                                <div class="text-muted small">Recibir notificaciones importantes por correo electrónico</div>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="weekly_digest" 
                                                   name="weekly_digest" 
                                                   <?= ($user->weekly_digest ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="weekly_digest">
                                                <strong>Resumen Semanal</strong>
                                                <div class="text-muted small">Recibir un resumen semanal de la actividad</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Notas administrativas -->
                                <div class="mb-3">
                                    <label for="admin_notes" class="form-label">Notas Administrativas</label>
                                    <textarea class="form-control" 
                                              id="admin_notes" 
                                              name="admin_notes" 
                                              rows="3"
                                              maxlength="500"
                                              placeholder="Notas internas sobre este usuario (no visible para el usuario)..."><?= htmlspecialchars($user->admin_notes ?? '') ?></textarea>
                                    <div class="form-text">
                                        <span id="notesCounter">0</span>/500 caracteres
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="form-actions">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="/admin/users" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </a>
                                </div>
                                <div class="btn-group">
                                    <?php if ($is_edit): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                onclick="deleteUser()"
                                                <?= $user->role === 'admin' && $current_user->id === $user->id ? 'disabled' : '' ?>>
                                            <i class="fas fa-trash me-1"></i>Eliminar Usuario
                                        </button>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-save me-1"></i>
                                        <?= $is_edit ? 'Actualizar Usuario' : 'Crear Usuario' ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Panel de ayuda -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent border-0">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Ayuda y Referencias
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Roles del Sistema</h6>
                            <ul class="list-unstyled">
                                <li><strong>Administrador:</strong> Acceso completo al sistema</li>
                                <li><strong>Administrador de Área:</strong> Gestión de su área específica</li>
                                <li><strong>Revisor:</strong> Puede revisar y comentar proyectos</li>
                                <li><strong>Cliente:</strong> Puede crear y gestionar sus proyectos</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Políticas de Contraseña</h6>
                            <ul class="list-unstyled">
                                <li>• Mínimo 8 caracteres</li>
                                <li>• Se recomienda combinar mayúsculas y minúsculas</li>
                                <li>• Incluir números y símbolos</li>
                                <li>• Evitar información personal</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminación -->
<?php if ($is_edit): ?>
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                </div>
                <p>¿Estás seguro de que deseas eliminar al usuario <strong><?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?></strong>?</p>
                <p class="text-muted">Se eliminarán todos los datos asociados a este usuario.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteUser()">
                    <i class="fas fa-trash me-1"></i>Eliminar Usuario
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Variables globales
const isEdit = <?= $is_edit ? 'true' : 'false' ?>;
const userId = <?= $is_edit ? $user->id : 'null' ?>;

// Descripciones de roles
const roleDescriptions = <?= json_encode($role_descriptions ?? []) ?>;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    setupValidation();
    setupCounters();
    updateRoleDescription();
});

// Inicializar formulario
function initializeForm() {
    // Configurar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configurar áreas según el rol seleccionado
    updateAreasVisibility();
    
    // Listener para cambios en el rol
    document.getElementById('role').addEventListener('change', function() {
        updateAreasVisibility();
        updateRoleDescription();
    });
}

// Configurar validación del formulario
function setupValidation() {
    const form = document.getElementById('userForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitForm();
        }
    });
    
    // Validación en tiempo real
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
}

// Configurar contadores de caracteres
function setupCounters() {
    const notesTextarea = document.getElementById('admin_notes');
    const notesCounter = document.getElementById('notesCounter');
    
    function updateCounter() {
        const length = notesTextarea.value.length;
        notesCounter.textContent = length;
        
        if (length > 450) {
            notesCounter.classList.add('text-warning');
        } else {
            notesCounter.classList.remove('text-warning');
        }
    }
    
    notesTextarea.addEventListener('input', updateCounter);
    updateCounter(); // Inicializar
}

// Validar formulario completo
function validateForm() {
    const form = document.getElementById('userForm');
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    // Validaciones específicas
    if (!isEdit) {
        isValid &= validatePasswords();
    } else {
        const changePassword = document.getElementById('change_password').checked;
        if (changePassword) {
            isValid &= validateNewPasswords();
        }
    }
    
    return isValid;
}

// Validar campo individual
function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    // Limpiar estado anterior
    field.classList.remove('is-valid', 'is-invalid');
    
    // Validaciones por tipo de campo
    switch (field.name) {
        case 'first_name':
        case 'last_name':
            if (!value) {
                message = 'Este campo es requerido';
                isValid = false;
            } else if (value.length < 2) {
                message = 'Debe tener al menos 2 caracteres';
                isValid = false;
            }
            break;
            
        case 'email':
            if (!value) {
                message = 'El email es requerido';
                isValid = false;
            } else if (!isValidEmail(value)) {
                message = 'Formato de email inválido';
                isValid = false;
            }
            break;
            
        case 'phone':
            if (value && !isValidPhone(value)) {
                message = 'Formato de teléfono inválido';
                isValid = false;
            }
            break;
            
        case 'role':
            if (!value) {
                message = 'Selecciona un rol';
                isValid = false;
            }
            break;
    }
    
    // Aplicar estado visual
    if (isValid) {
        field.classList.add('is-valid');
    } else {
        field.classList.add('is-invalid');
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = message;
        }
    }
    
    return isValid;
}

// Validar contraseñas (creación)
function validatePasswords() {
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password_confirmation');
    
    let isValid = true;
    
    if (password.value.length < 8) {
        password.classList.add('is-invalid');
        password.parentNode.parentNode.querySelector('.invalid-feedback').textContent = 'La contraseña debe tener al menos 8 caracteres';
        isValid = false;
    } else {
        password.classList.remove('is-invalid');
        password.classList.add('is-valid');
    }
    
    if (password.value !== confirmation.value) {
        confirmation.classList.add('is-invalid');
        confirmation.parentNode.parentNode.querySelector('.invalid-feedback').textContent = 'Las contraseñas no coinciden';
        isValid = false;
    } else {
        confirmation.classList.remove('is-invalid');
        confirmation.classList.add('is-valid');
    }
    
    return isValid;
}

// Validar nuevas contraseñas (edición)
function validateNewPasswords() {
    const newPassword = document.getElementById('new_password');
    const confirmation = document.getElementById('new_password_confirmation');
    
    let isValid = true;
    
    if (newPassword.value.length < 8) {
        newPassword.classList.add('is-invalid');
        newPassword.parentNode.parentNode.querySelector('.invalid-feedback').textContent = 'La contraseña debe tener al menos 8 caracteres';
        isValid = false;
    } else {
        newPassword.classList.remove('is-invalid');
        newPassword.classList.add('is-valid');
    }
    
    if (newPassword.value !== confirmation.value) {
        confirmation.classList.add('is-invalid');
        confirmation.parentNode.parentNode.querySelector('.invalid-feedback').textContent = 'Las contraseñas no coinciden';
        isValid = false;
    } else {
        confirmation.classList.remove('is-invalid');
        confirmation.classList.add('is-valid');
    }
    
    return isValid;
}

// Enviar formulario
function submitForm() {
    const form = document.getElementById('userForm');
    const submitBtn = document.getElementById('submitBtn');
    const formData = new FormData(form);
    
    // Deshabilitar botón y mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
    showLoader(true);
    
    const url = isEdit ? `/admin/users/${userId}` : '/admin/users';
    const method = isEdit ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`Usuario ${isEdit ? 'actualizado' : 'creado'} correctamente`, 'success');
            
            // Redirigir después de un momento
            setTimeout(() => {
                window.location.href = '/admin/users';
            }, 1500);
        } else {
            showAlert(data.message || 'Error al guardar usuario', 'error');
            
            // Mostrar errores específicos si existen
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.parentNode.querySelector('.invalid-feedback');
                        if (feedback) {
                            feedback.textContent = data.errors[field][0];
                        }
                    }
                });
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al conectar con el servidor', 'error');
    })
    .finally(() => {
        // Restaurar botón
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<i class="fas fa-save me-1"></i>${isEdit ? 'Actualizar Usuario' : 'Crear Usuario'}`;
        showLoader(false);
    });
}

// Funciones de interfaz
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.parentNode.querySelector('button');
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function togglePasswordFields() {
    const checkbox = document.getElementById('change_password');
    const passwordFields = document.getElementById('passwordFields');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('new_password_confirmation');
    
    if (checkbox.checked) {
        passwordFields.classList.remove('d-none');
        newPassword.required = true;
        confirmPassword.required = true;
    } else {
        passwordFields.classList.add('d-none');
        newPassword.required = false;
        confirmPassword.required = false;
        newPassword.value = '';
        confirmPassword.value = '';
        
        // Limpiar validaciones
        newPassword.classList.remove('is-valid', 'is-invalid');
        confirmPassword.classList.remove('is-valid', 'is-invalid');
    }
}

function updateRoleDescription() {
    const roleSelect = document.getElementById('role');
    const description = document.getElementById('roleDescription');
    const selectedOption = roleSelect.options[roleSelect.selectedIndex];
    
    if (selectedOption && selectedOption.dataset.description) {
        description.textContent = selectedOption.dataset.description;
        description.classList.remove('text-muted');
        description.classList.add('text-info');
    } else {
        description.textContent = 'Selecciona un rol para ver su descripción';
        description.classList.remove('text-info');
        description.classList.add('text-muted');
    }
}

function updateAreasVisibility() {
    const roleSelect = document.getElementById('role');
    const areasSection = document.getElementById('areasSection');
    const role = roleSelect.value;
    
    // Mostrar áreas solo para roles que las necesiten
    if (role === 'area_admin' || role === 'reviewer') {
        areasSection.style.display = 'block';
        
        // Hacer requerida al menos una área para estos roles
        const areaCheckboxes = areasSection.querySelectorAll('input[type="checkbox"]');
        areaCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', validateAreas);
        });
    } else {
        areasSection.style.display = 'none';
    }
}

function validateAreas() {
    const roleSelect = document.getElementById('role');
    const role = roleSelect.value;
    
    if (role === 'area_admin' || role === 'reviewer') {
        const checkedAreas = document.querySelectorAll('input[name="areas[]"]:checked');
        
        if (checkedAreas.length === 0) {
            showAlert('Debes seleccionar al menos un área para este rol', 'warning');
            return false;
        }
    }
    
    return true;
}

function sendPasswordReset() {
    if (confirm('¿Enviar email de restablecimiento de contraseña al usuario?')) {
        showLoader(true);
        
        fetch(`/admin/users/${userId}/reset-password`, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Email de restablecimiento enviado correctamente', 'success');
            } else {
                showAlert(data.message || 'Error al enviar email', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error al conectar con el servidor', 'error');
        })
        .finally(() => {
            showLoader(false);
        });
    }
}

function generateRandomPassword() {
    const length = 12;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    // Asegurar que tenga al menos una mayúscula, minúscula, número y símbolo
    if (!/[a-z]/.test(password)) password = password.substring(0, length-1) + 'a';
    if (!/[A-Z]/.test(password)) password = password.substring(0, length-2) + 'A' + password.charAt(length-1);
    if (!/[0-9]/.test(password)) password = password.substring(0, length-3) + '1' + password.substring(length-2);
    if (!/[!@#$%^&*]/.test(password)) password = password.substring(0, length-4) + '!' + password.substring(length-3);
    
    document.getElementById('new_password').value = password;
    document.getElementById('new_password_confirmation').value = password;
    document.getElementById('change_password').checked = true;
    togglePasswordFields();
    
    showAlert('Contraseña aleatoria generada. ¡No olvides guardarla!', 'info');
}

function deleteUser() {
    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    modal.show();
}

function confirmDeleteUser() {
    showLoader(true);
    
    fetch(`/admin/users/${userId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Usuario eliminado correctamente', 'success');
            
            // Cerrar modal y redirigir
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteUserModal'));
            modal.hide();
            
            setTimeout(() => {
                window.location.href = '/admin/users';
            }, 1500);
        } else {
            showAlert(data.message || 'Error al eliminar usuario', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al conectar con el servidor', 'error');
    })
    .finally(() => {
        showLoader(false);
    });
}

// Funciones de validación auxiliares
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,20}$/;
    return phoneRegex.test(phone);
}

// Prevenir envío accidental del formulario
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
    }
});
</script>

<style>
/* Estilos específicos del formulario de usuario */
.form-section {
    position: relative;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    background-color: #fdfdfd;
}

.form-section-title {
    position: absolute;
    top: -0.6rem;
    left: 1rem;
    background-color: #ffffff;
    padding: 0 0.5rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0;
}

.form-section-content {
    margin-top: 0.5rem;
}

.form-label.required::after {
    content: " *";
    color: #dc3545;
}

.areas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
}

.areas-grid .form-check {
    margin-bottom: 0;
}

.notification-preferences {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
}

.notification-preferences .form-check {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background-color: #ffffff;
    border-radius: 0.25rem;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.notification-preferences .form-check:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-actions {
    padding: 1.5rem 0;
    border-top: 1px solid #e9ecef;
    margin-top: 2rem;
}

.user-avatar .avatar-circle {
    font-size: 1.5rem;
    font-weight: bold;
}

.user-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.input-group .btn {
    border-color: #ced4da;
}

.input-group .btn:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}

.form-check-input:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-switch .form-check-input {
    width: 2em;
    height: 1em;
}

.form-switch .form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

.is-valid {
    border-color: #198754;
}

.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    font-size: 0.875em;
    color: #dc3545;
    margin-top: 0.25rem;
}

.form-text {
    font-size: 0.875em;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-section {
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .form-section-title {
        font-size: 0.9rem;
    }
    
    .areas-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-actions .btn-group {
        margin-top: 1rem;
        justify-content: stretch;
    }
    
    .form-actions .btn-group .btn {
        flex: 1;
    }
}

@media (max-width: 576px) {
    .user-meta {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .notification-preferences .form-check {
        padding: 0.5rem;
    }
    
    .btn-group {
        width: 100%;
    }
    
    .btn-group .btn {
        flex: 1;
    }
}

/* Animaciones */
@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        overflow: hidden;
    }
    to {
        opacity: 1;
        max-height: 200px;
        overflow: visible;
    }
}

#passwordFields {
    animation: slideDown 0.3s ease when not .d-none;
}

/* Estados de focus mejorados */
.form-control:focus,
.form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Mejoras de accesibilidad */
.form-check-input:focus {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

.btn:focus {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .form-section {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .form-section-title {
        background-color: #1a202c;
        color: #e2e8f0;
    }
    
    .areas-grid,
    .notification-preferences {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .notification-preferences .form-check {
        background-color: #4a5568;
        border-color: #718096;
    }
}
</style>

<?php include '../layouts/footer.php'; ?>