-- Migraciones para instalaciones existentes
USE chopcheck;

-- Añadir columna numero a mesas si no existe (MySQL 8+)
ALTER TABLE mesas ADD COLUMN IF NOT EXISTS numero INT NULL;

-- Crear tabla articulos si no existe
CREATE TABLE IF NOT EXISTS articulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  precio_centimos INT NOT NULL,
  activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Semillas básicas (solo si no existen)
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
 (10, 'Ensaladilla rusa', 700, 1),
 (11, 'Tortilla pincho', 350, 1);
