<?php
// ARCHIVO: config/db_connect.php
// OBJETIVO: Conectarse automáticamente según dónde estemos (Casa o Nube)

// 1. DETECTAR ENTORNO
// Si el servidor se llama 'localhost', estamos en tu PC.
$es_local = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1');

if ($es_local) {
    // === TUS DATOS DE XAMPP (LOCAL) ===
    $host = 'localhost';
    $user = 'root';      // Por defecto en XAMPP es root
    $pass = '';          // Por defecto en XAMPP es vacío
    $db   = 'chopcheck_db'; // Asegúrate de crear esta BD en tu phpMyAdmin local
    
    // Configuración extra para ver errores en tu PC
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

} else {
    // === DATOS DE INFINITYFREE (NUBE) ===
    // Ya configurados con los datos que me diste antes
    $host = 'sql105.infinityfree.com';
    $user = 'if0_41089836';
    $pass = 'wj7VujSjm6Ecf';
    $db   = 'if0_41089836_chopcheck';
    
    // En producción ocultamos errores feos al usuario
    ini_set('display_errors', 0);
    error_reporting(0);
}

// 2. CONECTAR
$conn = new mysqli($host, $user, $pass, $db);

// 3. VERIFICAR
if ($conn->connect_error) {
    // Si falla, devolvemos un JSON de error y matamos el proceso
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Error DB: ' . $conn->connect_error]));
}

// 4. JUEGO DE CARACTERES (Para tildes y ñ)
$conn->set_charset("utf8mb4");

?>
