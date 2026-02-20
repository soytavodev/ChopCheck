<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/request_payment.php
// DESCRIPCIÓN: El cliente avisa que quiere pagar en caja.
// Entrada: JSON { mesa_id }
// Acción: Pone la mesa en estado 'PAGANDO' y genera un PIN de 4 dígitos.
// Salida: { success: true } o error.
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$mesaId = isset($data['mesa_id']) ? (int)$data['mesa_id'] : 0;

if ($mesaId <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'ID de mesa no proporcionado o inválido.'
    ]);
    $conn->close();
    exit;
}

// Generar PIN de 4 dígitos (ej: 0427)
$pin = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

$stmt = $conn->prepare(
    "UPDATE sesiones 
     SET estado = 'PAGANDO', pin_pago_mesa = ? 
     WHERE id = ? AND estado <> 'CERRADA'"
);
$stmt->bind_param('si', $pin, $mesaId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode([
            'success' => false,
            'error'   => 'No se pudo actualizar la mesa (¿quizás ya está cerrada?).'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'Error al marcar la mesa como PAGANDO.'
    ]);
}

$stmt->close();
$conn->close();
