<?php
// src/models/Pago.php
require_once __DIR__ . '/../../config/database.php';

class Pago {
    
    public static function getTotalPagadoByMesa($mesa_id) {
        $db = getDB();
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_centimos),0) FROM pagos WHERE mesa_id = ? AND estado = 'pagado'");
        $stmt->execute([$mesa_id]);
        return (int)$stmt->fetchColumn();
    }

    public static function getEstadosByParticipantes($participante_ids) {
        if (empty($participante_ids)) return [];
        
        $db = getDB();
        $in = implode(',', array_fill(0, count($participante_ids), '?'));
        $sql = "SELECT p1.participante_id, p1.estado 
                FROM pagos p1
                INNER JOIN (
                    SELECT participante_id, MAX(id) AS max_id 
                    FROM pagos 
                    WHERE participante_id IN ($in) 
                    GROUP BY participante_id
                ) t ON t.max_id = p1.id";
                
        $stmt = $db->prepare($sql);
        $stmt->execute($participante_ids);
        
        $estados = [];
        while ($row = $stmt->fetch()) {
            $estados[$row['participante_id']] = $row['estado'];
        }
        return $estados;
    }

    public static function getUltimoPagoByParticipante($participante_id) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM pagos WHERE participante_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$participante_id]);
        return $stmt->fetch();
    }

    // NUEVO: Borrar pagos pendientes anteriores si el usuario se arrepiente y recalcula
    public static function limpiarPendientes($participante_id) {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM pagos WHERE participante_id = ? AND estado = 'pendiente'");
        return $stmt->execute([$participante_id]);
    }

    // NUEVO: Crear un registro de pago
    public static function crear($mesa_id, $participante_id, $pin, $total_centimos) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO pagos (mesa_id, participante_id, pin, total_centimos, estado) VALUES (?, ?, ?, ?, 'pendiente')");
        return $stmt->execute([$mesa_id, $participante_id, $pin, $total_centimos]);
    }
}
