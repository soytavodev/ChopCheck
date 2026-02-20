<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$mesa_id = $input['mesa_id'] ?? null;

if (!$mesa_id) {
    echo json_encode(['success' => false, 'error' => 'ID de mesa no proporcionado']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Borramos todos los items de esa sesión
    $stmt1 = $conn->prepare("DELETE FROM items WHERE sesion_id = ?");
    $stmt1->bind_param("i", $mesa_id);
    $stmt1->execute();

    // 2. Reseteamos la mesa a 'ABIERTA'
    $stmt2 = $conn->prepare("UPDATE sesiones SET estado = 'ABIERTA' WHERE id = ?");
    $stmt2->bind_param("i", $mesa_id);
    $stmt2->execute();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$conn->close();
