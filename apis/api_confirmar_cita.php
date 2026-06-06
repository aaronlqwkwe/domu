<?php
// Mantenemos los errores activos pero los capturamos de manera controlada
ini_set('display_errors', 0); 
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
session_start();

// 1. Seguridad: Solo el administrador puede usar esta API
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    echo json_encode([
        "success" => false, 
        "message" => "Acceso denegado. No tienes permisos de administrador."
    ]);
    exit;
}

require_once '../conexion.php';

// 2. Recibir los datos del fetch
$cita_id   = isset($_POST['cita_id']) ? intval($_POST['cita_id']) : 0;
$agente_id = isset($_POST['agente_id']) ? intval($_POST['agente_id']) : 0;

if ($cita_id === 0 || $agente_id === 0) {
    echo json_encode([
        "success" => false, 
        "message" => "Faltan datos obligatorios (Cita o Asesor)."
    ]);
    exit;
}

try {
    // 3. Actualizar la cita de forma directa colocando el agente_id y cambiando el estado
    $stmt_update = $conn->prepare("
        UPDATE citas 
        SET estado = 'confirmada', agente_id = ? 
        WHERE id = ? AND estado = 'pendiente'
    ");
    $stmt_update->execute([$agente_id, $cita_id]);

    if ($stmt_update->rowCount() === 0) {
        echo json_encode([
            "success" => false,
            "message" => "La cita ya fue procesada o el ID no coincide como pendiente."
        ]);
        exit;
    }

    // Si llegó hasta aquí, la base de datos ya se actualizó correctamente
    echo json_encode([
        "success" => true, 
        "message" => "¡Cita confirmada correctamente en la Base de Datos! (Envío de email temporalmente pausado)"
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Error interno de base de datos: " . $e->getMessage()
    ]);
    exit;
}