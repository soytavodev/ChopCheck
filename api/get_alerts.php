<?php
// ARCHIVO: api/get_alerts.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// 1. Leer notificaciones pendientes
$sql = "SELECT * FROM notificaciones ORDER BY fecha ASC";
$res = $conn->query($sql);

$alertas = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $alertas[] = $row;
    }
    
    // 2. Si encontramos alertas, limpiamos la tabla para no repetirlas (Efecto Toast)
    // Solo limpiamos si hemos leído algo, para ahorrar recursos
    if (count($alertas) > 0) {
        $conn->query("TRUNCATE TABLE notificaciones"); 
    }
}

echo json_encode($alertas);
$conn->close();
?>
