<?php
session_start();
// 1. Ruta corregida (sin la carpeta 'conexiones')
require_once '../conexion.php';

if (isset($_GET['id'])) {
    $id_eliminar = $_GET['id'];

    // Evitar que el admin se borre a sí mismo
    if ($id_eliminar == $_SESSION['usuario_id']) {
        header("Location: ../vistas/admin_dashboard.php?error=auto_borrado");
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id_eliminar);
        
        if ($stmt->execute()) {
            header("Location: ../vistas/admin_dashboard.php?mensaje=eliminado");
            exit;
        }
    } catch(PDOException $e) {
        // Esto pasa si el usuario tiene propiedades o registros amarrados a él
        header("Location: ../vistas/admin_dashboard.php?error=FK_constraint");
        exit;
    }
}
?>