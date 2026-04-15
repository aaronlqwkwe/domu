<?php
// Activar errores para ver qué está fallando
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo      = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio      = $_POST['precio'] ?? 0;
    $area_m2     = $_POST['area_m2'] ?? 0;
    $latitud     = !empty($_POST['latitud']) ? $_POST['latitud'] : null;
    $longitud    = !empty($_POST['longitud']) ? $_POST['longitud'] : null;
    
    // Si no hay agente en sesión, ponemos 1 por defecto para que no falle
    $agente_id   = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 1; 
    
    $nombre_imagen = "default.jpg"; 

    // --- LÓGICA DE LA IMAGEN ---
    if (isset($_FILES['foto'])) {
        $error_php = $_FILES['foto']['error'];
        
        if ($error_php === UPLOAD_ERR_OK) {
            $ruta_carpeta = "../uploads/";
            
            // Si la carpeta no existe, intentamos crearla
            if (!file_exists($ruta_carpeta)) {
                mkdir($ruta_carpeta, 0777, true);
            }

            $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $nombre_imagen = time() . "_" . uniqid() . "." . $extension;
            $ruta_destino = $ruta_carpeta . $nombre_imagen;
            
            // Intentar mover el archivo
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
                die("❌ ERROR: La imagen se recibió, pero PHP no tiene permisos para guardarla en la carpeta 'uploads'.");
            }
        } elseif ($error_php === UPLOAD_ERR_INI_SIZE || $error_php === UPLOAD_ERR_FORM_SIZE) {
            die("❌ ERROR: La imagen es demasiado pesada. El límite de tu servidor suele ser 2MB.");
        } elseif ($error_php !== UPLOAD_ERR_NO_FILE) {
            die("❌ ERROR DESCONOCIDO al subir la imagen. Código de error PHP: " . $error_php);
        }
    }

    // --- GUARDAR EN BASE DE DATOS ---
    try {
        $sql = "INSERT INTO propiedad (titulo, descripcion, precio, estado, area_m2, latitud, longitud, agente_id, imagen) 
                VALUES (:tit, :des, :pre, 'Disponible', :area, :lat, :lon, :age, :img)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':tit'  => $titulo,
            ':des'  => $descripcion,
            ':pre'  => $precio,
            ':area' => $area_m2,
            ':lat'  => $latitud,
            ':lon'  => $longitud,
            ':age'  => $agente_id,
            ':img'  => $nombre_imagen
        ]);

        header("Location: ../vistas/admin_dashboard.php?mensaje=propiedad_guardada");
        exit;
    } catch(PDOException $e) {
        die("❌ ERROR EN BASE DE DATOS: " . $e->getMessage());
    }
} else {
    die("❌ No se recibieron datos por POST.");
}
?>