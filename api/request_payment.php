<?php
// ARCHIVO: api/request_payment.php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
// Usamos el ID de la mesa que envía el JS
$mesa_id = isset($input['mesa_id']) ? intval($input['mesa_id']) : 1;

// Actualizamos el estado de la mesa a 'PAGANDO'
$stmt = $conn->prepare("UPDATE sesiones SET estado = 'PAGANDO' WHERE id = ?");
$stmt->bind_param("i", $mesa_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    // Si falla, enviamos el error real para verlo en la consola
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
$conn->close();
