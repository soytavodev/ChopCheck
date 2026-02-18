<?php
// ARCHIVO: api/undo_split.php
// OBJETIVO: Unir items y RESTAURAR SU NOMBRE ORIGINAL
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$grupo_id = $input['grupo_id'] ?? null;

if (!$grupo_id) {
    echo json_encode(['success' => false, 'error' => 'No se recibió el ID del grupo']);
    exit;
}

// 1. Obtener información del grupo antes de borrarlo
$sql = "SELECT nombre_producto, SUM(precio) as precio_total, sesion_id 
        FROM items 
        WHERE grupo_split = ? 
        GROUP BY grupo_split";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $grupo_id);
$stmt->execute();
$resultado = $stmt->get_result();
$info = $resultado->fetch_assoc();

if (!$info) {
    echo json_encode(['success' => false, 'error' => 'No se encontraron items de este grupo']);
    exit;
}

// === AQUÍ ESTÁ LA MAGIA (Limpieza de nombre) ===
$nombre_sucio = $info['nombre_producto']; // Ej: "1/2 de Agua"

// Expresión regular: Busca números + barra + números + " de " al principio
// Ej: "1/2 de ", "3/4 de ", "10/20 de "
$nombre_limpio = preg_replace('/^\d+\/\d+ de\s+/i', '', $nombre_sucio);

// Extra: Si por si acaso usaste paréntesis al final "(1/2)", esto también lo quita
$nombre_limpio = preg_replace('/\s*\(\d+\/\d+\)$/', '', $nombre_limpio);
// ===============================================

$precio_original = $info['precio_total'];
$sesion_id = $info['sesion_id'];

// 2. Borrar los trozos
$sql_delete = "DELETE FROM items WHERE grupo_split = ?";
$stmt_del = $conn->prepare($sql_delete);
$stmt_del->bind_param("s", $grupo_id);

if ($stmt_del->execute()) {
    // 3. Crear el item original con el NOMBRE LIMPIO
    $sql_insert = "INSERT INTO items (sesion_id, nombre_producto, precio, estado, grupo_split) VALUES (?, ?, ?, 'LIBRE', NULL)";
    $stmt_ins = $conn->prepare($sql_insert);
    
    // IMPORTANTE: Usamos $nombre_limpio
    $stmt_ins->bind_param("isd", $sesion_id, $nombre_limpio, $precio_original);
    
    if ($stmt_ins->execute()) {
        echo json_encode(['success' => true, 'nombre_restaurado' => $nombre_limpio]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fallo al crear el original']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Error al borrar trozos']);
}

$conn->close();
?>
