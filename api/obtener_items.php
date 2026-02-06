<?php
// ARCHIVO: api/obtener_items.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// MODIFICACIÓN: Añadimos "WHERE estado != 'PAGADO'"
// Así los items pagados desaparecen de la vista pública y del total.
$sql = "SELECT id, nombre_producto, precio, estado, id_usuario_asignado, grupo_split 
        FROM items 
        WHERE estado != 'PAGADO'";

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
