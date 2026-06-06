<?php
session_start();
header('Content-Type: application/json');
require_once '../conexion.php'; 

$data = json_decode(file_get_contents("php://input"));

if(isset($data->email) && isset($data->password)) {
    $email = trim($data->email);
    $password = $data->password;

    try {
        $stmt = $conn->prepare("SELECT id, nombre, password_hash, rol_id, estado_cuenta, codigo_verificacion FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificamos la contraseña encriptada
            if(password_verify($password, $user['password_hash'])) {
                
                // Normalizamos el estado de la cuenta (Elimina espacios y lo pasa a MAYÚSCULAS)
                $estado_actual = trim(strtoupper($user['estado_cuenta']));

                // Primero que nada, verificar si está baneado
                if ($estado_actual === 'BANEADO') {
                    echo json_encode(["success" => false, "message" => "Acceso denegado. Esta cuenta ha sido suspendida."]);
                    exit; 
                }

                // ========================================================
                // FASE 1: VERIFICACIÓN DE CORREO ELECTRÓNICO
                // ========================================================
                // Normalizamos el código de verificación por si viene como string vacío de la DB
                $codigo_otp = trim($user['codigo_verificacion']);

                if (!empty($codigo_otp) && $codigo_otp !== '0' && $codigo_otp !== null) {
                    $_SESSION['email_verificar'] = $email; 
                    
                    echo json_encode([
                        "success" => false, 
                        "requiere_verificacion" => true, 
                        "message" => "Falta verificar tu correo electrónico. Por favor revisa tu bandeja de entrada."
                    ]);
                    exit;
                }

                // ========================================================
                // FASE 2: APROBACIÓN DE CUENTA POR EL ADMINISTRADOR
                // ========================================================
                // Comparamos de forma segura contra 'APROBADO' en mayúsculas
                if ($estado_actual !== 'APROBADO') {
                    echo json_encode([
                        "success" => false, 
                        "message" => "Tu correo ya está verificado, pero falta que un administrador te dé de alta en el sistema."
                    ]);
                    exit;
                }

                // ========================================================
                // ACCESO AUTORIZADO (Pasó ambas fases con éxito)
                // ========================================================
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['email'] = $email; 
                $_SESSION['rol_id'] = $user['rol_id'];

                // Determinamos la pantalla destino según el rol
                $pantalla_destino = 'screen-catalog'; 
                if($user['rol_id'] == 1) $pantalla_destino = 'screen-admin';
                if($user['rol_id'] == 2) $pantalla_destino = 'screen-agent';

                echo json_encode([
                    "success" => true, 
                    "redirect" => $pantalla_destino, 
                    "nombre" => $user['nombre']
                ]);
                
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