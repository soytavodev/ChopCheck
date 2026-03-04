<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'helpers.php';
$pdo = db();

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));
$pid = (int)($_GET['pid'] ?? 0);
$msg = trim($_GET['msg'] ?? '');
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

// Items
$stmt = $pdo->prepare("SELECT * FROM items WHERE mesa_id = ? ORDER BY id ASC");
$stmt->execute([$mesa['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Último pago (si existe)
$stmt = $pdo->prepare("SELECT * FROM pagos WHERE participante_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$pid]);
$pago = $stmt->fetch(PDO::FETCH_ASSOC);

$total = 0;
$detalle = [];

foreach ($items as $it) {
    $cs = $pdo->prepare("SELECT participante_id FROM item_consumos WHERE item_id = ?");
    $cs->execute([$it['id']]);
    $cons = array_column($cs->fetchAll(PDO::FETCH_ASSOC), 'participante_id');

    if ($cons && in_array($pid, $cons)) {
        $shares = dividir_centimos_equitable($it['precio_centimos'], $cons);
        $miParte = $shares[$pid] ?? 0;
        $total += $miParte;
        $detalle[] = [
            'nombre' => $it['nombre'],
            'precio' => $it['precio_centimos'],
            'mi_parte' => $miParte,
            'n_cons' => count($cons)
        ];
    }
}

$estadoPago = $pago['estado'] ?? null; // null | pendiente | pagado
$pinPago = $pago['pin'] ?? null;
$totalBloqueado = isset($pago['total_centimos']) ? (int)$pago['total_centimos'] : null;

$pagadoYa = ($estadoPago === 'pagado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Tu consumo - ChopCheck</title>
  <link rel="stylesheet" href="styles.css">
  <?php if ($pagadoYa): ?>
  <meta http-equiv="refresh" content="2;url=index.php">
  <?php endif; ?>
</head>
<body>
  <h1>Tu consumo</h1>
  <p class="muted">Mesa <?= htmlspecialchars($mesa['numero'] ? ('Mesa '.$mesa['numero']) : ('Mesa '.$mesa['codigo'])) ?><?= $mesa['nombre'] ? ' · '.htmlspecialchars($mesa['nombre']) : '' ?></p>
  <h2><?= htmlspecialchars($part['nombre']) ?></h2>

  <?php if ($msg): ?>
    <div class="card"><strong><?= htmlspecialchars($msg) ?></strong></div>
  <?php endif; ?>

  <?php if (!$detalle): ?>
    <p>No tienes productos asignados todavía.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Producto</th>
          <th>Precio</th>
          <th>Tu parte</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($detalle as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['nombre']) ?> <?= $d['n_cons']>1 ? "<span class='muted'>(compartido)</span>" : "" ?></td>
            <td><?= centimos_a_euros($d['precio']) ?></td>
            <td><strong><?= centimos_a_euros($d['mi_parte']) ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2">Total actual</th>
          <th><?= centimos_a_euros($total) ?></th>
        </tr>
      </tfoot>
    </table>
  <?php endif; ?>

  <div class="card">
    <h3>Pago en caja con PIN</h3>

    <?php if ($estadoPago === 'pagado'): ?>
      <p>✅ <strong>Pago validado</strong>. Gracias.</p>
      <p class="muted">Serás redirigido automáticamente al inicio…</p>

    <?php elseif ($estadoPago === 'pendiente'): ?>
      <p>🔒 <strong>Pago pendiente</strong>. Muestra este <strong>PIN</strong> en caja:</p>
      <p style="font-size: 28px; font-weight: bold; letter-spacing: 2px;"><?= htmlspecialchars($pinPago) ?></p>
      <p><strong>Importe bloqueado:</strong> <?= centimos_a_euros($totalBloqueado ?? 0) ?></p>
      <p class="muted">Cuando el cajero lo valide, te sacaremos de la mesa automáticamente.</p>

    <?php else: ?>
      <form action="generar_pin.php" method="post" class="inline">
        <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
        <input type="hidden" name="pid" value="<?= $pid ?>">
        <button type="submit">Generar PIN y bloquear mi consumo</button>
      </form>
      <p class="muted">Se tomará el total actual como importe a pagar.</p>
    <?php endif; ?>
  </div>

  <p><a href="mesa.php?codigo=<?= urlencode($codigo) ?>">← Volver a mesa</a></p>

  <?php if (!$pagadoYa): ?>
  <script>
    const codigo = "<?= htmlspecialchars($codigo) ?>";
    const pid = <?= (int)$pid ?>;
    async function checkPago(){
      try{
        const r = await fetch(`pago_estado.php?codigo=${encodeURIComponent(codigo)}&pid=${pid}`, {cache:'no-store'});
        if(!r.ok) return;
        const data = await r.json();
        if (data && data.estado === 'pagado') {
          // Mostrar mensaje y redirigir
          alert('✅ Pago validado. Gracias.');
          window.location.href = 'index.php';
        }
      }catch(e){}
    }
    setInterval(checkPago, 2000);
  </script>
  <?php endif; ?>
</body>
</html>
