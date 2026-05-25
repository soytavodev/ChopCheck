<!DOCTYPE html>
<html lang="<?= getLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChopCheck</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="img/favicon.png" type="image/png">
</head>
<body style="text-align: center; padding-top: 5vh;">
    
    <div style="text-align: right; margin-bottom: 10px;">
        <a href="index.php?route=change_lang&l=es" style="text-decoration: none; font-size: 1.5rem; opacity: <?= getLang() == 'es' ? '1' : '0.5' ?>;">🇪🇸</a>
        <a href="index.php?route=change_lang&l=en" style="text-decoration: none; font-size: 1.5rem; margin-left: 10px; opacity: <?= getLang() == 'en' ? '1' : '0.5' ?>;">🇬🇧</a>
    </div>

    <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 20px;">
        <img src="img/logo.png" alt="ChopCheck Logo" style="max-width: 130px; margin-bottom: 15px;">
        <h1 style="border: none; margin: 0; padding: 0;">ChopCheck</h1>
    </div>

    <?php if (!empty($error)): ?>
        <div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; margin: 0 auto 20px auto; max-width: 400px; text-align: left;">
            <strong>Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div style="max-width: 400px; margin: 0 auto; background: var(--card-bg); padding: 30px; border-radius: 8px; box-shadow: var(--shadow-md); text-align: left;">
        
        <h2 style="color: var(--wood-primary); margin-top: 0; margin-bottom: 20px; font-weight: 500; text-align: center;">
            <?= __('titulo_home') ?>
        </h2>
        
        <form action="index.php?route=join" method="post">
            <input type="text" name="codigo" placeholder="<?= __('codigo_mesa') ?>" required maxlength="8" style="text-transform: uppercase; text-align: center; font-size: 1.2rem; letter-spacing: 2px;">
            <input type="text" name="nombre" placeholder="<?= __('tu_nombre') ?>" required maxlength="60" style="text-align: center; font-size: 1.1rem;">
            <button type="submit" style="width: 100%; font-size: 1.1rem; padding: 15px; margin-top: 10px;">
                <?= __('unirse') ?>
            </button>
        </form>
    </div>

    <hr style="max-width: 400px; margin: 30px auto;">
    <p style="text-align: center;">
        <small><?= __('acceso_personal') ?> <a href="index.php?route=admin_login"><?= __('ir_caja') ?></a></small>
    </p>

</body>
</html>
