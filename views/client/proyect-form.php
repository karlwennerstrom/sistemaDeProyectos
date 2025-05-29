<?php
// Configurar variables para el layout
$page_header = true;
$is_edit = isset($project) && $project->id;
$page_title = $is_edit ? 'Editar Proyecto' : 'Nuevo Proyecto';
$page_description = $is_edit ? 'Modifica la información de tu proyecto' : 'Crea un nuevo proyecto para el proceso de aprobación';

$additional_css = ['/public/assets/css/forms.css', '/public/assets/css/project-form.css'];
$additional_js = ['/public/assets/js/project-form.js', '/public/assets/js/form-validation.js'];

$breadcrumb = [
    ['text' => 'Dashboard', 'url' => '/client/dashboard'],
    ['text' => 'Mis Proyectos', 'url' => '/client/my-projects'],
    ['text' => $page_title, 'url' => '']
];

$page_actions = '
    <div class="btn-group">
        <a href="/client/my-projects" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver a Mis Proyectos
        </a>
        ' . ($is_edit ? '
        <a href="/client/projects/' . $project->id . '" class="btn btn-outline-info">
            <i class="fas fa-eye me-1"></i>Ver Proyecto
        </a>
        ' : '') . '
    </div>
';
?>

<?php include '../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12">
            
            <!-- Progreso del formulario -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-3">
                            <div class="form-progress">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="progress-step active" data-step="1">
                                        <div class="step-circle">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <span class="step-label">Información Básica</span>
                                    </div>
                                    <div class="progress-line"></div>
                                    <div class="progress-step" data-step="2">
                                        <div class="step-circle">
                                            <i class="fas fa-sitemap"></i>
                                        </div>
                                        <span class="step-label">Áreas Involucradas</span>
                                    </div>
                                    <div class="progress-line"></div>
                                    <div class="progress-step" data-step="3">
                                        <div class="step-circle">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <span class="step-label">Documentos</span>
                                    </div>
                                    <div class="progress-line"></div>
                                    <div class="progress-step" data-step="4">
                                        <div class="step-circle">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <span class="step-label">Revisión Final</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulario principal -->
            <form id="projectForm" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="project_id" value="<?= $project->id ?>">
                <?php endif; ?>
                
                <!-- Paso 1: Información Básica -->
                <div class="form-step active" data-step="1">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <div class="d-flex align-items-center">
                                <div class="step-icon bg-primary bg-opacity-10 me-3">
                                    <i class="fas fa-info-circle text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0">Información Básica del Proyecto</h5>
                                    <p class="text-muted mb-0">Define los aspectos fundamentales de tu proyecto</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-4">
                                        <label for="project_name" class="form-label required">Nombre del Proyecto</label>
                                        <input type="text" 
                                               class="form-control form-control-lg" 
                                               id="project_name" 
                                               name="name" 
                                               value="<?= htmlspecialchars($project->name ?? '') ?>"
                                               required
                                               maxlength="255"
                                               placeholder="Ej: Sistema de Gestión de Inventario">
                                        <div class="form-text">
                                            Elige un nombre claro y descriptivo que identifique tu proyecto
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label for="project_priority" class="form-label">Prioridad</label>
                                        <select class="form-select" id="project_priority" name="priority">
                                            <option value="low" <?= ($project->priority ?? 'medium') === 'low' ? 'selected' : '' ?>>
                                                Baja
                                            </option>
                                            <option value="medium" <?= ($project->priority ?? 'medium') === 'medium' ? 'selected' : '' ?>>
                                                Media
                                            </option>
                                            <option value="high" <?= ($project->priority ?? 'medium') === 'high' ? 'selected' : '' ?>>
                                                Alta
                                            </option>
                                            <option value="urgent" <?= ($project->priority ?? 'medium') === 'urgent' ? 'selected' : '' ?>>
                                                Urgente
                                            </option>
                                        </select>
                                        <div class="form-text">
                                            Indica la urgencia de tu proyecto
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="project_description" class="form-label required">Descripción del Proyecto</label>
                                <textarea class="form-control" 
                                          id="project_description" 
                                          name="description" 
                                          rows="5"
                                          required
                                          maxlength="2000"
                                          placeholder="Describe detalladamente tu proyecto: objetivos, alcance, beneficios esperados..."><?= htmlspecialchars($project->description ?? '') ?></textarea>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="form-text">
                                        Proporciona una descripción completa que ayude a los revisores a entender tu proyecto
                                    </div>
                                    <small class="text-muted">
                                        <span id="descriptionCounter">0</span>/2000 caracteres
                                    </small>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="estimated_start_date" class="form-label">Fecha de Inicio Estimada</label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="estimated_start_date" 
                                               name="estimated_start_date"
                                               value="<?= $project->estimated_start_date ?? '' ?>"
                                               min="<?= date('Y-m-d') ?>">
                                        <div class="form-text">
                                            ¿Cuándo planeas comenzar el proyecto?
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="estimated_end_date" class="form-label">Fecha de Finalización Estimada</label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="estimated_end_date" 
                                               name="estimated_end_date"
                                               value="<?= $project->estimated_end_date ?? '' ?>"
                                               min="<?= date('Y-m-d') ?>">
                                        <div class="form-text">
                                            ¿Cuándo esperas completar el proyecto?
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="budget_estimated" class="form-label">Presupuesto Estimado</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="budget_estimated" 
                                                   name="budget_estimated"
                                                   value="<?= $project->budget_estimated ?? '' ?>"
                                                   min="0"
                                                   step="1000"
                                                   placeholder="0">
                                            <span class="input-group-text">CLP</span>
                                        </div>
                                        <div class="form-text">
                                            Estimación aproximada del costo del proyecto
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="business_justification" class="form-label">Justificación de Negocio</label>
                                        <select class="form-select" id="business_justification" name="business_justification">
                                            <option value="">Seleccionar justificación</option>
                                            <option value="efficiency" <?= ($project->business_justification ?? '') === 'efficiency' ? 'selected' : '' ?>>
                                                Mejora de Eficiencia
                                            </option>
                                            <option value="cost_reduction" <?= ($project->business_justification ?? '') === 'cost_reduction' ? 'selected' : '' ?>>
                                                Reducción de Costos
                                            </option>
                                            <option value="compliance" <?= ($project->business_justification ?? '') === 'compliance' ? 'selected' : '' ?>>
                                                Cumplimiento Normativo
                                            </option>
                                            <option value="strategic" <?= ($project->business_justification ?? '') === 'strategic' ? 'selected' : '' ?>>
                                                Objetivo Estratégico
                                            </option>
                                            <option value="innovation" <?= ($project->business_justification ?? '') === 'innovation' ? 'selected' : '' ?>>
                                                Innovación
                                            </option>
                                            <option value="maintenance" <?= ($project->business_justification ?? '') === 'maintenance' ? 'selected' : '' ?>>
                                                Mantenimiento
                                            </option>
                                        </select>
                                        <div class="form-text">
                                            ¿Cuál es el driver principal del proyecto?
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(2)">
                                    Siguiente: Áreas Involucradas
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Paso 2: Áreas Involucradas -->
                <div class="form-step" data-step="2">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <div class="d-flex align-items-center">
                                <div class="step-icon bg-info bg-opacity-10 me-3">
                                    <i class="fas fa-sitemap text-info"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0">Áreas Involucradas en el Proyecto</h5>
                                    <p class="text-muted mb-0">Selecciona las áreas que deben revisar y aprobar tu proyecto</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Importante:</strong> Cada área seleccionada revisará tu proyecto independientemente. 
                                Asegúrate de incluir todas las áreas relevantes para evitar retrasos en el proceso.
                            </div>
                            
                            <div class="areas-selection">
                                <div class="row">
                                    <?php 
                                    $areas = \UC\ApprovalSystem\Utils\Helper::getAreas();
                                    $selected_areas = $project ? json_decode($project->areas, true) ?? [] : [];
                                    
                                    foreach ($areas as $area_key => $area_info): 
                                    ?>
                                        <div class="col-lg-6 col-md-12 mb-4">
                                            <div class="area-card <?= in_array($area_key, $selected_areas) ? 'selected' : '' ?>" 
                                                 data-area="<?= $area_key ?>">
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           id="area_<?= $area_key ?>" 
                                                           name="areas[]" 
                                                           value="<?= $area_key ?>"
                                                           <?= in_array($area_key, $selected_areas) ? 'checked' : '' ?>
                                                           onchange="toggleAreaCard(this)">
                                                    <label class="form-check-label w-100" for="area_<?= $area_key ?>">
                                                        <div class="area-header">
                                                            <div class="area-icon">
                                                                <i class="fas fa-<?= $area_info['icon'] ?? 'cube' ?>"></i>
                                                            </div>
                                                            <div class="area-info">
                                                                <h6 class="area-name"><?= htmlspecialchars($area_info['name']) ?></h6>
                                                                <p class="area-description"><?= htmlspecialchars($area_info['description']) ?></p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="area-details">
                                                            <div class="detail-item">
                                                                <i class="fas fa-clock text-primary me-2"></i>
                                                                <span>Tiempo estimado: <?= $area_info['review_time'] ?? '5-7 días' ?></span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <i class="fas fa-user text-success me-2"></i>
                                                                <span>Responsable: <?= $area_info['responsible'] ?? 'Equipo ' . $area_info['name'] ?></span>
                                                            </div>
                                                            <?php if (isset($area_info['requirements'])): ?>
                                                                <div class="detail-item">
                                                                    <i class="fas fa-list-check text-warning me-2"></i>
                                                                    <span>Documentos: <?= count($area_info['requirements']) ?> requeridos</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="selected-areas-summary mt-4">
                                <h6>Resumen de Áreas Seleccionadas</h6>
                                <div id="areasSelected" class="areas-summary-content">
                                    <p class="text-muted">Ninguna área seleccionada aún</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="prevStep(1)">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Anterior
                                </button>
                                <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(3)" id="step2NextBtn" disabled>
                                    Siguiente: Documentos
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Paso 3: Documentos -->
                <div class="form-step" data-step="3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <div class="d-flex align-items-center">
                                <div class="step-icon bg-warning bg-opacity-10 me-3">
                                    <i class="fas fa-file-alt text-warning"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0">Documentos del Proyecto</h5>
                                    <p class="text-muted mb-0">Sube los documentos iniciales o hazlo después de crear el proyecto</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Nota:</strong> Puedes subir documentos ahora o después de crear el proyecto. 
                                Los documentos requeridos dependen de las áreas que hayas seleccionado.
                            </div>
                            
                            <div class="documents-section">
                                <div id="requiredDocuments">
                                    <!-- Se llenará dinámicamente según las áreas seleccionadas -->
                                </div>
                                
                                <div class="upload-zone mt-4">
                                    <div class="upload-area" id="uploadArea">
                                        <div class="upload-content">
                                            <i class="fas fa-cloud-upload-alt text-primary mb-3" style="font-size: 3rem;"></i>
                                            <h6>Arrastra archivos aquí o haz clic para seleccionar</h6>
                                            <p class="text-muted">
                                                Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP<br>
                                                Tamaño máximo: 10 MB por archivo
                                            </p>
                                            <input type="file" 
                                                   id="documentFiles" 
                                                   name="documents[]" 
                                                   multiple 
                                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip"
                                                   style="display: none;">
                                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('documentFiles').click()">
                                                <i class="fas fa-plus me-2"></i>Seleccionar Archivos
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="uploadedFiles" class="uploaded-files mt-4"></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="prevStep(2)">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Anterior
                                </button>
                                <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(4)">
                                    Siguiente: Revisión Final
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Paso 4: Revisión Final -->
                <div class="form-step" data-step="4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <div class="d-flex align-items-center">
                                <div class="step-icon bg-success bg-opacity-10 me-3">
                                    <i class="fas fa-check text-success"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0">Revisión Final del Proyecto</h5>
                                    <p class="text-muted mb-0">Verifica toda la información antes de guardar</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="review-section">
                                <!-- Información básica -->
                                <div class="review-item mb-4">
                                    <h6 class="review-title">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        Información Básica
                                    </h6>
                                    <div class="review-content">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Nombre:</strong> <span id="reviewName">-</span>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Prioridad:</strong> <span id="reviewPriority">-</span>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <strong>Descripción:</strong><br>
                                            <span id="reviewDescription" class="text-muted">-</span>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <strong>Inicio Estimado:</strong> <span id="reviewStartDate">-</span>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Fin Estimado:</strong> <span id="reviewEndDate">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Áreas seleccionadas -->
                                <div class="review-item mb-4">
                                    <h6 class="review-title">
                                        <i class="fas fa-sitemap text-info me-2"></i>
                                        Áreas Involucradas
                                    </h6>
                                    <div class="review-content">
                                        <div id="reviewAreas">-</div>
                                    </div>
                                </div>
                                
                                <!-- Documentos -->
                                <div class="review-item mb-4">
                                    <h6 class="review-title">
                                        <i class="fas fa-file-alt text-warning me-2"></i>
                                        Documentos Iniciales
                                    </h6>
                                    <div class="review-content">
                                        <div id="reviewDocuments">-</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="save-options mt-4">
                                <h6>Opciones de Guardado</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="save_action" id="save_draft" value="draft" checked>
                                    <label class="form-check-label" for="save_draft">
                                        <strong>Guardar como borrador</strong>
                                        <div class="text-muted small">Podrás continuar editando el proyecto más tarde</div>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="save_action" id="save_submit" value="submit">
                                    <label class="form-check-label" for="save_submit">
                                        <strong>Guardar y enviar a revisión</strong>
                                        <div class="text-muted small">El proyecto se enviará inmediatamente a las áreas para revisión</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="prevStep(3)">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Anterior
                                </button>
                                <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $is_edit ? 'Actualizar Proyecto' : 'Crear Proyecto' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Panel de ayuda lateral -->
            <div class="help-panel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-question-circle me-2"></i>
                            ¿Necesitas Ayuda?
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="help-content">
                            <div class="help-item">
                                <h6>📋 Consejos para tu Proyecto</h6>
                                <ul class="list-unstyled">
                                    <li>• Usa un nombre descriptivo</li>
                                    <li>• Describe claramente los objetivos</li>
                                    <li>• Selecciona solo las áreas necesarias</li>
                                    <li>• Prepara documentos de calidad</li>
                                </ul>
                            </div>
                            
                            <div class="help-item">
                                <h6>⏱️ Tiempo de Revisión</h6>
                                <p class="text-muted small">
                                    El tiempo total depende de las áreas seleccionadas. 
                                    Generalmente entre 5-15 días hábiles.
                                </p>
                            </div>
                            
                            <div class="help-item">
                                <h6>📞 ¿Tienes Dudas?</h6>
                                <div class="d-grid gap-2">
                                    <a href="/client/help/project-workflow" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-book me-1"></i>Guía Completa
                                    </a>
                                    <a href="/client/help/contact" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-envelope me-1"></i>Contactar Soporte
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentStep = 1;
let selectedAreas = <?= json_encode($selected_areas) ?>;
let uploadedFiles = [];
const isEdit = <?= $is_edit ? 'true' : 'false' ?>;

