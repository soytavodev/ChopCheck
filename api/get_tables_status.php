<?php
// ARCHIVO: api/get_tables_status.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$mesas = [];
$TOTAL_MESAS = 6; // Definimos que tu bar tiene 6 mesas

for ($i = 1; $i <= $TOTAL_MESAS; $i++) {
    // Sumamos el precio de todo lo que NO esté pagado en esa mesa
    $sql = "SELECT SUM(precio) as total, COUNT(*) as items 
            FROM items 
            WHERE sesion_id = $i AND estado != 'PAGADO'";
    
    $resultado = $conn->query($sql);
    $datos = $resultado->fetch_assoc();
    
    // Si es NULL (mesa vacía), ponemos 0
    $total = $datos['total'] ?? 0;
    $cantidad = $datos['items'] ?? 0;
    
    $mesas[] = [
        'id' => $i,
        'total' => floatval($total),
        'items' => intval($cantidad),
        'estado' => ($total > 0) ? 'OCUPADA' : 'LIBRE'
    ];
}

echo json_encode($mesas);
$conn->close();
?>
