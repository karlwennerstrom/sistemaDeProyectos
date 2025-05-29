<?php
/**
 * Script de Configuración Automática del Proyecto
 * Sistema de Aprobación Multi-Área - Universidad Católica
 * 
 * Instrucciones:
 * 1. Crear una carpeta llamada 'approval-system'
 * 2. Colocar este archivo setup.php en la raíz de esa carpeta
 * 3. Ejecutar: php setup.php
 * 4. El script creará toda la estructura automáticamente
 */

echo "🏛️  Configurando Sistema de Aprobación UC...\n\n";

// Definir la estructura completa del proyecto
$structure = [
    // Carpetas principales
    'config' => [],
    'src' => [],
    'src/Models' => [],
    'src/Controllers' => [],
    'src/Services' => [],
    'src/Utils' => [],
    'public' => [],
    'public/assets' => [],
    'public/assets/css' => [],
    'public/assets/js' => [],
    'public/assets/img' => [],
    'public/errors' => [],
    'views' => [],
    'views/admin' => [],
    'views/client' => [],
    'views/layouts' => [],
    'uploads' => [],
    'uploads/documents' => [],
    'uploads/templates' => [],
    'uploads/temp' => [],
    'database' => [],
    'database/migrations' => [],
    'database/seeds' => [],
    'logs' => [],
    'vendor' => []
];

// Definir todos los archivos que necesitamos crear
$files = [
    // Archivos de configuración raíz
    'composer.json' => '',
    '.env.example' => '',
    '.env' => '',
    '.htaccess' => '',
    'README.md' => '',
    '.gitignore' => '',
    
    // Archivos de configuración
    'config/app.php' => '<?php',
    'config/database.php' => '<?php',
    'config/cas.php' => '<?php',
    'config/email.php' => '<?php',
    
    // Modelos
    'src/Models/BaseModel.php' => '<?php',
    'src/Models/User.php' => '<?php',
    'src/Models/Admin.php' => '<?php',
    'src/Models/Project.php' => '<?php',
    'src/Models/ProjectStage.php' => '<?php',
    'src/Models/ProjectFeedback.php' => '<?php',
    'src/Models/Document.php' => '<?php',
    'src/Models/DocumentTemplate.php' => '<?php',
    
    // Controladores
    'src/Controllers/BaseController.php' => '<?php',
    'src/Controllers/AuthController.php' => '<?php',
    'src/Controllers/ProjectController.php' => '<?php',
    'src/Controllers/AdminController.php' => '<?php',
    'src/Controllers/DocumentController.php' => '<?php',
    'src/Controllers/ApiController.php' => '<?php',
    
    // Servicios
    'src/Services/CASService.php' => '<?php',
    'src/Services/EmailService.php' => '<?php',
    'src/Services/FileService.php' => '<?php',
    'src/Services/NotificationService.php' => '<?php',
    
    // Utilidades
    'src/Utils/Database.php' => '<?php',
    'src/Utils/Session.php' => '<?php',
    'src/Utils/Validator.php' => '<?php',
    'src/Utils/Logger.php' => '<?php',
    'src/Utils/Helper.php' => '<?php',
    
    // Archivos públicos
    'public/index.php' => '<?php',
    'public/login.php' => '<?php',
    'public/logout.php' => '<?php',
    'public/callback.php' => '<?php',
    'public/assets/css/style.css' => '',
    'public/assets/css/admin.css' => '',
    'public/assets/css/client.css' => '',
    'public/assets/js/app.js' => '',
    'public/assets/js/admin.js' => '',
    'public/assets/js/client.js' => '',
    
    // Páginas de error
    'public/errors/404.php' => '<?php',
    'public/errors/403.php' => '<?php',
    'public/errors/500.php' => '<?php',
    
    // Vistas de administración
    'views/admin/dashboard.php' => '',
    'views/admin/projects.php' => '',
    'views/admin/project-detail.php' => '',
    'views/admin/users.php' => '',
    'views/admin/documents.php' => '',
    'views/admin/settings.php' => '',
    
    // Vistas de cliente
    'views/client/dashboard.php' => '',
    'views/client/project-status.php' => '',
    'views/client/upload-documents.php' => '',
    'views/client/my-projects.php' => '',
    
    // Layouts
    'views/layouts/header.php' => '',
    'views/layouts/footer.php' => '',
    'views/layouts/nav.php' => '',
    'views/layouts/admin-nav.php' => '',
    'views/layouts/client-nav.php' => '',
    
    // Base de datos
    'database/schema.sql' => '',
    'database/migrations/001_create_users_table.sql' => '',
    'database/migrations/002_create_projects_table.sql' => '',
    'database/migrations/003_create_documents_table.sql' => '',
    'database/seeds/admin_users.sql' => '',
    'database/seeds/document_templates.sql' => '',
    
    // Archivos de logs (temporales)
    'logs/.gitkeep' => '',
    'logs/app.log' => '',
    'logs/error.log' => '',
    
    // Uploads (temporales)
    'uploads/documents/.gitkeep' => '',
    'uploads/templates/.gitkeep' => '',
    'uploads/temp/.gitkeep' => '',
];

