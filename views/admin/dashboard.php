<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - ChopCheck</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/chopcheck.png" type="image/png">
</head>
<body>
    
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="img/logo.png" alt="ChopCheck Logo" style="max-height: 40px;">
            <h1 style="margin: 0; border: none; padding: 0;">Caja - ChopCheck</h1>
        </div>
        <div>
            Hola, <strong><?= htmlspecialchars($_SESSION['admin_username']) ?></strong> | 
            <a href="index.php?route=admin_logout" style="color: var(--danger);">Cerrar sesión</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background: var(--danger-light); color: var(--danger); padding: 15px; border-left: 5px solid var(--danger); margin-bottom: 20px; border-radius: 4px;">
            <strong><?= htmlspecialchars($error) ?></strong>
        </div>
    <?php endif; ?>

    <?php if (!empty($msg)): ?>
        <div style="background: var(--success-light); color: var(--success); padding: 15px; border-left: 5px solid var(--success); margin-bottom: 20px; border-radius: 4px;">
            <strong><?= htmlspecialchars($msg) ?></strong>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px;">
        <div style="background: var(--card-bg); padding: 20px; border-radius: 8px; flex: 1; min-width: 300px; box-shadow: var(--shadow-sm); border-top: 4px solid var(--wood-primary);">
            <h2 style="margin-top:0;">Validar Pago (Cobrar PIN)</h2>
            <form action="index.php?route=admin_validar_pago" method="post">
                <label>Código de la Mesa:</label>
                <input type="text" name="codigo" required maxlength="8" placeholder="Ej: A1B2C3" style="text-transform: uppercase;">

                <label>PIN del Cliente:</label>
                <input type="text" name="pin" required maxlength="6" style="font-size: 20px; letter-spacing: 5px; text-align: center;">

                <button type="submit" style="width: 100%; background-color: var(--success);">
                    Validar y Cobrar
                </button>
            </form>
        </div>

        <div style="background: var(--card-bg); padding: 20px; border-radius: 8px; flex: 1; min-width: 300px; box-shadow: var(--shadow-sm); border-top: 4px solid var(--text-main);">
            <h2 style="margin-top:0;">Abrir / Crear Mesa</h2>
            <form action="index.php?route=admin_crear_mesa" method="post">
                <label>Número físico de la mesa:</label>
                <input type="number" name="numero" required min="1" placeholder="Ej: 5">

                <label>Descripción (Opcional):</label>
                <input type="text" name="nombre" placeholder="Terraza, Barra, etc.">

                <button type="submit" style="width: 100%;">
                    Generar Código y Abrir Mesa
                </button>
            </form>
            <p style="font-size: 12px; color: var(--text-muted); margin-top: 10px;">Si la mesa estaba cerrada, se reabrirá con un código nuevo por seguridad.</p>
        </div>
    </div>

    <h2>Plano del Local</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
        <?php if (empty($mesas)): ?>
            <p style="color: var(--text-muted);">No hay mesas registradas en el sistema todavía.</p>
        <?php else: ?>
            <?php foreach ($mesas as $m): ?>
                <div style="background: var(--card-bg); border-radius: 8px; padding: 20px; box-shadow: var(--shadow-sm); position: relative; overflow: hidden; opacity: <?= $m['cerrado'] ? '0.6' : '1' ?>;">
                    
                    <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background-color: <?= $m['cerrado'] ? 'var(--danger)' : 'var(--success)' ?>;"></div>
                    
                    <h3 style="margin-top: 0; margin-bottom: 5px;">Mesa <?= htmlspecialchars($m['numero']) ?> <?= htmlspecialchars($m['nombre'] ? ' - '.$m['nombre'] : '') ?></h3>
                    
                    <p style="margin: 0 0 10px 0; font-size: 14px;">
                        Código: <strong style="font-size: 18px; letter-spacing: 2px; color: var(--wood-primary);"><?= htmlspecialchars($m['codigo']) ?></strong>
                    </p>
                    
                    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">
                        <span style="display: block; margin-bottom: 3px;">👥 Participantes: <strong><?= (int)$m['num_participantes'] ?></strong></span>
                        <span style="display: block;">🍔 Ítems pedidos: <strong><?= (int)$m['num_items'] ?></strong></span>
                    </div>
                    
                    <div style="border-top: 1px solid var(--border-color); padding-top: 15px; text-align: right;">
                        <a href="index.php?route=admin_mesa&codigo=<?= urlencode($m['codigo']) ?>" style="background: var(--wood-primary); color: white; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 14px; display: inline-block;">
                            Gestionar Mesa
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>
