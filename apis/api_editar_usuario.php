<?php
// apis/api_editar_usuario.php
header('Content-Type: application/json');
session_start();

// Validar permisos de administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Permisos insuficientes.']);
    exit;
}

require_once '../conexion.php';

// Validar que se reciban los datos obligatorios
if (!isset($_POST['id_usuario']) || !isset($_POST['nombre']) || !isset($_POST['email']) || !isset($_POST['rol_id'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios para actualizar.']);
    exit;
}

$id_usuario = intval($_POST['id_usuario']);
$nombre = trim($_POST['nombre']);
$email = trim($_POST['email']);
$rol_id = intval($_POST['rol_id']);

if (empty($nombre) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'El nombre y el correo electrónico no pueden estar vacíos.']);
    exit;
}

try {
    // 1. Validar que el correo no esté ocupado por otro usuario distinto
    $check_email = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
    $check_email->execute([':email' => $email, ':id' => $id_usuario]);
    
    if ($check_email->fetch()) {
        echo json_encode(['success' => false, 'message' => 'El correo electrónico ya se encuentra registrado por otro usuario.']);
        exit;
    }

    // 2. Ejecutar la actualización segura de datos
    $stmt = $conn->prepare("UPDATE usuarios SET nombre = :nombre, email = :email, rol_id = :rol_id WHERE id = :id");
    $resultado = $stmt->execute([
        ':nombre' => $nombre,
        ':email'  => $email,
        ':rol_id' => $rol_id,
        ':id'     => $id_usuario
    ]);

    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se realizaron cambios en el perfil.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
exit;