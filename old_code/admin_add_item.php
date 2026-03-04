<?php
require 'auth.php';
ensure_admin();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
$pdo = db();

$codigo = strtoupper(trim($_POST['codigo'] ?? ''));
$articulo_id = (int)($_POST['articulo_id'] ?? 0);
$cantidad = max(1, (int)($_POST['cantidad'] ?? 1));

if (!$codigo || !$articulo_id) { header("Location: admin.php"); exit; }

// Mesa
$stmt = $pdo->prepare("SELECT * FROM mesas WHERE codigo = ?");
$stmt->execute([$codigo]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mesa) die("Mesa no encontrada");

// Artículo
$stmt = $pdo->prepare("SELECT * FROM articulos WHERE id = ? AND activo = 1");
$stmt->execute([$articulo_id]);
$art = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$art) die("Artículo no válido");

// Insertar N unidades como N items
$pdo->beginTransaction();
try {
    $ins = $pdo->prepare("INSERT INTO items (mesa_id, nombre, precio_centimos) VALUES (?, ?, ?)");
    for ($i=0; $i<$cantidad; $i++) {
        $ins->execute([$mesa['id'], $art['nombre'], $art['precio_centimos']]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die('Error al añadir items: ' . $e->getMessage());
}

header("Location: admin_mesa.php?codigo=" . urlencode($codigo));
exit;
