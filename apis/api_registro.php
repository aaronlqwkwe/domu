<?php
header('Content-Type: application/json');
require_once '../conexion.php'; // Salimos de la carpeta apis para encontrar conexion.php

// 1. Verificamos que los textos vengan en $_POST
if(isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['curp'])) {
    
    // 2. Verificamos que las imágenes vengan en $_FILES
    if(isset($_FILES['ine_frente']) && isset($_FILES['ine_reverso'])) {
        
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $curp = strtoupper(trim($_POST['curp']));
        
        // Asignamos el rol por defecto (2 = Cliente) según tu JS
        $rol_id = 2; 

        try {
            // Verificar si el correo ya existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                echo json_encode(["success" => false, "message" => "Este correo ya está registrado."]);
                exit;
            }

            // ==========================================
            // MANEJO DE IMÁGENES (SUBIDA AL SERVIDOR)
            // ==========================================
            $directorio_destino = '../uploads/ine/'; // Asegúrate de que esta carpeta exista y tenga permisos de escritura
            
            // Creamos la carpeta si no existe
            if (!file_exists($directorio_destino)) {
                mkdir($directorio_destino, 0777, true);
            }

            // Generamos nombres únicos usando time() para que no se sobreescriban fotos con el mismo nombre
            $nombre_frente = time() . '_frente_' . basename($_FILES['ine_frente']['name']);
            $nombre_reverso = time() . '_reverso_' . basename($_FILES['ine_reverso']['name']);

            $ruta_frente = $directorio_destino . $nombre_frente;
            $ruta_reverso = $directorio_destino . $nombre_reverso;

            // Movemos los archivos de la memoria temporal de PHP a su carpeta final
            if(move_uploaded_file($_FILES['ine_frente']['tmp_name'], $ruta_frente) && 
               move_uploaded_file($_FILES['ine_reverso']['tmp_name'], $ruta_reverso)) {

                // ==========================================
                // GUARDAR EN BASE DE DATOS
                // ==========================================
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $estado_cuenta = 'pendiente'; // Se registra bloqueado hasta que el admin lo apruebe

                // Preparamos el INSERT con las nuevas columnas
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol_id, curp, ine_frente, ine_reverso, estado_cuenta) VALUES (:nombre, :email, :hash, :rol_id, :curp, :ine_frente, :ine_reverso, :estado_cuenta)");
                
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':hash', $hash);
                $stmt->bindParam(':rol_id', $rol_id);
                $stmt->bindParam(':curp', $curp);
                $stmt->bindParam(':ine_frente', $nombre_frente); // Solo guardamos el nombre del archivo en la DB
                $stmt->bindParam(':ine_reverso', $nombre_reverso);
                $stmt->bindParam(':estado_cuenta', $estado_cuenta);

                if($stmt->execute()) {
                    echo json_encode(["success" => true, "message" => "¡Cuenta creada! Un administrador validará tu información pronto."]);
                } else {
                    echo json_encode(["success" => false, "message" => "Error al guardar en la base de datos."]);
                }

            } else {
                echo json_encode(["success" => false, "message" => "Error al subir las imágenes al servidor."]);
            }

        } catch(PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error SQL: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Faltan las fotos de la INE."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Faltan datos de texto por enviar."]);
}
?>