<?php
// public/index.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/helpers/helpers.php';

$route = $_GET['route'] ?? 'home';

switch ($route) {
    // ==========================================
    // RUTAS DEL USUARIO (CLIENTE)
    // ==========================================
    case 'home':
        require_once __DIR__ . '/../src/controllers/HomeController.php';
        $controller = new HomeController();
        $controller->index();
        break;

    case 'join':
        require_once __DIR__ . '/../src/controllers/MesaController.php';
        $controller = new MesaController();
        $controller->join();
        break;

    case 'mesa':
        require_once __DIR__ . '/../src/controllers/MesaController.php';
        $controller = new MesaController();
        $controller->show();
        break;

    case 'toggle_consumo':
        require_once __DIR__ . '/../src/controllers/MesaController.php';
        $controller = new MesaController();
        $controller->toggleConsumo();
        break;

    case 'resumen':
        require_once __DIR__ . '/../src/controllers/MesaController.php';
        $controller = new MesaController();
        $controller->resumen();
        break;

    case 'generar_pin':
        require_once __DIR__ . '/../src/controllers/MesaController.php';
        $controller = new MesaController();
        $controller->generarPin();
        break;

    case 'api_pago_estado':
        require_once __DIR__ . '/../src/controllers/MesaController.php';
        $controller = new MesaController();
        $controller->apiPagoEstado();
        break;

    // ==========================================
    // RUTAS DEL ADMINISTRADOR (PERSONAL)
    // ==========================================
    case 'admin_login':
        require_once __DIR__ . '/../src/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->showLogin();
        break;

    case 'admin_do_login':
        require_once __DIR__ . '/../src/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->doLogin();
        break;

    case 'admin_dashboard':
        require_once __DIR__ . '/../src/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->dashboard();
        break;
        
    case 'admin_crear_mesa':
        require_once __DIR__ . '/../src/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->crearMesa();
        break;

    case 'admin_validar_pago':
        require_once __DIR__ . '/../src/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->validarPago();
        break;

    // NUEVAS RUTAS DE GESTIÓN INTERNA DE MESA
    case 'admin_mesa':
        require_once __DIR__ . '/../src/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->gestionarMesa();
        break;

    case 'admin_toggle_estado':
        require_once __DIR__ . '/../src/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->toggleEstadoMesa();
        break;

    case 'admin_add_articulo':
        require_once __DIR__ . '/../src/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->addArticulo();
        break;

    case 'admin_add_manual':
        require_once __DIR__ . '/../src/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->addItemManual();
        break;

    case 'admin_logout':
        require_once __DIR__ . '/../src/controllers/AdminController.php';
        $controller = new AdminController();
        $controller->logout();
        break;

    default:
        http_response_code(404);
        echo "<h1>Error 404</h1><p>Ruta no encontrada.</p>";
        break;
}
