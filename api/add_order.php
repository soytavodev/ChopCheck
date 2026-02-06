<?php
// ARCHIVO: api/add_order.php
// OBJETIVO: Recibir la lista del admin y guardarla en la base de datos

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// 1. Leemos los datos que envía el admin.html
$input = json_decode(file_get_contents('php://input'), true);
$mesa_id = $input['mesa_id'] ?? 1; // Por defecto a la Mesa 1
$items = $input['items'] ?? [];

// 2. Validamos
if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'La comanda está vacía']);
    exit;
}

// 3. Insertamos cada plato en la base de datos
$conn->begin_transaction();
try {
    // Preparamos la sentencia SQL una sola vez (más eficiente y seguro)
    $stmt = $conn->prepare("INSERT INTO items (sesion_id, nombre_producto, precio, estado) VALUES (?, ?, ?, 'LIBRE')");
    
    foreach ($items as $item) {
        $nombre = $item['nombre'];
        $precio = $item['precio'];
        // "isd" significa: Integer (id), String (nombre), Double (precio)
        $stmt->bind_param("isd", $mesa_id, $nombre, $precio);
        $stmt->execute();
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback(); // Si falla algo, cancelamos todo para no dejar medias comandas
    echo json_encode(['success' => false, 'error' => 'Error SQL: ' . $e->getMessage()]);
}

$conn->close();
?>
