-- ==============================================================================
-- PROYECTO: ChopCheck Pro
-- ARCHIVO: database/schema.sql
-- DESCRIPCIÓN: Estructura unificada para gestión de comandas y pagos compartidos.
-- OJO: Este script BORRA y RECREA todas las tablas relacionadas.
-- ==============================================================================

-- 1. LIMPIEZA (para desarrollo; en producción úsalo con cuidado)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS carta;
DROP TABLE IF EXISTS sesiones;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. TABLA DE MESAS / SESIONES
-- Cada fila representa una mesa activa en el local.
CREATE TABLE sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_mesa VARCHAR(20) NOT NULL UNIQUE,  -- Ej: 'MESA-01', 'TERRAZA-1'
    estado ENUM('ABIERTA', 'PAGANDO', 'CERRADA') DEFAULT 'ABIERTA',
    pin_pago_mesa VARCHAR(4) DEFAULT NULL,    -- PIN usado para validar pago en caja
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. USUARIOS (CLIENTES ENLAZADOS A UNA MESA)
-- Son usuarios volátiles que se sientan en una mesa, ponen un alias y listo.
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id INT NOT NULL,
    alias VARCHAR(50) NOT NULL,
    token_recuperacion VARCHAR(100) NOT NULL, -- Para recuperar sesión tras refresh
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. CARTA (PRODUCTOS)
CREATE TABLE carta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. ITEMS / COMANDAS
-- Aquí se guardan los platos/bebidas pedidos en cada mesa.
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id INT NOT NULL,                  -- Mesa a la que pertenece el item
    nombre_producto VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    id_usuario_asignado INT DEFAULT NULL,    -- Quién ha reclamado el item
    estado ENUM('LIBRE', 'ASIGNADO', 'PAGADO') DEFAULT 'LIBRE',
    grupo_split VARCHAR(50) DEFAULT NULL,    -- Identificador para platos divididos
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario_asignado) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. DATOS DE PRUEBA (para que puedas jugar en local)
INSERT INTO sesiones (codigo_mesa) VALUES
    ('MESA-01'),
    ('MESA-02');

INSERT INTO carta (categoria, nombre, precio) VALUES
    ('Montaditos', 'Clásico (Jamón/Tomate)', 2.50),
    ('Tapas', 'Patatas Bravas', 6.00),
    ('Tapas', 'Ensaladilla Rusa', 5.50),
    ('Bebidas', 'Cerveza Turia', 2.80),
    ('Bebidas', 'Ron del Capitán', 4.50);
