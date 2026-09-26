# Sistema de Registro de Productos

Sistema web sencillo en PHP + MySQL (PDO) + Bootstrap 5 para registrar
productos de 4 categorías (Correderas, Bisagras, Pistones, Cerradura),
cada una con sus propios atributos.

## Estructura de archivos

```
sistema_productos/
│
├── index.php          → Página principal: formulario de registro + botón para ver productos
├── guardar.php         → Recibe el formulario y guarda el producto en la base de datos
├── listar.php           → Genera la tabla de productos (se carga dentro del modal)
├── database.sql         → Script para crear la base de datos y la tabla
├── config/
│   └── db.php            → Conexión a la base de datos con PDO
└── assets/
    ├── css/estilo.css     → Estilos con la paleta rojo, negro y blanco
    └── js/formulario.js   → Muestra los atributos según la categoría y carga la tabla por AJAX
```

## Cómo instalarlo en XAMPP (paso a paso)

1. **Instala XAMPP** (si no lo tienes) desde https://www.apachefriends.org
   y ábrelo. Enciende los módulos **Apache** y **MySQL** desde el panel de control.

2. **Copia la carpeta del proyecto.**
   Copia toda la carpeta `sistema_productos` dentro de la carpeta `htdocs`
   de tu instalación de XAMPP. Normalmente está en:
   - Windows: `C:\xampp\htdocs\`
   - Mac: `/Applications/XAMPP/htdocs/`

   Quedaría así: `C:\xampp\htdocs\sistema_productos\`

3. **Crea la base de datos.**
   Abre tu navegador y entra a `http://localhost/phpmyadmin`.
   - Ve a la pestaña **"Importar"**.
   - Selecciona el archivo `database.sql` de este proyecto.
   - Dale clic a **"Continuar"** / **"Importar"** al final de la página.
   - Esto creará automáticamente la base de datos `sistema_productos`
     y la tabla `productos`.

4. **Revisa los datos de conexión (opcional).**
   El archivo `config/db.php` ya está configurado con los datos por
   defecto de XAMPP (usuario `root`, sin contraseña). Si tu XAMPP tiene
   otra configuración, edita ese archivo.

5. **Abre el sistema.**
   En tu navegador entra a:
   ```
   http://localhost/sistema_productos/
   ```

6. **Úsalo:**
   - Llena el código, nombre del producto y nombre del proveedor.
   - Elige una categoría: aparecerán automáticamente los campos
     correspondientes a esa categoría.
   - Llena la descripción (opcional) y da clic en "Registrar producto".
   - Para ver todos los productos guardados, haz clic en el botón
     **"Ver productos registrados"**: se abrirá un cuadro (modal) con
     la tabla de todos los productos.

## Notas sobre el código

- **`config/db.php`**: abre la conexión a la base de datos usando PDO,
  que es una forma segura y moderna de hablar con MySQL desde PHP.
- **`index.php`**: tiene el formulario. Cada categoría tiene su propio
  bloque de campos (`div.bloque-atributos`) que normalmente está oculto
  (`style="display:none"`).
- **`assets/js/formulario.js`**: cuando cambias el `<select>` de
  categoría, este script oculta todos los bloques de atributos y
  muestra solo el que corresponde a la categoría elegida. También hace
  que esos campos sean obligatorios solo cuando están visibles.
- **`guardar.php`**: recibe los datos del formulario por `POST`, arma
  una consulta `INSERT` preparada (con PDO, para evitar inyección SQL)
  y guarda el producto.
- **`listar.php`**: consulta todos los productos (`SELECT * FROM
  productos`) y arma la tabla HTML. Este archivo se carga dentro del
  modal usando `fetch()` (AJAX) desde `formulario.js`, sin recargar la
  página completa.

## Notas sobre la base de datos

La tabla `productos` tiene una sola fila por producto, con columnas
para **todos** los atributos posibles de las 4 categorías (algunas
quedan vacías según la categoría elegida). Es un diseño simple, fácil
de entender para un proyecto de aprendizaje. Si más adelante quieres
algo más "profesional", se podría separar en varias tablas
relacionadas (una tabla por categoría), pero para este alcance no es
necesario.
