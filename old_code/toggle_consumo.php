<?php
// toggle_consumo.php — Toggle consumidor en item (bloquea por cierre/pago/inactivo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
$pdo = db();

$codigo = strtoupper(trim($_POST['codigo'] ?? ''));
$item_id = (int)($_POST['item_id'] ?? 0);
$participante_id = (int)($_POST['participante_id'] ?? 0);

if (!$codigo || !$item_id || !$participante_id) { header("Location: index.php"); exit; }

// Mesa
$stmt = $pdo->prepare("SELECT id, cerrado FROM mesas WHERE codigo = ?");
$stmt->execute([$codigo]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mesa) die("Mesa no encontrada");

// Bloqueo si mesa cerrada
if ((int)$mesa['cerrado'] === 1) {
    header("Location: mesa.php?codigo=" . urlencode($codigo));
    exit;
}

// Item válido para la mesa
$stmt = $pdo->prepare("SELECT id FROM items WHERE id = ? AND mesa_id = ?");
$stmt->execute([$item_id, $mesa['id']]);
if (!$stmt->fetch()) die("Item inválido");

// Participante válido y ACTIVO en la mesa
$stmt = $pdo->prepare("SELECT id FROM participantes WHERE id = ? AND mesa_id = ? AND activo = 1");
$stmt->execute([$participante_id, $mesa['id']]);
if (!$stmt->fetch()) {
    header("Location: mesa.php?codigo=" . urlencode($codigo));
    exit;
}

// Bloqueo si ya tiene pago pendiente/pagado (por coherencia con flujo)
$stmt = $pdo->prepare("SELECT 1 FROM pagos WHERE participante_id = ? AND estado IN ('pendiente','pagado') LIMIT 1");
$stmt->execute([$participante_id]);
if ($stmt->fetch()) {
    header("Location: mesa.php?codigo=" . urlencode($codigo));
    exit;
}

// Toggle
$stmt = $pdo->prepare("SELECT id FROM item_consumos WHERE item_id = ? AND participante_id = ?");
$stmt->execute([$item_id, $participante_id]);
$ya = $stmt->fetch();

if ($ya) {
    $pdo->prepare("DELETE FROM item_consumos WHERE id = ?")->execute([$ya['id']]);
} else {
    $pdo->prepare("INSERT INTO item_consumos (item_id, participante_id) VALUES (?, ?)")->execute([$item_id, $participante_id]);
}

header("Location: mesa.php?codigo=" . urlencode($codigo));
exit;
