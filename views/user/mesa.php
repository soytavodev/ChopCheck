<?php
if (isset($mesa['cerrado']) && (int)$mesa['cerrado'] === 1) {
    echo '<div id="ticket-mesa"><div id="mesa-cerrada-flag"></div></div>';
    echo '<script>alert("'.__('alerta_cierre').'"); window.location.href="index.php?route=home";</script>';
    exit; 
}
?>
<!DOCTYPE html>
<html lang="<?= getLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('mesa') ?> <?= htmlspecialchars($mesa['codigo'] ?? '') ?> - ChopCheck</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div style="text-align: right; margin-bottom: 10px;">
        <a href="index.php?route=change_lang&l=es" style="text-decoration: none; font-size: 1.5rem; opacity: <?= getLang() == 'es' ? '1' : '0.5' ?>;">🇪🇸</a>
        <a href="index.php?route=change_lang&l=en" style="text-decoration: none; font-size: 1.5rem; margin-left: 10px; opacity: <?= getLang() == 'en' ? '1' : '0.5' ?>;">🇬🇧</a>
    </div>

    <h1><?= htmlspecialchars($mesa['seccion'] ?? 'Local') ?>, <?= htmlspecialchars($mesa['nombre'] ?? '') ?></h1>
    <p><?= __('codigo') ?>: <strong><?= htmlspecialchars($mesa['codigo'] ?? '') ?></strong></p>

    <div id="ticket-mesa">
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            
            <div style="border: 1px solid var(--border-color); padding: 15px; flex: 1; min-width: 300px; border-radius: 8px; background: var(--card-bg);">
                <h2><?= __('participantes') ?></h2>
                <p><strong><?= __('total_pendiente') ?>:</strong> <?= centimos_a_euros($totalRestante ?? 0) ?></p>
                <ul>
                    <?php foreach ($participantes as $p): 
                        $pid = $p['id'];
                        $estado = $estadoPorPid[$pid] ?? null;
                        $badge = $estado === 'pagado' ? __('pagado') : ($estado === 'pendiente' ? __('pendiente') : '');
                    ?>
                        <li>
                            <?= htmlspecialchars($p['nombre']) ?> —
                            <strong><?= centimos_a_euros($totales[$pid] ?? 0) ?></strong>
                            <?= $badge ?>
                            <br>
                            <a href="index.php?route=resumen&codigo=<?= urlencode($mesa['codigo']) ?>&pid=<?= $pid ?>" style="font-size: 0.9rem;"><?= __('ver_consumo') ?></a>
                        </li>
                    <?php endforeach; ?>
                    <?php if(empty($participantes)): ?>
                        <li><?= __('nadie_mesa') ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div style="border: 1px solid var(--border-color); padding: 15px; flex: 1; min-width: 300px; border-radius: 8px; background: var(--card-bg);">
                <h2><?= __('productos_consumidos') ?></h2>
                <?php if (empty($items)): ?>
                    <p><?= __('aun_no_productos') ?></p>
                <?php else: ?>
                    <table border="1" width="100%" style="border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th><?= __('producto') ?></th>
                                <th><?= __('precio') ?></th>
                                <th><?= __('consumidores') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): 
                                $cons = $consumosPorItem[$it['id']] ?? [];
                                $nCons = count($cons);
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($it['nombre']) ?></td>
                                    <td>
                                        <?= centimos_a_euros($it['precio_centimos']) ?>
                                        <?= $nCons > 1 ? "<br><small style='color: #666;'>".__('compartido')."</small>" : "" ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <?php foreach ($participantes as $p): 
                                                $pid = $p['id'];
                                                $tiene = in_array($pid, $cons);
                                                $estado = $estadoPorPid[$pid] ?? null;
                                                $bloqueado = ($estado === 'pendiente' || $estado === 'pagado');
                                            ?>
                                                <form action="index.php?route=toggle_consumo" method="post" style="margin: 0;">
                                                    <input type="hidden" name="codigo" value="<?= htmlspecialchars($mesa['codigo']) ?>">
                                                    <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                                    <input type="hidden" name="participante_id" value="<?= $pid ?>">
                                                    <button type="submit" 
                                                        <?= $bloqueado ? 'disabled' : '' ?>
                                                        style="cursor: <?= $bloqueado ? 'not-allowed' : 'pointer' ?>; 
                                                               padding: 8px 12px; 
                                                               border: 1px solid <?= $tiene ? '#28a745' : '#ccc' ?>; 
                                                               border-radius: 15px; 
                                                               background-color: <?= $tiene ? '#d4edda' : '#f8f9fa' ?>; 
                                                               color: <?= $tiene ? '#155724' : '#333' ?>;
                                                               opacity: <?= $bloqueado ? '0.5' : '1' ?>;
                                                               min-width: 60px;">
                                                        <?= htmlspecialchars($p['nombre']) ?>
                                                    </button>
                                                </form>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <a href="index.php?route=home" style="display: inline-block; padding: 10px 20px; background: var(--border-color); border-radius: 6px; color: var(--text-main);"><?= __('salir_inicio') ?></a>
    </div>

    <script>
        setInterval(function() {
            fetch(window.location.href, { cache: 'no-store' })
                .then(response => {
                    if (!response.ok) throw new Error('Error en red');
                    return response.text();
                })
                .then(html => {
                    let parser = new DOMParser();
                    let doc = parser.parseFromString(html, 'text/html');
                    
                    if (doc.getElementById('mesa-cerrada-flag')) {
                        alert("<?= __('alerta_cierre') ?>");
                        window.location.href = 'index.php?route=home';
                        return; 
                    }
                    
                    let ticketNuevo = doc.getElementById('ticket-mesa');
                    if (ticketNuevo) {
                        document.getElementById('ticket-mesa').innerHTML = ticketNuevo.innerHTML;
                    }
                })
                .catch(error => console.error('Fallo de sincronización:', error));
        }, 2000);
    </script>
</body>
</html>
