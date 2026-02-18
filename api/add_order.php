<?php
// ARCHIVO: api/add_order.php
// VERSIÓN: UNIVERSAL (Detecta automáticamente el formato del pedido)

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db_connect.php';

// 1. Recibir los datos crudos
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

// 2. DETECTOR DE FORMATO (Aquí estaba el problema)
// Buscamos los productos bajo cualquier nombre probable
$productos = [];
$mesa_id = $input['mesa_id'] ?? 1;

if (isset($input['productos'])) {
    $productos = $input['productos'];
} elseif (isset($input['items'])) {
    $productos = $input['items'];
} elseif (isset($input['pedido'])) {
    $productos = $input['pedido'];
} elseif (is_array($input) && isset($input[0]['nombre'])) {
    // Si mandaron el array "a pelo" sin etiqueta
    $productos = $input;
}

// 3. Validación final
if (empty($productos)) {
    // Si sigue vacío, devolvemos lo que recibimos para que tú veas el error en pantalla
    echo json_encode([
        'success' => false, 
        'error' => 'No han llegado productos. Recibí esto: ' . substr($raw_input, 0, 100)
    ]);
    exit;
}

// 4. Insertar en Base de Datos (Seguro)
$stmt = $conn->prepare("INSERT INTO items (sesion_id, nombre_producto, precio, estado) VALUES (?, ?, ?, 'LIBRE')");

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error SQL Prepare: ' . $conn->error]);
    exit;
}

$guardados = 0;
foreach ($productos as $prod) {
    // A veces llega como 'nombre', a veces como 'nombre_producto'. Prevenimos eso.
    $nombre = $prod['nombre'] ?? $prod['nombre_producto'] ?? 'Producto Desconocido';
    $precio = $prod['precio'] ?? 0;

    $stmt->bind_param("isd", $mesa_id, $nombre, $precio);
    if ($stmt->execute()) {
        $guardados++;
    }
}

// 5. Respuesta
echo json_encode([
    'success' => true, 
    'mensaje' => "Cocina: Oído $guardados platos para Mesa $mesa_id"
]);

$stmt->close();
$conn->close();
?>
