<?php
// ARCHIVO: api/get_table_detail.php
// VERSIÓN SIMPLIFICADA (SAFE MODE)
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$mesa_id = $_GET['mesa_id'] ?? 1;

// SOLUCIÓN: Quitamos el JOIN temporalmente. Solo leemos la tabla items que sabemos que funciona.
// Así descartamos que el error venga de la tabla usuarios.
$sql = "SELECT id, nombre_producto, precio, estado 
        FROM items 
        WHERE sesion_id = $mesa_id AND estado != 'PAGADO'";

$resultado = $conn->query($sql);

if (!$resultado) {
    // Si falla la consulta, devolvemos el error exacto de SQL para que lo veas
    echo json_encode(['error' => 'Error SQL: ' . $conn->error]);
    exit;
}

$items = [];
while ($fila = $resultado->fetch_assoc()) {
    // Añadimos un nombre genérico si no hacemos el JOIN
    $fila['nombre_usuario'] = ($fila['estado'] === 'ASIGNADO') ? 'Cliente' : '-';
    $items[] = $fila;
}

echo json_encode($items);
$conn->close();
?>
