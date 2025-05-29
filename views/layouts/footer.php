</main>
    
    <!-- Footer -->
    <footer class="uc-footer bg-light border-top mt-auto">
        <div class="container-fluid">
            <div class="row py-4">
                
                <!-- Información institucional -->
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= $_ENV['UC_LOGO_URL'] ?? '/public/assets/img/logo-uc.png' ?>" 
                             alt="Universidad Católica" 
                             height="30" 
                             class="me-2">
                        <span class="fw-bold text-primary">Universidad Católica</span>
                    </div>
                    <p class="text-muted mb-2">
                        Sistema de Aprobación Multi-Área para la gestión de proyectos de desarrollo institucional.
                    </p>
                    <div class="d-flex gap-2">
                        <a href="<?= $_ENV['UC_WEBSITE'] ?? 'https://www.uc.cl' ?>" 
                           class="btn btn-outline-primary btn-sm" 
                           target="_blank" 
                           rel="noopener">
                            <i class="fas fa-external-link-alt me-1"></i>
                            Sitio UC
                        </a>
                        <a href="/help/about" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-info-circle me-1"></i>
                            Acerca de
                        </a>
                    </div>
                </div>
                
                <!-- Enlaces rápidos -->
                <div class="col-md-2 col-6 mb-3">
                    <h6 class="fw-bold text-dark mb-3">Enlaces Rápidos</h6>
                    <ul class="list-unstyled">
                        <?php if (isset($current_user)): ?>
                            <?php if ($current_user->role === 'admin'): ?>
                                <li><a href="/admin/dashboard" class="text-muted text-decoration-none">Dashboard</a></li>
                                <li><a href="/admin/projects" class="text-muted text-decoration-none">Proyectos</a></li>
                                <li><a href="/admin/users" class="text-muted text-decoration-none">Usuarios</a></li>
                                <li><a href="/admin/reports" class="text-muted text-decoration-none">Reportes</a></li>
                            <?php else: ?>
                                <li><a href="/client/dashboard" class="text-muted text-decoration-none">Dashboard</a></li>
                                <li><a href="/client/projects" class="text-muted text-decoration-none">Mis Proyectos</a></li>
                                <li><a href="/client/documents" class="text-muted text-decoration-none">Documentos</a></li>
                                <li><a href="/client/feedback" class="text-muted text-decoration-none">Feedback</a></li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><a href="/login" class="text-muted text-decoration-none">Iniciar Sesión</a></li>
                            <li><a href="/help" class="text-muted text-decoration-none">Ayuda</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Soporte y ayuda -->
                <div class="col-md-2 col-6 mb-3">
                    <h6 class="fw-bold text-dark mb-3">Soporte</h6>
                    <ul class="list-unstyled">
                        <li><a href="/help/user-guide" class="text-muted text-decoration-none">Guía de Usuario</a></li>
                        <li><a href="/help/faq" class="text-muted text-decoration-none">FAQ</a></li>
                        <li><a href="/help/contact" class="text-muted text-decoration-none">Contacto</a></li>
                        <li><a href="/help/tutorials" class="text-muted text-decoration-none">Tutoriales</a></li>
                    </ul>
                </div>
                
                <!-- Información del sistema -->
                <div class="col-md-4 mb-3">
                    <h6 class="fw-bold text-dark mb-3">Información del Sistema</h6>
                    
                    <!-- Estado del sistema -->
                    <div class="d-flex align-items-center mb-2">
                        <div class="system-status me-2">
                            <div class="status-indicator bg-success rounded-circle" 
                                 style="width: 8px; height: 8px;"
                                 title="Sistema operativo"></div>
                        </div>
                        <small class="text-muted">Sistema Operativo</small>
                    </div>
                    
                    <!-- Versión -->
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-code me-1"></i>
                            Versión: <span class="fw-bold">1.0.0</span>
                        </small>
                    </div>
                    
                    <!-- Última actualización -->
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-sync me-1"></i>
                            Actualizado: <span class="fw-bold"><?= date('d/m/Y') ?></span>
                        </small>
                    </div>
                    
                    <!-- Enlaces de sistema para administradores -->
                    <?php if (isset($current_user) && $current_user->role === 'admin'): ?>
                        <div class="mt-3">
                            <div class="btn-group-sm">
                                <a href="/admin/system/health" 
                                   class="btn btn-outline-success btn-sm me-1" 
                                   title="Estado del Sistema">
                                    <i class="fas fa-heartbeat"></i>
                                </a>
                                <a href="/admin/system/logs" 
                                   class="btn btn-outline-info btn-sm me-1" 
                                   title="Logs del Sistema">
                                    <i class="fas fa-file-text"></i>
                                </a>
                                <a href="/admin/settings" 
                                   class="btn btn-outline-secondary btn-sm" 
                                   title="Configuración">
                                    <i class="fas fa-cog"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Separador -->
            <hr class="my-2">
            
            <!-- Copyright y enlaces legales -->
            <div class="row align-items-center py-2">
                <div class="col-md-6">
                    <small class="text-muted">
                        © <?= date('Y') ?> Universidad Católica. Todos los derechos reservados.
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small>
                        <a href="/legal/privacy" class="text-muted text-decoration-none me-3">Privacidad</a>
                        <a href="/legal/terms" class="text-muted text-decoration-none me-3">Términos</a>
                        <a href="/legal/cookies" class="text-muted text-decoration-none">Cookies</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts de Bootstrap -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="/public/assets/js/app.js"></script>
    
    <?php if (isset($current_user) && $current_user->role === 'admin'): ?>
        <script src="/public/assets/js/admin.js"></script>
    <?php else: ?>
        <script src="/public/assets/js/client.js"></script>
    <?php endif; ?>
    
    <!-- Scripts adicionales específicos de página -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?= $js_file ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Script inline si existe -->
    <?php if (isset($inline_js)): ?>
        <script>
            <?= $inline_js ?>
        </script>
    <?php endif; ?>
    
    <!-- Google Analytics o herramientas de analítica -->
    <?php if (isset($_ENV['GOOGLE_ANALYTICS_ID']) && !empty($_ENV['GOOGLE_ANALYTICS_ID'])): ?>
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $_ENV['GOOGLE_ANALYTICS_ID'] ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?= $_ENV['GOOGLE_ANALYTICS_ID'] ?>');
        </script>
    <?php endif; ?>
    
    <!-- Script para manejo de notificaciones en tiempo real -->
    <script>
        // Configuración global de notificaciones
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar sistema de notificaciones
            if (window.APP_CONFIG.USER_ROLE !== 'guest') {
                initializeNotifications();
                
                // Polling para notificaciones cada 30 segundos
                setInterval(function() {
                    fetchNotifications();
                }, 30000);
            }
            
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Inicializar popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Auto-ocultar alertas después de 5 segundos
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
        
        // Función para mostrar alertas globales
        function showAlert(message, type = 'info', timeout = 5000) {
            const alertsContainer = document.getElementById('global-alerts');
            const alertId = 'alert-' + Date.now();
            
            const alertHTML = `
                <div id="${alertId}" class="alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-triangle' : 'info-circle')} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            `;
            
            alertsContainer.insertAdjacentHTML('beforeend', alertHTML);
            
            // Auto-ocultar después del timeout
            if (timeout > 0) {
                setTimeout(function() {
                    const alert = document.getElementById(alertId);
                    if (alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, timeout);
            }
        }
        
        // Función para mostrar/ocultar loader global
        function showLoader(show = true) {
            const loader = document.getElementById('global-loader');
            if (show) {
                loader.classList.remove('d-none');
            } else {
                loader.classList.add('d-none');
            }
        }
        
        // Función para inicializar notificaciones
        function initializeNotifications() {
            fetchNotifications();
        }
        
        // Función para obtener notificaciones
        function fetchNotifications() {
            fetch('/api/notifications', {
                headers: {
                    'Authorization': 'Bearer ' + (localStorage.getItem('api_token') || ''),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.unread_count);
                    updateNotificationsList(data.notifications);
                }
            })
            .catch(error => {
                console.warn('Error fetching notifications:', error);
            });
        }
        
        // Función para actualizar el badge de notificaciones
        function updateNotificationBadge(count) {
            const badge = document.getElementById('notification-count');
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Función para actualizar la lista de notificaciones
        function updateNotificationsList(notifications) {
            const container = document.getElementById('notifications-list');
            
            if (notifications.length === 0) {
                container.innerHTML = `
                    <li class="dropdown-item text-muted text-center py-3">
                        <i class="fas fa-bell-slash me-2"></i>
                        No hay notificaciones
                    </li>
                `;
                return;
            }
            
            let html = '';
            notifications.slice(0, 5).forEach(notification => {
                const timeAgo = getTimeAgo(notification.created_at);
                const icon = getNotificationIcon(notification.type);
                const unreadClass = notification.is_read ? '' : 'fw-bold';
                
                html += `
                    <li class="dropdown-item notification-item ${unreadClass}" 
                        data-notification-id="${notification.id}"
                        onclick="markNotificationAsRead(${notification.id})">
                        <div class="d-flex">
                            <div class="notification-icon me-2">
                                <i class="fas fa-${icon} text-primary"></i>
                            </div>
                            <div class="notification-content flex-grow-1">
                                <div class="notification-title">${notification.title}</div>
                                <div class="notification-message text-muted small">${notification.message}</div>
                                <div class="notification-time text-muted small">${timeAgo}</div>
                            </div>
                            ${!notification.is_read ? '<div class="notification-unread"><span class="badge bg-primary rounded-pill"></span></div>' : ''}
                        </div>
                    </li>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Función para marcar notificación como leída
        function markNotificationAsRead(notificationId) {
            fetch(`/api/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + (localStorage.getItem('api_token') || ''),
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar UI
                    const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (item) {
                        item.classList.remove('fw-bold');
                        const unreadBadge = item.querySelector('.notification-unread');
                        if (unreadBadge) {
                            unreadBadge.remove();
                        }
                    }
                    
                    // Actualizar contador
                    fetchNotifications();
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }
        
        // Función para marcar todas las notificaciones como leídas
        function markAllNotificationsRead() {
            fetch('/api/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + (localStorage.getItem('api_token') || ''),
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.APP_CONFIG.CSRF_TOKEN
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Todas las notificaciones marcadas como leídas', 'success');
                    fetchNotifications();
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
                showAlert('Error al marcar notificaciones como leídas', 'error');
            });
        }
        
        // Funciones auxiliares
        function getNotificationIcon(type) {
            const icons = {
                'project_status': 'project-diagram',
                'document_uploaded': 'file-upload',
                'document_approved': 'check-circle',
                'document_rejected': 'times-circle',
                'feedback_received': 'comment',
                'user_assigned': 'user-plus',
                'system_alert': 'exclamation-triangle',
                'default': 'bell'
            };
            return icons[type] || icons.default;
        }
        
        function getTimeAgo(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = Math.floor((now - time) / 1000);
            
            if (diff < 60) return 'Hace un momento';
            if (diff < 3600) return `Hace ${Math.floor(diff / 60)} min`;
            if (diff < 86400) return `Hace ${Math.floor(diff / 3600)} h`;
            if (diff < 604800) return `Hace ${Math.floor(diff / 86400)} días`;
            
            return time.toLocaleDateString();
        }
    </script>
    
    <style>
        /* Estilos para el footer */
        .uc-footer {
            margin-top: auto;
        }
        
        .system-status {
            display: flex;
            align-items: center;
        }
        
        .status-indicator {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 6px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
        
        /* Estilos para el loader global */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loader-content {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            text-align: center;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        /* Estilos para notificaciones */
        .notification-dropdown {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            cursor: pointer;
            transition: background-color 0.2s ease;
            border: none !important;
            padding: 0.75rem 1rem;
        }
        
        .notification-item:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
        
        .notification-icon {
            width: 30px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 0.25rem;
        }
        
        .notification-content {
            min-width: 0;
        }
        
        .notification-title {
            font-size: 0.9rem;
            line-height: 1.3;
            margin-bottom: 0.25rem;
        }
        
        .notification-message {
            font-size: 0.8rem;
            line-height: 1.2;
            margin-bottom: 0.25rem;
            word-wrap: break-word;
        }
        
        .notification-time {
            font-size: 0.75rem;
        }
        
        .notification-unread {
            width: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 0.5rem;
        }
        
        .notification-unread .badge {
            width: 8px;
            height: 8px;
            padding: 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .uc-footer .row > div {
                margin-bottom: 1.5rem !important;
            }
            
            .notification-dropdown {
                width: 300px !important;
            }
        }
        
        /* Mejoras de accesibilidad */
        .notification-item:focus {
            outline: 2px solid #0d6efd;
            outline-offset: -2px;
        }
        
        /* Estilos para alertas globales */
        #global-alerts {
            z-index: 1050;
        }
        
        #global-alerts .alert {
            min-width: 300px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>

</body>
</html>