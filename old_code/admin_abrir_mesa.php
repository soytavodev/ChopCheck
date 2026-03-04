<?php
require 'auth.php';
ensure_admin();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'db.php';
$pdo = db();

$codigo = strtoupper(trim($_POST['codigo'] ?? ''));
if (!$codigo) { header("Location: admin.php"); exit; }

$stmt = $pdo->prepare("UPDATE mesas SET cerrado = 0 WHERE codigo = ?");
$stmt->execute([$codigo]);

header("Location: admin.php");
exit;

