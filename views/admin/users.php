<?php
// Configurar variables para el layout
$page_header = true;
$page_description = 'Gestión completa de usuarios del sistema';
$additional_css = ['/public/assets/css/datatables.css'];
$additional_js = ['/public/assets/js/datatables.js', '/public/assets/js/user-management.js'];

$breadcrumb = [
    ['text' => 'Administración', 'url' => '/admin'],
    ['text' => 'Usuarios', 'url' => '/admin/users']
];

$page_actions = '
    <div class="btn-group">
        <a href="/admin/users/new" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i>Nuevo Usuario
        </a>
        <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
            <span class="visually-hidden">Más opciones</span>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#" onclick="exportUsers()">
                <i class="fas fa-download me-2"></i>Exportar Lista
            </a></li>
            <li><a class="dropdown-item" href="#" onclick="bulkInvite()">
                <i class="fas fa-envelope me-2"></i>Invitación Masiva
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/admin/users/inactive">
                <i class="fas fa-user-slash me-2"></i>Usuarios Inactivos
            </a></li>
        </ul>
    </div>
';
?>

<?php include '../layouts/header.php'; ?>

<div class="container-fluid py-4">
    
    <!-- Estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Total Usuarios</h6>
                            <h3 class="mb-0 text-primary"><?= number_format($total_users) ?></h3>
                        </div>
                        <div class="icon-circle bg-primary bg-opacity-10">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Activos</h6>
                            <h3 class="mb-0 text-success">
                                <?= array_reduce($users, function($count, $user) { 
                                    return $user->is_active ? $count + 1 : $count; 
                                }, 0) ?>
                            </h3>
                        </div>
                        <div class="icon-circle bg-success bg-opacity-10">
                            <i class="fas fa-user-check text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Administradores</h6>
                            <h3 class="mb-0 text-info">
                                <?= array_reduce($users, function($count, $user) { 
                                    return in_array($user->role, ['admin', 'area_admin']) ? $count + 1 : $count; 
                                }, 0) ?>
                            </h3>
                        </div>
                        <div class="icon-circle bg-info bg-opacity-10">
                            <i class="fas fa-user-shield text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted text-uppercase mb-1">Nuevos (7 días)</h6>
                            <h3 class="mb-0 text-warning">
                                <?= array_reduce($users, function($count, $user) { 
                                    return strtotime($user->created_at) > strtotime('-7 days') ? $count + 1 : $count; 
                                }, 0) ?>
                            </h3>
                        </div>
                        <div class="icon-circle bg-warning bg-opacity-10">
                            <i class="fas fa-user-plus text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros y búsqueda -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="Nombre, email o ID..."
                                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Rol</label>
                            <select class="form-select" name="role">
                                <option value="">Todos los roles</option>
                                <?php foreach ($roles as $role_key => $role_name): ?>
                                    <option value="<?= $role_key ?>" 
                                            <?= ($filters['role'] ?? '') === $role_key ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Área</label>
                            <select class="form-select" name="area">
                                <option value="">Todas las áreas</option>
                                <?php foreach ($areas as $area_key => $area_name): ?>
                                    <option value="<?= $area_key ?>" 
                                            <?= ($filters['area'] ?? '') === $area_key ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($area_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="status">
                                <option value="">Todos</option>
                                <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>
                                    Activos
                                </option>
                                <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                    Inactivos
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i>Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla de usuarios -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            Lista de Usuarios
                            <span class="badge bg-secondary ms-2"><?= number_format($total_users) ?> total</span>
                        </h5>
                        <div class="card-actions">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" onclick="selectAll()">
                                    <i class="fas fa-check-square me-1"></i>Seleccionar Todo
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="bulkActions()" disabled id="bulkActionBtn">
                                    <i class="fas fa-cog me-1"></i>Acciones en Lote
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="usersTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                        </div>
                                    </th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Áreas</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th>Registro</th>
                                    <th width="120">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr data-user-id="<?= $user->id ?>">
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input user-checkbox" 
                                                           type="checkbox" 
                                                           value="<?= $user->id ?>"
                                                           onchange="updateBulkActions()">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3">
                                                        <?php 
                                                        $initials = strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));
                                                        ?>
                                                        <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                             style="width: 40px; height: 40px; border-radius: 50%; font-weight: bold;">
                                                            <?= $initials ?>
                                                        </div>
                                                    </div>
                                                    <div class="user-info">
                                                        <div class="user-name fw-semibold">
                                                            <?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?>
                                                        </div>
                                                        <div class="user-email text-muted">
                                                            <?= htmlspecialchars($user->email) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $user->role === 'admin' ? 'danger' : ($user->role === 'area_admin' ? 'warning' : ($user->role === 'reviewer' ? 'info' : 'secondary')) ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $user->role)) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($user->areas)): ?>
                                                    <?php 
                                                    $user_areas = json_decode($user->areas, true) ?? [];
                                                    if (count($user_areas) > 2): 
                                                    ?>
                                                        <span class="badge bg-light text-dark me-1">
                                                            <?= count($user_areas) ?> áreas
                                                        </span>
                                                        <i class="fas fa-info-circle text-muted" 
                                                           data-bs-toggle="tooltip" 
                                                           title="<?= implode(', ', array_map(function($area) use ($areas) { 
                                                               return $areas[$area] ?? $area; 
                                                           }, $user_areas)) ?>">
                                                        </i>
                                                    <?php else: ?>
                                                        <?php foreach ($user_areas as $area): ?>
                                                            <span class="badge bg-light text-dark me-1">
                                                                <?= htmlspecialchars($areas[$area] ?? $area) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin áreas asignadas</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user->is_active): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Activo
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-times me-1"></i>Inactivo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user->last_login): ?>
                                                    <span class="text-muted" data-bs-toggle="tooltip" 
                                                          title="<?= date('d/m/Y H:i:s', strtotime($user->last_login)) ?>">
                                                        <?= \UC\ApprovalSystem\Utils\Helper::timeAgo($user->last_login) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Nunca</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-muted" data-bs-toggle="tooltip" 
                                                      title="<?= date('d/m/Y H:i:s', strtotime($user->created_at)) ?>">
                                                    <?= date('d/m/Y', strtotime($user->created_at)) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/admin/users/<?= $user->id ?>/edit" 
                                                       class="btn btn-outline-primary" 
                                                       title="Editar usuario">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-outline-info dropdown-toggle dropdown-toggle-split" 
                                                            data-bs-toggle="dropdown">
                                                        <span class="visually-hidden">Más opciones</span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="/admin/users/<?= $user->id ?>">
                                                                <i class="fas fa-eye me-2"></i>Ver Perfil
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="resetPassword(<?= $user->id ?>)">
                                                                <i class="fas fa-key me-2"></i>Restablecer Contraseña
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="toggleUserStatus(<?= $user->id ?>, <?= $user->is_active ? 'false' : 'true' ?>)">
                                                                <i class="fas fa-<?= $user->is_active ? 'ban' : 'check' ?> me-2"></i>
                                                                <?= $user->is_active ? 'Desactivar' : 'Activar' ?>
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" 
                                                               href="#" 
                                                               onclick="deleteUser(<?= $user->id ?>, '<?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?>')"
                                                               <?= $user->role === 'admin' && $current_user->id === $user->id ? 'disabled' : '' ?>>
                                                                <i class="fas fa-trash me-2"></i>Eliminar
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;"></i>
                                                <h6 class="text-muted">No se encontraron usuarios</h6>
                                                <p class="text-muted">Ajusta los filtros o crea un nuevo usuario</p>
                                                <a href="/admin/users/new" class="btn btn-primary">
                                                    <i class="fas fa-user-plus me-1"></i>Crear Usuario
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-transparent border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info">
                                <small class="text-muted">
                                    Mostrando <?= count($users) ?> de <?= number_format($total_users) ?> usuarios
                                </small>
                            </div>
                            <nav aria-label="Paginación de usuarios">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($current_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $current_page - 1 ?><?= http_build_query($filters) ? '&' . http_build_query($filters) : '' ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    ?>
                                    
                                    <?php if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1<?= http_build_query($filters) ? '&' . http_build_query($filters) : '' ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= http_build_query($filters) ? '&' . http_build_query($filters) : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $total_pages ?><?= http_build_query($filters) ? '&' . http_build_query($filters) : '' ?>">
                                                <?= $total_pages ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($current_page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $current_page + 1 ?><?= http_build_query($filters) ? '&' . http_build_query($filters) : '' ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para acciones en lote -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acciones en Lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Has seleccionado <span id="selectedCount">0</span> usuario(s).</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="bulkActivate()">
                        <i class="fas fa-check me-2"></i>Activar Usuarios
                    </button>
                    <button type="button" class="btn btn-warning" onclick="bulkDeactivate()">
                        <i class="fas fa-ban me-2"></i>Desactivar Usuarios
                    </button>
                    <button type="button" class="btn btn-info" onclick="bulkAssignArea()">
                        <i class="fas fa-sitemap me-2"></i>Asignar Área
                    </button>
                    <button type="button" class="btn btn-danger" onclick="bulkDelete()">
                        <i class="fas fa-trash me-2"></i>Eliminar Usuarios
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let selectedUsers = [];

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    initializeTooltips();
    
    // Configurar filtros automáticos
    setupAutoFilters();
});

