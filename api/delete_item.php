<?php
// ARCHIVO: api/delete_item.php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$item_id = $input['item_id'] ?? 0;

if (!$item_id) {
    echo json_encode(['success' => false, 'error' => 'Falta el ID']);
    exit;
}

// SOLO permitimos borrar si está LIBRE y NO es un trozo dividido (para evitar errores matemáticos)
// Si el usuario quiere borrar un trozo, primero debe "Reunir" (Undo) el plato original.
$sql_check = "SELECT estado, grupo_split FROM items WHERE id = $item_id";
$res = $conn->query($sql_check);
$item = $res->fetch_assoc();

if (!$item) {
    echo json_encode(['success' => false, 'error' => 'El item no existe']);
    exit;
}

if ($item['estado'] !== 'LIBRE') {
    echo json_encode(['success' => false, 'error' => 'No puedes borrar algo que está asignado. Suéltalo primero.']);
    exit;
}

if (!empty($item['grupo_split'])) {
    echo json_encode(['success' => false, 'error' => 'No puedes borrar un trozo suelto. Reúne el plato completo primero.']);
    exit;
}

// Procedemos a borrar
$sql_delete = "DELETE FROM items WHERE id = $item_id";
if ($conn->query($sql_delete)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error SQL al borrar']);
}

$conn->close();
?>
