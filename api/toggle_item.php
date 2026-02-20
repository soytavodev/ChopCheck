<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/toggle_item.php
// DESCRIPCIÓN: Reclamar o liberar un item por parte de un usuario.
// Entrada: JSON { item_id, usuario_id }
// Salida: { success: true } o { success: false, error: '...' }
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

// Importante: que nada distinto a JSON se imprima (no warnings en crudo)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Los errores van al log, no a la salida

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$itemId    = isset($data['item_id']) ? (int)$data['item_id'] : 0;
$usuarioId = isset($data['usuario_id']) ? (int)$data['usuario_id'] : 0;

if ($itemId <= 0 || $usuarioId <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Datos insuficientes (item o usuario no válidos).'
    ]);
    $conn->close();
    exit;
}

// 1. Consultar estado actual del item
$stmt = $conn->prepare("SELECT estado, id_usuario_asignado FROM items WHERE id = ?");
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error'   => 'Error al preparar la consulta: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

$stmt->bind_param('i', $itemId);
$stmt->execute();
$res = $stmt->get_result();
$item = $res->fetch_assoc();
$stmt->close();

if (!$item) {
    echo json_encode([
        'success' => false,
        'error'   => 'El producto no existe.'
    ]);
    $conn->close();
    exit;
}

// 2. Decidir qué hacer según estado actual
if ($item['estado'] === 'LIBRE') {
    // Reclamar para el usuario
    $sql = "UPDATE items SET estado = 'ASIGNADO', id_usuario_asignado = ? WHERE id = ?";
    $stmtUpd = $conn->prepare($sql);
    if (!$stmtUpd) {
        echo json_encode([
            'success' => false,
            'error'   => 'Error preparando actualización: ' . $conn->error
        ]);
        $conn->close();
        exit;
    }
    $stmtUpd->bind_param('ii', $usuarioId, $itemId);

} elseif ($item['id_usuario_asignado'] == $usuarioId) {
    // El item ya es suyo → lo libera
    $sql = "UPDATE items SET estado = 'LIBRE', id_usuario_asignado = NULL WHERE id = ?";
    $stmtUpd = $conn->prepare($sql);
    if (!$stmtUpd) {
        echo json_encode([
            'success' => false,
            'error'   => 'Error preparando liberación: ' . $conn->error
        ]);
        $conn->close();
        exit;
    }
    $stmtUpd->bind_param('i', $itemId);

} else {
    // Lo tiene otro usuario, no se puede tocar
    echo json_encode([
        'success' => false,
        'error'   => 'Este producto ya ha sido reclamado por otro cliente.'
    ]);
    $conn->close();
    exit;
}

// 3. Ejecutar actualización
if ($stmtUpd->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'Error al actualizar el producto: ' . $stmtUpd->error
    ]);
}

$stmtUpd->close();
$conn->close();