/**
 * Función para crear directorios
 */
function createDirectories($structure) {
    echo "📁 Creando estructura de directorios...\n";
    
    foreach ($structure as $dir => $subdirs) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "   ✅ Creado: $dir/\n";
            } else {
                echo "   ❌ Error creando: $dir/\n";
            }
        } else {
            echo "   ⚠️  Ya existe: $dir/\n";
        }
    }
    echo "\n";
}

/**
 * Función para crear archivos
 */
function createFiles($files) {
    echo "📄 Creando archivos del proyecto...\n";
    
    foreach ($files as $file => $content) {
        // Crear directorio padre si no existe
        $dir = dirname($file);
        if ($dir !== '.' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        if (!file_exists($file)) {
            if (file_put_contents($file, $content) !== false) {
                echo "   ✅ Creado: $file\n";
            } else {
                echo "   ❌ Error creando: $file\n";
            }
        } else {
            echo "   ⚠️  Ya existe: $file\n";
        }
    }
    echo "\n";
}

/**
 * Función para crear contenido del composer.json
 */
function createComposerJson() {
    $composerContent = '{
    "name": "uc/approval-system",
    "description": "Sistema de Aprobación Multi-Área - Universidad Católica",
    "type": "project",
    "require": {
        "php": ">=7.4",
        "phpmailer/phpmailer": "^6.8",
        "vlucas/phpdotenv": "^5.5",
        "monolog/monolog": "^3.4",
        "symfony/http-foundation": "^6.3",
        "twig/twig": "^3.7",
        "respect/validation": "^2.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.6"
    },
    "autoload": {
        "psr-4": {
            "UC\\\\ApprovalSystem\\\\": "src/",
            "UC\\\\ApprovalSystem\\\\Models\\\\": "src/Models/",
            "UC\\\\ApprovalSystem\\\\Controllers\\\\": "src/Controllers/", 
            "UC\\\\ApprovalSystem\\\\Services\\\\": "src/Services/",
            "UC\\\\ApprovalSystem\\\\Utils\\\\": "src/Utils/"
        }
    },
    "scripts": {
        "post-install-cmd": [
            "php -r \\"copy(\\".env.example\\", \\".env\\");\\"" 
        ]
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}';
    
    file_put_contents('composer.json', $composerContent);
}

/**
 * Función para crear contenido del .env.example
 */
function createEnvExample() {
    $envContent = '# Configuración de la Aplicación
APP_NAME="Sistema de Aprobación UC"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/approval-system

# Configuración de Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=approval_system
DB_USER=root
DB_PASS=

# Configuración CAS
CAS_SERVER=sso-lib.uc.cl
CAS_PORT=443
CAS_URI=/cas
CAS_VERSION=2.0
CAS_DEBUG=false

# Configuración de Email
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=sistema@uc.cl
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=sistema@uc.cl
MAIL_FROM_NAME="Sistema de Aprobación UC"

# Configuración de Archivos
MAX_FILE_SIZE=10485760
ALLOWED_EXTENSIONS=pdf,doc,docx,xls,xlsx,ppt,pptx,zip
UPLOAD_PATH=uploads/documents/
TEMPLATE_PATH=uploads/templates/

# Configuración de Sesión
SESSION_LIFETIME=7200
SESSION_NAME=approval_system_session

# URLs de la Universidad
UC_LOGO_URL=https://www.uc.cl/images/logo-uc.png
UC_WEBSITE=https://www.uc.cl

# Configuración de Logs
LOG_LEVEL=info
LOG_PATH=logs/

# Configuración de Seguridad
CSRF_TOKEN_NAME=csrf_token
ENCRYPTION_KEY=base64:your-32-character-secret-key-here

# Configuración de Cache
CACHE_ENABLED=true
CACHE_LIFETIME=3600';
    
    file_put_contents('.env.example', $envContent);
    if (!file_exists('.env')) {
        file_put_contents('.env', $envContent);
    }
}

/**
 * Función para crear .htaccess
 */
function createHtaccess() {
    $htaccessContent = '# Habilitar mod_rewrite
RewriteEngine On

# Redirigir todo a public/index.php excepto archivos existentes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php?route=$1 [QSA,L]

# Seguridad - Ocultar archivos sensibles
<Files ".env">
    Order Allow,Deny
    Deny from all
</Files>

<Files "composer.json">
    Order Allow,Deny  
    Deny from all
</Files>

# Proteger directorio vendor
<IfModule mod_alias.c>
    RedirectMatch 403 /vendor/.*$
</IfModule>

# Configuración para subida de archivos
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300

# Configuración de headers de seguridad
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>';
    
    file_put_contents('.htaccess', $htaccessContent);
}

/**
 * Función para crear .gitignore
 */
function createGitignore() {
    $gitignoreContent = '# Dependencias
/vendor/
/node_modules/

# Variables de entorno
.env
.env.local
.env.*.local

# Logs
/logs/*.log
/logs/*.txt

# Archivos subidos
/uploads/documents/*
!/uploads/documents/.gitkeep
/uploads/temp/*
!/uploads/temp/.gitkeep

# Cache
/cache/

# IDEs
.vscode/
.idea/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Temporal
*.tmp
*.bak

# Composer
composer.phar
composer.lock';
    
    file_put_contents('.gitignore', $gitignoreContent);
}

/**
 * Función para crear README.md
 */
function createReadme() {
    $readmeContent = '# Sistema de Aprobación Multi-Área
## Universidad Católica

### Descripción
Sistema de gestión de aprobaciones para proyectos de desarrollo con múltiples áreas de validación.

### Instalación

1. **Clonar el repositorio**
```bash
git clone [url-del-repo]
cd approval-system
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar variables de entorno**
```bash
cp .env.example .env
# Editar .env con tus configuraciones
```

4. **Crear base de datos**
```bash
mysql -u root -p < database/schema.sql
```

5. **Configurar permisos**
```bash
chmod 755 uploads/
chmod 755 logs/
```

### Configuración CAS
- Servidor: sso-lib.uc.cl
- Puerto: 443
- URI: /cas

### Áreas del Sistema
- 🏗️ Arquitectura
- 🔧 Infraestructura  
- 🛡️ Seguridad
- 📊 Base de Datos
- 🔗 Integraciones
- 🌐 Ambientes
- 🔍 JCPS
- 📈 Monitoreo

### Licencia
Propiedad de la Universidad Católica
';
    
    file_put_contents('README.md', $readmeContent);
}

// Ejecutar el setup
echo "🚀 Iniciando configuración del proyecto...\n\n";

// Crear directorios
createDirectories($structure);

// Crear archivos
createFiles($files);

// Crear archivos específicos con contenido
echo "📝 Creando archivos de configuración...\n";
createComposerJson();
echo "   ✅ composer.json creado\n";

createEnvExample();
echo "   ✅ .env.example y .env creados\n";

createHtaccess();
echo "   ✅ .htaccess creado\n";

createGitignore();
echo "   ✅ .gitignore creado\n";

createReadme();
echo "   ✅ README.md creado\n";

echo "\n🎉 ¡Configuración completada!\n\n";
echo "📋 Próximos pasos:\n";
echo "1. Ejecutar: composer install\n";
echo "2. Configurar tu archivo .env\n";
echo "3. Crear la base de datos MySQL\n";
echo "4. Configurar permisos: chmod 755 uploads/ logs/\n";
echo "\n✨ ¡Tu proyecto está listo para desarrollo!\n";

?>