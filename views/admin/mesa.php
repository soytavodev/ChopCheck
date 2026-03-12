<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Mesa <?= htmlspecialchars($mesa['codigo']) ?> - Admin ChopCheck</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body style="font-family: sans-serif; padding: 20px; background: #f4f4f4;">

    <div style="margin-bottom: 20px;">
        <a href="index.php?route=admin_dashboard" style="text-decoration: none; color: #0056b3;">← Volver al Dashboard</a>
    </div>

    <h1>Gestionar Mesa <?= htmlspecialchars($mesa['numero'] ?? '') ?> <?= htmlspecialchars($mesa['nombre'] ? ' - '.$mesa['nombre'] : '') ?></h1>
    <p>Código: <strong style="font-size: 20px; color: #0056b3; letter-spacing: 2px;"><?= htmlspecialchars($mesa['codigo']) ?></strong></p>

    <?php if (!empty($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-left: 5px solid #dc3545; margin-bottom: 15px;"><strong><?= htmlspecialchars($error) ?></strong></div>
    <?php endif; ?>
    <?php if (!empty($msg)): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-left: 5px solid #28a745; margin-bottom: 15px;"><strong><?= htmlspecialchars($msg) ?></strong></div>
    <?php endif; ?>

    <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #ccc; margin-bottom: 20px;">
        <h3 style="margin-top: 0;">Estado: <?= $mesa['cerrado'] ? '<span style="color:red;">CERRADA</span>' : '<span style="color:green;">ABIERTA</span>' ?></h3>
        <form action="index.php?route=admin_toggle_estado" method="post">
            <input type="hidden" name="codigo" value="<?= htmlspecialchars($mesa['codigo']) ?>">
            <input type="hidden" name="cerrado" value="<?= $mesa['cerrado'] ? '0' : '1' ?>">
            <button type="submit" style="background: <?= $mesa['cerrado'] ? '#28a745' : '#dc3545' ?>; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                <?= $mesa['cerrado'] ? 'Abrir Mesa' : 'Cerrar Mesa (Bloquea pedidos)' ?>
            </button>
        </form>
    </div>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        
        <div style="flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 8px; border: 1px solid #ccc;">
            <h2>Catálogo Rápido</h2>
            <form method="get" action="index.php" style="margin-bottom: 15px;">
                <input type="hidden" name="route" value="admin_mesa">
                <input type="hidden" name="codigo" value="<?= htmlspecialchars($mesa['codigo']) ?>">
                <input type="text" name="q" value="<?= htmlspecialchars($busqueda ?? '') ?>" placeholder="Buscar en catálogo..." style="padding: 6px; width: 60%;">
                <button type="submit" style="padding: 6px;">Buscar</button>
            </form>

            <table border="1" width="100%" style="border-collapse: collapse; text-align: left;">
                <tr style="background: #eee;"><th>Producto</th><th>Precio</th><th>Acción</th></tr>
                <?php foreach ($articulos as $a): ?>
                    <tr>
                        <td style="padding: 5px;"><?= htmlspecialchars($a['nombre']) ?></td>
                        <td style="padding: 5px;"><?= centimos_a_euros($a['precio_centimos']) ?></td>
                        <td style="padding: 5px;">
                            <form action="index.php?route=admin_add_articulo" method="post" style="margin: 0;">
                                <input type="hidden" name="codigo" value="<?= htmlspecialchars($mesa['codigo']) ?>">
                                <input type="hidden" name="articulo_id" value="<?= $a['id'] ?>">
                                <input type="number" name="cantidad" value="1" min="1" max="10" style="width: 40px;">
                                <button type="submit" <?= $mesa['cerrado'] ? 'disabled' : '' ?>>Añadir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h3 style="margin-top: 30px;">Añadir Manualmente</h3>
            <form action="index.php?route=admin_add_manual" method="post">
                <input type="hidden" name="codigo" value="<?= htmlspecialchars($mesa['codigo']) ?>">
                <input type="text" name="nombre" placeholder="Nombre (Ej: Carajillo)" required style="padding: 6px; margin-bottom: 5px; width: 100%; box-sizing: border-box;">
                <input type="text" name="precio" placeholder="Precio (Ej: 2,50)" required style="padding: 6px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                <button type="submit" <?= $mesa['cerrado'] ? 'disabled' : '' ?> style="width: 100%; padding: 8px;">Añadir Manual</button>
            </form>
        </div>

        <div style="flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 8px; border: 1px solid #ccc;">
            <h2>Resumen Actual</h2>
            
            <h3>Participantes (<?= count($participantes) ?>)</h3>
            <ul>
                <?php foreach ($participantes as $p): ?>
                    <li><?= htmlspecialchars($p['nombre']) ?></li>
                <?php endforeach; ?>
                <?php if(empty($participantes)) echo "<li style='color: #888;'>Nadie se ha unido aún.</li>"; ?>
            </ul>

            <h3>Ítems en la Mesa (<?= count($items) ?>)</h3>
            <?php if (empty($items)): ?>
                <p style="color: #888;">La mesa no ha pedido nada.</p>
            <?php else: ?>
                <ul style="max-height: 400px; overflow-y: auto; list-style: none; padding-left: 0;">
                    <?php foreach ($items as $it): ?>
                        <li style="margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 5px; display: flex; justify-content: space-between; align-items: center; background: #fafafa; padding: 10px; border-radius: 5px;">
                            
                            <div style="font-size: 16px;">
                                <strong><?= htmlspecialchars($it['nombre']) ?></strong> — <?= centimos_a_euros($it['precio_centimos']) ?>
                            </div>
                            
                            <form action="index.php?route=admin_delete_item" method="post" style="margin: 0;" onsubmit="return confirm('¿Seguro que quieres eliminar este producto de la cuenta?');">
                                <input type="hidden" name="codigo" value="<?= htmlspecialchars($mesa['codigo']) ?>">
                                <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                <button type="submit" <?= $mesa['cerrado'] ? 'disabled' : '' ?> style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: <?= $mesa['cerrado'] ? 'not-allowed' : 'pointer' ?>; font-size: 14px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                    ✖ Borrar
                                </button>
                            </form>
                            
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
