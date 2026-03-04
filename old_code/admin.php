<?php
require 'auth.php';
ensure_admin();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'helpers.php';
$pdo = db();

$stChk = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mesas' AND COLUMN_NAME = 'numero' LIMIT 1");
$stChk->execute();
$tieneNumero = (bool)$stChk->fetchColumn();

$order = $tieneNumero ? "m.cerrado ASC, m.numero ASC, m.id ASC" : "m.cerrado ASC, m.id ASC";

$sql = "SELECT m.*,
    (SELECT COUNT(*) FROM participantes p WHERE p.mesa_id = m.id AND p.activo = 1) AS num_participantes,
    (SELECT COUNT(*) FROM items i WHERE i.mesa_id = m.id) AS num_items
  FROM mesas m
  ORDER BY $order";
$stmt = $pdo->query($sql);
$mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Admin - ChopCheck</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .grid-mesas { display:grid; grid-template-columns: repeat(auto-fill,minmax(240px,1fr)); gap:16px; }
    .mesa-card { border:1px solid #e5e5e5; border-radius:10px; padding:12px; }
    .cerrada { opacity:.6 }
    .status { font-size:.9em; color:#666 }
    .actions form { display:inline-block; margin-right:6px; }
  </style>
</head>
<body>
  <h1>Vista admin</h1>

  <div class="grid">
    <div class="card">
      <h2>Crear / Abrir mesa por número</h2>
      <form action="crear_mesa.php" method="post">
        <label>Número de mesa:
          <input type="number" name="numero" min="1" required placeholder="Ej: 1">
        </label>
        <label>Nombre/Descripción (opcional):
          <input type="text" name="nombre" maxlength="100" placeholder="Terraza / Sala / etc.">
        </label>
        <button type="submit">Abrir Mesa N</button>
      </form>
      <p class="muted">Si la mesa existe y está cerrada, se reabre con un código nuevo. Si está abierta, se avisa.</p>
    </div>

    <div class="card">
      <h2>Validar pagos (PIN)</h2>
      <form action="validar_pago.php" method="post">
        <label>Código de mesa:
          <input type="text" name="codigo" maxlength="8" required>
        </label>
        <label>PIN:
          <input type="text" name="pin" maxlength="6" required>
        </label>
        <button type="submit">Validar</button>
      </form>
    </div>
  </div>

  <div class="card">
    <h2>Plano de mesas</h2>
    <div class="grid-mesas">
      <?php if (!$mesas): ?>
        <p>No hay mesas creadas.</p>
      <?php else: foreach ($mesas as $m): ?>
        <div class="mesa-card <?= $m['cerrado'] ? 'cerrada' : '' ?>">
          <h3><?= $tieneNumero && $m['numero'] ? 'Mesa '.htmlspecialchars($m['numero']) : 'Mesa' ?><?= $m['nombre'] ? ' · '.htmlspecialchars($m['nombre']) : '' ?></h3>
          <p class="status">
            Código: <strong><?= htmlspecialchars($m['codigo']) ?></strong><br>
            Estado: <?= $m['cerrado'] ? 'CERRADA' : 'ABIERTA' ?><br>
            Participantes: <?= (int)$m['num_participantes'] ?> · Items: <?= (int)$m['num_items'] ?>
          </p>
          <p class="actions">
            <a href="admin_mesa.php?codigo=<?= urlencode($m['codigo']) ?>">Gestionar</a> ·
            <a href="mesa.php?codigo=<?= urlencode($m['codigo']) ?>" target="_blank">Vista usuario</a>
          </p>
          <div class="actions">
            <?php if ($m['cerrado']): ?>
              <form action="admin_abrir_mesa.php" method="post">
                <input type="hidden" name="codigo" value="<?= htmlspecialchars($m['codigo']) ?>">
                <button type="submit">Abrir mesa</button>
              </form>
            <?php else: ?>
              <form action="admin_cerrar_mesa.php" method="post">
                <input type="hidden" name="codigo" value="<?= htmlspecialchars($m['codigo']) ?>">
                <button type="submit">Cerrar mesa</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <p><a href="index.php">← Ir a vista usuario</a></p>
</body>
</html>
