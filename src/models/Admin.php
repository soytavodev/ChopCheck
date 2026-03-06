<?php
// src/models/Admin.php
require_once __DIR__ . '/../../config/database.php';

class Admin {
    
    public static function login($username, $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? AND activo = 1");
        $stmt->execute([$username]);
        $adm = $stmt->fetch();
        
        // Verificamos el hash seguro de la contraseña
        if ($adm && password_verify($password, $adm['pass_hash'])) {
            return $adm;
        }
        return false;
    }

    // Método temporal para crear tu primer usuario
    public static function crearSetup($username, $password) {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admins (username, pass_hash, activo) VALUES (?, ?, 1)");
        return $stmt->execute([$username, $hash]);
    }
}
