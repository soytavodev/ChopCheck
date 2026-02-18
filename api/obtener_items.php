<?php
// ARCHIVO: api/obtener_items.php
// CORRECCIÓN: Ahora incluye 'grupo_split' para que funcione la unión
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/db_connect.php';

$mesa_id = $_GET['mesa_id'] ?? 1;

// AGREGADO: items.grupo_split en la consulta
$sql = "SELECT items.id, items.nombre_producto, items.precio, items.estado, 
               items.id_usuario_asignado, items.sesion_id, items.grupo_split,
               usuarios.alias as nombre_usuario 
        FROM items 
        LEFT JOIN usuarios ON items.id_usuario_asignado = usuarios.id 
        WHERE items.sesion_id = $mesa_id AND items.estado != 'PAGADO'
        ORDER BY items.id ASC";

$resultado = $conn->query($sql);

$items = [];
while ($fila = $resultado->fetch_assoc()) {
    $fila['id'] = intval($fila['id']);
    $fila['precio'] = floatval($fila['precio']);
    $fila['id_usuario_asignado'] = $fila['id_usuario_asignado'] ? intval($fila['id_usuario_asignado']) : null;
    $items[] = $fila;
}

echo json_encode($items);
$conn->close();
?>
