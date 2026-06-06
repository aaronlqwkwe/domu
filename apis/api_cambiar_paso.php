<?php
session_start();

// 1. Reporte de errores por si necesitas debuguear en la terminal
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Validar que el usuario esté logeado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../vistas/login.php");
    exit;
}

// 3. CORREGIDO: Ajustamos la validación para que busque 'nuevo_paso' tal como lo envía tu formulario
if (!isset($_POST['apartado_id']) || !isset($_POST['nuevo_paso'])) {
    header("Location: ../vistas/admin_dashboard.php?mensaje=datos_incompletos");
    exit;
}

require_once '../conexion.php'; 

$apartado_id = intval($_POST['apartado_id']);
$nuevo_paso = intval($_POST['nuevo_paso']); // 🌟 Recibe correctamente el select de tu formulario

try {
    $conn->beginTransaction();

    // 1. Actualizar el paso legal del proceso de apartado
    $stmt1 = $conn->prepare("UPDATE const_apartados SET paso_legal = :paso WHERE id = :id");
    $stmt1->execute([
        ':paso' => $nuevo_paso, 
        ':id' => $apartado_id
    ]);

    // 2. 🌟 AUTOMATIZACIÓN: Si el paso seleccionado es 0 (Cancelar Operación), liberar la propiedad
    if ($nuevo_paso === 0) {
        
        // Usamos la propiedad_id que mandas de manera directa en el formulario oculto de tu tabla
        if (isset($_POST['propiedad_id']) && !empty($_POST['propiedad_id'])) {
            $propiedad_id = intval($_POST['propiedad_id']);
            
            $stmt2 = $conn->prepare("UPDATE propiedad SET estado = 'disponible' WHERE id = :propiedad_id");
            $stmt2->execute([':propiedad_id' => $propiedad_id]);
        }
    }

    $conn->commit();
    
    // 3. Redirección sincronizada con las alertas que ya tienes programadas en tu HTML
    header("Location: ../vistas/admin_dashboard.php?mensaje=actualizado_correcto");
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) { 
        $conn->rollBack(); 
    }
    die("Error crítico al actualizar el trámite legal: " . $e->getMessage());
}