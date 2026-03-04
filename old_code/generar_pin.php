<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'helpers.php';
$pdo = db();

$codigo = strtoupper(trim($_POST['codigo'] ?? ''));
$pid = (int)($_POST['pid'] ?? 0);
if (!$codigo || !$pid) { header("Location: index.php"); exit; }

// Mesa
$stmt = $pdo->prepare("SELECT * FROM mesas WHERE codigo = ?");
$stmt->execute([$codigo]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mesa) die("Mesa no encontrada");

// Participante
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE id = ? AND mesa_id = ?");
$stmt->execute([$pid, $mesa['id']]);
$part = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$part) die("Participante no válido");

// Calcular total
$stmt = $pdo->prepare("SELECT * FROM items WHERE mesa_id = ? ORDER BY id ASC");
$stmt->execute([$mesa['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $it) {
    $cs = $pdo->prepare("SELECT participante_id FROM item_consumos WHERE item_id = ?");
    $cs->execute([$it['id']]);
    $cons = array_column($cs->fetchAll(PDO::FETCH_ASSOC), 'participante_id');
    if ($cons && in_array($pid, $cons)) {
        $shares = dividir_centimos_equitable($it['precio_centimos'], $cons);
        $total += ($shares[$pid] ?? 0);
    }
}

if ($total <= 0) {
    header("Location: resumen.php?codigo=" . urlencode($codigo) . "&pid=" . $pid . "&msg=" . urlencode("No tienes consumo asignado."));
    exit;
}

// Borrar pendiente anterior
$pdo->prepare("DELETE FROM pagos WHERE participante_id = ? AND estado = 'pendiente'")->execute([$pid]);

$pin = generar_pin_pago(4);

$stmt = $pdo->prepare("INSERT INTO pagos (mesa_id, participante_id, pin, total_centimos, estado) VALUES (?, ?, ?, ?, 'pendiente')");
$stmt->execute([$mesa['id'], $pid, $pin, $total]);

header("Location: resumen.php?codigo=" . urlencode($codigo) . "&pid=" . $pid);
exit;
