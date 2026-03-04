<?php
// add_participante.php — alta de participante (bloquea si mesa cerrada)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
$pdo = db();

$codigo = strtoupper(trim($_POST['codigo'] ?? ''));
$nombre = trim($_POST['nombre'] ?? '');
if (!$codigo || !$nombre) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT id, cerrado FROM mesas WHERE codigo = ?");
$stmt->execute([$codigo]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mesa) die("Mesa no encontrada");

// Si mesa cerrada, no permitir altas
if ((int)$mesa['cerrado'] === 1) {
    header("Location: mesa.php?codigo=" . urlencode($codigo));
    exit;
}

$stmt = $pdo->prepare("INSERT INTO participantes (mesa_id, nombre) VALUES (?, ?)");
$stmt->execute([$mesa['id'], $nombre]);

header("Location: mesa.php?codigo=" . urlencode($codigo));
exit;
