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
        // Añadimos la columna "pagos_pendientes" para el semáforo y ordenamos por sección y número
        $sql = "SELECT m.*,
                    (SELECT COUNT(*) FROM participantes p WHERE p.mesa_id = m.id AND p.activo = 1) AS num_participantes,
                    (SELECT COUNT(*) FROM items i WHERE i.mesa_id = m.id) AS num_items,
                    (SELECT COUNT(*) FROM pagos pa WHERE pa.mesa_id = m.id AND pa.estado = 'pendiente') AS pagos_pendientes
                FROM mesas m
                ORDER BY FIELD(m.seccion, 'Terraza', 'Salón', 'Barra'), m.numero ASC";
        return $db->query($sql)->fetchAll();
    }

    public static function findById($id) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM mesas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Ya no creamos, solo "Abrimos" una mesa estática existente
    public static function abrir($id, $nuevo_codigo) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE mesas SET codigo = ?, cerrado = 0 WHERE id = ?");
        return $stmt->execute([$nuevo_codigo, $id]);
    }

    public static function cambiarEstado($id, $cerrado) {
        $db = getDB();
        // Si la cerramos, borramos el código para que nadie más pueda entrar con él
        $codigo = $cerrado ? NULL : generarCodigoMesa(6);
        $stmt = $db->prepare("UPDATE mesas SET cerrado = ?, codigo = ? WHERE id = ?");
        return $stmt->execute([$cerrado, $codigo, $id]);
    }
}
