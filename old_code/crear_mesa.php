<?php
require 'auth.php';
ensure_admin();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'helpers.php';
$pdo = db();

$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : null;
$numero = isset($_POST['numero']) ? (int)$_POST['numero'] : 0;

if ($numero <= 0) {
    die("Número de mesa inválido. <a href='admin.php'>Volver</a>");
}

// ¿Ya hay una mesa con ese número?
$stmt = $pdo->prepare("SELECT * FROM mesas WHERE numero = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$numero]);
$existe = $stmt->fetch(PDO::FETCH_ASSOC);

$codigo = null;
do {
    $codigo = generarCodigoMesa(6);
    $st = $pdo->prepare("SELECT id FROM mesas WHERE codigo = ?");
    $st->execute([$codigo]);
} while ($st->fetch());

if ($existe) {
    if ((int)$existe['cerrado'] === 0) {
        die("La Mesa $numero ya está abierta. <a href='admin.php'>Volver a mesas</a>");
    }
    // Reabrir mesa cerrada: nuevo código, nombre opcional, cerrado=0
    $up = $pdo->prepare("UPDATE mesas SET codigo = ?, nombre = ?, cerrado = 0 WHERE id = ?");
    $up->execute([$codigo, ($nombre ?: $existe['nombre']), $existe['id']]);

    header("Location: admin_mesa.php?codigo=" . urlencode($codigo));
    exit;
}

// Crear mesa nueva con ese número
$ins = $pdo->prepare("INSERT INTO mesas (codigo, nombre, numero, cerrado) VALUES (?, ?, ?, 0)");
$ins->execute([$codigo, $nombre ?: null, $numero]);

header("Location: admin_mesa.php?codigo=" . urlencode($codigo));
exit;
