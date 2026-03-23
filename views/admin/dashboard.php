<!DOCTYPE html>
<html lang="es">
<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Plano del Local - ChopCheck</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/chopcheck.png" type="image/png">
    <style>
        .grid-mesas {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .mesa-card {
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 120px;
        }
        .mesa-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        /* Semáforo de colores */
        .mesa-blanca { background: #ffffff; border: 2px solid #e0e0e0; color: #555; }
        .mesa-naranja { background: #fff4e6; border: 2px solid #ff9900; }
        .mesa-verde { background: #e6f4ea; border: 2px solid #28a745; animation: pulse 2s infinite; }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
    </style>
</head>
<body>
    
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="img/logo.png" alt="ChopCheck Logo" style="max-height: 40px;">
            <h1 style="margin: 0; border: none; padding: 0;">Plano del Local</h1>
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

    <div style="background: var(--card-bg); padding: 20px; border-radius: 8px; box-shadow: var(--shadow-sm); border-top: 4px solid var(--success); margin-bottom: 30px; max-width: 500px;">
        <h2 style="margin-top:0; font-size: 1.2rem;">Validar Pago (Cobrar PIN)</h2>
        <form action="index.php?route=admin_validar_pago" method="post" style="display: flex; gap: 10px; margin-bottom: 0;">
            <input type="text" name="codigo" required maxlength="8" placeholder="Código Mesa" style="width: 30%; text-transform: uppercase; margin-bottom: 0;">
            <input type="text" name="pin" required maxlength="6" placeholder="PIN Cliente" style="width: 40%; font-size: 18px; letter-spacing: 2px; text-align: center; margin-bottom: 0;">
            <button type="submit" style="width: 30%; background-color: var(--success); padding: 10px;">Cobrar</button>
        </form>
    </div>

    <?php foreach ($mesasPorSeccion as $seccion => $mesasSec): ?>
        <h2 style="border-bottom: 1px solid #ccc; padding-bottom: 5px; color: var(--wood-primary);"><?= htmlspecialchars($seccion) ?></h2>
        <div class="grid-mesas">
            <?php foreach ($mesasSec as $m): 
                // Lógica del Semáforo
                $clase = 'mesa-blanca';
                if ((int)$m['cerrado'] === 0) {
                    $clase = ((int)$m['pagos_pendientes'] > 0) ? 'mesa-verde' : 'mesa-naranja';
                }
            ?>
                <?php if ((int)$m['cerrado'] === 1): ?>
                    <form action="index.php?route=admin_crear_mesa" method="post" style="margin:0;">
                        <input type="hidden" name="mesa_id" value="<?= $m['id'] ?>">
                        <button type="submit" class="mesa-card <?= $clase ?>" style="width: 100%; font-size: 1.1rem; font-weight: bold;">
                            <span style="font-size: 1.5rem; display:block; margin-bottom:5px;">🍽️</span>
                            <?= htmlspecialchars($m['nombre']) ?>
                            <span style="display:block; font-size: 0.8rem; font-weight: normal; margin-top: 5px;">Libre</span>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="index.php?route=admin_mesa&codigo=<?= urlencode($m['codigo']) ?>" class="mesa-card <?= $clase ?>">
                        <span style="font-size: 1.5rem; display:block; margin-bottom:5px;">
                            <?= $clase === 'mesa-verde' ? '💳' : '👥' ?>
                        </span>
                        <strong style="font-size: 1.1rem;"><?= htmlspecialchars($m['nombre']) ?></strong>
                        <span style="display:block; font-size: 0.8rem; margin-top: 5px; background: rgba(0,0,0,0.1); padding: 2px 5px; border-radius: 4px;">
                            Cód: <strong><?= htmlspecialchars($m['codigo']) ?></strong>
                        </span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

</body>
</html>
