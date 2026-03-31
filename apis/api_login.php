<?php
session_start();
header('Content-Type: application/json');
require_once '../conexion.php'; 

$data = json_decode(file_get_contents("php://input"));

if(isset($data->email) && isset($data->password)) {
    $email = trim($data->email);
    $password = $data->password;

    try {
        // Buscamos al usuario usando rol_id
        $stmt = $conn->prepare("SELECT id, nombre, password_hash, rol_id FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificamos la contraseña
            if(password_verify($password, $user['password_hash'])) {
                
                // Guardamos datos en sesión
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol_id'] = $user['rol_id'];

                // Determinamos la pantalla destino
                $pantalla_destino = 'screen-catalog'; // Por defecto cliente
                if($user['rol_id'] == 1) $pantalla_destino = 'screen-admin';
                if($user['rol_id'] == 2) $pantalla_destino = 'screen-agent';

                echo json_encode(["success" => true, "redirect" => $pantalla_destino, "nombre" => $user['nombre']]);
            } else {
                echo json_encode(["success" => false, "message" => "Contraseña incorrecta."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "El usuario no existe."]);
        }
    } catch(PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error SQL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Por favor llena todos los campos."]);
}
?>