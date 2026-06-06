<?php
// ==========================================
// 1. CONFIGURACIÓN DE ERRORES Y CABECERAS
// ==========================================
ini_set('display_errors', 0); // Apagamos errores visuales para no romper respuestas JSON/Header
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
session_start();

// ==========================================
// 2. CAPA DE SEGURIDAD: CONTROL DE ACCESO
// ==========================================
// Permitimos la entrada únicamente a Administradores (Rol 1) y Agentes (Rol 2)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol_id'] != 1 && $_SESSION['rol_id'] != 2)) {
    echo json_encode([
        "success" => false, 
        "message" => "Acceso denegado. No cuentas con los privilegios necesarios para eliminar registros."
    ]);
    exit;
}

// Conexión limpia al motor de la Base de Datos
require_once '../conexion.php';

// ==========================================
// 3. PROCESAMIENTO DEL ID DE LA CITA
// ==========================================
// Detecta el ID ya sea que viaje por URL (?id=X) o por una petición Fetch/POST
$cita_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if ($cita_id === 0) {
    echo json_encode([
        "success" => false, 
        "message" => "ID de cita inválido o parámetro ausente."
    ]);
    exit;
}

try {
    // ==========================================
    // 4. EJECUCIÓN DEL BORRADO PERMANENTE
    // ==========================================
    $stmt = $conn->prepare("DELETE FROM citas WHERE id = ?");
    $stmt->execute([$cita_id]);

    // Validamos si la fila realmente existía y fue removida
    if ($stmt->rowCount() > 0) {
        
        // Si la petición vino de un enlace HTML directo tradicional (GET),
        // lo redirigimos de vuelta a la página anterior inyectando el mensaje de éxito.
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SERVER['HTTP_REFERER'])) {
            // Analizamos la URL de procedencia para evitar bucles con parámetros repetidos
            $url_retorno = strtok($_SERVER['HTTP_REFERER'], '?');
            header("Location: " . $url_retorno . "?mensaje=cita_eliminada");
            exit;
        }

        // Respuesta estándar si decides consumirla usando JavaScript/Fetch en el futuro
        echo json_encode([
            "success" => true, 
            "message" => "La cita ha sido eliminada permanentemente del ecosistema."
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "No se pudo eliminar: La cita no existe o ya había sido removida previamente."
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Error crítico en el motor de base de datos: " . $e->getMessage()
    ]);
}
?>