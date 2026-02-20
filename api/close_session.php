<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/close_session.php
// DESCRIPCIÓN: Cierra una mesa: borra items y la deja en estado ABIERTA y sin PIN.
// Entrada: JSON { mesa_id }
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$mesaId = isset($data['mesa_id']) ? (int)$data['mesa_id'] : 0;

if ($mesaId <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'ID de mesa no proporcionado.'
    ]);
    $conn->close();
    exit;
}

$conn->begin_transaction();

try {
    // Borrar todos los items de esa sesión
    $stmtDel = $conn->prepare("DELETE FROM items WHERE sesion_id = ?");
    $stmtDel->bind_param('i', $mesaId);
    $stmtDel->execute();
    $stmtDel->close();

    // Resetear estado de la mesa y limpiar PIN
    $stmtUpd = $conn->prepare(
        "UPDATE sesiones 
         SET estado = 'ABIERTA', pin_pago_mesa = NULL
         WHERE id = ?"
    );
    $stmtUpd->bind_param('i', $mesaId);
    $stmtUpd->execute();
    $stmtUpd->close();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error'   => 'Error al cerrar la mesa.'
    ]);
}

$conn->close();
