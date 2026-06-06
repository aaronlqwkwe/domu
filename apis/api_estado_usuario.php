<?php
session_start();
header('Content-Type: application/json');
require_once '../conexion.php';

// 1. Validar que el usuario en sesión sea Administrador (rol_id = 1)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    echo json_encode(["success" => false, "message" => "Acceso denegado. No tienes permisos de administrador."]);
    exit;
}

// 2. Leer los datos enviados (Soporta tanto FormData normal como JSON Fetch)
$usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
$nuevo_estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

if ($usuario_id === 0 || empty($nuevo_estado)) {
    // Intento de lectura alternativa por si tu JS lo manda como JSON puro
    $data = json_decode(file_get_contents("php://input"));
    if ($data) {
        $usuario_id = isset($data->usuario_id) ? intval($data->usuario_id) : 0;
        $nuevo_estado = isset($data->estado) ? trim($data->estado) : '';
    }
}

// 3. Validar parámetros mínimos requeridos
if ($usuario_id === 0 || empty($nuevo_estado)) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios (ID o Estado)."]);
    exit;
}

try {
    // 🛠️ CORRECCIÓN 1: Forzamos el estado a MAYÚSCULAS fijas ('APROBADO', 'BANEADO', 'PENDIENTE')
    $estado_fijo = strtoupper($nuevo_estado);

    // 🛠️ CORRECCIÓN 2: Si el admin va a pasar el estado a 'APROBADO', aprovechamos de una vez
    // para limpiar la columna 'codigo_verificacion' poniéndola en NULL. Esto evita que el login se atore en la Fase 1.
    if ($estado_fijo === 'APROBADO') {
        $stmt = $conn->prepare("UPDATE usuarios SET estado_cuenta = :estado, codigo_verificacion = NULL WHERE id = :id");
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET estado_cuenta = :estado WHERE id = :id");
    }

    $stmt->bindParam(':estado', $estado_fijo);
    $stmt->bindParam(':id', $usuario_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true, 
            "message" => "El usuario ha sido actualizado a estado: $estado_fijo de forma exitosa."
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "La consulta se ejecutó pero no alteró ninguna fila."]);
    }

} catch (PDOException $e) {
    // Esto enviará el error real de SQL (como lo de la columna 'correo') al JS para que lo puedas ver en el alert
    echo json_encode([
        "success" => false, 
        "message" => "Error en la Base de Datos: " . $e->getMessage()
    ]);
    exit;
}
?>