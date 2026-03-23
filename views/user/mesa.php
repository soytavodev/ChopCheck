<?php
// CONTROL DE SEGURIDAD: Si la mesa se acaba de cerrar, mandamos un aviso oculto
if (isset($mesa['cerrado']) && (int)$mesa['cerrado'] === 1) {
    echo '<div id="ticket-mesa"><div id="mesa-cerrada-flag"></div></div>';
    echo '<script>alert("La mesa ha sido cerrada por el administrador. Redirigiendo al inicio..."); window.location.href="index.php?route=home";</script>';
    exit; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Mesa <?= htmlspecialchars($mesa['codigo'] ?? '') ?> - ChopCheck</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Mesa <?= htmlspecialchars($mesa['numero'] ?? '') ?> <?= htmlspecialchars($mesa['nombre'] ?? '') ?></h1>
    <p>Código: <strong><?= htmlspecialchars($mesa['codigo'] ?? '') ?></strong></p>

    <div id="ticket-mesa">
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            
            <div style="border: 1px solid #ccc; padding: 15px; flex: 1; min-width: 300px; border-radius: 8px; background: var(--card-bg);">
                <h2>Participantes</h2>
                <p><strong>Total pendiente:</strong> <?= centimos_a_euros($totalRestante ?? 0) ?></p>
                <ul>
                    <?php foreach ($participantes as $p): 
                        $pid = $p['id'];
                        $estado = $estadoPorPid[$pid] ?? null;
                        $badge = $estado === 'pagado' ? '✅ Pagado' : ($estado === 'pendiente' ? '🔒 Pendiente' : '');
                    ?>
                        <li>
                            <?= htmlspecialchars($p['nombre']) ?> —
                            <strong><?= centimos_a_euros($totales[$pid] ?? 0) ?></strong>
                            <?= $badge ?>
                            <br>
                            <a href="index.php?route=resumen&codigo=<?= urlencode($mesa['codigo']) ?>&pid=<?= $pid ?>" style="font-size: 0.9rem;">Ver mi consumo</a>
                        </li>
                    <?php endforeach; ?>
                    <?php if(empty($participantes)): ?>
                        <li>No hay nadie en la mesa.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div style="border: 1px solid #ccc; padding: 15px; flex: 1; min-width: 300px; border-radius: 8px; background: var(--card-bg);">
                <h2>Productos consumidos</h2>
                <?php if (empty($items)): ?>
                    <p>Aún no hay productos.</p>
                <?php else: ?>
                    <table border="1" width="100%" style="border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Consumidores</th>
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
                                        <?= $nCons > 1 ? "<br><small style='color: #666;'>(compartido)</small>" : "" ?>
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
                                                        <?= $bloqueado ? 'disabled title="Bloqueado por pago pendiente o realizado"' : '' ?>
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
        <a href="index.php?route=home" style="display: inline-block; padding: 10px 20px; background: #ddd; border-radius: 6px; color: #333;">← Salir al inicio</a>
    </div>

    <script>
        setInterval(function() {
            // EL FIX ESTÁ AQUÍ: { cache: 'no-store' } obliga al móvil a no usar memoria antigua
            fetch(window.location.href, { cache: 'no-store' })
                .then(response => {
                    if (!response.ok) throw new Error('Error en red');
                    return response.text();
                })
                .then(html => {
                    let parser = new DOMParser();
                    let doc = parser.parseFromString(html, 'text/html');
                    
                    if (doc.getElementById('mesa-cerrada-flag')) {
                        alert("La mesa ha sido cerrada. Serás redirigido al inicio de la app.");
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
