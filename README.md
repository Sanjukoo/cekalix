# CEKALIX - Sistema de Gestión de Inventario

Una aplicación Laravel moderna y completa para gestionar productos, proveedores e importaciones.

## Características Principales

### Autenticación
- Login seguro con email y contraseña
- Control de roles de usuario
- Sesiones protegidas con CSRF

### Gestión de Productos
- CRUD completo de productos
- Categorías dinámicas (Correderas, Bisagras, Pistones, Cerraduras)
- Atributos específicos según categoría
- Control de stock con indicadores visuales
- Búsqueda y paginación

### Gestión de Proveedores
- CRUD de proveedores
- Estado activo/inactivo
- Protección de integridad referencial

### Importaciones
- Registro de llegada de mercadería
- Relación con proveedores y productos
- Trazabilidad completa
- Estados de importación
- Historial automático

### Dashboard
- Estadísticas en tiempo real
- Productos bajo stock
- Acceso rápido a funciones principales

---

## Inicio Rápido

### Requisitos
- PHP 8.1+
- Composer
- MySQL / MariaDB (XAMPP)

### Instalación

```bash
# 1. Instalar dependencias (si es necesario)
composer install

# 2. Crear archivo .env (si no existe)
cp .env.example .env

# 3. Generar clave de aplicación
php artisan key:generate

# 4. Ejecutar migraciones con datos de prueba
php artisan migrate:fresh --seed

# 5. Iniciar servidor (opcional)
php artisan serve
```

### Acceso
- URL: http://localhost/cekalix/public
- Email: admin@cekalix.com
- Contraseña: password

---

## Documentación

- SETUP.md - Instrucciones de instalación y configuración
- GUIA_USO.md - Guía completa de uso del sistema
- MIGRACION.md - Detalles técnicos

---

## Arquitectura

### Modelos
- User - Usuarios del sistema
- Categoria - Categorías de productos
- Producto - Inventario
- AtributoProducto - Atributos dinámicos
- Proveedor - Proveedores
- Importacion - Importaciones
- ImportacionDetalle - Detalles de importaciones

### Base de Datos
- MySQL / MariaDB (base de datos `cekalix`)
- 7 tablas normalizadas
- Foreign keys con cascadas

### Controladores
- Laravel Fortify - Autenticación (login/logout)
- DashboardController - Panel principal
- ProductoController - Gestión de productos
- ProveedorController - Gestión de proveedores
- ImportacionController - Gestión de importaciones

---

## Interfaz

- Framework: Bootstrap 5.3
- Responsive: Adaptable a móviles, tablets y desktop
- Tema: Azul oscuro y rojo corporativo
- UX: Intuitiva y amigable

---

## Seguridad

- CSRF Protection
- XSS Prevention
- SQL Injection Prevention
- Password Hashing (bcrypt)
- Autenticación con middleware
- Validación robusta

---

## Estado del Proyecto

- Funcionalidades: 100% completadas
- Pruebas: Base de datos con datos de ejemplo
- Documentación: Completa
- Seguridad: Implementada
- UI/UX: Moderna y consistente

---

## Datos de Prueba

### Usuarios
- admin@cekalix.com / password
- usuario@cekalix.com / password

### Categorías
- Correderas
- Bisagras
- Pistones
- Cerraduras

### Proveedores
- Proveedor Internacional A.
- Global Trade Corp.
- Importadora del Pacífico

---

## Tecnologías

- Backend: Laravel 11, PHP 8.1+
- Frontend: Bootstrap 5.3, Blade Templates
- Database: MySQL / MariaDB
- ORM: Eloquent
- Validación: Form Requests
- Seguridad: Laravel Security Features

---

## Soporte

Para más información:
- Documentación de Laravel: https://laravel.com/docs
- Consulta SETUP.md para configuración
- Consulta GUIA_USO.md para usar la aplicación
- Consulta MIGRACION.md para detalles técnicos

---

## Licencia

Este proyecto es código original desarrollado como sistema de gestión de inventario.

---

Versión: 1.0.0
Estado: Producción
