<?php
// src/controllers/MesaController.php
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../models/Participante.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Pago.php';

class MesaController {
    
    public function join() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=home");
            exit;
        }

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $nombre = trim($_POST['nombre'] ?? '');

        if (empty($codigo) || empty($nombre)) {
            $_SESSION['error'] = "El código de mesa y tu apodo son obligatorios.";
            header("Location: index.php?route=home");
            exit;
        }

        $mesa = Mesa::findByCodigo($codigo);

        if (!$mesa || (int)$mesa['cerrado'] === 1) {
            $_SESSION['error'] = "La mesa no existe o ya está cerrada.";
            header("Location: index.php?route=home");
            exit;
        }

        Participante::crear($mesa['id'], $nombre);
        header("Location: index.php?route=mesa&codigo=" . urlencode($codigo));
        exit;
    }

    public function show() {
        $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
        if (empty($codigo)) {
            header("Location: index.php?route=home");
            exit;
        }

        $mesa = Mesa::findByCodigo($codigo);
        if (!$mesa) {
            $_SESSION['error'] = "Mesa no encontrada.";
            header("Location: index.php?route=home");
            exit;
        }

        $participantes = Participante::getByMesaId($mesa['id']);
        $items = Item::getByMesaId($mesa['id']);
        $item_ids = array_column($items, 'id');
        $consumosPorItem = Item::getConsumosByItems($item_ids);

        $totales = [];
        foreach ($participantes as $p) {
            $totales[$p['id']] = 0;
        }
        
        $totalMesa = 0;

        foreach ($items as $it) {
            $totalMesa += (int)$it['precio_centimos'];
            $cons = $consumosPorItem[$it['id']] ?? [];
            if (count($cons) > 0) {
                $shares = dividir_centimos_equitable($it['precio_centimos'], $cons);
                foreach ($shares as $pid => $cents) {
                    if (isset($totales[$pid])) {
                        $totales[$pid] += $cents;
                    }
                }
            }
        }

        $totalPagado = Pago::getTotalPagadoByMesa($mesa['id']);
        $totalRestante = max(0, $totalMesa - $totalPagado);

        $pids = array_column($participantes, 'id');
        $estadoPorPid = Pago::getEstadosByParticipantes($pids);

        require_once __DIR__ . '/../../views/user/mesa.php';
    }

    public function toggleConsumo() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=home");
            exit;
        }

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $item_id = (int)($_POST['item_id'] ?? 0);
        $participante_id = (int)($_POST['participante_id'] ?? 0);

        if (!$codigo || !$item_id || !$participante_id) {
            header("Location: index.php?route=home");
            exit;
        }

        $mesa = Mesa::findByCodigo($codigo);
        if (!$mesa || (int)$mesa['cerrado'] === 1) {
            header("Location: index.php?route=home");
            exit;
        }

        Item::toggleConsumo($item_id, $participante_id);
        header("Location: index.php?route=mesa&codigo=" . urlencode($codigo));
        exit;
    }

    public function resumen() {
        $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
        $pid = (int)($_GET['pid'] ?? 0);
        $msg = trim($_GET['msg'] ?? ''); 

        if (!$codigo || !$pid) {
            header("Location: index.php?route=home");
            exit;
        }

        $mesa = Mesa::findByCodigo($codigo);
        if (!$mesa) {
            header("Location: index.php?route=home");
            exit;
        }

        $part = Participante::findByIdAndMesa($pid, $mesa['id']);
        if (!$part) {
            header("Location: index.php?route=home");
            exit;
        }

        $items = Item::getByMesaId($mesa['id']);
        $item_ids = array_column($items, 'id');
        $consumosPorItem = Item::getConsumosByItems($item_ids);

        $total = 0;
        $detalle = [];

        foreach ($items as $it) {
            $cons = $consumosPorItem[$it['id']] ?? [];
            if (in_array($pid, $cons)) {
                $shares = dividir_centimos_equitable($it['precio_centimos'], $cons);
                $miParte = $shares[$pid] ?? 0;
                $total += $miParte;
                $detalle[] = [
                    'nombre' => $it['nombre'],
                    'precio' => $it['precio_centimos'],
                    'mi_parte' => $miParte,
                    'n_cons' => count($cons)
                ];
            }
        }

        $pago = Pago::getUltimoPagoByParticipante($pid);
        $estadoPago = $pago['estado'] ?? null; 
        $pinPago = $pago['pin'] ?? null;
        $totalBloqueado = isset($pago['total_centimos']) ? (int)$pago['total_centimos'] : null;

        require_once __DIR__ . '/../../views/user/resumen.php';
    }

    // NUEVO: Generar PIN seguro y guardar en BD
    public function generarPin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=home");
            exit;
        }

        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $pid = (int)($_POST['pid'] ?? 0);

        if (!$codigo || !$pid) {
            header("Location: index.php?route=home");
            exit;
        }

        $mesa = Mesa::findByCodigo($codigo);
        if (!$mesa) {
            header("Location: index.php?route=home");
            exit;
        }

        // Volvemos a calcular el total en backend por seguridad.
        $items = Item::getByMesaId($mesa['id']);
        $item_ids = array_column($items, 'id');
        $consumosPorItem = Item::getConsumosByItems($item_ids);

        $total = 0;
        foreach ($items as $it) {
            $cons = $consumosPorItem[$it['id']] ?? [];
            if (in_array($pid, $cons)) {
                $shares = dividir_centimos_equitable($it['precio_centimos'], $cons);
                $total += ($shares[$pid] ?? 0);
            }
        }

        if ($total <= 0) {
            header("Location: index.php?route=resumen&codigo=" . urlencode($codigo) . "&pid=" . $pid . "&msg=" . urlencode("No tienes consumo asignado para pagar."));
            exit;
        }

        Pago::limpiarPendientes($pid);
        $pin = generar_pin_pago(4); // Genera PIN de 4 dígitos
        Pago::crear($mesa['id'], $pid, $pin, $total);

        header("Location: index.php?route=resumen&codigo=" . urlencode($codigo) . "&pid=" . $pid);
        exit;
    }

    // NUEVO: API para que el frontend consulte si ya le cobraron
    public function apiPagoEstado() {
        header('Content-Type: application/json; charset=utf-8');
        
        $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
        $pid = (int)($_GET['pid'] ?? 0);

        if (!$codigo || !$pid) {
            echo json_encode(['error' => 'parametros']);
            exit;
        }

        $pago = Pago::getUltimoPagoByParticipante($pid);
        $estado = $pago['estado'] ?? null;

        echo json_encode(['estado' => $estado]);
        exit;
    }
}
