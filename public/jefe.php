<?php
// Script temporal de emergencia para crear al Admin
require_once '../config/database.php';

try {
    $pdo = getDB();
    $hash = password_hash('123456', PASSWORD_DEFAULT);
    
    // Insertamos al jefe. Si ya existe, no hace nada y no da error.
    $stmt = $pdo->prepare("INSERT IGNORE INTO admins (username, pass_hash) VALUES ('jefe', ?)");
    $stmt->execute([$hash]);
    
    echo "<h1 style='color: green;'>¡Usuario 'jefe' creado con exito! Contraseña: 123456</h1>";
    echo "<p>Ya puedes borrar este archivo (jefe.php) y entrar a la caja.</p>";
} catch (Exception $e) {
    echo "<h1 style='color: red;'>Error: " . $e->getMessage() . "</h1>";
}
?>
