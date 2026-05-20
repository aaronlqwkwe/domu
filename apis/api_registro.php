<?php
header('Content-Type: application/json');
require_once '../conexion.php'; // Solo una vez al inicio

if (isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['curp'])) {
    // 1. Verificamos que los textos vengan en $_POST (ya verificado arriba, pero mantenemos la lógica)
    
    // 2. Verificamos que las imágenes vengan en $_FILES
    if (isset($_FILES['ine_frente']) && isset($_FILES['ine_reverso'])) {
        
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $curp = strtoupper(trim($_POST['curp']));
        
        $rol_id = 2; // Asignamos el rol por defecto (2 = Cliente)

        try {
            // Verificar si el correo ya existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => false, "message" => "Este correo ya está registrado."]);
                exit;
            }

            // ==========================================
            // MANEJO DE IMÁGENES
            // ==========================================
            
            $directorio_destino = __DIR__ . '/../uploads/ine/'; 
            
            if (!file_exists($directorio_destino)) {
                echo json_encode(["success" => false, "message" => "La carpeta no existe en: " . $directorio_destino]);
                exit;
            }

            if (!is_writable($directorio_destino)) {
                echo json_encode(["success" => false, "message" => "No hay permisos de escritura en: " . $directorio_destino]);
                exit;
            }

            $nombre_frente = time() . '_frente_' . basename($_FILES['ine_frente']['name']);
            $nombre_reverso = time() . '_reverso_' . basename($_FILES['ine_reverso']['name']);

            $ruta_frente = $directorio_destino . $nombre_frente;
            $ruta_reverso = $directorio_destino . $nombre_reverso;

            // Movemos los archivos UNA SOLA VEZ y guardamos el resultado (true o false)
            $subida_frente = move_uploaded_file($_FILES['ine_frente']['tmp_name'], $ruta_frente);
            $subida_reverso = move_uploaded_file($_FILES['ine_reverso']['tmp_name'], $ruta_reverso);

            // Verificamos si ambas variables dieron "true"
            if ($subida_frente && $subida_reverso) {

                // ==========================================
                // GUARDAR EN BASE DE DATOS
                // ==========================================
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $estado_cuenta = 'pendiente'; 

                $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol_id, curp, ine_frente, ine_reverso, estado_cuenta) VALUES (:nombre, :email, :hash, :rol_id, :curp, :ine_frente, :ine_reverso, :estado_cuenta)");
                
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':hash', $hash);
                $stmt->bindParam(':rol_id', $rol_id);
                $stmt->bindParam(':curp', $curp);
                $stmt->bindParam(':ine_frente', $nombre_frente); 
                $stmt->bindParam(':ine_reverso', $nombre_reverso);
                $stmt->bindParam(':estado_cuenta', $estado_cuenta);

                if ($stmt->execute()) {
                    echo json_encode(["success" => true, "message" => "¡Cuenta creada! Un administrador validará tu información pronto."]);
                } else {
                    echo json_encode(["success" => false, "message" => "Error al guardar en la base de datos."]);
                }

            } else {
                // Mensaje detallado si falla el movimiento
                $estado_F = $subida_frente ? "OK" : "Falló";
                $estado_R = $subida_reverso ? "OK" : "Falló";
                echo json_encode([
                    "success" => false, 
                    "message" => "Fallo al mover. Frente: $estado_F | Reverso: $estado_R | Ruta Intentada: $ruta_frente"
                ]);
            }

        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error SQL: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Faltan las fotos de la INE."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Faltan datos de texto por enviar."]);
}
?>