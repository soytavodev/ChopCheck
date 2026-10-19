<?php
// database/crear_admin.php
// Crea un usuario para el panel del camarero. Solo se puede ejecutar desde la terminal:
//   php database/crear_admin.php usuario contraseña

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta desde la terminal.');
}

if ($argc !== 3) {
    fwrite(STDERR, "Uso: php database/crear_admin.php usuario contraseña\n");
    exit(1);
}

require_once __DIR__ . '/../src/models/Admin.php';

[, $usuario, $password] = $argv;

try {
    Admin::crearSetup($usuario, $password);
    echo "Usuario '$usuario' creado.\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'No se pudo crear el usuario: ' . $e->getMessage() . "\n");
    exit(1);
}
