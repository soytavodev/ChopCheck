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
        
        $mesasRaw = Mesa::getAllWithStats();
        
        $mesasPorSeccion = [];
        foreach ($mesasRaw as $m) {
            $mesasPorSeccion[$m['seccion']][] = $m;
        }

        require_once __DIR__ . '/../../views/admin/dashboard.php';
    }

    public function crearMesa() { 
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php?route=admin_dashboard"); exit; }
        
        $mesa_id = (int)($_POST['mesa_id'] ?? 0);
        $mesa = Mesa::findById($mesa_id);

        if ($mesa && (int)$mesa['cerrado'] === 1) {
            do {
                $codigo = generarCodigoMesa(6);
                $existeCodigo = Mesa::findByCodigo($codigo);
            } while ($existeCodigo);

            Mesa::abrir($mesa['id'], $codigo);
            $_SESSION['msg'] = "Mesa abierta. Código generado: " . $codigo;
            header("Location: index.php?route=admin_mesa&codigo=" . $codigo);
            exit;
        }
        
        $_SESSION['error'] = "La mesa ya estaba abierta o no existe.";
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
            
            // ===============================================
            // MAGIA DE LIMPIEZA NUCLEAR AL CERRAR LA MESA
            // ===============================================
            if ($cerrado === 1) {
                $db = getDB();
                // Borra todo físicamente de la BD
                $db->prepare("DELETE FROM items WHERE mesa_id = ?")->execute([$mesa['id']]);
                $db->prepare("DELETE FROM pagos WHERE mesa_id = ?")->execute([$mesa['id']]);
                $db->prepare("DELETE FROM participantes WHERE mesa_id = ?")->execute([$mesa['id']]);
            }

            $_SESSION['msg'] = $cerrado ? "Mesa cerrada. Se ha vaciado la cuenta completamente." : "Mesa reabierta.";
            
            if ($cerrado === 1) {
                header("Location: index.php?route=admin_dashboard");
            } else {
                header("Location: index.php?route=admin_mesa&codigo=" . urlencode($mesa['codigo']));
            }
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
            // FIX: Auto-asignación si hay 1 sola persona en la mesa
            $participantes = Participante::getByMesaId($mesa['id']);
            $unico_pid = (count($participantes) === 1) ? $participantes[0]['id'] : null;

            for ($i=0; $i<$cantidad; $i++) {
                Item::crear($mesa['id'], $art['nombre'], $art['precio_centimos'], $unico_pid);
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
            // FIX: Auto-asignación si hay 1 sola persona en la mesa
            $participantes = Participante::getByMesaId($mesa['id']);
            $unico_pid = (count($participantes) === 1) ? $participantes[0]['id'] : null;

            Item::crear($mesa['id'], $nombre, $centimos, $unico_pid);
            $_SESSION['msg'] = "Producto manual añadido.";
        } else {
            $_SESSION['error'] = "Error al añadir. Verifica datos y que la mesa esté abierta.";
        }
        header("Location: index.php?route=admin_mesa&codigo=" . urlencode($codigo)); exit;
    }

    public function deleteItem() {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php?route=admin_dashboard"); exit; }

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $item_id = (int)($_POST['item_id'] ?? 0);

        $mesa = Mesa::findByCodigo($codigo);
        if ($mesa && $item_id > 0 && (int)$mesa['cerrado'] === 0) {
            $item = Item::findById($item_id);
            if ($item && $item['mesa_id'] == $mesa['id']) {
                Item::eliminar($item_id);
                $_SESSION['msg'] = "❌ Producto eliminado: " . htmlspecialchars($item['nombre']);
            } else {
                $_SESSION['error'] = "Error de seguridad.";
            }
        } else {
            $_SESSION['error'] = "No se pudo eliminar.";
        }
        header("Location: index.php?route=admin_mesa&codigo=" . urlencode($codigo)); exit;
    }

    public function logout() { session_destroy(); header("Location: index.php?route=home"); exit; }
}
