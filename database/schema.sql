-- ==========================================
-- ESTRUCTURA DEFINITIVA CHOPCHECK (V1.0)
-- ==========================================

-- ATENCIÓN: Solo descomentar estas dos líneas si estás en tu PC (Localhost).
-- En InfinityFree (Producción) déjalas comentadas para evitar errores.
-- CREATE DATABASE IF NOT EXISTS chopcheck CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE chopcheck;

-- 1. LIMPIEZA TOTAL
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS item_consumos, pagos, items, participantes, mesas, articulos, admins;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. CREACIÓN DE TABLAS
CREATE TABLE mesas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(8) UNIQUE NULL,
  nombre VARCHAR(100),
  numero INT NULL,
  seccion VARCHAR(50) NOT NULL DEFAULT 'General',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  cerrado TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE participantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mesa_id INT NOT NULL,
  nombre VARCHAR(60) NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  activo TINYINT(1) DEFAULT 1,
  FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mesa_id INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  precio_centimos INT NOT NULL,
  anadido_por_participante_id INT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE,
  FOREIGN KEY (anadido_por_participante_id) REFERENCES participantes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE item_consumos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  participante_id INT NOT NULL,
  UNIQUE KEY uniq_item_part (item_id, participante_id),
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mesa_id INT NOT NULL,
  participante_id INT NOT NULL,
  pin VARCHAR(6) NOT NULL,
  total_centimos INT NOT NULL,
  estado ENUM('pendiente','pagado') NOT NULL DEFAULT 'pendiente',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  pagado_en TIMESTAMP NULL,
  FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE,
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
  INDEX idx_mesa_pin_estado (mesa_id, pin, estado),
  INDEX idx_part_estado (participante_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE articulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  precio_centimos INT NOT NULL,
  activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  activo TINYINT(1) DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 3. SEMILLAS (PLANO ESTÁTICO DE MESAS)
-- ==========================================
INSERT INTO mesas (numero, nombre, seccion, cerrado) VALUES
(1, 'Mesa 1', 'Terraza', 1), (2, 'Mesa 2', 'Terraza', 1), (3, 'Mesa 3', 'Terraza', 1), (4, 'Mesa 4', 'Terraza', 1),
(5, 'Mesa 5', 'Salón', 1), (6, 'Mesa 6', 'Salón', 1), (7, 'Mesa 7', 'Salón', 1), (8, 'Mesa 8', 'Salón', 1),
(9, 'Taburete 1', 'Barra', 1), (10, 'Taburete 2', 'Barra', 1), (11, 'Taburete 3', 'Barra', 1), (12, 'Taburete 4', 'Barra', 1);

-- ==========================================
-- 4. SEMILLAS (CATÁLOGO DE RESTAURANTE)
-- ==========================================
INSERT INTO articulos (nombre, precio_centimos, activo) VALUES
('Caña de Cerveza', 200, 1), ('Doble de Cerveza', 350, 1), ('Refresco Cola', 250, 1),
('Agua Mineral 50cl', 150, 1), ('Copa de Vino Tinto', 300, 1), ('Café Solo', 150, 1),
('Patatas Bravas', 550, 1), ('Croquetas Caseras (6 ud)', 600, 1), ('Ensaladilla Rusa', 500, 1),
('Tortilla de Patata', 450, 1), ('Calamares a la Andaluza', 750, 1), ('Tabla de Quesos', 850, 1),
('Bocadillo de Calamares', 650, 1), ('Bocadillo de Jamón Ibérico', 800, 1), ('Bocadillo de Lomo y Queso', 600, 1),
('Bocadillo de Tortilla', 500, 1), ('Bocadillo Vegetal', 550, 1),
('Montadito de Pringá', 300, 1), ('Montadito de Salmón y Queso', 350, 1), ('Montadito de Chistorra', 250, 1),
('Montadito de Solomillo', 400, 1), ('Montadito de Queso de Cabra', 300, 1);
