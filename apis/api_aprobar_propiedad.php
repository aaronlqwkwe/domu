<?php
session_start();
require_once '../conexion.php';

// Verificamos que sea el Administrador (Rol 1)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    die("Acceso denegado. Solo administradores pueden aprobar propiedades.");
}

// Verificamos que hayamos recibido un ID por la URL
if (isset($_GET['id'])) {
    $propiedad_id = $_GET['id'];
    
    try {
        // Actualizamos el estado a 'Disponible'
        $stmt = $conn->prepare("UPDATE propiedad SET estado = 'Disponible' WHERE id = :id");
        $stmt->bindParam(':id', $propiedad_id);
        $stmt->execute();
        
        // Redirigimos de vuelta al dashboard del admin con un mensaje de éxito
        header("Location: ../vistas/admin_dashboard.php?mensaje=aprobada");
        exit;

    } catch (PDOException $e) {
        die("Error al actualizar la base de datos: " . $e->getMessage());
    }
} else {
    die("No se proporcionó un ID válido.");
}
?>