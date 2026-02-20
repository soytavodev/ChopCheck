<?php
// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: api/undo_split.php
// DESCRIPCIÓN: Reunir partes de un plato previamente dividido.
// Entrada: JSON { grupo_id }
// ==============================================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connect.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$grupoId = isset($data['grupo_id']) ? trim($data['grupo_id']) : '';

if ($grupoId === '') {
    echo json_encode([
        'success' => false,
        'error'   => 'ID de grupo no recibido.'
    ]);
    $conn->close();
    exit;
}

// 1. Verificar si alguna parte ya está ocupada
$sqlCheck = "
    SELECT COUNT(*) AS ocupados
    FROM items
    WHERE grupo_split = ?
      AND estado != 'LIBRE'
";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param('s', $grupoId);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();
$check = $resCheck->fetch_assoc();
$stmtCheck->close();

if ($check['ocupados'] > 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'No puedes unir el plato; alguien ya ha reclamado una parte.'
    ]);
    $conn->close();
    exit;
}

// 2. Obtener información agregada del grupo
$sqlInfo = "
    SELECT 
        MAX(nombre_producto) AS nombre_producto,
        SUM(precio)          AS total,
        MAX(sesion_id)       AS sesion_id
    FROM items
    WHERE grupo_split = ?
    GROUP BY grupo_split
";

$stmtInfo = $conn->prepare($sqlInfo);
$stmtInfo->bind_param('s', $grupoId);
$stmtInfo->execute();
$resInfo = $stmtInfo->get_result();
$info = $resInfo->fetch_assoc();
$stmtInfo->close();

if (!$info) {
    echo json_encode([
        'success' => false,
        'error'   => 'No se encontraron las partes del grupo.'
    ]);
    $conn->close();
    exit;
}

// Limpiar el nombre: quitar "1/2 de", "1/3 de", etc.
$nombreOriginal = preg_replace('/^\d+\/\d+\s+de\s+/i', '', $info['nombre_producto']);
$sesionId       = (int)$info['sesion_id'];
$totalPrecio    = (float)$info['total'];

// 3. Transacción para borrar trozos e insertar original
$conn->begin_transaction();

try {
    // Borrar trozos
    $stmtDel = $conn->prepare("DELETE FROM items WHERE grupo_split = ?");
    $stmtDel->bind_param('s', $grupoId);
    $stmtDel->execute();
    $stmtDel->close();

    // Insertar original
    $stmtIns = $conn->prepare(
        "INSERT INTO items (sesion_id, nombre_producto, precio, estado, grupo_split)
         VALUES (?, ?, ?, 'LIBRE', NULL)"
    );
    $stmtIns->bind_param('isd', $sesionId, $nombreOriginal, $totalPrecio);
    $stmtIns->execute();
    $stmtIns->close();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error'   => 'Error crítico al unir el plato.'
    ]);
}

$conn->close();
