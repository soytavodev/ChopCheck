<?php
// ARCHIVO: api/clear_table.php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$mesa_id = $input['mesa_id'] ?? 1; // Por defecto Mesa 1

if (!$mesa_id) {
    echo json_encode(['success' => false, 'error' => 'Falta ID mesa']);
    exit;
}

// 1. Borramos los items de esa mesa
$sql = "DELETE FROM items WHERE sesion_id = $mesa_id";

if ($conn->query($sql)) {
    // Opcional: También podrías borrar los usuarios de esa sesión si quisieras un reset total
    // $conn->query("DELETE FROM usuarios_temp WHERE sesion_id = $mesa_id"); 
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al limpiar']);
}

$conn->close();
?>
