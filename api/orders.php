<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/orders.php
// DESCRIPCIÓN: Inserta un nuevo item en la mesa desde el panel admin.
// Entrada: JSON { mesa_id, nombre, precio }
// Salida: { success: true } o { success: false, error: '...' }
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

// Importante: que NO se impriman warnings/notices que rompan el JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // Errores al log, no al output

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$mesaId = isset($data['mesa_id']) ? (int)$data['mesa_id'] : 0;
$nombre = isset($data['nombre']) ? trim($data['nombre']) : '';
$precio = isset($data['precio']) ? (float)$data['precio'] : 0;

if ($mesaId <= 0 || $nombre === '' || $precio <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Datos incompletos para añadir el plato (mesa, nombre o precio).'
    ]);
    $conn->close();
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO items (sesion_id, nombre_producto, precio, estado) 
     VALUES (?, ?, ?, 'LIBRE')"
);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error'   => 'Error al preparar la consulta: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

$stmt->bind_param('isd', $mesaId, $nombre, $precio);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'No se pudo añadir el plato: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
