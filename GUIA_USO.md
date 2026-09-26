# Guía de Uso - CEKALIX

## 1. Iniciar Sesión

URL: http://localhost/cekalix/public

Credenciales de prueba:
```
Email: admin@cekalix.com
Contraseña: password
```

---

## 2. Dashboard

Al iniciar sesión, verás el Dashboard con:
- Total de productos registrados
- Proveedores activos
- Importaciones registradas
- Productos con stock bajo (< 10 unidades)
- Botones de acceso rápido

---

## 3. Gestión de Productos

### Crear nuevo producto
1. Haz clic en "Nuevo Producto" (botón rojo)
2. Completa los datos:
   - Código: Identificador único (ej: CORR-001)
   - Nombre: Nombre del producto
   - Categoría: Correderas, Bisagras, Pistones o Cerraduras
   - Proveedor: Nombre del proveedor
   - Stock: Cantidad disponible
   - Unidades por Caja: Unidades que contiene cada caja
3. Se mostrarán atributos específicos según la categoría:
   - Correderas: Longitud, Espesor, Ancho, Color
   - Bisagras: Tipo, Acabado, Peso
   - Pistones: Fuerza, Longitud, Acabado
   - Cerraduras: Material, Tamaño
4. Haz clic en "Registrar Producto"

### Ver productos
1. Haz clic en "Productos" en el menú lateral
2. Verás una tabla con todos los productos registrados
3. Puedes:
   - Ver detalles completos
   - Editar producto
   - Eliminar producto

### Editar producto
1. Haz clic en el botón Editar en la tabla
2. Modifica los datos necesarios
3. Haz clic en "Actualizar Producto"

### Ver detalles
1. Haz clic en el botón Ver en la tabla
2. Se abrirá la página de detalles con toda la información
3. Desde aquí puedes:
   - Editar el producto
   - Eliminar el producto
   - Volver al listado

---

## 4. Gestión de Proveedores

### Crear nuevo proveedor
1. Haz clic en "Proveedores" en el menú lateral
2. Haz clic en "Nuevo Proveedor" (botón rojo)
3. Completa:
   - Nombre: Nombre del proveedor
   - Estado: Marca "Proveedor Activo" si debe estar disponible
4. Haz clic en "Registrar Proveedor"

### Ver y editar proveedores
1. En la tabla de proveedores:
   - Editar: Modifica nombre o estado
   - Eliminar: Solo si no tiene importaciones

### Estado de proveedor
- Activo: Disponible para seleccionar en importaciones
- Inactivo: No aparece en la lista de importaciones

---

## 5. Gestión de Importaciones

### Registrar nueva importación
1. Haz clic en "Importaciones" en el menú lateral
2. Haz clic en "Nueva Importación" (botón azul)
3. Completa los datos generales:
   - Proveedor: Selecciona de la lista de activos
   - Fecha de Llegada: Fecha en que llegó la mercadería
   - N.º de Factura: Número de factura del proveedor
   - N.º de Contenedor: Número del contenedor de envío
4. Completa el detalle de mercadería:
   - SKU/Producto: Selecciona el producto que se importó
   - Cajas Facturadas: Cantidad de cajas
5. Haz clic en "Registrar Importación"
6. Se asignará estado "En recepción" automáticamente

### Ver importaciones
1. En la tabla de importaciones verás:
   - ID: Número de folio
   - Proveedor: Nombre del proveedor
   - Factura/Contenedor: Números de referencia
   - Fecha Llegada: Cuándo llegó
   - Producto (SKU): Código del producto importado
   - Cajas: Cantidad de cajas
   - Estado: Estado actual de la importación

### Ver detalles de importación
1. Haz clic en el botón Ver en la fila
2. Se abrirá la página con detalles completos:
   - Información del proveedor
   - Números de factura y contenedor
   - Productos relacionados con cantidad de cajas
   - Fecha de registro

---

## 6. Interfaz y Elementos

### Menú lateral
- Dashboard: Panel principal
- Productos: Gestión de inventario
- Proveedores: Gestión de proveedores
- Importaciones: Registro de importaciones

### Botones
- Rojo: Acciones críticas (guardar, registrar, crear)
- Negro: Acciones secundarias
- Azul: Acciones neutras
- Amarillo: Editar
- Rojo claro: Eliminar

### Indicadores
- Stock bajo (< 10 unidades): rojo
- Stock normal: verde
- En recepción (importación): amarillo
- Procesado (importación): verde

### Alertas
- Verde: Operación exitosa
- Rojo: Error o validación fallida
- Amarillo: Advertencia

---

## 7. Búsqueda y Filtrado

### Productos
- Los productos están ordenados por código
- Tabla paginada (15 por página)
- Busca en el navegador con Ctrl+F

### Proveedores
- Ordenados por nombre
- Tabla paginada (15 por página)

### Importaciones
- Ordenadas por fecha más reciente
- Tabla paginada (15 por página)

---

## 8. Flujo Típico de Trabajo

### Scenario: Recibir importación de productos

1. Un proveedor envía mercadería
   - Tienes factura, número de contenedor, fecha de llegada

2. Registra los productos individuales (si no existen)
   - Ve a Productos → Nuevo Producto
   - Llena todos los datos
   - Selecciona la categoría correcta
   - Completa los atributos específicos

3. Registra la importación
   - Ve a Importaciones → Nueva Importación
   - Selecciona el proveedor
   - Ingresa factura, contenedor, fecha
   - Selecciona cada producto que llegó y cantidad de cajas
   - Registra la importación

4. Verifica el registro
   - Ve a Importaciones
   - Busca la importación que acabas de crear
   - Haz clic en Ver para confirmar que todo esté correcto
   - El estado debería ser "En recepción"

5. Actualiza stock (si es necesario)
   - Ve a Productos
   - Edita el producto
   - Actualiza la cantidad en stock
   - Guarda cambios

---

## 9. Validaciones y Restricciones

### Productos
- No puedes crear dos productos con el mismo código
- Código obligatorio
- Stock no puede ser negativo
- Unidades por caja debe ser mínimo 1

### Proveedores
- No puedes eliminar un proveedor que tiene importaciones
- Nombre es obligatorio

### Importaciones
- Todos los campos son obligatorios
- El proveedor debe estar activo
- El producto debe existir previamente
- Cajas debe ser mínimo 1

---

## 10. Solución de Problemas Comunes

### Error: "Todos los campos marcados como obligatorios"
Solución: Verifica que hayas llenado todos los campos del formulario

### Error: "El proveedor seleccionado no existe"
Solución: El proveedor está inactivo. Edita el proveedor y actívalo

### Error: "No se puede eliminar un proveedor"
Solución: Ese proveedor tiene importaciones. Primero elimina las importaciones

### Producto no aparece en la lista de importaciones
Solución: Primero registra el producto antes de usarlo en una importación

### Stock muestra en rojo
Solución: El stock está por debajo de 10 unidades. Considera reabastecer

---

## 11. Cerrar Sesión

1. Haz clic en tu nombre en la esquina superior derecha
2. Selecciona "Cerrar sesión"
3. Serás redirigido al login

---

## Soporte

Si encuentras problemas o tienes preguntas, verifica:
- SETUP.md - Instalación y configuración
- MIGRACION.md - Detalles técnicos
- Consulta la documentación de Laravel: https://laravel.com/docs
