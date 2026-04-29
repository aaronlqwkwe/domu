<?php
session_start();
header('Content-Type: application/json');
require_once '../conexion.php'; 

$data = json_decode(file_get_contents("php://input"));

if(isset($data->email) && isset($data->password)) {
    $email = trim($data->email);
    $password = $data->password;

    try {
        // 1. CAMBIO AQUÍ: Agregamos 'estado_cuenta' al SELECT
        $stmt = $conn->prepare("SELECT id, nombre, password_hash, rol_id, estado_cuenta FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificamos la contraseña
            if(password_verify($password, $user['password_hash'])) {
                
                // ==========================================
                // 2. NUEVO CANDADO DE SEGURIDAD (KYC)
                // ==========================================
                if ($user['estado_cuenta'] === 'pendiente') {
                    // Bloqueamos el paso y regresamos un mensaje para el Frontend
                    echo json_encode(["success" => false, "message" => "Tu cuenta está en revisión. Un administrador validará tu INE pronto."]);
                    exit; // Detiene la ejecución aquí mismo
                }
                
                if ($user['estado_cuenta'] === 'baneado') {
                    // Bloqueo total
                    echo json_encode(["success" => false, "message" => "Acceso denegado. Esta cuenta ha sido suspendida."]);
                    exit; // Detiene la ejecución
                }
                // ==========================================

                // 3. Si llega a esta línea, significa que su estado es 'aprobado'. 
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