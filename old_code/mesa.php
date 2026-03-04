<?php
// mesa.php — Vista Usuario (expulsión al cerrar mesa, total restante, auto-refresco 2s)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'helpers.php';
$pdo = db();

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));
if (!$codigo) { header("Location: index.php"); exit; }

// Mesa
$stmt = $pdo->prepare("SELECT * FROM mesas WHERE codigo = ?");
$stmt->execute([$codigo]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mesa) { die("Mesa no encontrada"); }

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
        $consumosPorItem[$r['item_id']][] = $r['participante_id'];
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

// Total pagado (pagos congelados)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_centimos),0) FROM pagos WHERE mesa_id = ? AND estado = 'pagado'");
$stmt->execute([$mesa['id']]);
$totalPagado = (int)$stmt->fetchColumn();
$totalRestante = max(0, $totalMesa - $totalPagado);

// Pago/estado por participante (último)
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
        $estadoPorPid[$r['participante_id']] = $r['estado'];
    }
}

// URL para compartir (lleva al index para unirse)
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST']
         . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
$joinUrl = $baseUrl . "/index.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($mesa['numero'] ? ('Mesa '.$mesa['numero']) : $mesa['codigo']) ?> - ChopCheck</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .chip.disabled { opacity: 0.5; cursor: not-allowed; }
    .chip.on.disabled { background: #e0e0e0; border-color: #ccc; }
    .badge { font-size: 0.9em; color: #666; }
    .qr { margin-top: 8px; }
    .inline { display: inline-block; }
  </style>
</head>
<body>
  <h1><?= htmlspecialchars($mesa['numero'] ? ('Mesa '.$mesa['numero']) : 'Mesa') ?><?= $mesa['nombre'] ? ' · '.htmlspecialchars($mesa['nombre']) : '' ?></h1>
  <p class="muted">Código: <?= htmlspecialchars($mesa['codigo']) ?></p>

  <div class="grid">
    <div class="card">
      <h2>Participantes</h2>
      <p><strong>Total pendiente:</strong> <span id="total_mesa"><?= centimos_a_euros($totalRestante) ?></span></p>
      <ul id="participantes_list">
        <?php foreach ($participantes as $p):
            $pid = $p['id'];
            $estado = $estadoPorPid[$pid] ?? null; // null | pendiente | pagado
            $badge = $estado === 'pagado' ? '✅ Pagado' : ($estado === 'pendiente' ? '🔒 Pendiente' : '');
        ?>
          <li>
            <?= htmlspecialchars($p['nombre']) ?> —
            <strong><?= centimos_a_euros($totales[$pid] ?? 0) ?></strong>
            <?php if ($badge): ?> <span class="badge">· <?= $badge ?></span><?php endif; ?>
            &nbsp;|&nbsp;
            <a href="resumen.php?codigo=<?= urlencode($codigo) ?>&pid=<?= $pid ?>">Tu consumo</a>
          </li>
        <?php endforeach; ?>
      </ul>

      <h3>Añadir participante</h3>
      <form id="form_add_participante" action="add_participante.php" method="post">
        <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
        <label>Apodo:
          <input type="text" name="nombre" maxlength="60" required>
        </label>
        <button type="submit">Añadir</button>
      </form>
    </div>

    <div class="card">
      <h2>Productos</h2>
      <?php if (!$items): ?>
        <p id="no_items_msg">Aún no hay productos.</p>
      <?php endif; ?>
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Precio</th>
            <th>Consumidores</th>
          </tr>
        </thead>
        <tbody id="items_tbody">
        <?php foreach ($items as $it):
            $cons = $consumosPorItem[$it['id']] ?? [];
            $nCons = count($cons);
        ?>
          <tr>
            <td><?= htmlspecialchars($it['nombre']) ?></td>
            <td>
              <?= centimos_a_euros($it['precio_centimos']) ?>
              <?= $nCons>1 ? "<span class='muted'>(compartido)</span>" : "" ?>
            </td>
            <td>
              <div class="chips">
              <?php foreach ($participantes as $p):
                  $pid = $p['id'];
                  $tiene = in_array($pid, $cons);
                  $estado = $estadoPorPid[$pid] ?? null;
                  $bloqueado = ($estado === 'pendiente' || $estado === 'pagado');
              ?>
                <form action="toggle_consumo.php" method="post" class="inline">
                  <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
                  <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                  <input type="hidden" name="participante_id" value="<?= $pid ?>">
                  <button
                    type="submit"
                    class="chip <?= $tiene ? 'on' : '' ?> <?= $bloqueado ? 'disabled' : '' ?>"
                    <?= $bloqueado ? 'disabled title="Bloqueado por pago pendiente o realizado"' : '' ?>
                  >
                    <?= htmlspecialchars($p['nombre']) ?>
                  </button>
                </form>
              <?php endforeach; ?>
              </div>

              <?php if ($nCons>0):
                $shares = dividir_centimos_equitable($it['precio_centimos'], $cons);
                $txt = [];
                foreach ($shares as $spid=>$c) {
                  $nombreP = null;
                  foreach ($participantes as $pp) { if ($pp['id'] == $spid) { $nombreP = $pp['nombre']; break; } }
                  $txt[] = htmlspecialchars($nombreP ?? ('#'.$spid)) . ': ' . centimos_a_euros($c);
                }
              ?>
                <div class="muted">Reparto → <?= implode(' · ', $txt) ?></div>
              <?php else: ?>
                <div class="muted warn">Sin consumidores: este ítem no se cobrará a nadie hasta que se asigne.</div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h3>Compartir</h3>
    <p>URL para unirse: <code><?= htmlspecialchars($joinUrl) ?></code></p>
    <div class="qr">
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($joinUrl) ?>" alt="QR para unirse">
    </div>
  </div>

  <script>
  const codigoMesa = "<?= htmlspecialchars($codigo) ?>";

  function euros(cents){const n=(cents/100).toFixed(2).replace('.',',');return n+' €';}
  function escapeHtml(s){return (s??'').toString().replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#39;' }[m]))}

  function renderParticipantes(data) {
    const ul = document.getElementById('participantes_list');
    const totalMesaEl = document.getElementById('total_mesa');
    if (totalMesaEl && typeof data.total_restante_centimos === 'number') {
      totalMesaEl.textContent = euros(data.total_restante_centimos);
    }
    ul.innerHTML = (data.participantes||[]).map(p => {
      const badge = p.estado === 'pagado' ? ' · <span class="badge">✅ Pagado</span>' :
                    (p.estado === 'pendiente' ? ' · <span class="badge">🔒 Pendiente</span>' : '');
      return `<li>${escapeHtml(p.nombre)} — <strong>${euros(p.total_centimos||0)}</strong>${badge}
              &nbsp;|&nbsp;<a href="resumen.php?codigo=${encodeURIComponent(codigoMesa)}&pid=${p.id}">Tu consumo</a></li>`;
    }).join('') || '<li class="muted">(sin participantes)</li>';
  }

  function renderItems(data) {
    const tbody = document.getElementById('items_tbody');
    const noItems = document.getElementById('no_items_msg');
    const participantes = data.participantes || [];
    const participantesById = {};
    participantes.forEach(p => participantesById[p.id] = p);

    if (!data.items || data.items.length === 0) {
      if (tbody) tbody.innerHTML = '';
      if (noItems) noItems.style.display = 'block';
      return;
    }
    if (noItems) noItems.style.display = 'none';

    tbody.innerHTML = data.items.map(it => {
      const nCons = (it.consumidores || []).length;
      const chips = participantes.map(p => {
        const tiene = (it.consumidores||[]).includes(p.id);
        const bloqueadoP = (p.estado === 'pendiente' || p.estado === 'pagado');
        return `
          <form action="toggle_consumo.php" method="post" class="inline">
            <input type="hidden" name="codigo" value="${codigoMesa}">
            <input type="hidden" name="item_id" value="${it.id}">
            <input type="hidden" name="participante_id" value="${p.id}">
            <button type="submit" class="chip ${tiene?'on':''} ${bloqueadoP?'disabled':''}" ${bloqueadoP?'disabled title="Bloqueado por pago pendiente o realizado"':''}>
              ${escapeHtml(p.nombre)}
            </button>
          </form>`;
      }).join('');

      let repartoTxt = '';
      if (nCons > 0 && it.shares) {
        const tmp = [];
        for (const pid in it.shares) {
          const pidNum = parseInt(pid, 10);
          const nombre = participantesById[pidNum]?.nombre || ('#'+pid);
          tmp.push(`${escapeHtml(nombre)}: ${euros(it.shares[pid])}`);
        }
        repartoTxt = `<div class="muted">Reparto → ${tmp.join(' · ')}</div>`;
      } else if (nCons === 0) {
        repartoTxt = `<div class="muted warn">Sin consumidores: este ítem no se cobrará a nadie hasta que se asigne.</div>`;
      }

      return `
        <tr>
          <td>${escapeHtml(it.nombre)}</td>
          <td>${euros(it.precio_centimos)} ${nCons>1?`<span class='muted'>(compartido)</span>`:''}</td>
          <td>
            <div class="chips">${chips}</div>
            ${repartoTxt}
          </td>
        </tr>`;
    }).join('');
  }

  async function refrescarMesa(){
    try{
      const r = await fetch('mesa_data.php?codigo=' + encodeURIComponent(codigoMesa), {cache:'no-store'});
      if(!r.ok) return;
      const data = await r.json();
      if (data && !data.error) {
        // Si la mesa está cerrada, EXPULSAR inmediatamente
        if (data.mesa && parseInt(data.mesa.cerrado,10) === 1) {
          window.location.href = 'index.php';
          return;
        }
        renderParticipantes(data);
        renderItems(data);
      }
    }catch(e){}
  }

  window.addEventListener('load', refrescarMesa);
  setInterval(refrescarMesa, 2000);
  </script>
</body>
</html>
