<?php
// ARCHIVO: api/send_alert.php
// OBJETIVO: Generar un PIN ÚNICO para esta solicitud y avisar al admin
// ESTADO: COMPLETO

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

$mesa_id = $input['mesa_id'] ?? 1;
$usuario = $input['usuario'] ?? 'Anónimo';
$monto = $input['monto'] ?? 0;
$mensaje_cliente = $input['mensaje'] ?? 'Solicita atención';

// 1. GENERAR PIN ALEATORIO (4 dígitos)
$nuevo_pin = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

// 2. GUARDAR EL PIN EN UN ARCHIVO (Sobrescribe el anterior de esa mesa)
// Usamos un archivo simple para no complicar la base de datos
$archivo_pin = "../config/pin_mesa_{$mesa_id}.txt";
file_put_contents($archivo_pin, $nuevo_pin);

// 3. CREAR MENSAJE PARA EL ADMIN
// Incluimos el PIN en el mensaje para que el camarero lo vea rápido
$mensaje_final = "$mensaje_cliente. PIN DE PAGO: $nuevo_pin";

// 4. INSERTAR NOTIFICACIÓN EN BASE DE DATOS
$stmt = $conn->prepare("INSERT INTO notificaciones (mesa_id, nombre_usuario, mensaje, monto) VALUES (?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("issd", $mesa_id, $usuario, $mensaje_final, $monto);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'mensaje' => 'Aviso enviado. Espera a que el camarero te diga el PIN.',
            'debug_pin' => $nuevo_pin // (Opcional) Para ver en consola si estás probando solo
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar aviso en BD']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Error SQL Prepare: ' . $conn->error]);
}

$conn->close();
?>
