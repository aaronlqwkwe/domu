<?php
// apis/api_subir_documento_notaria.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documento_notarial'])) {
    $propiedad_id = intval($_POST['propiedad_id']);
    $apartado_id = intval($_POST['apartado_id']);
    
    $file = $_FILES['documento_notarial'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    // Validar extensión
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileExt !== 'pdf') {
        header("Location: ../vistas/admin_dashboard.php?error=solo_pdf");
        exit;
    }

    // Validar errores y tamaño (5MB máx)
    if ($fileError === 0 && $fileSize < 5242880) {
        // Crear un nombre único para evitar que se sobrescriban archivos
        $nuevoNombreArchivo = "notaria_prop_" . $propiedad_id . "_" . uniqid('', true) . ".pdf";
        $directorioDestino = "../uploads/documentos/";
        
        // Crear la carpeta si no existe
        if (!file_exists($directorioDestino)) {
            mkdir($directorioDestino, 0777, true);
        }

        $rutaFinal = $directorioDestino . $nuevoNombreArchivo;

        if (move_uploaded_file($fileTmpName, $rutaFinal)) {
            try {
                // Insertar el registro en la tabla multimedia
                // Nota: Asegúrate de mapear los nombres de tus columnas correctamente (id, propiedad_id, ruta_archivo, tipo)
                $stmt = $conn->prepare("INSERT INTO multimedia (propiedad_id, ruta_archivo, tipo) VALUES (:propiedad_id, :ruta, 'documento')");
                $stmt->execute([
                    ':propiedad_id' => $propiedad_id,
                    ':ruta' => $rutaFinal
                ]);

                header("Location: ../vistas/admin_dashboard.php?mensaje=documento_subido");
                exit;
            } catch (PDOException $e) {
                die("Error al guardar en la base de datos: " . $e->getMessage());
            }
        } else {
            header("Location: ../vistas/admin_dashboard.php?error=error_al_mover");
            exit;
        }
    } else {
        header("Location: ../vistas/admin_dashboard.php?error=archivo_invalido_o_muy_grande");
        exit;
    }
}
