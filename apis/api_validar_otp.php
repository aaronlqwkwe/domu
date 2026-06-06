<?php
session_start();
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['email_verificar'];
    $codigo_usuario = trim($_POST['codigo_usuario']);

    try {
        // Buscamos al usuario que coincida con el email y el código proporcionado
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND codigo_verificacion = :code LIMIT 1");
        $stmt->execute([
            ':email' => $email,
            ':code'  => $codigo_usuario
        ]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // 🎉 ¡Coinciden! Activamos la cuenta y borramos el código para que no se use de nuevo
            $update = $conn->prepare("UPDATE usuarios SET estado_cuenta = 'aprobado', codigo_verificacion = NULL WHERE id = :id");
            $update->execute([':id' => $usuario['id']]);

            // Limpiamos la variable temporal de verificación de la sesión
            unset($_SESSION['email_verificar']);

            // Lo mandamos al login avisando que ya está validado
            header("Location: ../login.php?mensaje=cuenta_activada");
            exit;
        } else {
            // Código mal ingresado
            header("Location: ../verificar_codigo.php?error=codigo_incorrecto");
            exit;
        }

    } catch (PDOException $e) {
        die("Error en la validación: " . $e->getMessage());
    }
}