<?php
header('Content-Type: application/json');
session_start();

// =================================================================
// 🛡️ CONTROL DE SEGURIDAD: VERIFICAR LOGIN Y ROL DE CLIENTE
// =================================================================
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 3) {
    echo json_encode([
        "success" => false, 
        "message" => "Acceso denegado. Debes iniciar sesión como cliente para agendar."
    ]);
    exit;
}

// Requerimos la conexión subiendo un nivel, ya que esta API está dentro de la carpeta /apis/
require_once '../conexion.php';

// Recogemos los datos enviados de forma asíncrona por el JavaScript (Fetch)
$cliente_id   = $_SESSION['usuario_id'];
$propiedad_id = isset($_POST['propiedad_id']) ? intval($_POST['propiedad_id']) : 0;
$fecha        = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
// 🛠️ CORRECCIÓN AQUÍ: Se arregló el error de sintaxis en el isset de $_POST
$hora         = isset($_POST['hora']) ? trim($_POST['hora']) : '';

// Validamos que los campos obligatorios vengan llenos
if (empty($propiedad_id) || empty($fecha) || empty($hora)) {
    echo json_encode([
        "success" => false, 
        "message" => "Error: Faltan parámetros obligatorios para procesar la cita."
    ]);
    exit;
}

try {
    // =================================================================
    // 🔍 COMPROBACIÓN ANTIDUPLICADOS CON EL NUEVO ENFOQUE DE CUPOS
    // =================================================================
    // Primero, contamos cuántos asesores activos hay totales
    $stmt_agentes = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = 2 AND estado_cuenta = 'aprobado'");
    $stmt_agentes->execute();
    $total_agentes = intval($stmt_agentes->fetchColumn());
    if ($total_agentes === 0) { $total_agentes = 3; } // Respaldo

    // Contamos cuántas citas ya se reservaron para esa fecha y hora exacta
    $check_stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM citas 
        WHERE fecha = ? AND hora = ? AND estado != 'rechazada'
    ");
    $check_stmt->execute([$fecha, $hora]);
    $citas_existentes = intval($check_stmt->fetchColumn());
    
    // Si ya no quedan cupos/asesores libres para este bloque de tiempo en general
    if ($citas_existentes >= $total_agentes) {
        echo json_encode([
            "success" => false, 
            "message" => "Lo sentimos, los cupos para este horario se acaban de agotar. Por favor, selecciona una hora diferente."
        ]);
        exit;
    }

    // Extra: Validar que el MISMO cliente no intente agendar la misma casa el mismo día a la misma hora
    $duplicado_cliente = $conn->prepare("SELECT id FROM citas WHERE cliente_id = ? AND propiedad_id = ? AND fecha = ? AND hora = ? AND estado != 'rechazada'");
    $duplicado_cliente->execute([$cliente_id, $propiedad_id, $fecha, $hora]);
    if ($duplicado_cliente->fetch()) {
        echo json_encode([
            "success" => false, 
            "message" => "Ya tienes una solicitud de recorrido registrada para esta propiedad en este mismo horario."
        ]);
        exit;
    }

    // =================================================================
    // 💾 INSERCIÓN DE LA NUEVA CITA EN ESTADO 'PENDIENTE'
    // =================================================================
    $stmt = $conn->prepare("
        INSERT INTO citas (cliente_id, propiedad_id, fecha, hora, estado) 
        VALUES (?, ?, ?, ?, 'pendiente')
    ");
    
    $resultado = $stmt->execute([$cliente_id, $propiedad_id, $fecha, $hora]);

    if ($resultado) {
        echo json_encode([
            "success" => true, 
            "message" => "¡Solicitud de recorrido enviada con éxito! El administrador validará la agenda para confirmarte un asesor."
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "No se pudo completar el registro en la agenda interna. Intenta de nuevo."
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Fallo interno en la base de datos: " . $e->getMessage()
    ]);
}
?>