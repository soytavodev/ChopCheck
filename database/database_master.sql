-- ARCHIVO: database/database_master.sql
-- PROYECTO: ChopCheck
-- VERSIÓN: 2.1 (Con Fuerza Bruta para limpieza)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- === ⚠️ EL TRUCO MAESTRO ===
-- Desactivamos la vigilancia de claves foráneas. 
-- Esto permite borrar tablas aunque estén conectadas entre sí.
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- 1. LIMPIEZA TOTAL
-- ==========================================
-- Borramos TODAS las tablas posibles que hayamos creado alguna vez
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS carta;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS sesiones;
DROP TABLE IF EXISTS usuarios_temp; 

-- ==========================================
-- 2. ESTRUCTURA (TABLAS LIMPIAS)
-- ==========================================

-- Tabla 1: Usuarios (Clientes)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alias VARCHAR(50) NOT NULL,
    fecha_entrada DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla 2: Carta (Menú)
CREATE TABLE carta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla 3: Items (Pedidos)
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id INT NOT NULL DEFAULT 1, 
    nombre_producto VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    estado ENUM('LIBRE', 'ASIGNADO', 'PAGADO') DEFAULT 'LIBRE',
    id_usuario_asignado INT DEFAULT NULL,
    grupo_split VARCHAR(50) DEFAULT NULL,
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario FOREIGN KEY (id_usuario_asignado) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla 4: Notificaciones (Avisos)
CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mesa_id INT NOT NULL,
    nombre_usuario VARCHAR(50) NOT NULL,
    mensaje VARCHAR(255) NOT NULL,
    monto DECIMAL(10,2) DEFAULT 0.00,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 3. DATOS INICIALES (MENÚ)
-- ==========================================

INSERT INTO carta (categoria, nombre, precio) VALUES 
('Montaditos', 'Clásico (Jamón/Tomate)', 2.50),
('Montaditos', 'Sobrasada y Queso', 2.80),
('Montaditos', 'Tortilla, Pimiento, Brie', 3.50),
('Tapas', 'Ensaladilla Rusa', 5.50),
('Tapas', 'Patatas Bravas', 6.00),
('Tapas', 'Patatas Fritas', 4.00),
('Tapas', 'Gambas al Ajillo', 10.50),
('Tapas', 'Choricitos a la Sidra', 7.00),
('Tapas', 'Boquerones en Vinagre', 6.50),
('Tapas', 'Tabla Jamón y Queso', 14.00),
('Bebidas', 'Cerveza Turia', 2.80),
('Bebidas', 'Coca-Cola', 2.50),
('Bebidas', 'Agua', 1.50),
('Bebidas', 'Copa de Vino', 3.50);

-- === REACTIVAMOS SEGURIDAD ===
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- 1. Borramos si existía alguno previo para evitar conflictos
DROP USER IF EXISTS 'admin'@'localhost';

-- 2. Creamos el usuario con la contraseña EXACTA (incluyendo el punto)
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'Hakaishin2.';

-- 3. Le damos poder absoluto sobre la base de datos de ChopCheck
GRANT ALL PRIVILEGES ON chopcheck_db.* TO 'admin'@'localhost';

-- 4. Guardamos y salimos
FLUSH PRIVILEGES;
EXIT;
