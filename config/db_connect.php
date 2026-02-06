<?php
// ARCHIVO: config/db_connect.php

// Errores al máximo para ver qué pasa
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$user = 'admin'; // El usuario que creamos antes
$pass = 'Hakaishin2.';       // La contraseña que pusimos
$db   = 'chopcheck_db';

// Establecemos un tiempo de espera corto (5 segundos) para que no se quede cargando siempre
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

if (!$conn->real_connect($host, $user, $pass, $db)) {
    die("ERROR DE CONEXIÓN: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
