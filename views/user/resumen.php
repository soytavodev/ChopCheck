<!DOCTYPE html>
<html lang="<?= getLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('tu_consumo') ?> - ChopCheck</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div style="text-align: right; margin-bottom: 10px;">
        <a href="index.php?route=change_lang&l=es" style="text-decoration: none; font-size: 1.5rem; opacity: <?= getLang() == 'es' ? '1' : '0.5' ?>;">🇪🇸</a>
        <a href="index.php?route=change_lang&l=en" style="text-decoration: none; font-size: 1.5rem; margin-left: 10px; opacity: <?= getLang() == 'en' ? '1' : '0.5' ?>;">🇬🇧</a>
    </div>

    <h1><?= __('tu_consumo') ?></h1>
    <p style="font-size: 1.1rem; margin-bottom: 5px;">
        <?= htmlspecialchars($mesa['seccion'] ?? 'Local') ?>, <?= htmlspecialchars($mesa['nombre'] ?? '') ?>
    </p>
    <p style="margin-bottom: 15px;"><strong><?= __('codigo') ?>:</strong> <span style="letter-spacing: 1px; color: var(--wood-primary);"><?= htmlspecialchars($codigo) ?></span></p>

    <h2><?= __('participante') ?>: <?= htmlspecialchars($part['nombre']) ?></h2>

    <?php if (!empty($msg)): ?>
        <div style="background: var(--danger-light); color: var(--danger); padding: 10px; border-radius: 4px; margin-bottom: 20px;">
            <strong><?= htmlspecialchars($msg) ?></strong>
        </div>
    <?php endif; ?>

    <?php if (empty($detalle)): ?>
        <p><?= __('aun_no_productos') ?></p>
    <?php else: ?>
        <table border="1" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th><?= __('producto') ?></th>
                    <th><?= __('precio_original') ?></th>
                    <th><?= __('tu_parte') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalle as $d): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($d['nombre']) ?> 
                            <?= $d['n_cons'] > 1 ? "<small style='color: #666;'>".__('compartido')."</small>" : "" ?>
                        </td>
                        <td><?= centimos_a_euros($d['precio']) ?></td>
                        <td><strong><?= centimos_a_euros($d['mi_parte']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" style="text-align: right;"><?= __('total_actual') ?></th>
                    <th><?= centimos_a_euros($total) ?></th>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <div style="border: 2px solid var(--wood-primary); padding: 15px; border-radius: 8px; background: var(--card-bg);">
        <h3><?= __('pago_caja_pin') ?></h3>

        <?php if ($estadoPago === 'pagado'): ?>
            <p style="color: var(--success);">✅ <strong><?= __('pago_validado') ?></strong></p>

        <?php elseif ($estadoPago === 'pendiente'): ?>
            <p>🔒 <strong><?= __('pago_pendiente') ?></strong> <?= __('muestra_pin') ?></p>
            <p style="font-size: 28px; font-weight: bold; letter-spacing: 5px; text-align: center; background: #fff; border: 1px dashed #ccc; padding: 10px; margin: 15px 0;">
                <?= htmlspecialchars($pinPago) ?>
            </p>
            <p><strong><?= __('importe_bloqueado') ?></strong> <?= centimos_a_euros($totalBloqueado ?? 0) ?></p>

        <?php else: ?>
            <form action="index.php?route=generar_pin" method="post">
                <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo) ?>">
                <input type="hidden" name="pid" value="<?= $pid ?>">
                <button type="submit" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 10px;">
                    <?= __('generar_pin_bloquear') ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <br>
    <a href="index.php?route=mesa&codigo=<?= urlencode($codigo) ?>" style="display:inline-block; margin-top:15px;"><?= __('volver_mesa') ?></a>

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
                    alert('✅ <?= __('pago_validado') ?>');
                    window.location.href = 'index.php?route=home';
                }
            } catch (e) {}
        }
        setInterval(checkPago, 2000);
    </script>
    <?php endif; ?>
</body>
</html>
