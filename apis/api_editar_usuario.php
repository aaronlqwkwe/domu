<?php
session_start();
require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $rol_id = $_POST['rol_id'];

    try {
        // SQL dinámico: Solo actualizamos password si el admin escribió algo
        $sql = "UPDATE usuarios SET nombre = :nom, email = :em, rol_id = :rol";
        if (!empty($_POST['password'])) {
            $pass_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql .= ", password_hash = :pass";
        }
        $sql .= " WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nom', $nombre);
        $stmt->bindParam(':em', $email);
        $stmt->bindParam(':rol', $rol_id);
        $stmt->bindParam(':id', $id);
        if (!empty($_POST['password'])) $stmt->bindParam(':pass', $pass_hash);

        $stmt->execute();
        header("Location: ../vistas/admin_dashboard.php?mensaje=editado");
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}