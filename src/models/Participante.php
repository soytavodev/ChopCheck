<?php
// src/models/Participante.php
require_once __DIR__ . '/../../config/database.php';

class Participante {
    
    public static function crear($mesa_id, $nombre) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO participantes (mesa_id, nombre) VALUES (?, ?)");
        return $stmt->execute([$mesa_id, $nombre]);
    }

    public static function getByMesaId($mesa_id) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM participantes WHERE mesa_id = ? AND activo = 1 ORDER BY id ASC");
        $stmt->execute([$mesa_id]);
        return $stmt->fetchAll();
    }

    // MODIFICADO: Ahora comprobamos que el usuario siga activo para evitar "fantasmas"
    public static function findByIdAndMesa($id, $mesa_id) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM participantes WHERE id = ? AND mesa_id = ? AND activo = 1");
        $stmt->execute([$id, $mesa_id]);
        return $stmt->fetch();
    }

    // NUEVO: Expulsión masiva. Se llama cuando el Admin cierra la mesa
    public static function desactivarPorMesa($mesa_id) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE participantes SET activo = 0 WHERE mesa_id = ?");
        return $stmt->execute([$mesa_id]);
    }
}
