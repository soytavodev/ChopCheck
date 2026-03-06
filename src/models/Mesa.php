<?php
// src/models/Mesa.php
require_once __DIR__ . '/../../config/database.php';

class Mesa {
    
    public static function findByCodigo($codigo) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM mesas WHERE codigo = ?");
        $stmt->execute([$codigo]);
        return $stmt->fetch(); 
    }

    public static function getAllWithStats() {
        $db = getDB();
        $sql = "SELECT m.*,
                    (SELECT COUNT(*) FROM participantes p WHERE p.mesa_id = m.id AND p.activo = 1) AS num_participantes,
                    (SELECT COUNT(*) FROM items i WHERE i.mesa_id = m.id) AS num_items
                FROM mesas m
                ORDER BY m.cerrado ASC, m.numero ASC, m.id ASC";
        return $db->query($sql)->fetchAll();
    }

    public static function findByNumero($numero) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM mesas WHERE numero = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$numero]);
        return $stmt->fetch();
    }

    public static function crear($codigo, $nombre, $numero) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO mesas (codigo, nombre, numero, cerrado) VALUES (?, ?, ?, 0)");
        return $stmt->execute([$codigo, $nombre, $numero]);
    }

    public static function reabrir($id, $nuevo_codigo, $nuevo_nombre) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE mesas SET codigo = ?, nombre = ?, cerrado = 0 WHERE id = ?");
        return $stmt->execute([$nuevo_codigo, $nuevo_nombre, $id]);
    }

    // NUEVO: Cambiar el estado de la mesa (0 = abierta, 1 = cerrada)
    public static function cambiarEstado($id, $cerrado) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE mesas SET cerrado = ? WHERE id = ?");
        return $stmt->execute([$cerrado, $id]);
    }
}
