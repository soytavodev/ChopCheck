<?php
require 'auth.php';
ensure_admin();
// validar_pago.php — Admin valida pagos por PIN
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'helpers.php';
$pdo = db();

$mensaje = null;
$detalle = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $pin = trim($_POST['pin'] ?? '');

    if ($codigo && $pin) {
        // Mesa
        $stmt = $pdo->prepare("SELECT * FROM mesas WHERE codigo = ?");
        $stmt->execute([$codigo]);
        $mesa = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($mesa) {
            // Pago pendiente con ese PIN
            $stmt = $pdo->prepare("SELECT p.*, pa.nombre AS nombre_participante
                                   FROM pagos p
                                   JOIN participantes pa ON pa.id = p.participante_id
                                   WHERE p.mesa_id = ? AND p.pin = ? AND p.estado = 'pendiente'
                                   ORDER BY p.id DESC LIMIT 1");
            $stmt->execute([$mesa['id'], $pin]);
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pago) {
                // 1) Marcar pago como pagado
                $upd = $pdo->prepare("UPDATE pagos SET estado = 'pagado', pagado_en = NOW() WHERE id = ?");
                $upd->execute([$pago['id']]);

                // 2) Desactivar participante para que desaparezca de la mesa
                $updPart = $pdo->prepare("UPDATE participantes SET activo = 0 WHERE id = ? AND mesa_id = ?");
                $updPart->execute([$pago['participante_id'], $mesa['id']]);

                $mensaje = "✅ Pago validado correctamente.";
                $detalle = [
                    'mesa' => $codigo,
                    'participante' => $pago['nombre_participante'],
                    'total' => centimos_a_euros((int)$pago['total_centimos']),
                    'pin' => $pin
                ];
            } else {
                $mensaje = "❌ PIN no válido o ya utilizado para la mesa $codigo.";
            }
        } else {
            $mensaje = "❌ Mesa no encontrada.";
        }
    } else {
        $mensaje = "Introduce código y PIN.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Validar pago - ChopCheck</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <h1>Validar pago</h1>
  <div class="card">
    <form action="validar_pago.php" method="post">
      <label>Código de mesa:
        <input type="text" name="codigo" maxlength="8" required>
      </label>
      <label>PIN:
        <input type="text" name="pin" maxlength="6" required>
      </label>
      <button type="submit">Validar</button>
    </form>
  </div>

  <?php if ($mensaje): ?>
    <div class="card">
      <h2>Resultado</h2>
      <p><?= htmlspecialchars($mensaje) ?></p>
      <?php if ($detalle): ?>
        <ul>
          <li><strong>Mesa:</strong> <?= htmlspecialchars($detalle['mesa']) ?></li>
          <li><strong>Participante:</strong> <?= htmlspecialchars($detalle['participante']) ?></li>
          <li><strong>Total:</strong> <?= htmlspecialchars($detalle['total']) ?></li>
          <li><strong>PIN:</strong> <?= htmlspecialchars($detalle['pin']) ?></li>
        </ul>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <p>
    <a href="admin.php">← Volver a mesas</a>
  </p>
</body>
</html>
