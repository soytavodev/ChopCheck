<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require 'db.php';
$pdo = db();

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));
$pid = (int)($_GET['pid'] ?? 0);

if (!$codigo || !$pid) { echo json_encode(['error'=>'parametros']); exit; }

// Mesa
$stmt = $pdo->prepare("SELECT * FROM mesas WHERE codigo = ?");
$stmt->execute([$codigo]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mesa) { echo json_encode(['error'=>'mesa']); exit; }

// Participante pertenece a mesa
$stmt = $pdo->prepare("SELECT 1 FROM participantes WHERE id = ? AND mesa_id = ?");
$stmt->execute([$pid, $mesa['id']]);
if (!$stmt->fetchColumn()) { echo json_encode(['error'=>'participante']); exit; }

// Último pago
$stmt = $pdo->prepare("SELECT estado FROM pagos WHERE participante_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$pid]);
$estado = $stmt->fetchColumn();

echo json_encode(['estado' => $estado ?: null]);
