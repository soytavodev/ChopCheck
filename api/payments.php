<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/payments.php
// DESCRIPCIÓN: Validación de PIN y marcado de items como pagados.
// Entrada: JSON { mesa_id, usuario_id, pin }
// Acción: Verifica PIN y pone en 'PAGADO' los items del usuario en esa mesa.
// Salida: { success: true } o error.
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$mesaId    = isset($data['mesa_id']) ? (int)$data['mesa_id'] : 0;
$usuarioId = isset($data['usuario_id']) ? (int)$data['usuario_id'] : 0;
$pin       = isset($data['pin']) ? trim($data['pin']) : '';

if ($mesaId <= 0 || $usuarioId <= 0 || $pin === '') {
    echo json_encode([
        'success' => false,
        'error'   => 'Datos incompletos para procesar el pago.'
    ]);
    $conn->close();
    exit;
}

// 1. Verificar que la mesa está en PAGANDO y que el PIN coincide
$stmt = $conn->prepare(
    "SELECT id 
     FROM sesiones 
     WHERE id = ? AND estado = 'PAGANDO' AND pin_pago_mesa = ?"
);
$stmt->bind_param('is', $mesaId, $pin);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'PIN incorrecto o mesa no está en estado PAGANDO.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

// 2. Marcar como PAGADO los items asignados a este usuario en esa mesa
$upd = $conn->prepare(
    "UPDATE items 
     SET estado = 'PAGADO' 
     WHERE sesion_id = ? AND id_usuario_asignado = ? AND estado = 'ASIGNADO'"
);
$upd->bind_param('ii', $mesaId, $usuarioId);

if ($upd->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'Error al marcar los items como pagados.'
    ]);
}

$upd->close();
$conn->close();
