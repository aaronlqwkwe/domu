<?php
session_start();
require_once '../conexion.php';

// ¡Validamos correctamente que el Rol 2 es el Agente!
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 2) {
    die("Acceso denegado. No tienes permisos de agente.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio = $_POST['precio'] ?? 0;
    $area_m2 = $_POST['area_m2'] ?? 0;
    $latitud = $_POST['latitud'] ?? '';
    $longitud = $_POST['longitud'] ?? '';
    
    // Capturamos el ID del agente
    $agente_id = $_SESSION['usuario_id'];
    
    // Estado por defecto
    $estado = 'Pendiente'; 

    $nombre_imagen = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombre_imagen = time() . '_' . uniqid() . '.' . $ext;
        $ruta_destino = '../uploads/' . $nombre_imagen;
        move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino);
    }

    try {
        $stmt = $conn->prepare("INSERT INTO propiedad (titulo, descripcion, precio, area_m2, latitud, longitud, estado, agente_id, imagen) 
                                VALUES (:titulo, :descripcion, :precio, :area_m2, :latitud, :longitud, :estado, :agente_id, :imagen)");
        
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':area_m2', $area_m2);
        $stmt->bindParam(':latitud', $latitud);
        $stmt->bindParam(':longitud', $longitud);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':agente_id', $agente_id);
        $stmt->bindParam(':imagen', $nombre_imagen);
        
        $stmt->execute();
        
        // Redirigimos al dashboard
        header("Location: ../vistas/agente_dashboard.php?mensaje=propiedad_guardada");
        exit;

    } catch (PDOException $e) {
        die("Error de BD: " . $e->getMessage());
    }
}
?>