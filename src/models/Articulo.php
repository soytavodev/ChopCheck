<?php
// src/models/Articulo.php
require_once __DIR__ . '/../../config/database.php';

class Articulo {
    
    // Obtener todo el catálogo activo
    public static function getAllActivos($busqueda = '') {
        $db = getDB();
        if ($busqueda !== '') {
            $stmt = $db->prepare("SELECT * FROM articulos WHERE activo = 1 AND nombre LIKE ? ORDER BY nombre ASC");
            $stmt->execute(['%' . $busqueda . '%']);
            return $stmt->fetchAll();
        } else {
            return $db->query("SELECT * FROM articulos WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
        }
    }

    public static function findById($id) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM articulos WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
