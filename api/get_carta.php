<?php
// ARCHIVO: api/get_carta.php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$sql = "SELECT id, categoria, nombre, precio FROM carta ORDER BY categoria ASC, nombre ASC";
$res = $conn->query($sql);

if (!$res) {
    echo json_encode(['error' => $conn->error]);
    exit;
}

$productos = [];
while ($fila = $res->fetch_assoc()) {
    $productos[] = $fila;
}

echo json_encode($productos);
$conn->close();
