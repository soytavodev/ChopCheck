<?php
// src/models/Item.php
require_once __DIR__ . '/../../config/database.php';

class Item {
    
    public static function getByMesaId($mesa_id) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM items WHERE mesa_id = ? ORDER BY id DESC");
        $stmt->execute([$mesa_id]);
        return $stmt->fetchAll();
    }

    public static function getConsumosByItems($item_ids) {
        if (empty($item_ids)) return [];
        $db = getDB();
        $in = implode(',', array_fill(0, count($item_ids), '?'));
        $stmt = $db->prepare("SELECT item_id, participante_id FROM item_consumos WHERE item_id IN ($in)");
        $stmt->execute($item_ids);
        $consumos = [];
        while ($row = $stmt->fetch()) {
            $consumos[$row['item_id']][] = (int)$row['participante_id'];
        }
        return $consumos;
    }

    public static function toggleConsumo($item_id, $participante_id) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM item_consumos WHERE item_id = ? AND participante_id = ?");
        $stmt->execute([$item_id, $participante_id]);
        $existe = $stmt->fetch();

        if ($existe) {
            $stmt = $db->prepare("DELETE FROM item_consumos WHERE id = ?");
            return $stmt->execute([$existe['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO item_consumos (item_id, participante_id) VALUES (?, ?)");
            return $stmt->execute([$item_id, $participante_id]);
        }
    }

    // NUEVO: Crear un item en la mesa
    public static function crear($mesa_id, $nombre, $precio_centimos) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO items (mesa_id, nombre, precio_centimos) VALUES (?, ?, ?)");
        return $stmt->execute([$mesa_id, $nombre, $precio_centimos]);
    }
}
