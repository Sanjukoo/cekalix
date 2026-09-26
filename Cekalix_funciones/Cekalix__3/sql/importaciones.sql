-- =====================================================
-- Módulo de IMPORTACIONES
-- Se crea dentro de la misma base de datos: sistema_productos
-- para poder relacionarse con tu tabla `productos`.
-- Ejecuta este archivo en phpMyAdmin (pestaña "Importar")
-- DESPUÉS de haber ejecutado tu database.sql de productos.
-- =====================================================

CREATE DATABASE IF NOT EXISTS sistema_productos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE sistema_productos;

-- -----------------------------------------------------
-- Tu tabla de productos (no se modifica si ya existe)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    proveedor VARCHAR(100) NOT NULL,
    categoria VARCHAR(20) NOT NULL,
    longitud VARCHAR(50)  DEFAULT NULL,
    espesor  VARCHAR(50)  DEFAULT NULL,
    ancho    VARCHAR(50)  DEFAULT NULL,
    color    VARCHAR(50)  DEFAULT NULL,
    tipo     VARCHAR(50)  DEFAULT NULL,
    acabado  VARCHAR(50)  DEFAULT NULL,
    peso     VARCHAR(50)  DEFAULT NULL,
    fuerza   VARCHAR(50)  DEFAULT NULL,
    material VARCHAR(50)  DEFAULT NULL,
    tamano   VARCHAR(50)  DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Proveedores de importación
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS proveedores (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nombre  VARCHAR(150) NOT NULL,
    activo  TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Cabecera de la importación
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS importaciones (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id    INT          NOT NULL,
    num_factura     VARCHAR(50)  NOT NULL,
    num_contenedor  VARCHAR(50)  NOT NULL,
    fecha_llegada   DATE         NOT NULL,
    estado          VARCHAR(30)  NOT NULL DEFAULT 'En recepción',
    fecha_registro  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_importacion_proveedor
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Detalle de la importación
-- Relacionado con importaciones (cabecera) y con productos
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS importacion_detalle (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    importacion_id    INT NOT NULL,
    producto_id       INT NOT NULL,
    cajas_facturadas  INT NOT NULL,
    CONSTRAINT chk_cajas_positivas CHECK (cajas_facturadas > 0),
    CONSTRAINT fk_detalle_importacion
        FOREIGN KEY (importacion_id) REFERENCES importaciones(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto
        FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Datos iniciales de proveedores
-- (los productos ya los tienes en tu tabla)
-- -----------------------------------------------------
INSERT INTO proveedores (id, nombre) VALUES
    (1, 'Proveedor Internacional A.'),
    (2, 'Global Trade Corp.'),
    (3, 'Importadora del Pacífico')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
