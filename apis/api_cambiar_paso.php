<?php
session_start();
require_once '../conexion.php';

// 1. Validar que vengan los datos necesarios por POST
if (!isset($_POST['apartado_id']) || !isset($_POST['nuevo_paso']) || !isset($_POST['propiedad_id'])) {
    header("Location: ../vistas/admin_dashboard.php?error=datos_incompletos");
    exit;
}

$apartado_id = intval($_POST['apartado_id']);
$nuevo_paso = intval($_POST['nuevo_paso']);
$propiedad_id = intval($_POST['propiedad_id']);

try {
    $conn->beginTransaction();

    // 2. Actualizar el paso legal en la tabla 'const_apartados'
    $stmt = $conn->prepare("UPDATE const_apartados SET paso_legal = :nuevo_paso WHERE id = :apartado_id");
    $stmt->execute([
        ':nuevo_paso' => $nuevo_paso,
        ':apartado_id' => $apartado_id
    ]);

    // 3. 🌟 LÓGICA DE RECHAZO (Paso 0): Si se rechaza, la casa vuelve a estar 'disponible'
    if ($nuevo_paso === 0) {
        $stmtProp = $conn->prepare("UPDATE propiedad SET estado = 'disponible' WHERE id = :propiedad_id");
        $stmtProp->execute([':propiedad_id' => $propiedad_id]);
    } 
    // 🌟 LÓGICA DE ENTREGA (Paso 4): Si ya se entregó, puedes pasarla a 'vendida' si manejas ese estado
    elseif ($nuevo_paso === 4) {
        $stmtProp = $conn->prepare("UPDATE propiedad SET estado = 'vendida' WHERE id = :propiedad_id");
        $stmtProp->execute([':propiedad_id' => $propiedad_id]);
    }
    // Si se mueve a pasos 1, 2 o 3, se asegura de que se mantenga 'apartada'
    else {
        $stmtProp = $conn->prepare("UPDATE propiedad SET estado = 'apartada' WHERE id = :propiedad_id");
        $stmtProp->execute([':propiedad_id' => $propiedad_id]);
    }

    $conn->commit();
    header("Location: ../vistas/admin_dashboard.php?mensaje=actualizado_correcto");
    exit;

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    die("Error al actualizar el estado: " . $e->getMessage());
}