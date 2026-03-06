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

    // NUEVO: Buscar a un participante específico en una mesa
    public static function findByIdAndMesa($id, $mesa_id) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM participantes WHERE id = ? AND mesa_id = ?");
        $stmt->execute([$id, $mesa_id]);
        return $stmt->fetch();
    }
}
