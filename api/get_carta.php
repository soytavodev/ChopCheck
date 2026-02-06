<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$sql = "SELECT * FROM carta ORDER BY categoria, nombre";
$res = $conn->query($sql);

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
$conn->close();
?>
