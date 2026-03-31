<?php
// Activamos reporte de errores para ver qué pasa exactamente
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recolectamos los datos del formulario
    $titulo      = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio      = $_POST['precio'] ?? 0;
    $estado      = $_POST['estado'] ?? 'Disponible';
    $area_m2     = $_POST['area_m2'] ?? 0;
    $latitud     = $_POST['latitud'] ?? null;
    $longitud    = $_POST['longitud'] ?? null;
    $agente_id   = $_SESSION['usuario_id']; // El ID del admin logeado

    try {
        // SQL basado exactamente en tu captura de phpMyAdmin
        // OJO: Verifica si 'area_m2' en tu DB tiene guion bajo o es 'area m2'
        $sql = "INSERT INTO propiedad (titulo, descripcion, precio, estado, area_m2, latitud, longitud, agente_id) 
                VALUES (:tit, :des, :pre, :est, :area, :lat, :lon, :age)";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->execute([
            ':tit'  => $titulo,
            ':des'  => $descripcion,
            ':pre'  => $precio,
            ':est'  => $estado,
            ':area' => $area_m2,
            ':lat'  => $latitud,
            ':lon'  => $longitud,
            ':age'  => $agente_id
        ]);

        // Si llega aquí, es que funcionó
        header("Location: ../vistas/admin_dashboard.php?mensaje=propiedad_guardada");
        exit;

    } catch(PDOException $e) {
        // En lugar de pantalla blanca, esto te dirá el error
        echo "<h3>Error al guardar en la base de datos:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<a href='../vistas/admin_dashboard.php'>Volver al panel</a>";
        exit;
    }
} else {
    header("Location: ../vistas/admin_dashboard.php");
    exit;
}