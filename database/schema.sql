-- ==============================================================================
-- PROYECTO: ChopCheck
-- ARCHIVO: database/schema.sql
-- DESCRIPCIÓN: Script de inicialización de BBDD.
--              Incluye estructura de tablas y datos de prueba (Mock Data).
-- ==============================================================================

-- 1. LIMPIEZA DE ENTORNO
-- Borramos la base de datos si ya existe para evitar conflictos en reinicios.
DROP DATABASE IF EXISTS chopcheck_db;

-- 2. CREACIÓN DE LA BASE DE DATOS
-- Usamos utf8mb4 para soportar emojis y caracteres especiales completos.
CREATE DATABASE chopcheck_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Seleccionamos la base de datos para ejecutar las siguientes sentencias.
USE chopcheck_db;

-- ==============================================================================
-- 3. DEFINICIÓN DE TABLAS (DDL)
-- ==============================================================================

-- TABLA: sesiones
-- Representa una "Mesa" física en el restaurante.
CREATE TABLE sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,          -- Identificador único interno
    codigo_acceso VARCHAR(20) NOT NULL UNIQUE,  -- Código alfanumérico para el QR
    estado ENUM('ABIERTA', 'CERRADA') DEFAULT 'ABIERTA', -- Control de flujo
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP -- Auditoría de creación
);

-- TABLA: usuarios_temp
-- Usuarios volátiles. No requiere registro (email/pass), solo un alias.
CREATE TABLE usuarios_temp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id INT NOT NULL,                     -- Relación con la mesa
    alias VARCHAR(50) NOT NULL,                 -- Nombre visible (ej: "Juan")
    token_recuperacion VARCHAR(100) NOT NULL,   -- Token para cookie (persistencia de sesión)
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- INTEGRIDAD REFERENCIAL:
    -- Si se borra la mesa (sesion), se borran sus usuarios (CASCADE).
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id) ON DELETE CASCADE
);

-- TABLA: items
-- Representa cada producto consumido.
-- IMPORTANTE: Usamos "Atomización". No hay campo 'cantidad'.
-- Si piden 2 cervezas, se insertan 2 filas. Esto facilita dividir cuentas.
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id INT NOT NULL,                     -- Relación con la mesa
    nombre_producto VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,              -- Formato monetario estándar
    
    id_usuario_asignado INT DEFAULT NULL,       -- Quién paga esto (NULL = nadie)
    estado ENUM('LIBRE', 'ASIGNADO', 'PAGADO') DEFAULT 'LIBRE',
    
    -- INTEGRIDAD REFERENCIAL:
    -- Si se borra la mesa, adiós a los items.
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id) ON DELETE CASCADE,
    
    -- Si un usuario se va (borrado), el ítem vuelve a quedar libre (SET NULL)
    -- en lugar de borrarse el producto.
    FOREIGN KEY (id_usuario_asignado) REFERENCES usuarios_temp(id) ON DELETE SET NULL
);

-- ==============================================================================
-- 4. CARGA DE DATOS DE PRUEBA (DML)
-- ==============================================================================

-- Creamos una Mesa de prueba
INSERT INTO sesiones (codigo_acceso) VALUES ('MESA-TEST-1');

-- Insertamos usuarios ficticios
INSERT INTO usuarios_temp (sesion_id, alias, token_recuperacion) VALUES 
(1, 'Gustavo', 'tok_gus_123'), -- Usuario 1
(1, 'Ana', 'tok_ana_456');     -- Usuario 2

-- Insertamos productos (Comanda de ejemplo)
-- Observar la atomización: Las cervezas van por separado.
INSERT INTO items (sesion_id, nombre_producto, precio) VALUES 
(1, 'Cerveza Turia', 3.50),     -- Item 1
(1, 'Cerveza Turia', 3.50),     -- Item 2
(1, 'Patatas Bravas', 6.00),    -- Item 3
(1, 'Pizza Margarita', 12.00);  -- Item 4

-- Simulamos una interacción: Gustavo reclama la primera cerveza.
-- Actualizamos el estado a 'ASIGNADO' y vinculamos al ID 1 (Gustavo).
UPDATE items 
SET id_usuario_asignado = 1, estado = 'ASIGNADO' 
WHERE id = 1;

-- =================================================================================

-- 1. Crear usuario nuevo
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'Hakaishin2.';

-- 2. Darle permiso TOTAL sobre la base de datos chopcheck
GRANT ALL PRIVILEGES ON chopcheck_db.* TO 'admin'@'localhost';

-- 3. Guardar cambios
FLUSH PRIVILEGES;
EXIT;
