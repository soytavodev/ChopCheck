<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ChopCheck - Inicio</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/favicon.png" type="image/png">
</head>
<body style="text-align: center; padding-top: 5vh;">
    
    <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 30px;">
        <img src="img/logo.png" alt="ChopCheck Logo" style="max-width: 130px; margin-bottom: 15px;">
        <h1 style="border: none; margin: 0; padding: 0;">ChopCheck</h1>
    </div>

    <?php if (!empty($error)): ?>
        <div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; margin: 0 auto 20px auto; max-width: 400px; text-align: left;">
            <strong>Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div style="background: var(--card-bg); padding: 30px; border-radius: 12px; box-shadow: var(--shadow-md); max-width: 400px; margin: 0 auto; text-align: left;">
        <h2 style="margin-top: 0;">Unirse a una mesa</h2>
        <form action="index.php?route=join" method="post">
            <label>Código de mesa:</label>
            <input type="text" name="codigo" maxlength="8" required placeholder="Ej: A1B2C3" style="text-transform: uppercase;">
            
            <label>Tu apodo:</label>
            <input type="text" name="nombre" maxlength="60" required placeholder="Ej: Juan">
            
            <button type="submit" style="width: 100%; padding: 12px; font-size: 1.1rem; margin-top: 10px;">Entrar a la Mesa</button>
        </form>
        <p style="text-align: center; margin-top: 15px;"><small>También puedes llegar aquí escaneando el QR que te entregan en mesa.</small></p>
    </div>

    <hr style="max-width: 400px; margin: 30px auto;">
    <p><small>¿Eres personal del local? <a href="index.php?route=admin_login">Ir a la Caja</a></small></p>
</body>
</html>
