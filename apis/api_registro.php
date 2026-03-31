<?php
header('Content-Type: application/json');
require_once '../conexion.php'; // Salimos de la carpeta apis para encontrar conexion.php

$data = json_decode(file_get_contents("php://input"));

if(isset($data->nombre) && isset($data->email) && isset($data->password)) {
    $nombre = trim($data->nombre);
    $email = trim($data->email);
    $password = $data->password;
    
    // Asignamos el rol por defecto (3 = Cliente)
    $rol_id = 3; 

    try {
        // Verificar si el correo ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            echo json_encode(["success" => false, "message" => "Este correo ya está registrado."]);
            exit;
        }

        // Hashear la contraseña e insertar usando rol_id
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol_id) VALUES (:nombre, :email, :hash, :rol_id)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':rol_id', $rol_id);

        if($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "¡Cuenta creada exitosamente!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al guardar en la base de datos."]);
        }
    } catch(PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error SQL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Faltan datos por enviar."]);
}
?>