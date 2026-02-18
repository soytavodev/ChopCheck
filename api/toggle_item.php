<?php
// ARCHIVO: api/toggle_item.php
// VERSIÓN: FINAL & DIAGNÓSTICO
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// Leer entrada JSON
$input = json_decode(file_get_contents('php://input'), true);

// Aceptamos variantes para que no falle si el JS cambia
$item_id = $input['item_id'] ?? $input['id'] ?? null;
$usuario_id = $input['usuario_id'] ?? $input['user_id'] ?? null;
$accion = $input['accion'] ?? $input['action'] ?? null;

// Validación detallada (para que sepas qué pasa)
if (!$item_id || !$usuario_id || !$accion) {
    echo json_encode([
        'success' => false, 
        'error' => "Faltan datos. Recibido -> Item: " . ($item_id ?? 'NULL') . ", User: " . ($usuario_id ?? 'NULL') . ", Accion: " . ($accion ?? 'NULL')
    ]);
    exit;
}

// Lógica de Asignar/Liberar
if ($accion === 'asignar') {
    $sql = "UPDATE items SET estado = 'ASIGNADO', id_usuario_asignado = ? WHERE id = ? AND estado = 'LIBRE'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $usuario_id, $item_id);
} else {
    // Solo permitimos liberar si eres el dueño
    $sql = "UPDATE items SET estado = 'LIBRE', id_usuario_asignado = NULL WHERE id = ? AND id_usuario_asignado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $item_id, $usuario_id);
}

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo modificar (quizás ya lo tomó otro)']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Error SQL: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
