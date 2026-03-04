<?php
function db() {
    static $pdo;
    if ($pdo) return $pdo;

    $host = '127.0.0.1';
    $db   = 'tu_base_de_datos_aqui';
    $user = 'tu_usuario_aqui';
    $pass = 'tu_contraseña_aqui.';
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    return $pdo;
}
