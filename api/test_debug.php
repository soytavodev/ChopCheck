<?php
// ARCHIVO: api/test_debug.php
// OBJETIVO: Diagnóstico total del sistema (Backend)

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain'); // Texto plano para leerlo fácil

echo "=== INICIANDO DIAGNÓSTICO CHOPCHECK ===\n";

// 1. PRUEBA DE CONEXIÓN
echo "[1] Conectando a la base de datos... ";
require_once '../config/db_connect.php';

if ($conn->connect_error) {
    die("FALLO: " . $conn->connect_error . "\n");
}
echo "OK (Conectado a " . $conn->host_info . ")\n";

// 2. PRUEBA DE USUARIO
echo "[2] Creando usuario de prueba 'Tester'... ";
$token = bin2hex(random_bytes(8));
$sql_user = "INSERT INTO usuarios_temp (sesion_id, alias, token_recuperacion) VALUES (1, 'Tester', '$token')";
if ($conn->query($sql_user)) {
    $user_id = $conn->insert_id;
    echo "OK (ID creado: $user_id)\n";
} else {
    die("FALLO SQL: " . $conn->error . "\n");
}

// 3. PRUEBA DE LECTURA DE ÍTEMS
echo "[3] Buscando el primer ítem LIBRE... ";
$sql_item = "SELECT * FROM items WHERE estado = 'LIBRE' LIMIT 1";
$res = $conn->query($sql_item);
$item = $res->fetch_assoc();

if (!$item) {
    // Si no hay libres, reseteamos todo para probar
    echo "No hay libres. Reseteando items... ";
    $conn->query("UPDATE items SET estado='LIBRE', id_usuario_asignado=NULL");
    $res = $conn->query($sql_item);
    $item = $res->fetch_assoc();
}

if ($item) {
    echo "OK (Encontrado: " . $item['nombre_producto'] . " - ID: " . $item['id'] . ")\n";
} else {
    die("FALLO: No hay productos en la tabla 'items'. ¿Has corrido el schema.sql?\n");
}

// 4. PRUEBA DE ESCRITURA (TOGGLE)
echo "[4] Intentando asignar el ítem al usuario $user_id... ";
$item_id = $item['id'];
$sql_update = "UPDATE items SET estado='ASIGNADO', id_usuario_asignado=$user_id WHERE id=$item_id";

if ($conn->query($sql_update)) {
    if ($conn->affected_rows > 0) {
        echo "OK (Base de datos actualizada correctamente)\n";
    } else {
        echo "ALERTA: La consulta funcionó pero no cambió nada (¿Ya estaba asignado?)\n";
    }
} else {
    die("FALLO SQL UPDATE: " . $conn->error . "\n");
}

// 5. VERIFICACIÓN FINAL
echo "[5] Verificando cambio... ";
$sql_check = "SELECT estado, id_usuario_asignado FROM items WHERE id=$item_id";
$check = $conn->query($sql_check)->fetch_assoc();

if ($check['estado'] === 'ASIGNADO' && $check['id_usuario_asignado'] == $user_id) {
    echo "¡ÉXITO TOTAL! El backend funciona perfectamente.\n";
    echo "CONCLUSIÓN: El problema está en el Frontend (JavaScript/Navegador).\n";
} else {
    echo "FALLO: El ítem sigue LIBRE. Algo bloqueó el cambio.\n";
}

$conn->close();
?>	
