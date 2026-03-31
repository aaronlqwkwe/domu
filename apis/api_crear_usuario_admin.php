<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password_hash'];
    $rol_id = $_POST['rol_id'];

    $password_encriptada = password_hash($password, PASSWORD_DEFAULT);

    try {
        // SQL corregido con tu columna password_hash
        $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol_id) VALUES (:nombre, :email, :pass, :rol)";
        $stmt = $conn->prepare($sql);
        
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':pass', $password_encriptada);
        $stmt->bindParam(':rol', $rol_id);

        if ($stmt->execute()) {
            // --- CAMBIO PARA PRUEBA ---
            echo "¡ÉXITO! El usuario se guardó. <a href='../vistas/admin_dashboard.php'>Volver al Panel</a>";
            exit; 
            // ---------------------------
        } else {
            echo "El execute falló pero no lanzó excepción.";
        }

    } catch(PDOException $e) {
        die("Error de BD: " . $e->getMessage());
    }
}
?>