<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/get_carta.php
// DESCRIPCIÓN: Devuelve la carta de productos.
// Salida: JSON [ { id, categoria, nombre, precio } ]
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

$sql = "SELECT id, categoria, nombre, precio FROM carta ORDER BY categoria, nombre";
$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        'success' => false,
        'error'   => 'Error al obtener la carta.'
    ]);
    $conn->close();
    exit;
}

$productos = [];
while ($fila = $res->fetch_assoc()) {
    $fila['precio'] = (float)$fila['precio'];
    $productos[] = $fila;
}

echo json_encode($productos);

$conn->close();
