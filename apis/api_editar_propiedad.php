<?php
session_start();

// Validar sesión de administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

require_once '../conexion.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id = intval($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $area_m2 = intval($_POST['area_m2'] ?? 0);
    $estado = trim($_POST['estado'] ?? 'Disponible');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $latitud = (!empty($_POST['latitud'])) ? floatval($_POST['latitud']) : null;
    $longitud = (!empty($_POST['longitud'])) ? floatval($_POST['longitud']) : null;

    if ($id <= 0 || empty($titulo) || $precio <= 0) {
        header("Location: ../admin_dashboard.php?error=datos_incompletos");
        exit;
    }

    try {
        $sql = "UPDATE propiedad SET 
                    titulo = :titulo, 
                    precio = :precio, 
                    area_m2 = :area_m2, 
                    estado = :estado, 
                    descripcion = :descripcion, 
                    latitud = :latitud, 
                    longitud = :longitud 
                WHERE id = :id";
        
        $params = [
            ':titulo' => $titulo,
            ':precio' => $precio,
            ':area_m2' => $area_m2,
            ':estado' => $estado,
            ':descripcion' => $descripcion,
            ':latitud' => $latitud,
            ':longitud' => $longitud,
            ':id' => $id
        ];

        // Procesar imagen nueva si el usuario la subió
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $fileName = $_FILES['imagen']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($fileExtension, $extensiones_permitidas)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], '../uploads/' . $newFileName)) {
                    $sql = str_replace("WHERE", ", imagen = :imagen WHERE", $sql);
                    $params[':imagen'] = $newFileName;
                }
            }
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        // Todo salió bien, regresamos al panel de control
        header("Location: ../vistas/admin_dashboard.php?actualizado=exito");
        exit;

    } catch (PDOException $e) {
        die("Error al guardar los cambios: " . $e->getMessage());
    }
} else {
    header("Location: ../admin_dashboard.php");
    exit;
}