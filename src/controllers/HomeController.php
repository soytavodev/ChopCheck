<?php
// src/controllers/HomeController.php

class HomeController {
    
    // Método para mostrar la página principal
    public function index() {
        // En un futuro aquí podríamos comprobar si hay mensajes de error en la sesión
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']); // Limpiamos el error tras leerlo
        
        // Cargamos la vista
        require_once __DIR__ . '/../../views/user/home.php';
    }
}
