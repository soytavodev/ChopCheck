<?php
// ARCHIVO: api/toggle_item.php
// OBJETIVO: Asignar o desasignar un producto a un usuario (Toggle)

header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$item_id = $input['item_id'] ?? 0;
$user_id = $input['user_id'] ?? 0;

if (!$item_id || !$user_id) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// 1. Consultamos el estado actual del item
$sql_check = "SELECT estado, id_usuario_asignado FROM items WHERE id = $item_id";
$res = $conn->query($sql_check);
$item = $res->fetch_assoc();

if (!$item) {
    echo json_encode(['success' => false, 'error' => 'Item no encontrado']);
    exit;
}

// 2. Lógica del TOGGLE (Interruptor)
if ($item['estado'] === 'LIBRE') {
    // A) Si está libre -> Lo asignamos al usuario
    $sql_update = "UPDATE items SET estado = 'ASIGNADO', id_usuario_asignado = $user_id WHERE id = $item_id";

} elseif ($item['estado'] === 'ASIGNADO' && $item['id_usuario_asignado'] == $user_id) {
    // B) Si ya es mío -> Lo libero (me he arrepentido)
    $sql_update = "UPDATE items SET estado = 'LIBRE', id_usuario_asignado = NULL WHERE id = $item_id";

} else {
    // C) Si es de otro -> No hago nada (Error o bloqueo)
    echo json_encode(['success' => false, 'error' => 'Este producto ya lo tiene otro']);
    exit;
}

// 3. Ejecutamos el cambio
if ($conn->query($sql_update)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error SQL']);
}

$conn->close();
?>
