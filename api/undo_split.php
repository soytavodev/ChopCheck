<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/undo_split.php
// DESCRIPCIÓN: Reúne trozos divididos. Versión compatible con ONLY_FULL_GROUP_BY.
// ==============================================================================

header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$grupo_id = isset($input['grupo_id']) ? $input['grupo_id'] : '';

if (empty($grupo_id)) {
    echo json_encode(['success' => false, 'error' => 'ID de grupo no recibido.']);
    exit;
}

// 1. Verificamos si alguna parte ya está ocupada
$sql_check = "SELECT COUNT(*) as ocupados FROM items WHERE grupo_split = '$grupo_id' AND estado != 'LIBRE'";
$res_check = $conn->query($sql_check);
$check_data = $res_check->fetch_assoc();

if ($check_data['ocupados'] > 0) {
    echo json_encode(['success' => false, 'error' => 'No puedes unir el plato; alguien ya ha reclamado una parte.']);
    exit;
}

// 2. Obtenemos la información usando funciones de agregación (MAX) para cumplir con ONLY_FULL_GROUP_BY
$sql_info = "SELECT MAX(nombre_producto) as nombre_producto, SUM(precio) as total, MAX(sesion_id) as sesion_id 
             FROM items 
             WHERE grupo_split = '$grupo_id' 
             GROUP BY grupo_split";

$res_info = $conn->query($sql_info);
$info = $res_info ? $res_info->fetch_assoc() : null;

if (!$info) {
    echo json_encode(['success' => false, 'error' => 'No se encontraron las partes del grupo.']);
    exit;
}

// Limpiamos el nombre: Quitamos el prefijo de fracción (ej: "1/2 de Papas" -> "Papas")
$nombre_original = preg_replace('/^\d+\/\d+ de\s+/i', '', $info['nombre_producto']);

// 3. PROCESO ATÓMICO
$conn->begin_transaction();

try {
    // Borramos los trozos
    $conn->query("DELETE FROM items WHERE grupo_split = '$grupo_id'");
    
    // Insertamos el original
    $sesion = $info['sesion_id'];
    $precio = $info['total'];
    $stmt_ins = $conn->prepare("INSERT INTO items (sesion_id, nombre_producto, precio, estado, grupo_split) VALUES (?, ?, ?, 'LIBRE', NULL)");
    $stmt_ins->bind_param("isd", $sesion, $nombre_original, $precio);
    $stmt_ins->execute();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Error crítico en la base de datos.']);
}

$conn->close();
