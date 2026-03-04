<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require 'db.php';
require 'helpers.php';
$pdo = db();

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));
if (!$codigo) { echo json_encode(['error'=>'codigo requerido']); exit; }

// Mesa
$stmt = $pdo->prepare("SELECT * FROM mesas WHERE codigo = ?");
$stmt->execute([$codigo]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mesa) { echo json_encode(['error'=>'mesa no encontrada']); exit; }

// Participantes
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE mesa_id = ? AND activo = 1 ORDER BY id ASC");
$stmt->execute([$mesa['id']]);
$participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Items
$stmt = $pdo->prepare("SELECT * FROM items WHERE mesa_id = ? ORDER BY id DESC");
$stmt->execute([$mesa['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consumos por item
$consumosPorItem = [];
if ($items) {
    $ids = array_column($items, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT item_id, participante_id FROM item_consumos WHERE item_id IN ($in)");
    $stmt->execute($ids);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $consumosPorItem[$r['item_id']][] = (int)$r['participante_id'];
    }
}

// Totales por participante y mesa
$totales = [];
foreach ($participantes as $p) $totales[$p['id']] = 0;
$totalMesa = 0;

foreach ($items as $it) {
    $totalMesa += (int)$it['precio_centimos'];
    $cons = $consumosPorItem[$it['id']] ?? [];
    if (count($cons) > 0) {
        $shares = dividir_centimos_equitable($it['precio_centimos'], $cons);
        foreach ($shares as $pid => $cents) {
            if (!isset($totales[$pid])) $totales[$pid] = 0;
            $totales[$pid] += $cents;
        }
    }
}

// Total pagado (congelado)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_centimos),0) FROM pagos WHERE mesa_id = ? AND estado = 'pagado'");
$stmt->execute([$mesa['id']]);
$totalPagado = (int)$stmt->fetchColumn();
$totalRestante = max(0, $totalMesa - $totalPagado);

// Estado de pago por participante (último)
$estadoPorPid = [];
$pids = array_column($participantes, 'id');
if (!empty($pids)) {
    $in = implode(',', array_fill(0, count($pids), '?'));
    $st = $pdo->prepare("SELECT p1.* FROM pagos p1
                         INNER JOIN (
                           SELECT participante_id, MAX(id) AS max_id
                           FROM pagos
                           WHERE participante_id IN ($in)
                           GROUP BY participante_id
                         ) t ON t.max_id = p1.id");
    $st->execute($pids);
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        $estadoPorPid[(int)$r['participante_id']] = $r['estado'];
    }
}

$outParticipantes = array_map(function($p) use ($totales, $estadoPorPid) {
    return [
        'id' => (int)$p['id'],
        'nombre' => $p['nombre'],
        'total_centimos' => (int)($totales[$p['id']] ?? 0),
        'estado' => $estadoPorPid[(int)$p['id']] ?? null
    ];
}, $participantes);

$outItems = array_map(function($it) use ($consumosPorItem) {
    $cons = $consumosPorItem[$it['id']] ?? [];
    $shares = null;
    if ($cons) {
        $shares = dividir_centimos_equitable($it['precio_centimos'], $cons);
        foreach ($shares as $k=>$v) $shares[$k] = (int)$v;
    }
    return [
        'id' => (int)$it['id'],
        'nombre' => $it['nombre'],
        'precio_centimos' => (int)$it['precio_centimos'],
        'consumidores' => array_map('intval', $cons),
        'shares' => $shares
    ];
}, $items);

echo json_encode([
    'mesa' => [
        'codigo' => $mesa['codigo'],
        'numero' => is_null($mesa['numero']) ? null : (int)$mesa['numero'],
        'nombre' => $mesa['nombre'],
        'cerrado' => (int)$mesa['cerrado']
    ],
    'total_mesa_centimos' => $totalMesa,
    'total_pagado_centimos' => $totalPagado,
    'total_restante_centimos' => $totalRestante,
    'participantes' => $outParticipantes,
    'items' => $outItems
]);
