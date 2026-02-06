<?php
// ARCHIVO: api/split_item.php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$item_id = $input['item_id'] ?? 0;
$parts = intval($input['parts'] ?? 0);

if (!$item_id || $parts < 2) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

// 1. Obtener producto original
$sql = "SELECT * FROM items WHERE id = $item_id AND estado = 'LIBRE'";
$res = $conn->query($sql);

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Ítem no disponible para dividir']);
    exit;
}

$item = $res->fetch_assoc();
$precio_original = floatval($item['precio']);
$nombre_original = $item['nombre_producto'];
$sesion_id = $item['sesion_id'];

// 2. Calcular matemáticas
$nuevo_precio = $precio_original / $parts;
$nuevo_nombre = "1/$parts de " . $nombre_original;
$grupo_split = uniqid('split_'); // ID único para este grupo

// 3. Transacción Atómica
$conn->begin_transaction();

try {
    // A. Borrar el padre (Guardamos su ID por si acaso, pero lo eliminamos)
    $conn->query("DELETE FROM items WHERE id = $item_id");

    // B. Crear los hijos
    $stmt = $conn->prepare("INSERT INTO items (sesion_id, nombre_producto, precio, estado, grupo_split) VALUES (?, ?, ?, 'LIBRE', ?)");
    
    for ($i = 0; $i < $parts; $i++) {
        $stmt->bind_param("isds", $sesion_id, $nuevo_nombre, $nuevo_precio, $grupo_split);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
