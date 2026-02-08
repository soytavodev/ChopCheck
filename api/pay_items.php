<?php
// ARCHIVO: api/pay_items.php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? 0;
$pin_introducido = $input['pin'] ?? '';

// === CONFIGURACIÓN ===
$PIN_MAESTRO = "8888"; // El código que el camarero le dice al cliente
// =====================

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Usuario no identificado']);
    exit;
}

// VALIDACIÓN DEL PIN
if ($pin_introducido !== $PIN_MAESTRO) {
    echo json_encode(['success' => false, 'error' => 'PIN incorrecto. Pide el código en caja.']);
    exit;
}

// Si el PIN es correcto, procedemos al pago
$sql = "UPDATE items SET estado = 'PAGADO' WHERE id_usuario_asignado = $user_id AND estado = 'ASIGNADO'";

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al procesar el pago']);
}

$conn->close();
?>
