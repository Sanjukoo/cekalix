# CEKALIX - Sistema de Gestión de Inventario

Instrucciones de instalación y configuración.

## Instalación Completada

Este proyecto integra la funcionalidad de tres sistemas de inventario.

---

## Inicio Rápido

### Usuario de Prueba
```
Email: admin@cekalix.com
Contraseña: password
```

### Acceso a la Aplicación
```
URL: http://localhost/cekalix/public
```

---

## Base de Datos

### Configuración Actual (SQLite)
La aplicación está configurada para usar SQLite para facilitar el desarrollo y pruebas.

Archivo: database/database.sqlite

### Migrar a MySQL (Opcional)

Si deseas usar MySQL en lugar de SQLite:

#### 1. Inicia MySQL en XAMPP
- Abre XAMPP Control Panel
- Haz clic en "Start" junto a MySQL

#### 2. Actualiza el archivo .env
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cekalix
DB_USERNAME=root
DB_PASSWORD=
```

#### 3. Crea la base de datos MySQL
```bash
php artisan migrate:fresh --seed
```

---

## Comandos Útiles

### Crear nuevo usuario
```bash
php artisan tinker
>>> \App\Models\User::create(['name' => 'Nombre', 'email' => 'usuario@example.com', 'password' => bcrypt('password'), 'role' => 'user'])
>>> exit
```

### Resetear base de datos
```bash
php artisan migrate:fresh --seed
```

### Ver todas las rutas
```bash
php artisan route:list
```

---

## Estructura de la Aplicación

### Modelos
- User - Usuarios del sistema
- Categoria - Categorías de productos
- Producto - Productos del inventario
- AtributoProducto - Atributos dinámicos de productos
- Proveedor - Proveedores
- Importacion - Registro de importaciones
- ImportacionDetalle - Detalles de importaciones

### Controladores
- AuthController - Autenticación
- DashboardController - Panel principal
- ProductoController - CRUD de productos
- ProveedorController - CRUD de proveedores
- ImportacionController - Gestión de importaciones

### Vistas
- auth/login - Formulario de login
- dashboard/index - Panel de inicio
- productos/* - Gestión de productos
- proveedores/* - Gestión de proveedores
- importaciones/* - Gestión de importaciones

---

## Características Implementadas

### Autenticación
- Login seguro con email y contraseña
- Sesiones regeneradas
- Logout
- Protección de rutas con middleware auth

### Gestión de Productos
- Crear, leer, actualizar, eliminar productos
- Categorías dinámicas (Correderas, Bisagras, Pistones, Cerraduras)
- Atributos específicos por categoría
- Control de stock
- Búsqueda y paginación
- Carga AJAX de tabla de productos

### Gestión de Proveedores
- Crear, leer, actualizar, eliminar proveedores
- Estado activo/inactivo
- Protección contra eliminación si tiene importaciones

### Importaciones
- Registro de importaciones desde proveedores
- Relación con productos mediante detalles
- Número de factura y contenedor
- Fecha de llegada
- Estado de importación
- Transacciones para integridad de datos

---

## Diseño

- Framework CSS: Bootstrap 5.3
- Tema: Azul oscuro y rojo
- Responsive: Adaptable a móviles y tablets
- Componentes: Modales, tablas, alertas, etc.

---

## Seguridad

- CSRF protection en todos los formularios
- XSS prevention con Blade templates
- Validación de entrada en todos los controladores
- Contraseñas hasheadas con bcrypt
- Route model binding automático

---

## Solución de Problemas

### Error: "SQLSTATE[HY000] [2002] No se puede establecer conexión"
Solución: MySQL no está ejecutándose. Inicia MySQL desde XAMPP Control Panel o vuelve a usar SQLite.

### Error: "Port already in use"
Solución: Cambia el puerto en APP_URL en el .env

### Error: "Class not found"
Solución: Ejecuta composer dump-autoload

---

## Soporte

Para más información sobre Laravel, visita: https://laravel.com/docs
