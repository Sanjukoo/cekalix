-- =====================================================
-- Base de datos: sistema_productos
-- Ejecuta este archivo en phpMyAdmin (pestaña "Importar")
-- o desde la consola de MySQL de XAMPP
-- =====================================================

CREATE DATABASE IF NOT EXISTS sistema_productos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE sistema_productos;

CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    proveedor VARCHAR(100) NOT NULL,
    stock INT UNSIGNED NOT NULL,
    unidad_caja INT UNSIGNED NOT NULL,
    categoria VARCHAR(20) NOT NULL,   -- correderas, bisagras, pistones, cerraduras

    -- Atributos de CORREDERAS
    longitud VARCHAR(50)  DEFAULT NULL,
    espesor  VARCHAR(50)  DEFAULT NULL,
    ancho    VARCHAR(50)  DEFAULT NULL,
    color    VARCHAR(50)  DEFAULT NULL,

    -- Atributos de BISAGRAS (tipo y acabado también se usan en PISTONES)
    tipo     VARCHAR(50)  DEFAULT NULL,
    acabado  VARCHAR(50)  DEFAULT NULL,
    peso     VARCHAR(50)  DEFAULT NULL,

    -- Atributos de PISTONES (longitud y acabado se reutilizan)
    fuerza   VARCHAR(50)  DEFAULT NULL,

    -- Atributos de CERRADURA
    material VARCHAR(50)  DEFAULT NULL,
    tamano   VARCHAR(50)  DEFAULT NULL,

    -- Común a todas las categorías
    descripcion TEXT DEFAULT NULL,

    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
