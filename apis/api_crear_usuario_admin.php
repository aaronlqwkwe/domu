<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recibir datos de texto (incluyendo la nueva CURP)
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password_hash'];
    $rol_id = $_POST['rol_id'];
    $curp = trim($_POST['curp']); // NUEVO CAMPO

    $password_encriptada = password_hash($password, PASSWORD_DEFAULT);

    // 2. Configurar el directorio para guardar las INEs
    // IMPORTANTE: Debes crear esta carpeta en tu proyecto
    $directorio_subida = '../uploads/ine/'; 

    // 3. Generar nombres únicos para los archivos (evita sobrescrituras)
    $nombre_frente = time() . "_frente_" . basename($_FILES['ine_frente']['name']);
    $nombre_reverso = time() . "_reverso_" . basename($_FILES['ine_reverso']['name']);
    
    $ruta_frente = $directorio_subida . $nombre_frente;
    $ruta_reverso = $directorio_subida . $nombre_reverso;

    // 4. Mover los archivos temporales a su carpeta definitiva
    if (move_uploaded_file($_FILES['ine_frente']['tmp_name'], $ruta_frente) && 
        move_uploaded_file($_FILES['ine_reverso']['tmp_name'], $ruta_reverso)) {
        
        try {
            // 5. SQL actualizado con las nuevas columnas
            // Nota: No necesitamos insertar 'estado_cuenta' porque MySQL ya le pone 'pendiente' por defecto
            $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol_id, curp, ine_frente, ine_reverso) 
                    VALUES (:nombre, :email, :pass, :rol, :curp, :frente, :reverso)";
            $stmt = $conn->prepare($sql);
            
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':pass', $password_encriptada);
            $stmt->bindParam(':rol', $rol_id);
            $stmt->bindParam(':curp', $curp); 
            $stmt->bindParam(':frente', $nombre_frente); // Solo guardamos el nombre del archivo
            $stmt->bindParam(':reverso', $nombre_reverso); 

            if ($stmt->execute()) {
                // --- CAMBIO PARA PRUEBA ---
                echo "¡ÉXITO! El usuario se guardó, las fotos se subieron y la cuenta está PENDIENTE. <a href='../vistas/admin_dashboard.php'>Volver al Panel</a>";
                exit; 
                // ---------------------------
            } else {
                echo "El execute falló pero no lanzó excepción.";
            }

        } catch(PDOException $e) {
            // Si la base de datos falla, idealmente deberíamos borrar las fotos que se acaban de subir para no dejar "basura"
            unlink($ruta_frente);
            unlink($ruta_reverso);
            die("Error de BD: " . $e->getMessage());
        }

    } else {
        // Camino de error si las imágenes no se pudieron guardar
        echo "Error: No se pudieron subir las imágenes. Verifica que seleccionaste ambos archivos y que la carpeta 'uploads/ine/' existe.";
    }
}
?>