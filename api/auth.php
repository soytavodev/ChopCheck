<?php
// ARCHIVO: api/auth.php
// DESCRIPCIÓN: Gestiona el ingreso de nuevos piratas (usuarios) a la mesa.

header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$alias = $input['alias'] ?? '';
$codigo_mesa = $input['codigo_mesa'] ?? 'MESA-01'; // Por defecto mesa 1

if (empty($alias)) {
    echo json_encode(['success' => false, 'error' => '¿Cómo te llamas, marinero?']);
    exit;
}

// 1. Verificamos que la mesa exista y esté abierta
$stmt = $conn->prepare("SELECT id FROM sesiones WHERE codigo_mesa = ? AND estado = 'ABIERTA'");
$stmt->bind_param("s", $codigo_mesa);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Esa mesa no está disponible en esta taberna.']);
    exit;
}
$mesa = $res->fetch_assoc();
$sesion_id = $mesa['id'];

// 2. Creamos el usuario
$token = bin2hex(random_bytes(16)); // Token de persistencia
$stmt_ins = $conn->prepare("INSERT INTO usuarios (sesion_id, alias, token_recuperacion) VALUES (?, ?, ?)");
$stmt_ins->bind_param("iss", $sesion_id, $alias, $token);

if ($stmt_ins->execute()) {
    echo json_encode([
        'success' => true,
        'usuario_id' => $conn->insert_id,
        'alias' => $alias,
        'token' => $token
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al enrolar al usuario.']);
}

$stmt->close();
$conn->close();