// Funciones de selección
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    
    userCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const userCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const bulkActionBtn = document.getElementById('bulkActionBtn');
    
    selectedUsers = Array.from(userCheckboxes).map(cb => cb.value);
    
    if (selectedUsers.length > 0) {
        bulkActionBtn.disabled = false;
        bulkActionBtn.innerHTML = `<i class="fas fa-cog me-1"></i>Acciones (${selectedUsers.length})`;
    } else {
        bulkActionBtn.disabled = true;
        bulkActionBtn.innerHTML = '<i class="fas fa-cog me-1"></i>Acciones en Lote';
    }
    
    // Actualizar el checkbox "seleccionar todo"
    const totalCheckboxes = document.querySelectorAll('.user-checkbox').length;
    const checkedCheckboxes = document.querySelectorAll('.user-checkbox:checked').length;
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    if (checkedCheckboxes === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (checkedCheckboxes === totalCheckboxes) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
    }
}

// Funciones de acciones individuales
function toggleUserStatus(userId, newStatus) {
    if (confirm('¿Estás seguro de cambiar el estado de este usuario?')) {
        showLoader(true);
        
        fetch(`/admin/users/${userId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
            },
            body: JSON.stringify({ is_active: newStatus === 'true' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Estado del usuario actualizado correctamente', 'success');
                location.reload();
            } else {
                showAlert(data.message || 'Error al cambiar estado', 'error');
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

function deleteUser(userId, userName) {
    if (confirm(`¿Estás seguro de eliminar al usuario "${userName}"?\n\nEsta acción no se puede deshacer.`)) {
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
                document.querySelector(`tr[data-user-id="${userId}"]`).remove();
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
}

function resetPassword(userId) {
    if (confirm('¿Enviar email de restablecimiento de contraseña?')) {
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
                showAlert('Email de restablecimiento enviado', 'success');
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

// Funciones de acciones en lote
function bulkActions() {
    if (selectedUsers.length === 0) {
        showAlert('Selecciona al menos un usuario', 'warning');
        return;
    }
    
    document.getElementById('selectedCount').textContent = selectedUsers.length;
    const modal = new bootstrap.Modal(document.getElementById('bulkActionsModal'));
    modal.show();
}

function bulkActivate() {
    executeBulkAction('activate', 'Activar usuarios seleccionados');
}

function bulkDeactivate() {
    executeBulkAction('deactivate', 'Desactivar usuarios seleccionados');
}

function bulkDelete() {
    if (confirm(`¿Estás seguro de eliminar ${selectedUsers.length} usuario(s)?\n\nEsta acción no se puede deshacer.`)) {
        executeBulkAction('delete', 'Eliminar usuarios seleccionados');
    }
}

function executeBulkAction(action, description) {
    showLoader(true);
    
    fetch('/admin/users/bulk-action', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
        },
        body: JSON.stringify({
            action: action,
            user_ids: selectedUsers
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`${description} completado correctamente`, 'success');
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('bulkActionsModal'));
            modal.hide();
            
            // Recargar página
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showAlert(data.message || `Error en ${description}`, 'error');
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

function bulkAssignArea() {
    // Aquí puedes implementar un modal para seleccionar área
    showAlert('Función de asignación de área en desarrollo', 'info');
}

// Funciones de exportación
function exportUsers() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    showLoader(true);
    
    // Crear enlace de descarga
    const link = document.createElement('a');
    link.href = `/admin/users/export?${params.toString()}`;
    link.download = '';
    link.click();
    
    showLoader(false);
    showAlert('Exportación iniciada', 'success');
}

function bulkInvite() {
    showAlert('Función de invitación masiva en desarrollo', 'info');
}

// Funciones de filtrado
function setupAutoFilters() {
    const filterForm = document.getElementById('filterForm');
    const filterInputs = filterForm.querySelectorAll('input, select');
    
    // Aplicar filtros automáticamente al cambiar
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.name !== 'search') {
                filterForm.submit();
            }
        });
    });
    
    // Para búsqueda, esperar un poco antes de filtrar
    const searchInput = filterForm.querySelector('[name="search"]');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterForm.submit();
        }, 500);
    });
}

function clearFilters() {
    const form = document.getElementById('filterForm');
    form.reset();
    window.location.href = '/admin/users';
}

// Funciones de utilidad
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function selectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    selectAllCheckbox.checked = true;
    toggleSelectAll();
}
</script>

<style>
/* Estilos específicos para gestión de usuarios */
.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.user-avatar .avatar-circle {
    font-size: 0.875rem;
}

.user-info .user-name {
    line-height: 1.2;
    margin-bottom: 0.125rem;
}

.user-info .user-email {
    font-size: 0.875rem;
    line-height: 1.2;
}

.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.empty-state {
    padding: 3rem 1rem;
}

.pagination-info {
    font-size: 0.875rem;
}

.page-link {
    border-color: #dee2e6;
    color: #6c757d;
}

.page-link:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}

.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.dropdown-menu {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border: none;
}

.dropdown-item {
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: rgba(0, 123, 255, 0.1);
    transform: translateX(2px);
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.form-check-input:indeterminate {
    background-color: #6c757d;
    border-color: #6c757d;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.4rem;
    }
    
    .user-info .user-name {
        font-size: 0.9rem;
    }
    
    .user-info .user-email {
        font-size: 0.8rem;
    }
    
    .badge {
        font-size: 0.7em;
    }
}

@media (max-width: 576px) {
    .card-actions {
        margin-top: 1rem;
    }
    
    .pagination {
        justify-content: center;
    }
    
    .pagination-info {
        text-align: center;
        margin-bottom: 1rem;
    }
}

/* Estados de fila */
tr[data-user-id] {
    transition: all 0.2s ease;
}

tr[data-user-id]:hover {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Animaciones */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

tbody tr {
    animation: fadeIn 0.3s ease;
}

/* Mejoras de accesibilidad */
.btn:focus,
.form-control:focus,
.form-select:focus {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

.dropdown-item:focus {
    outline: 2px solid #0d6efd;
    outline-offset: -2px;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .table th {
        background-color: #2d3748;
        color: #e2e8f0;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    
    .page-link {
        background-color: #2d3748;
        border-color: #4a5568;
        color: #e2e8f0;
    }
    
    .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
}
</style>

<?php include '../layouts/footer.php'; ?>