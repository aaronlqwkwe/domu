<?php
session_start();
require_once '../conexion.php';

// Validar que el usuario sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

// Validar que los campos requeridos estén presentes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['propiedad_id'])) {
    
    $id = intval($_POST['propiedad_id']);
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $area_m2 = floatval($_POST['area_m2']);
    $estado = trim($_POST['estado']);
    $latitud = trim($_POST['latitud']);
    $longitud = trim($_POST['longitud']);
    $nombre_foto = trim($_POST['foto_actual']); // Por defecto, conserva la foto vieja

    // Procesar la nueva foto SOLO si el usuario subió una
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $permitidos = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $tipo = $_FILES['foto']['type'];
        
        if (in_array($tipo, $permitidos)) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            // Generamos un nombre único para evitar que se sobreescriban
            $nuevo_nombre = 'prop_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $ruta_destino = '../uploads/' . $nuevo_nombre;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
                $nombre_foto = $nuevo_nombre; // Actualizamos la variable con la nueva foto
                
                // Opcional: Eliminar la foto vieja del disco para no llenar el servidor de basura
                $foto_vieja_ruta = '../uploads/' . trim($_POST['foto_actual']);
                if (!empty($_POST['foto_actual']) && file_exists($foto_vieja_ruta)) {
                    @unlink($foto_vieja_ruta);
                }
            }
        }
    }

    // Actualizar la base de datos
    try {
        $sql = "UPDATE propiedad 
                SET titulo = :titulo, 
                    descripcion = :descripcion, 
                    precio = :precio, 
                    area_m2 = :area_m2, 
                    estado = :estado, 
                    imagen = :imagen, 
                    latitud = :latitud, 
                    longitud = :longitud 
                WHERE id = :id";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':titulo'      => $titulo,
            ':descripcion' => $descripcion,
            ':precio'      => $precio,
            ':area_m2'     => $area_m2,
            ':estado'      => $estado,
            ':imagen'      => $nombre_foto,
            ':latitud'     => $latitud,
            ':longitud'    => $longitud,
            ':id'          => $id
        ]);

        // Redirigir con mensaje de éxito
        header("Location: ../vistas/admin_dashboard.php?mensaje=propiedad_editada");
        exit;

    } catch (PDOException $e) {
        die("Error al actualizar la propiedad: " . $e->getMessage());
    }
} else {
    header("Location: ../vistas/admin_dashboard.php");
    exit;
}