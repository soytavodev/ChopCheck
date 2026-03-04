<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'helpers.php';
$pdo = db();

$codigo = strtoupper(trim($_POST['codigo'] ?? ''));
$nombre = trim($_POST['nombre'] ?? '');
$precio = trim($_POST['precio'] ?? '');

if (!$codigo || !$nombre || !$precio) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT id FROM mesas WHERE codigo = ?");
$stmt->execute([$codigo]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mesa) die("Mesa no encontrada");

$centimos = euros_a_centimos($precio);
if ($centimos <= 0) die("Precio inválido");

$stmt = $pdo->prepare("INSERT INTO items (mesa_id, nombre, precio_centimos) VALUES (?, ?, ?) ");
$stmt->execute([$mesa['id'], $nombre, $centimos]);

header("Location: mesa.php?codigo=" . urlencode($codigo));
exit;
