<?php
// ARCHIVO: api/send_alert.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

// Datos recibidos del JS
$mesa_id = $input['mesa_id'] ?? 1;
$usuario = $input['usuario'] ?? 'Cliente Anónimo';
$monto = $input['monto'] ?? 0.00;

// Validación básica
if (!$usuario) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

// Insertar alerta
$sql = "INSERT INTO notificaciones (mesa_id, nombre_usuario, mensaje, monto) VALUES ($mesa_id, '$usuario', 'SOLICITUD_PAGO', $monto)";

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$conn->close();
?>
