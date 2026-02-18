<?php
// ARCHIVO: api/pay_items.php
// OBJETIVO: Cobrar validando el PIN específico de la mesa
// ESTADO: COMPLETO

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

// Datos recibidos del cliente
$usuario_id = $input['usuario_id'] ?? $input['user_id'] ?? null;
$pin_ingresado = $input['pin'] ?? '';
// Asumimos mesa 1 si no llega, o podrías buscarla en BD
$mesa_id = $input['mesa_id'] ?? 1; 

if (!$usuario_id) {
    echo json_encode(['success' => false, 'error' => 'Error: No te reconozco (ID Usuario nulo)']);
    exit;
}

// 1. LEER EL PIN REAL DE LA MESA
$archivo_pin = "../config/pin_mesa_{$mesa_id}.txt";
$pin_correcto = "";

if (file_exists($archivo_pin)) {
    $pin_correcto = file_get_contents($archivo_pin);
} else {
    echo json_encode(['success' => false, 'error' => 'No hay ningún pago iniciado. Dale a "Avisar Camarero" primero.']);
    exit;
}

// 2. COMPARAR
// Limpiamos espacios en blanco por si acaso
$pin_ingresado = trim($pin_ingresado);
$pin_correcto = trim($pin_correcto);

if ($pin_ingresado !== $pin_correcto) {
    echo json_encode(['success' => false, 'error' => 'PIN incorrecto. El camarero tiene el código nuevo.']);
    exit;
}

// 3. REALIZAR COBRO (Marcar como PAGADO)
$sql = "UPDATE items SET estado = 'PAGADO' WHERE id_usuario_asignado = ? AND estado = 'ASIGNADO'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // OPCIONAL: Borrar el PIN para que no se pueda reusar
        unlink($archivo_pin); 
        echo json_encode(['success' => true, 'mensaje' => 'Pago aceptado. ¡Gracias!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No tienes nada pendiente de pago.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Error SQL: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
