<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// 1. Seguridad: Solo usuarios autenticados (Admin o el propio usuario si fuera el caso, aquí lo dejamos para Admin)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    echo json_encode([
        "success" => false, 
        "message" => "Acceso denegado. No tienes permisos para realizar esta acción."
    ]);
    exit;
}

require_once '../conexion.php';

// Cargar PHPMailer
require '../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Recibir el ID de la cita
$cita_id = isset($_POST['cita_id']) ? intval($_POST['cita_id']) : 0;

if ($cita_id === 0) {
    echo json_encode([
        "success" => false, 
        "message" => "ID de cita no válido."
    ]);
    exit;
}

try {
    $conn->beginTransaction();

    // 3. Obtener información de la cita antes de cancelarla (para el correo)
    $stmt_info = $conn->prepare("
        SELECT 
            c.fecha, 
            c.hora, 
            u_cliente.nombre AS cliente_nombre, 
            u_cliente.email AS cliente_correo, 
            u_prop.titulo AS propiedad_nombre
        FROM citas c
        INNER JOIN usuarios u_cliente ON c.cliente_id = u_cliente.id
        INNER JOIN propiedad u_prop ON c.propiedad_id = u_prop.id
        WHERE c.id = ?
    ");
    $stmt_info->execute([$cita_id]);
    $datos_cita = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$datos_cita) {
        throw new Exception("No se encontró la información de la cita.");
    }

    // 4. Actualizar el estado a 'cancelada'
    // Permitimos cancelar tanto las 'pendiente' como las ya 'confirmada'
    $stmt = $conn->prepare("
        UPDATE citas 
        SET estado = 'cancelada' 
        WHERE id = ? AND estado != 'cancelada'
    ");
    $stmt->execute([$cita_id]);

    if ($stmt->rowCount() > 0) {
        
        $fecha_formateada = date('d/m/Y', strtotime($datos_cita['fecha']));

        // 5. Configuración y envío del correo con PHPMailer
        $mail = new PHPMailer(true);

        // Configuración SMTP (Usa tus mismas credenciales de siempre)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tu_correo@gmail.com';                
        $mail->Password   = 'tu_contraseña_de_aplicacion';        
        $mail->SMTPSecure = PHPMailer::ENCRYPT_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Destinatarios
        $mail->setFrom('no-reply@domurealestate.com', 'Domu Real Estate');
        $mail->addAddress($datos_cita['cliente_correo'], $datos_cita['cliente_nombre']);

        // Contenido HTML del correo de cancelación
        $mail->isHTML(true);
        $mail->Subject = "Actualización de tu cita: Cancelada - Domu Real Estate";
        
        $mail->Body = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Cita Cancelada</title>
        </head>
        <body style='font-family: Arial, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 20px; margin: 0;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;'>
                
                <div style='background-color: #ef4444; padding: 30px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 800;'>Cita Cancelada</h1>
                    <p style='color: #fee2e2; margin: 5px 0 0 0; font-size: 14px;'>Notificación sobre tu solicitud de visita.</p>
                </div>

                <div style='padding: 30px;'>
                    <p style='font-size: 16px; margin-top: 0;'>Hola <strong>" . htmlspecialchars($datos_cita['cliente_nombre']) . "</strong>,</p>
                    <p style='font-size: 14px; color: #64748b; line-height: 1.5;'>Te informamos que la cita programada para visitar el siguiente inmueble ha sido <strong>cancelada</strong>:</p>
                    
                    <div style='background-color: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; border: 1px solid #e2e8f0;'>
                        <h3 style='margin: 0 0 10px 0; color: #0f172a; font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;'>" . htmlspecialchars($datos_cita['propiedad_nombre']) . "</h3>
                        
                        <table style='width: 100%; font-size: 14px; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 5px 0; color: #64748b; width: 40%;'><strong>Fecha original:</strong></td>
                                <td style='padding: 5px 0; color: #1e293b; text-decoration: line-through;'>" . $fecha_formateada . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 5px 0; color: #64748b;'><strong>Hora original:</strong></td>
                                <td style='padding: 5px 0; color: #1e293b; text-decoration: line-through Triton;'>" . htmlspecialchars($datos_cita['hora']) . " hrs</td>
                            </tr>
                        </table>
                    </div>

                    <p style='font-size: 14px; color: #64748b; line-height: 1.5;'>Si crees que esto se debe a un error o deseas agendar una nueva fecha para este u otro inmueble, puedes ingresar nuevamente a nuestra plataforma o ponerte en contacto con soporte técnico.</p>
                </div>

                <div style='background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;'>
                    &copy; " . date('Y') . " Domu Real Estate. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->send();
        $conn->commit();

        echo json_encode([
            "success" => true, 
            "message" => "La cita ha sido cancelada correctamente y se ha notificado al cliente por correo."
        ]);

    } else {
        $conn->rollBack();
        echo json_encode([
            "success" => false, 
            "message" => "La cita no pudo ser cancelada (puede que ya estuviera cancelada)."
        ]);
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        "success" => false, 
        "message" => "Error en el proceso: " . $e->getMessage()
    ]);
}
?>