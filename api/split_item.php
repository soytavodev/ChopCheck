<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/split_item.php
// DESCRIPCIÓN: Divide un item en N partes iguales.
// Entrada: JSON { item_id, parts }
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$itemId = isset($data['item_id']) ? (int)$data['item_id'] : 0;
$parts  = isset($data['parts']) ? (int)$data['parts'] : 0;

if ($itemId <= 0 || $parts < 2) {
    echo json_encode([
        'success' => false,
        'error'   => 'Datos inválidos para dividir.'
    ]);
    $conn->close();
    exit;
}

// 1. Obtener el producto original
$stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND estado = 'LIBRE'");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'El ítem no está libre o no existe.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$item = $res->fetch_assoc();
$stmt->close();

$precioDividido = (float)$item['precio'] / $parts;
$nombreDividido = '1/' . $parts . ' de ' . $item['nombre_producto'];
$grupoId        = uniqid('grp_');
$sesionId       = (int)$item['sesion_id'];

// 2. Transacción para borrar original e insertar partes
$conn->begin_transaction();

try {
    // Borrar original
    $stmtDel = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmtDel->bind_param('i', $itemId);
    $stmtDel->execute();
    $stmtDel->close();

    // Insertar nuevas partes
    $stmtIns = $conn->prepare(
        "INSERT INTO items (sesion_id, nombre_producto, precio, estado, grupo_split)
         VALUES (?, ?, ?, 'LIBRE', ?)"
    );

    for ($i = 0; $i < $parts; $i++) {
        $stmtIns->bind_param('isds', $sesionId, $nombreDividido, $precioDividido, $grupoId);
        $stmtIns->execute();
    }

    $stmtIns->close();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error'   => 'Error al dividir el plato.'
    ]);
}

$conn->close();
