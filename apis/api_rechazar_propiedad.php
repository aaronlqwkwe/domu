<?php
session_start();
require_once '../conexion.php';

// Verificar que sea Administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    die("Acceso denegado.");
}

if (isset($_GET['id'])) {
    $propiedad_id = $_GET['id'];
    
    try {
        // Cambiamos el estado a "Rechazado"
        $stmt = $conn->prepare("UPDATE propiedad SET estado = 'Rechazado' WHERE id = :id");
        $stmt->bindParam(':id', $propiedad_id);
        $stmt->execute();
        
        header("Location: ../vistas/admin_dashboard.php?mensaje=rechazada");
        exit;

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>