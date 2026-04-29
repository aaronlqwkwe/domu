<?php
session_start();
header('Content-Type: application/json');
require_once '../conexion.php';

// Validar que solo el Admin (rol_id = 1) pueda hacer esto
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
    echo json_encode(["success" => false, "message" => "Acceso denegado. No eres administrador."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_POST['id_usuario'];
    $nuevo_estado = $_POST['estado']; // Recibirá 'aprobado' o 'baneado'

    try {
        $sql = "UPDATE usuarios SET estado_cuenta = :estado WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id', $id_usuario);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "El estado del usuario se actualizó a: " . $nuevo_estado]);
        } else {
            echo json_encode(["success" => false, "message" => "No se pudo actualizar el estado."]);
        }
    } catch(PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error SQL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
}
?>