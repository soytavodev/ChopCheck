<?php
// ARCHIVO: api/check.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>1. Probando archivo de conexión...</h1>";

// Intentamos cargar la configuración
if (file_exists('../config/db_connect.php')) {
    require_once '../config/db_connect.php';
    echo "<p>✅ Archivo config/db_connect.php encontrado.</p>";
} else {
    die("<h2 style='color:red'>❌ ERROR: No encuentro config/db_connect.php</h2>");
}

echo "<h1>2. Probando conexión a MySQL...</h1>";

if ($conn->ping()) {
    echo "<h2 style='color:green'>✅ ¡CONEXIÓN EXITOSA!</h2>";
    echo "<p>Base de datos: <strong>$db</strong></p>";
} else {
    echo "<h2 style='color:red'>❌ ERROR DE CONEXIÓN: " . $conn->error . "</h2>";
}

echo "<h1>3. Probando consulta de mesas...</h1>";
$sql = "SELECT COUNT(*) as total FROM items";
$res = $conn->query($sql);
if ($res) {
    $fila = $res->fetch_assoc();
    echo "<p>✅ Consulta SQL correcta. Hay " . $fila['total'] . " items en la tabla.</p>";
} else {
    echo "<p style='color:red'>❌ Error SQL: " . $conn->error . "</p>";
}
?>
