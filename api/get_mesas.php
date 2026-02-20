<?php
// api/get_mesas.php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$sql = "SELECT id, codigo_mesa, estado FROM sesiones ORDER BY id ASC";
$res = $conn->query($sql);

$mesas = [];
while ($fila = $res->fetch_assoc()) {
    $mesas[] = $fila;
}
echo json_encode($mesas);
