<?php
// ARCHIVO: api/join_session.php
// OBJETIVO: Crear un usuario nuevo y devolver su ID

header('Content-Type: application/json');
require_once '../config/db_connect.php';

// 1. Recibir datos (El nombre del usuario)
$input = json_decode(file_get_contents('php://input'), true);
$alias = $input['alias'] ?? '';
$mesa_codigo = 'MESA-TEST-1'; // Por ahora fijo, luego será dinámico

if (empty($alias)) {
    echo json_encode(['success' => false, 'error' => 'Necesitas un nombre']);
    exit;
}

// 2. Buscar el ID de la sesión (Mesa)
// Esto es seguridad: verificamos que la mesa existe
$sql_mesa = "SELECT id FROM sesiones WHERE codigo_acceso = '$mesa_codigo' LIMIT 1";
$res_mesa = $conn->query($sql_mesa);

if ($res_mesa->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Mesa no encontrada']);
    exit;
}
$fila_mesa = $res_mesa->fetch_assoc();
$sesion_id = $fila_mesa['id'];

// 3. Crear el usuario en la base de datos
// Generamos un token aleatorio para "recordar" al usuario si recarga la página
$token = bin2hex(random_bytes(16)); 

$sql_insert = "INSERT INTO usuarios_temp (sesion_id, alias, token_recuperacion) VALUES ($sesion_id, '$alias', '$token')";

if ($conn->query($sql_insert)) {
    // ÉXITO: Devolvemos el ID nuevo que MySQL acaba de crear
    echo json_encode([
        'success' => true, 
        'user_id' => $conn->insert_id, // <--- ESTO ES ORO: El ID real (ej: 3, 4, 5...)
        'token' => $token
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al crear usuario']);
}

$conn->close();
?>
