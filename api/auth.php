<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/auth.php
// DESCRIPCIÓN: Alta de usuario en una mesa (cliente).
// Entrada: JSON { alias, codigo_mesa }
// Salida: JSON con datos de sesión y usuario.
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

// Leer cuerpo JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Validación básica
$alias       = isset($data['alias']) ? trim($data['alias']) : '';
$codigoMesa  = isset($data['codigo_mesa']) ? trim($data['codigo_mesa']) : '';

if ($alias === '' || $codigoMesa === '') {
    echo json_encode([
        'success' => false,
        'error'   => 'Faltan datos: alias o código de mesa.'
    ]);
    exit;
}

// 1. Verificar que la mesa exista y esté abierta o en proceso de pago
//    Para tu flujo, tiene sentido permitir unirse mientras la mesa no esté CERRADA.
$stmt = $conn->prepare(
    "SELECT id, codigo_mesa, estado 
     FROM sesiones 
     WHERE codigo_mesa = ? AND estado IN ('ABIERTA', 'PAGANDO')"
);
$stmt->bind_param('s', $codigoMesa);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Esa mesa no está disponible en este momento.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$mesa = $res->fetch_assoc();
$sesionId = (int)$mesa['id'];

$stmt->close();

// 2. Crear usuario vinculado a la sesión
$token = bin2hex(random_bytes(16));  // Token de recuperación sencilla

$stmtIns = $conn->prepare(
    "INSERT INTO usuarios (sesion_id, alias, token_recuperacion) 
     VALUES (?, ?, ?)"
);
$stmtIns->bind_param('iss', $sesionId, $alias, $token);

if ($stmtIns->execute()) {
    $usuarioId = $conn->insert_id;

    echo json_encode([
        'success'    => true,
        'usuario_id' => $usuarioId,
        'alias'      => $alias,
        'token'      => $token,
        'sesion_id'  => $sesionId,
        'codigo_mesa'=> $codigoMesa
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'No se pudo crear el usuario en la mesa.'
    ]);
}

$stmtIns->close();
$conn->close();
