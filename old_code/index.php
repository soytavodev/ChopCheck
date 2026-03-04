<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'db.php';
require 'helpers.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>ChopCheck - Inicio (Usuarios)</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <h1>ChopCheck</h1>

  <div class="card">
    <h2>Unirse a una mesa</h2>
    <form action="unirse_mesa.php" method="post">
      <label>Código de mesa:
        <input type="text" name="codigo" maxlength="8" required>
      </label>
      <label>Tu apodo:
        <input type="text" name="nombre" maxlength="60" required>
      </label>
      <button type="submit">Entrar</button>
    </form>
    <p class="muted">También puedes llegar aquí escaneando el QR que te entregan en mesa.</p>
  </div>

  <p class="muted">¿Eres personal del local? <a href="admin.php">Ir a vista admin</a></p>
</body>
</html>
