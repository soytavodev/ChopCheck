<?php
// ARCHIVO: api/get_table_detail.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// Recibimos el ID de la mesa (si no llega nada, asumimos mesa 1)
$mesa_id = $_GET['mesa_id'] ?? 1;

// Seleccionamos los items que NO están pagados
// Hacemos JOIN con usuarios para saber quién pidió (opcional, pero útil)
$sql = "SELECT items.id, items.nombre_producto, items.precio, items.estado, usuarios.alias as nombre_usuario 
        FROM items 
        LEFT JOIN usuarios ON items.id_usuario_asignado = usuarios.id 
        WHERE items.sesion_id = $mesa_id AND items.estado != 'PAGADO'";

$resultado = $conn->query($sql);

$items = [];
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $items[] = $fila;
    }
}

echo json_encode($items);
$conn->close();
?>
