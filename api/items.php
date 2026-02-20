<?php
// ARCHIVO: api/items.php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 1;

// La clave es el LEFT JOIN para traer el alias de la tabla usuarios
$sql = "SELECT i.*, u.alias as nombre_usuario 
        FROM items i 
        LEFT JOIN usuarios u ON i.id_usuario_asignado = u.id 
        WHERE i.sesion_id = ? 
        ORDER BY i.id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mesa_id);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($fila = $res->fetch_assoc()) {
    $items[] = $fila;
}

echo json_encode($items);
$conn->close();
