<?php
// ARCHIVO: api/pay_items.php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Usuario no identificado']);
    exit;
}

// Marcamos como PAGADO todo lo que este usuario tenía ASIGNADO
// IMPORTANTE: No borramos (DELETE), solo cambiamos estado para mantener historial.
$sql = "UPDATE items SET estado = 'PAGADO' WHERE id_usuario_asignado = $user_id AND estado = 'ASIGNADO'";

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al procesar el pago']);
}

$conn->close();
?>
