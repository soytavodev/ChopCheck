<?php
// ARCHIVO: api/split_item.php
// DESCRIPCIÓN: Divide un ítem en N partes proporcionales.

header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$item_id = $input['item_id'] ?? 0;
$parts = intval($input['parts'] ?? 0);

if (!$item_id || $parts < 2) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos para dividir.']);
    exit;
}

// 1. Obtener el producto original
$stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND estado = 'LIBRE'");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'El ítem no está libre o no existe.']);
    exit;
}

$item = $res->fetch_assoc();
$precio_dividido = floatval($item['precio']) / $parts;
$nombre_dividido = "1/{$parts} de " . $item['nombre_producto'];
$grupo_id = uniqid('grp_'); // Identificador único para poder "reunirlos" luego
$sesion_id = $item['sesion_id'];

// 2. Transacción para asegurar que no se pierdan datos
$conn->begin_transaction();

try {
    // Borramos el original
    $conn->query("DELETE FROM items WHERE id = $item_id");

    // Insertamos las nuevas partes
    $stmt_ins = $conn->prepare("INSERT INTO items (sesion_id, nombre_producto, precio, estado, grupo_split) VALUES (?, ?, ?, 'LIBRE', ?)");
    
    for ($i = 0; $i < $parts; $i++) {
        $stmt_ins->bind_param("isds", $sesion_id, $nombre_dividido, $precio_dividido, $grupo_id);
        $stmt_ins->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
