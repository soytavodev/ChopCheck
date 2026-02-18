<?php
// ARCHIVO: api/toggle_item.php
// DESCRIPCIÓN: Asigna o libera un producto para un usuario.

header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$item_id = $input['item_id'] ?? null;
$usuario_id = $input['usuario_id'] ?? null;

if (!$item_id || !$usuario_id) {
    echo json_encode(['success' => false, 'error' => 'Datos insuficientes.']);
    exit;
}

// 1. Consultamos el estado actual del ítem
$stmt = $conn->prepare("SELECT estado, id_usuario_asignado FROM items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$res = $stmt->get_result();
$item = $res->fetch_assoc();

if (!$item) {
    echo json_encode(['success' => false, 'error' => 'El producto no existe.']);
    exit;
}

// 2. Lógica de cambio de estado
if ($item['estado'] === 'LIBRE') {
    // Si está libre, lo reclamamos para nosotros
    $sql = "UPDATE items SET estado = 'ASIGNADO', id_usuario_asignado = ? WHERE id = ?";
    $stmt_upd = $conn->prepare($sql);
    $stmt_upd->bind_param("ii", $usuario_id, $item_id);
} elseif ($item['id_usuario_asignado'] == $usuario_id) {
    // Si ya es nuestro, lo liberamos
    $sql = "UPDATE items SET estado = 'LIBRE', id_usuario_asignado = NULL WHERE id = ?";
    $stmt_upd = $conn->prepare($sql);
    $stmt_upd->bind_param("i", $item_id);
} else {
    // Si es de otro usuario, no hacemos nada
    echo json_encode(['success' => false, 'error' => 'Este producto ya ha sido reclamado por otro cliente.']);
    exit;
}

if ($stmt_upd->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al actualizar el registro.']);
}

$stmt->close();
$conn->close();