// Información de áreas
const areasInfo = <?= json_encode($areas) ?>;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    setupValidation();
    setupFileUpload();
    setupCounters();
    updateAreasSelection();
});

function initializeForm() {
    // Configurar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configurar validación de fechas
    setupDateValidation();
    
    // Si es edición, actualizar el progreso
    if (isEdit) {
        updateFormProgress();
    }
}

function setupValidation() {
    const form = document.getElementById('projectForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateCurrentStep() && validateAllSteps()) {
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

function setupFileUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('documentFiles');
    
    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        const files = Array.from(e.dataTransfer.files);
        handleFileSelection(files);
    });
    
    // Click para seleccionar
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', function() {
        const files = Array.from(this.files);
        handleFileSelection(files);
    });
}

function setupCounters() {
    const descriptionTextarea = document.getElementById('project_description');
    const descriptionCounter = document.getElementById('descriptionCounter');
    
    function updateDescriptionCounter() {
        const length = descriptionTextarea.value.length;
        descriptionCounter.textContent = length;
        
        if (length > 1800) {
            descriptionCounter.classList.add('text-warning');
        } else {
            descriptionCounter.classList.remove('text-warning');
        }
    }
    
    descriptionTextarea.addEventListener('input', updateDescriptionCounter);
    updateDescriptionCounter(); // Inicializar
}

