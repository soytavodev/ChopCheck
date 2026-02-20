<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/get_mesas.php
// DESCRIPCIÓN: Listado de mesas con su estado y total acumulado.
// Salida: JSON [ { id, codigo_mesa, estado, total, pin_pago_mesa } ]
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

$sql = "
    SELECT 
        s.id,
        s.codigo_mesa,
        s.estado,
        s.pin_pago_mesa,
        COALESCE(SUM(i.precio), 0) AS total
    FROM sesiones s
    LEFT JOIN items i ON i.sesion_id = s.id
    GROUP BY s.id, s.codigo_mesa, s.estado, s.pin_pago_mesa
    ORDER BY s.codigo_mesa ASC
";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        'success' => false,
        'error'   => 'Error al obtener las mesas.'
    ]);
    $conn->close();
    exit;
}

$mesas = [];
while ($fila = $res->fetch_assoc()) {
    $fila['total'] = (float)$fila['total'];
    $mesas[] = $fila;
}

echo json_encode($mesas);

$conn->close();
