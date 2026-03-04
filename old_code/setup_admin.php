<?php
// setup_admin.php — Úsalo una vez para crear el primer admin. Luego bórralo por seguridad.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
$pdo = db();

// Crear tabla si no existe (por si no corriste migrations_auth.sql)
$pdo->exec("
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  activo TINYINT(1) DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ¿Ya hay algún admin?
$ya = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if (!$username || !$password) {
        $msg = "Usuario y contraseña son requeridos.";
    } elseif ($password !== $password2) {
        $msg = "Las contraseñas no coinciden.";
    } else {
        // Si no hay admins, o permitimos crear otro (puedes limitarlo si quieres)
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $st = $pdo->prepare("INSERT INTO admins (username, pass_hash, activo) VALUES (?, ?, 1)");
            $st->execute([$username, $hash]);
            $ok = true;
        } catch (Exception $e) {
            $msg = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Setup Admin - ChopCheck</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <h1>Crear usuario Admin</h1>
  <?php if (!empty($ok)): ?>
    <div class="card"><strong>✅ Admin creado correctamente.</strong> Puedes ir a <a href="login.php">login</a>. Luego, borra <code>setup_admin.php</code>.</div>
  <?php else: ?>
    <?php if (!empty($msg)): ?><div class="card"><strong><?= htmlspecialchars($msg) ?></strong></div><?php endif; ?>
    <div class="card">
      <form method="post">
        <label>Usuario (login):
          <input type="text" name="username" maxlength="50" required>
        </label>
        <label>Contraseña:
          <input type="password" name="password" required>
        </label>
        <label>Repite contraseña:
          <input type="password" name="password2" required>
        </label>
        <button type="submit">Crear admin</button>
      </form>
    </div>
    <?php if ($ya > 0): ?>
      <p class="muted">Nota: Ya existen <?= (int)$ya ?> usuario(s) admin; puedes crear más si lo deseas y luego restringirlo.</p>
    <?php endif; ?>
  <?php endif; ?>
</body>
</html>