function setupDateValidation() {
    const startDateInput = document.getElementById('estimated_start_date');
    const endDateInput = document.getElementById('estimated_end_date');
    
    startDateInput.addEventListener('change', function() {
        if (this.value && endDateInput.value && this.value > endDateInput.value) {
            endDateInput.value = '';
            showAlert('La fecha de inicio no puede ser posterior a la fecha de fin', 'warning');
        }
        endDateInput.min = this.value;
    });
    
    endDateInput.addEventListener('change', function() {
        if (this.value && startDateInput.value && this.value < startDateInput.value) {
            showAlert('La fecha de fin no puede ser anterior a la fecha de inicio', 'warning');
            this.value = '';
        }
    });
}

// Navegación entre pasos
function nextStep(step) {
    if (validateCurrentStep()) {
        showStep(step);
        updateReviewSection();
    }
}

function prevStep(step) {
    showStep(step);
}

function showStep(step) {
    // Ocultar todos los pasos
    document.querySelectorAll('.form-step').forEach(stepElement => {
        stepElement.classList.remove('active');
    });
    
    // Mostrar paso actual
    document.querySelector(`[data-step="${step}"]`).classList.add('active');
    
    // Actualizar progreso visual
    updateProgressIndicator(step);
    
    currentStep = step;
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateProgressIndicator(step) {
    document.querySelectorAll('.progress-step').forEach((stepElement, index) => {
        if (index + 1 < step) {
            stepElement.classList.add('completed');
            stepElement.classList.remove('active');
        } else if (index + 1 === step) {
            stepElement.classList.add('active');
            stepElement.classList.remove('completed');
        } else {
            stepElement.classList.remove('active', 'completed');
        }
    });
}

// Validaciones
function validateCurrentStep() {
    switch (currentStep) {
        case 1:
            return validateStep1();
        case 2:
            return validateStep2();
        case 3:
            return validateStep3();
        case 4:
            return validateStep4();
        default:
            return true;
    }
}

function validateStep1() {
    const name = document.getElementById('project_name');
    const description = document.getElementById('project_description');
    
    let isValid = true;
    
    if (!validateField(name)) isValid = false;
    if (!validateField(description)) isValid = false;
    
    return isValid;
}

function validateStep2() {
    const selectedAreaInputs = document.querySelectorAll('input[name="areas[]"]:checked');
    
    if (selectedAreaInputs.length === 0) {
        showAlert('Debes seleccionar al menos un área para continuar', 'warning');
        return false;
    }
    
    return true;
}

function validateStep3() {
    // El paso de documentos es opcional
    return true;
}

function validateStep4() {
    // Validación final antes de enviar
    return validateStep1() && validateStep2();
}

function validateAllSteps() {
    return validateStep1() && validateStep2() && validateStep3() && validateStep4();
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    // Limpiar estado anterior
    field.classList.remove('is-valid', 'is-invalid');
    
    // Validaciones según el campo
    switch (field.name) {
        case 'name':
            if (!value) {
                message = 'El nombre del proyecto es requerido';
                isValid = false;
            } else if (value.length < 3) {
                message = 'El nombre debe tener al menos 3 caracteres';
                isValid = false;
            }
            break;
            
        case 'description':
            if (!value) {
                message = 'La descripción del proyecto es requerida';
                isValid = false;
            } else if (value.length < 20) {
                message = 'La descripción debe tener al menos 20 caracteres';
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

// Gestión de áreas
function toggleAreaCard(checkbox) {
    const areaCard = checkbox.closest('.area-card');
    const areaKey = checkbox.value;
    
    if (checkbox.checked) {
        areaCard.classList.add('selected');
        if (!selectedAreas.includes(areaKey)) {
            selectedAreas.push(areaKey);
        }
    } else {
        areaCard.classList.remove('selected');
        selectedAreas = selectedAreas.filter(area => area !== areaKey);
    }
    
    updateAreasSelection();
    updateRequiredDocuments();
    
    // Habilitar/deshabilitar botón siguiente
    const nextBtn = document.getElementById('step2NextBtn');
    nextBtn.disabled = selectedAreas.length === 0;
}

function updateAreasSelection() {
    const summaryContainer = document.getElementById('areasSelected');
    
    if (selectedAreas.length === 0) {
        summaryContainer.innerHTML = '<p class="text-muted">Ninguna área seleccionada aún</p>';
        return;
    }
    
    let html = '<div class="row">';
    selectedAreas.forEach(areaKey => {
        const areaInfo = areasInfo[areaKey];
        if (areaInfo) {
            html += `
                <div class="col-md-6 mb-2">
                    <div class="selected-area-item">
                        <i class="fas fa-${areaInfo.icon || 'cube'} text-primary me-2"></i>
                        <strong>${areaInfo.name}</strong>
                        <small class="text-muted d-block">Tiempo: ${areaInfo.review_time || '5-7 días'}</small>
                    </div>
                </div>
            `;
        }
    });
    html += '</div>';
    
    summaryContainer.innerHTML = html;
}

function updateRequiredDocuments() {
    const container = document.getElementById('requiredDocuments');
    
    if (selectedAreas.length === 0) {
        container.innerHTML = '<p class="text-muted">Selecciona áreas para ver los documentos requeridos</p>';
        return;
    }
    
    let html = '<h6>Documentos Requeridos por Área:</h6>';
    
    selectedAreas.forEach(areaKey => {
        const areaInfo = areasInfo[areaKey];
        if (areaInfo && areaInfo.requirements) {
            html += `
                <div class="area-requirements mb-3">
                    <h6 class="text-primary">
                        <i class="fas fa-${areaInfo.icon || 'cube'} me-2"></i>
                        ${areaInfo.name}
                    </h6>
                    <ul class="list-unstyled ms-3">
            `;
            
            areaInfo.requirements.forEach(req => {
                html += `
                    <li class="mb-1">
                        <i class="fas fa-file-alt text-muted me-2"></i>
                        ${req.name}
                        ${req.required ? '<span class="text-danger">*</span>' : ''}
                    </li>
                `;
            });
            
            html += '</ul></div>';
        }
    });
    
    container.innerHTML = html;
}

// Gestión de archivos
function handleFileSelection(files) {
    files.forEach(file => {
        if (validateFile(file)) {
            uploadedFiles.push({
                file: file,
                name: file.name,
                size: file.size,
                type: file.type
            });
        }
    });
    
    updateUploadedFilesList();
}

function validateFile(file) {
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                         'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                         'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                         'application/zip'];
    
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(pdf|doc|docx|xls|xlsx|ppt|pptx|zip)$/i)) {
        showAlert(`Tipo de archivo no permitido: ${file.name}`, 'error');
        return false;
    }
    
    if (file.size > maxSize) {
        showAlert(`Archivo muy grande: ${file.name} (máximo 10MB)`, 'error');
        return false;
    }
    
    return true;
}

function updateUploadedFilesList() {
    const container = document.getElementById('uploadedFiles');
    
    if (uploadedFiles.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<h6>Archivos Seleccionados:</h6><div class="uploaded-files-list">';
    
    uploadedFiles.forEach((fileObj, index) => {
        const sizeText = formatFileSize(fileObj.size);
        html += `
            <div class="uploaded-file-item">
                <div class="file-info">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    <div class="file-details">
                        <div class="file-name">${fileObj.name}</div>
                        <div class="file-size text-muted">${sizeText}</div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function removeFile(index) {
    uploadedFiles.splice(index, 1);
    updateUploadedFilesList();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Actualizar sección de revisión
function updateReviewSection() {
    if (currentStep !== 4) return;
    
    // Información básica
    document.getElementById('reviewName').textContent = document.getElementById('project_name').value || '-';
    document.getElementById('reviewPriority').textContent = getPriorityText(document.getElementById('project_priority').value);
    document.getElementById('reviewDescription').textContent = document.getElementById('project_description').value || '-';
    document.getElementById('reviewStartDate').textContent = formatDate(document.getElementById('estimated_start_date').value) || '-';
    document.getElementById('reviewEndDate').textContent = formatDate(document.getElementById('estimated_end_date').value) || '-';
    
    // Áreas
    const areasHtml = selectedAreas.length > 0 
        ? selectedAreas.map(area => `<span class="badge bg-primary me-1">${areasInfo[area]?.name || area}</span>`).join('')
        : '<span class="text-muted">Ninguna área seleccionada</span>';
    document.getElementById('reviewAreas').innerHTML = areasHtml;
    
    // Documentos
    const documentsHtml = uploadedFiles.length > 0
        ? uploadedFiles.map(file => `<div class="mb-1"><i class="fas fa-file-alt me-2"></i>${file.name}</div>`).join('')
        : '<span class="text-muted">No hay documentos seleccionados</span>';
    document.getElementById('reviewDocuments').innerHTML = documentsHtml;
}

// Envío del formulario
function submitForm() {
    const form = document.getElementById('projectForm');
    const submitBtn = document.getElementById('submitBtn');
    const formData = new FormData(form);
    
    // Agregar archivos
    uploadedFiles.forEach((fileObj, index) => {
        formData.append(`documents[${index}]`, fileObj.file);
    });
    
    // Deshabilitar botón y mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    showLoader(true);
    
    const url = isEdit ? `/client/projects/${document.querySelector('[name="project_id"]').value}` : '/client/projects';
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
            const action = document.querySelector('input[name="save_action"]:checked').value;
            const message = action === 'submit' 
                ? 'Proyecto creado y enviado a revisión correctamente'
                : 'Proyecto guardado correctamente';
            
            showAlert(message, 'success');
            
            // Redirigir después de un momento
            setTimeout(() => {
                window.location.href = data.redirect || '/client/my-projects';
            }, 2000);
        } else {
            showAlert(data.message || 'Error al guardar proyecto', 'error');
            
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
        submitBtn.innerHTML = `<i class="fas fa-save me-2"></i>${isEdit ? 'Actualizar Proyecto' : 'Crear Proyecto'}`;
        showLoader(false);
    });
}

// Funciones auxiliares
function getPriorityText(priority) {
    const priorities = {
        'low': 'Baja',
        'medium': 'Media',
        'high': 'Alta',
        'urgent': 'Urgente'
    };
    return priorities[priority] || priority;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES');
}

function updateFormProgress() {
    // Si es edición, marcar pasos como completados según los datos existentes
    if (document.getElementById('project_name').value && document.getElementById('project_description').value) {
        document.querySelector('[data-step="1"]').classList.add('completed');
    }
    
    if (selectedAreas.length > 0) {
        document.querySelector('[data-step="2"]').classList.add('completed');
        document.getElementById('step2NextBtn').disabled = false;
    }
}
</script>

<style>
/* Estilos específicos del formulario de proyecto */
.form-progress {
    padding: 1rem 0;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    flex: 1;
    text-align: center;
}

.step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    margin-bottom: 0.5rem;
}

.progress-step.active .step-circle {
    background-color: #0d6efd;
    color: white;
}

.progress-step.completed .step-circle {
    background-color: #198754;
    color: white;
}

.step-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6c757d;
    transition: color 0.3s ease;
}

.progress-step.active .step-label {
    color: #0d6efd;
    font-weight: 600;
}

.progress-step.completed .step-label {
    color: #198754;
    font-weight: 600;
}

.progress-line {
    height: 2px;
    background-color: #e9ecef;
    flex: 1;
    margin: 0 1rem;
    align-self: center;
    margin-top: -25px;
    z-index: -1;
}

.progress-step.completed + .progress-line {
    background-color: #198754;
}

.form-step {
    display: none;
}

.form-step.active {
    display: block;
    animation: fadeInUp 0.3s ease;
}

.step-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.area-card {
    border: 2px solid #e9ecef;
    border-radius: 0.75rem;
    padding: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    height: 100%;
}

.area-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.area-card.selected {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}

.area-header {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.area-icon {
    width: 50px;
    height: 50px;
    border-radius: 0.5rem;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: #0d6efd;
    margin-right: 1rem;
    flex-shrink: 0;
}

.area-info {
    flex-grow: 1;
}

.area-name {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #495057;
}

.area-description {
    color: #6c757d;
    font-size: 0.875rem;
    line-height: 1.4;
    margin-bottom: 0;
}

.area-details {
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.detail-item {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    color: #6c757d;
}

.upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 0.75rem;
    padding: 3rem 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.upload-area:hover,
.upload-area.drag-over {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.upload-content {
    pointer-events: none;
}

.uploaded-files-list {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
}

.uploaded-file-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.75rem;
    background-color: white;
    border-radius: 0.375rem;
    margin-bottom: 0.5rem;
    border: 1px solid #e9ecef;
}

.uploaded-file-item:last-child {
    margin-bottom: 0;
}

.file-info {
    display: flex;
    align-items: center;
    flex-grow: 1;
}

.file-details {
    margin-left: 0.5rem;
}

.file-name {
    font-weight: 500;
    color: #495057;
}

.file-size {
    font-size: 0.8rem;
}

.review-section {
    background-color: #f8f9fa;
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.review-item {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.25rem;
    border: 1px solid #e9ecef;
}

.review-title {
    font-weight: 600;
    margin-bottom: 1rem;
    color: #495057;
}

.review-content {
    color: #6c757d;
}

.save-options {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.25rem;
    border: 1px solid #e9ecef;
}

.help-panel {
    position: fixed;
    right: 2rem;
    top: 50%;
    transform: translateY(-50%);
    width: 300px;
    z-index: 1000;
}

.help-item {
    margin-bottom: 1.5rem;
}

.help-item:last-child {
    margin-bottom: 0;
}

.help-item h6 {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #495057;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .help-panel {
        position: static;
        width: 100%;
        transform: none;
        margin-top: 2rem;
    }
}

@media (max-width: 768px) {
    .form-progress {
        padding: 0.5rem 0;
    }
    
    .progress-step {
        flex-direction: row;
        justify-content: flex-start;
        text-align: left;
        margin-bottom: 1rem;
    }
    
    .step-circle {
        width: 40px;
        height: 40px;
        margin-right: 0.75rem;
        margin-bottom: 0;
        font-size: 1rem;
    }
    
    .step-label {
        font-size: 0.8rem;
    }
    
    .progress-line {
        display: none;
    }
    
    .area-header {
        flex-direction: column;
        text-align: center;
    }
    
    .area-icon {
        margin-right: 0;
        margin-bottom: 1rem;
        align-self: center;
    }
    
    .upload-area {
        padding: 2rem 1rem;
    }
    
    .step-icon {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
}

@media (max-width: 576px) {
    .card-body {
        padding: 1rem;
    }
    
    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }
    
    .area-card {
        padding: 1rem;
    }
    
    .review-item {
        padding: 1rem;
    }
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.area-card {
    animation: fadeInUp 0.3s ease;
}

.area-card:nth-child(odd) {
    animation-delay: 0.1s;
}

.area-card:nth-child(even) {
    animation-delay: 0.2s;
}

/* Estados de validación mejorados */
.is-valid {
    border-color: #198754;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.6-.6L7.03 2.3l-.6-.6L3.4 4.71l-1.18-1.18-.6.6z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.is-invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.5 5.5 1 1m0-1L5.5 6.5'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .area-card {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .area-card:hover,
    .area-card.selected {
        background-color: #4a5568;
        border-color: #0d6efd;
    }
    
    .upload-area {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .review-section,
    .save-options {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .review-item {
        background-color: #4a5568;
        border-color: #718096;
    }
}
</style>

<?php include '../layouts/footer.php'; ?>