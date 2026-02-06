<?php
// ARCHIVO: api/undo_split.php
// OBJETIVO: Revertir una división (Unir trozos)

header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$item_id = $input['item_id'] ?? 0;

// 1. Averiguar a qué grupo pertenece este trozo
$sql_info = "SELECT grupo_split, nombre_producto, sesion_id FROM items WHERE id = $item_id";
$res = $conn->query($sql_info);
$row = $res->fetch_assoc();

if (!$row || empty($row['grupo_split'])) {
    echo json_encode(['success' => false, 'error' => 'Este ítem no se puede unir.']);
    exit;
}

$grupo = $row['grupo_split'];
$sesion_id = $row['sesion_id'];

// 2. Verificar que TODOS los trozos estén LIBRES (No puedes unir si alguien ya pagó un trozo)
$sql_check = "SELECT COUNT(*) as ocupados FROM items WHERE grupo_split = '$grupo' AND estado != 'LIBRE'";
$check = $conn->query($sql_check)->fetch_assoc();

if ($check['ocupados'] > 0) {
    echo json_encode(['success' => false, 'error' => 'No se puede unir: alguien ya ha reclamado una parte.']);
    exit;
}

// 3. Recalcular precio total y nombre original
// Sumamos los precios de todos los miembros del grupo
$sql_sum = "SELECT SUM(precio) as total, COUNT(*) as partes FROM items WHERE grupo_split = '$grupo'";
$sum_data = $conn->query($sql_sum)->fetch_assoc();
$precio_total = $sum_data['total'];

// Limpiamos el nombre (quitamos "1/X de ")
// Regex simple: quitamos todo hasta el primer " de "
$nombre_sucio = $row['nombre_producto']; 
$partes = explode(' de ', $nombre_sucio, 2);
$nombre_original = (count($partes) > 1) ? $partes[1] : $nombre_sucio;

// 4. Ejecutar Fusión
$conn->begin_transaction();
try {
    // Borrar todos los trozos
    $conn->query("DELETE FROM items WHERE grupo_split = '$grupo'");

    // Insertar el ítem original renacido
    $stmt = $conn->prepare("INSERT INTO items (sesion_id, nombre_producto, precio, estado, grupo_split) VALUES (?, ?, ?, 'LIBRE', NULL)");
    $stmt->bind_param("isd", $sesion_id, $nombre_original, $precio_total);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
