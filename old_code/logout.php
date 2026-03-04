<?php
// login.php — acceso de personal del local
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'auth.php';
$pdo = db();

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username && $password) {
        $st = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND activo = 1");
        $st->execute([$username]);
        $adm = $st->fetch(PDO::FETCH_ASSOC);

        if ($adm && password_verify($password, $adm['pass_hash'])) {
            admin_login((int)$adm['id'], $adm['username']);
            header('Location: admin.php');
            exit;
        } else {
            $err = "Credenciales inválidas.";
        }
    } else {
        $err = "Introduce usuario y contraseña.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Acceso personal - ChopCheck</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <h1>Acceso personal del local</h1>

  <?php if ($err): ?>
    <div class="card"><strong><?= htmlspecialchars($err) ?></strong></div>
  <?php endif; ?>

  <div class="card">
    <form method="post" action="login.php">
      <label>Usuario:
        <input type="text" name="username" maxlength="50" required>
      </label>
      <label>Contraseña:
        <input type="password" name="password" required>
      </label>
      <button type="submit">Entrar</button>
    </form>
  </div>

  <p><a href="index.php">← Volver</a></p>
</body>
</html>
