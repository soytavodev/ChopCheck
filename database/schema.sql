-- ==============================================================================
-- PROYECTO: ChopCheck Pro 🔪🏴‍☠️
-- ARCHIVO: database/schema.sql
-- DESCRIPCIÓN: Estructura unificada para gestión de comandas y pagos.
-- ==============================================================================

-- 1. LIMPIEZA DE CUBIERTA (Borrar tablas previas para evitar conflictos)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS carta;
DROP TABLE IF EXISTS sesiones;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. LAS MESAS (Sesiones)
-- Cada mesa es una "partida" diferente en la taberna.
CREATE TABLE sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_mesa VARCHAR(20) NOT NULL UNIQUE, -- Ej: 'MESA-01'
    estado ENUM('ABIERTA', 'CERRADA') DEFAULT 'ABIERTA',
    pin_pago_mesa VARCHAR(4) DEFAULT NULL,    -- El PIN ahora vive aquí, no en un .txt
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. LA TRIPULACIÓN (Usuarios)
-- Usuarios volátiles que se unen a una mesa.
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id INT NOT NULL,
    alias VARCHAR(50) NOT NULL,
    token_recuperacion VARCHAR(100) NOT NULL, -- Para que no pierdan su cuenta al refrescar
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. EL BOTÍN (La Carta / Menú)
CREATE TABLE carta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. EL REGISTRO DE SAQUEO (Items / Comandas)
-- Aquí es donde ocurre la magia de "quién paga qué".
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id INT NOT NULL,
    nombre_producto VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    id_usuario_asignado INT DEFAULT NULL,    -- Quién reclama el botín
    estado ENUM('LIBRE', 'ASIGNADO', 'PAGADO') DEFAULT 'LIBRE',
    grupo_split VARCHAR(50) DEFAULT NULL,     -- Para platos compartidos
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario_asignado) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. CARGA INICIAL DE LA DESPENSA (Datos de prueba)
INSERT INTO sesiones (codigo_mesa) VALUES ('MESA-01'), ('MESA-02');

INSERT INTO carta (categoria, nombre, precio) VALUES 
('Montaditos', 'Clásico (Jamón/Tomate)', 2.50),
('Tapas', 'Patatas Bravas', 6.00),
('Tapas', 'Ensaladilla Rusa', 5.50),
('Bebidas', 'Cerveza Turia', 2.80),
('Bebidas', 'Ron del Capitán', 4.50);
