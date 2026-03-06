<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu consumo - ChopCheck</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Tu consumo</h1>
    <p>Mesa <?= htmlspecialchars($mesa['numero'] ?? '') ?> <?= htmlspecialchars($mesa['nombre'] ?? '') ?></p>
    <h2>Participante: <?= htmlspecialchars($part['nombre']) ?></h2>

    <?php if (!empty($msg)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; margin-bottom: 20px;">
            <strong><?= htmlspecialchars($msg) ?></strong>
        </div>
    <?php endif; ?>

    <?php if (empty($detalle)): ?>
        <p>No tienes productos asignados todavía.</p>
    <?php else: ?>
        <table border="1" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio Original</th>
                    <th>Tu parte a pagar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalle as $d): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($d['nombre']) ?> 
                            <?= $d['n_cons'] > 1 ? "<small style='color: #666;'>(compartido con ".($d['n_cons']-1)." más)</small>" : "" ?>
                        </td>
                        <td><?= centimos_a_euros($d['precio']) ?></td>
                        <td><strong><?= centimos_a_euros($d['mi_parte']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" style="text-align: right;">Total actual:</th>
                    <th><?= centimos_a_euros($total) ?></th>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <div style="border: 2px solid #0056b3; padding: 15px; border-radius: 8px; background: #f8f9fa;">
        <h3>Pago en caja con PIN</h3>

        <?php if ($estadoPago === 'pagado'): ?>
            <p style="color: green;">✅ <strong>Pago validado</strong>. Gracias.</p>
            <p><small>Serás redirigido automáticamente al inicio…</small></p>

        <?php elseif ($estadoPago === 'pendiente'): ?>
            <p>🔒 <strong>Pago pendiente</strong>. Muestra este <strong>PIN</strong> en caja:</p>
            <p style="font-size: 28px; font-weight: bold; letter-spacing: 5px; text-align: center; background: #fff; border: 1px dashed #ccc; padding: 10px;">
                <?= htmlspecialchars($pinPago) ?>
            </p>
            <p><strong>Importe bloqueado:</strong> <?= centimos_a_euros($totalBloqueado ?? 0) ?></p>
            <p><small>Cuando el cajero lo valide, te sacaremos de la mesa automáticamente.</small></p>

        <?php else: ?>
            <form action="index.php?route=generar_pin" method="post">
                <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
                <input type="hidden" name="pid" value="<?= $pid ?>">
                <button type="submit" style="padding: 10px 15px; font-size: 16px; background: #0056b3; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Generar PIN y bloquear mi consumo
                </button>
            </form>
            <p><small>Se tomará el total actual como importe final a pagar.</small></p>
        <?php endif; ?>
    </div>

    <br>
    <a href="index.php?route=mesa&codigo=<?= urlencode($codigo) ?>">← Volver a la mesa</a>

    <?php if ($estadoPago !== 'pagado'): ?>
    <script>
        const codigo = "<?= htmlspecialchars($codigo) ?>";
        const pid = <?= (int)$pid ?>;
        
        async function checkPago() {
            try {
                const r = await fetch(`index.php?route=api_pago_estado&codigo=${encodeURIComponent(codigo)}&pid=${pid}`, {cache: 'no-store'});
                if (!r.ok) return;
                const data = await r.json();
                if (data && data.estado === 'pagado') {
                    alert('✅ Pago validado. Gracias.');
                    window.location.href = 'index.php?route=home';
                }
            } catch (e) {
                console.error(e);
            }
        }
        
        // Consultar a la base de datos cada 2 segundos
        setInterval(checkPago, 2000);
    </script>
    <?php endif; ?>
</body>
</html>
