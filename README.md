# Sistema de Aprobación Multi-Área
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
