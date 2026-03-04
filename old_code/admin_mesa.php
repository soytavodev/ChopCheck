<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'helpers.php';
$pdo = db();

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));
$q = trim($_GET['q'] ?? '');
if (!$codigo) { header("Location: admin.php"); exit; }

// Mesa
$stmt = $pdo->prepare("SELECT * FROM mesas WHERE codigo = ?");
$stmt->execute([$codigo]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mesa) die("Mesa no encontrada");

// Catálogo
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM articulos WHERE activo = 1 AND nombre LIKE ? ORDER BY nombre ASC");
    $stmt->execute(['%'.$q.'%']);
} else {
    $stmt = $pdo->query("SELECT * FROM articulos WHERE activo = 1 ORDER BY nombre ASC");
}
$articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Participantes (contexto)
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE mesa_id = ? AND activo = 1 ORDER BY id ASC");
$stmt->execute([$mesa['id']]);
$participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Items actuales (contexto)
$stmt = $pdo->prepare("SELECT * FROM items WHERE mesa_id = ? ORDER BY id DESC");
$stmt->execute([$mesa['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Admin · <?= htmlspecialchars($mesa['numero'] ? ('Mesa '.$mesa['numero']) : $mesa['codigo']) ?> - ChopCheck</title>
  <link rel="stylesheet" href="styles.css">
  <style>.inline{display:inline-block}</style>
</head>
<body>
  <h1>Gestionar <?= htmlspecialchars($mesa['numero'] ? ('Mesa '.$mesa['numero']) : 'Mesa') ?><?= $mesa['nombre'] ? ' · '.htmlspecialchars($mesa['nombre']) : '' ?></h1>
  <p class="muted">
    Código: <?= htmlspecialchars($mesa['codigo']) ?> · 
    <a href="mesa.php?codigo=<?= urlencode($mesa['codigo']) ?>" target="_blank">Abrir vista usuario</a> · 
    <a href="admin.php">← Volver a admin</a>
  </p>

  <div class="card">
    <h2>Estado de mesa</h2>
    <p>Actualmente: <strong><?= $mesa['cerrado'] ? 'CERRADA' : 'ABIERTA' ?></strong></p>
    <div>
      <?php if ($mesa['cerrado']): ?>
        <form action="admin_abrir_mesa.php" method="post" class="inline">
          <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
          <button type="submit">Abrir mesa</button>
        </form>
      <?php else: ?>
        <form action="admin_cerrar_mesa.php" method="post" class="inline">
          <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
          <button type="submit">Cerrar mesa</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <h2>Catálogo (clic para añadir)</h2>
      <form method="get" action="admin_mesa.php">
        <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
        <label>Buscar artículo:
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cerveza, Agua, ...">
        </label>
        <button type="submit">Buscar</button>
      </form>

      <?php if (!$articulos): ?>
        <p>No hay artículos para mostrar.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr><th>Artículo</th><th>Precio</th><th>Unidades</th><th>Acción</th></tr>
          </thead>
          <tbody>
            <?php foreach ($articulos as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['nombre']) ?></td>
              <td><?= centimos_a_euros($a['precio_centimos']) ?></td>
              <td>
                <form action="admin_add_item.php" method="post" class="inline">
                  <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
                  <input type="hidden" name="articulo_id" value="<?= (int)$a['id'] ?>">
                  <input type="number" name="cantidad" min="1" max="20" value="1" style="width:70px">
              </td>
              <td>
                  <button type="submit">Añadir</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Añadir producto manual</h2>
      <form action="admin_add_item_manual.php" method="post">
        <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
        <label>Nombre del producto:
          <input type="text" name="nombre" maxlength="120" required placeholder="Ej: Zumo natural">
        </label>
        <label>Precio (€):
          <input type="text" name="precio" required placeholder="Ej: 3,50">
        </label>
        <button type="submit">Añadir</button>
      </form>

      <h3 style="margin-top:16px;">Participantes (<?= count($participantes) ?>)</h3>
      <p>
        <?php if ($participantes): ?>
          <?= htmlspecialchars(implode(', ', array_map(fn($p)=>$p['nombre'], $participantes))) ?>
        <?php else: ?>
          <span class="muted">(aún ninguno)</span>
        <?php endif; ?>
      </p>

      <h3>Items (<?= count($items) ?>)</h3>
      <?php if (!$items): ?>
        <p class="muted">Sin productos aún.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($items as $it): ?>
            <li><?= htmlspecialchars($it['nombre']) ?> — <?= centimos_a_euros($it['precio_centimos']) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h2>Validar pago aquí mismo</h2>
    <form action="validar_pago.php" method="post">
      <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
      <label>PIN:
        <input type="text" name="pin" maxlength="6" required>
      </label>
      <button type="submit">Validar</button>
    </form>
  </div>
</body>
</html>
