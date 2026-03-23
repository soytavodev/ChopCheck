<!DOCTYPE html>
<html lang="es">
<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Acceso Personal - ChopCheck</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/favicon.png" type="image/png">
</head>
<body style="background: var(--bg-color); font-family: sans-serif; padding: 50px; display: flex; flex-direction: column; align-items: center;">
    
    <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 20px;">
        <img src="img/logo.png" alt="ChopCheck Logo" style="max-width: 120px; margin-bottom: 10px;">
        <h1 style="border: none; margin: 0; padding: 0;">ChopCheck</h1>
    </div>

    <div style="width: 100%; max-width: 400px; background: var(--card-bg); padding: 30px; border-radius: 8px; box-shadow: var(--shadow-md);">
        <h2 style="margin-top: 0;">Acceso de Personal</h2>
        
        <?php if (!empty($error)): ?>
            <div style="color: #721c24; padding: 10px; border: 1px solid #f5c6cb; margin-bottom: 15px; background: #f8d7da; border-radius: 4px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($msg)): ?>
            <div style="color: #155724; padding: 10px; border: 1px solid #c3e6cb; margin-bottom: 15px; background: #d4edda; border-radius: 4px;">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form action="index.php?route=admin_do_login" method="post">
            <label>Usuario:</label>
            <input type="text" name="username" required>
            
            <label>Contraseña:</label>
            <input type="password" name="password" required>
            
            <button type="submit" style="width: 100%; padding: 12px; margin-top: 10px;">Entrar a Caja</button>
        </form>
        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php?route=home" style="color: var(--text-muted); font-size: 14px;">← Volver a la web de clientes</a>
        </div>
    </div>

</body>
</html>
