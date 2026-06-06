<?php
session_start();
require_once '../conexion.php'; // Asegura la ruta correcta hacia tu conexión

// 1. Validar que el usuario tenga una sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../vistas/login.php");
    exit;
}

// 2. Validar que se haya recibido el ID de la propiedad
if (!isset($_POST['propiedad_id']) || !is_numeric($_POST['propiedad_id'])) {
    header("Location: ../index.php");
    exit;
}

$usuario_id = intval($_SESSION['usuario_id']);
$propiedad_id = intval($_POST['propiedad_id']);
$fecha_actual = date('Y-m-m H:i:s');

try {
    // 3. Opcional: Verificar si la propiedad sigue disponible antes de apartar
    $check = $conn->prepare("SELECT estado FROM propiedad WHERE id = :id LIMIT 1");
    $check->execute([':id' => $propiedad_id]);
    $prop = $check->fetch(PDO::FETCH_ASSOC);

    if (!$prop || strtolower($prop['estado']) !== 'disponible') {
        header("Location: ../detalles.php?id=" . $propiedad_id . "&error=no_disponible");
        exit;
    }

    // Comienza una transacción para asegurar que ambos cambios ocurran juntos
    $conn->beginTransaction();

    // 4. INSERTAR en la tabla const_apartados vinculando el usuario de la sesión y el paso_legal en 1
    $sql_apartado = "INSERT INTO const_apartados (usuario_id, propiedad_id, fecha_apartado, paso_legal) 
                     VALUES (:usuario_id, :propiedad_id, :fecha, 1)";
    $stmt_apartado = $conn->prepare($sql_apartado);
    $stmt_apartado->execute([
        ':usuario_id'    => $usuario_id,
        ':propiedad_id'  => $propiedad_id,
        ':fecha'         => $fecha_actual
    ]);

    // 5. ACTUALIZAR el estado en la tabla propiedad a 'apartado'
    $sql_propiedad = "UPDATE propiedad SET estado = 'apartado' WHERE id = :propiedad_id";
    $stmt_propiedad = $conn->prepare($sql_propiedad);
    $stmt_propiedad->execute([':propiedad_id' => $propiedad_id]);

    // Confirmar cambios en la base de datos
    $conn->commit();

    // 6. Redireccionar directamente al dashboard del cliente para ver el avance
    header("Location: ../vistas/cliente_dashboard.php");
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    die("Error al procesar el apartado: " . $e->getMessage());
}