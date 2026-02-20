<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/items.php
// DESCRIPCIÓN: Devuelve los items (comanda) de una mesa concreta.
// Entrada: mesa_id (por GET, id de la tabla sesiones)
// Salida: JSON [ { id, nombre_producto, precio, estado, grupo_split,
//                  id_usuario_asignado, nombre_usuario } ]
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

$mesaId = isset($_GET['mesa_id']) ? (int)$_GET['mesa_id'] : 0;

if ($mesaId <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'ID de mesa inválido.'
    ]);
    $conn->close();
    exit;
}

$sql = "
    SELECT 
        i.id,
        i.nombre_producto,
        i.precio,
        i.estado,
        i.grupo_split,
        i.id_usuario_asignado,
        u.alias AS nombre_usuario
    FROM items i
    LEFT JOIN usuarios u ON i.id_usuario_asignado = u.id
    WHERE i.sesion_id = ?
    ORDER BY i.fecha_pedido ASC, i.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $mesaId);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($fila = $res->fetch_assoc()) {
    $fila['precio'] = (float)$fila['precio'];
    $items[] = $fila;
}

echo json_encode($items);

$stmt->close();
$conn->close();

