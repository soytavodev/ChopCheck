<?php
// ARCHIVO: api/items.php
// DESCRIPCIÓN: Obtiene todos los productos pedidos para una mesa específica.

header('Content-Type: application/json');
require_once '../config/db_connect.php';

$mesa_id = $_GET['mesa_id'] ?? 1; // Por ahora manejamos ID 1 por defecto

$sql = "SELECT i.*, u.alias as nombre_usuario 
        FROM items i 
        LEFT JOIN usuarios u ON i.id_usuario_asignado = u.id 
        WHERE i.sesion_id = ? AND i.estado != 'PAGADO'
        ORDER BY i.id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mesa_id);
$stmt->execute();
$resultado = $stmt->get_result();

$items = [];
while ($fila = $resultado->fetch_assoc()) {
    $items[] = $fila;
}

echo json_encode($items);
$conn->close();
