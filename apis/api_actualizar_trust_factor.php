<?php
session_start();
header('Content-Type: application/json');

// Verificar que esté logueado y que sea Administrador (asumiendo rol_id = 1 para Admin)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    echo json_encode(["success" => false, "message" => "Acceso denegado. No autorizado."]);
    exit;
}

require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
    $nuevo_tf = isset($_POST['trust_factor']) ? intval($_POST['trust_factor']) : null;

    if ($usuario_id === 0 || $nuevo_tf === null) {
        echo json_encode(["success" => false, "message" => "Datos incompletos o inválidos."]);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE usuarios SET trust_factor = ? WHERE id = ? AND rol_id = 3");
        $stmt->execute([$nuevo_tf, $usuario_id]);

        echo json_encode([
            "success" => true, 
            "message" => "Trust Factor actualizado correctamente."
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false, 
            "message" => "Error en la base de datos: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
}