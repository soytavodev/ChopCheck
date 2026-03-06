<?php
// src/controllers/AdminController.php
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../models/Pago.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Participante.php';
require_once __DIR__ . '/../models/Articulo.php';

class AdminController {
    
    private function requireLogin() {
        if (empty($_SESSION['admin_id'])) {
            header("Location: index.php?route=admin_login");
            exit;
        }
    }

    public function showLogin() {
        if (!empty($_SESSION['admin_id'])) { header("Location: index.php?route=admin_dashboard"); exit; }
        $error = $_SESSION['error'] ?? null; $msg = $_SESSION['msg'] ?? null;
        unset($_SESSION['error'], $_SESSION['msg']);
        require_once __DIR__ . '/../../views/admin/login.php';
    }

    public function doLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php?route=admin_login"); exit; }
        $username = trim($_POST['username'] ?? ''); $password = $_POST['password'] ?? '';
        $admin = Admin::login($username, $password);
        
        if ($admin) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id']; $_SESSION['admin_username'] = $admin['username'];
            header("Location: index.php?route=admin_dashboard"); exit;
        } else {
            $_SESSION['error'] = "Credenciales inválidas.";
            header("Location: index.php?route=admin_login"); exit;
        }
    }

    public function dashboard() {
        $this->requireLogin();
        $error = $_SESSION['error'] ?? null; $msg = $_SESSION['msg'] ?? null;
        unset($_SESSION['error'], $_SESSION['msg']);
        
        $mesas = Mesa::getAllWithStats();
        require_once __DIR__ . '/../../views/admin/dashboard.php';
    }

    public function crearMesa() {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php?route=admin_dashboard"); exit; }
        $numero = (int)($_POST['numero'] ?? 0); $nombre = trim($_POST['nombre'] ?? '');
        if ($numero <= 0) { $_SESSION['error'] = "El número debe ser mayor que 0."; header("Location: index.php?route=admin_dashboard"); exit; }

        do {
            $codigo = generarCodigoMesa(6);
            $existeCodigo = Mesa::findByCodigo($codigo);
        } while ($existeCodigo);

        $mesaExistente = Mesa::findByNumero($numero);
        if ($mesaExistente) {
            if ((int)$mesaExistente['cerrado'] === 0) {
                $_SESSION['error'] = "La Mesa $numero ya está abierta."; header("Location: index.php?route=admin_dashboard"); exit;
            }
            Mesa::reabrir($mesaExistente['id'], $codigo, $nombre ?: $mesaExistente['nombre']);
            $_SESSION['msg'] = "Mesa $numero reabierta con el nuevo código: $codigo";
        } else {
            Mesa::crear($codigo, $nombre, $numero);
            $_SESSION['msg'] = "Mesa $numero creada con el código: $codigo";
        }
        header("Location: index.php?route=admin_dashboard"); exit;
    }

    public function validarPago() {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php?route=admin_dashboard"); exit; }
        $codigo = strtoupper(trim($_POST['codigo'] ?? '')); $pin = trim($_POST['pin'] ?? '');

        if (!$codigo || !$pin) { $_SESSION['error'] = "Código y PIN requeridos."; header("Location: index.php?route=admin_dashboard"); exit; }
        $mesa = Mesa::findByCodigo($codigo);
        if (!$mesa) { $_SESSION['error'] = "Mesa no encontrada."; header("Location: index.php?route=admin_dashboard"); exit; }

        $db = getDB();
        $stmt = $db->prepare("SELECT p.*, pa.nombre AS nombre_participante FROM pagos p JOIN participantes pa ON pa.id = p.participante_id WHERE p.mesa_id = ? AND p.pin = ? AND p.estado = 'pendiente' ORDER BY p.id DESC LIMIT 1");
        $stmt->execute([$mesa['id'], $pin]);
        $pago = $stmt->fetch();

        if ($pago) {
            $db->prepare("UPDATE pagos SET estado = 'pagado', pagado_en = NOW() WHERE id = ?")->execute([$pago['id']]);
            $db->prepare("UPDATE participantes SET activo = 0 WHERE id = ? AND mesa_id = ?")->execute([$pago['participante_id'], $mesa['id']]);
            $_SESSION['msg'] = "✅ Pago validado. " . htmlspecialchars($pago['nombre_participante']) . " ha pagado " . centimos_a_euros((int)$pago['total_centimos']);
        } else {
            $_SESSION['error'] = "❌ PIN no válido para la mesa $codigo.";
        }
        header("Location: index.php?route=admin_dashboard"); exit;
    }

    // --- NUEVOS MÉTODOS DE GESTIÓN DE MESA ---

    public function gestionarMesa() {
        $this->requireLogin();
        $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
        $busqueda = trim($_GET['q'] ?? '');

        if (!$codigo) { header("Location: index.php?route=admin_dashboard"); exit; }
        $mesa = Mesa::findByCodigo($codigo);
        if (!$mesa) { $_SESSION['error'] = "Mesa no encontrada."; header("Location: index.php?route=admin_dashboard"); exit; }

        $participantes = Participante::getByMesaId($mesa['id']);
        $items = Item::getByMesaId($mesa['id']);
        $articulos = Articulo::getAllActivos($busqueda);

        $error = $_SESSION['error'] ?? null; $msg = $_SESSION['msg'] ?? null;
        unset($_SESSION['error'], $_SESSION['msg']);

        require_once __DIR__ . '/../../views/admin/mesa.php';
    }

    public function toggleEstadoMesa() {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php?route=admin_dashboard"); exit; }
        
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $cerrado = (int)($_POST['cerrado'] ?? 0);

        $mesa = Mesa::findByCodigo($codigo);
        if ($mesa) {
            Mesa::cambiarEstado($mesa['id'], $cerrado);
            $_SESSION['msg'] = $cerrado ? "Mesa cerrada correctamente." : "Mesa reabierta correctamente.";
            header("Location: index.php?route=admin_mesa&codigo=" . urlencode($codigo));
            exit;
        }
        header("Location: index.php?route=admin_dashboard"); exit;
    }

    public function addArticulo() {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php?route=admin_dashboard"); exit; }

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $articulo_id = (int)($_POST['articulo_id'] ?? 0);
        $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));

        $mesa = Mesa::findByCodigo($codigo);
        $art = Articulo::findById($articulo_id);

        if ($mesa && $art && (int)$mesa['cerrado'] === 0) {
            for ($i=0; $i<$cantidad; $i++) {
                Item::crear($mesa['id'], $art['nombre'], $art['precio_centimos']);
            }
            $_SESSION['msg'] = "$cantidad x " . htmlspecialchars($art['nombre']) . " añadido a la mesa.";
        } else {
            $_SESSION['error'] = "No se pudo añadir. Verifica que la mesa esté abierta.";
        }
        header("Location: index.php?route=admin_mesa&codigo=" . urlencode($codigo)); exit;
    }

    public function addItemManual() {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php?route=admin_dashboard"); exit; }

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = trim($_POST['precio'] ?? '');
        $centimos = euros_a_centimos($precio);

        $mesa = Mesa::findByCodigo($codigo);

        if ($mesa && $nombre && $centimos > 0 && (int)$mesa['cerrado'] === 0) {
            Item::crear($mesa['id'], $nombre, $centimos);
            $_SESSION['msg'] = "Producto manual añadido.";
        } else {
            $_SESSION['error'] = "Error al añadir. Verifica datos y que la mesa esté abierta.";
        }
        header("Location: index.php?route=admin_mesa&codigo=" . urlencode($codigo)); exit;
    }

    public function logout() { session_destroy(); header("Location: index.php?route=home"); exit; }
}
