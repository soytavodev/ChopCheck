<?php
// auth.php — utilidades de sesión y protección de rutas Admin
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Configuración de sesión simple (MVP). Para producción, considera Secure y SameSite=Strict con HTTPS.
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

function is_admin_logged(): bool {
    return !empty($_SESSION['admin_id']);
}

function ensure_admin(): void {
    if (!is_admin_logged()) {
        header('Location: login.php');
        exit;
    }
}

function admin_login(int $admin_id, string $username): void {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin_id;
    $_SESSION['admin_username'] = $username;
}

function admin_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
