-- Base de datos ChopCheck (MVP con Admin)
CREATE DATABASE IF NOT EXISTS chopcheck CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chopcheck;

CREATE TABLE IF NOT EXISTS mesas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(8) NOT NULL UNIQUE,
  nombre VARCHAR(100),
  numero INT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  cerrado TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS participantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mesa_id INT NOT NULL,
  nombre VARCHAR(60) NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  activo TINYINT(1) DEFAULT 1,
  FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mesa_id INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  precio_centimos INT NOT NULL,
  anadido_por_participante_id INT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE,
  FOREIGN KEY (anadido_por_participante_id) REFERENCES participantes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS item_consumos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  participante_id INT NOT NULL,
  UNIQUE KEY uniq_item_part (item_id, participante_id),
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pagos (
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

-- Catálogo de artículos para la vista admin
CREATE TABLE IF NOT EXISTS articulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  precio_centimos INT NOT NULL,
  activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Semillas básicas (ajusta precios a tu realidad)
INSERT INTO articulos (nombre, precio_centimos) VALUES
 ('Cerveza caña', 250),
 ('Cerveza tercio', 300),
 ('Agua', 200),
 ('Refresco', 250),
 ('Vino copa', 350),
 ('Café solo', 150),
 ('Café con leche', 180),
 ('Té', 170),
 ('Patatas bravas', 800),
 ('Ensaladilla rusa', 700),
 ('Tortilla pincho', 350)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);


-- 1) Crear la columna 'numero' en 'mesas' (para instalaciones previas)
ALTER TABLE mesas ADD COLUMN numero INT NULL AFTER nombre;

-- 2) Rellenar valores por primera vez (asignar Mesa 1, Mesa 2... según orden de creación)
SET @n := 0;
UPDATE mesas SET numero = (@n := @n + 1) ORDER BY id;

-- 3) (Recomendado) Crear catálogo de artículos si no existe y sembrar básicos
CREATE TABLE IF NOT EXISTS articulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  precio_centimos INT NOT NULL,
  activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO articulos (id, nombre, precio_centimos, activo) VALUES
 (1, 'Cerveza caña', 250, 1),
 (2, 'Cerveza tercio', 300, 1),
 (3, 'Agua', 200, 1),
 (4, 'Refresco', 250, 1),
 (5, 'Vino copa', 350, 1),
 (6, 'Café solo', 150, 1),
 (7, 'Café con leche', 180, 1),
 (8, 'Té', 170, 1),
 (9, 'Patatas bravas', 800, 1),
 (10,'Ensaladilla rusa', 700, 1),
 (11,'Tortilla pincho', 350, 1);


CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  activo TINYINT(1) DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

