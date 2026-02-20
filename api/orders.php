<?php
// ARCHIVO: api/orders.php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$mesa_id = $input['mesa_id'] ?? null;
$nombre = $input['nombre'] ?? '';
$precio = $input['precio'] ?? 0;

if (!$mesa_id || empty($nombre)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO items (sesion_id, nombre_producto, precio, estado) VALUES (?, ?, ?, 'LIBRE')");
$stmt->bind_param("isd", $mesa_id, $nombre, $precio);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se pudo añadir el plato.']);
}

$conn->close();
