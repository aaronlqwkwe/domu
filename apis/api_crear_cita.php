<?php
session_start();
require_once '../conexion.php';

// Importar las clases de PHPMailer al espacio de nombres global
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cambia esta ruta dependiendo de cómo instalaste PHPMailer (ej. '../vendor/autoload.php' si usas Composer)
require '../vendor/autoload.php'; 

if (!isset($_SESSION['usuario_id'])) {
    die("Error: No has iniciado sesión.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $propiedad_id = intval($_POST['propiedad_id']);
    $fecha_cita = $_POST['fecha_cita'];
    $hora_cita = $_POST['hora_cita'];
    $comentarios = htmlspecialchars($_POST['comentarios']);
    
    // Obtener datos extras del usuario de la sesión para el correo
    $nombre_cliente = $_SESSION['nombre'] ?? 'Cliente';
    $correo_cliente = $_SESSION['email'] ?? ''; // Asegúrate de guardar el email en el login de la sesión

    try {
        // 1. Insertar en la Base de Datos
        $stmt = $conn->prepare("INSERT INTO const_citas (usuario_id, propiedad_id, fecha_cita, hora_cita, comentarios, estado) VALUES (:u_id, :p_id, :fecha, :hora, :comm, 'pendiente')");
        $stmt->execute([
            ':u_id'    => $usuario_id,
            ':p_id'    => $propiedad_id,
            ':fecha'   => $fecha_cita,
            ':hora'    => $hora_cita,
            ':comm'    => $comentarios
        ]);

        // 2. Obtener el título de la propiedad para el cuerpo del correo
        $stmt_p = $conn->prepare("SELECT titulo FROM propiedad WHERE id = :p_id LIMIT 1");
        $stmt_p->execute([':p_id' => $propiedad_id]);
        $prop = $stmt_p->fetch(PDO::FETCH_ASSOC);
        $titulo_propiedad = $prop ? $prop['titulo'] : "Inmueble de Interés";

        // ==========================================
        // 🚀 CONFIGURACIÓN Y ENVÍO DE EMAIL
        // ==========================================
        $mail = new PHPMailer(true);

        // Configuración del Servidor SMTP (Ejemplo con Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                     // Servidor SMTP de tu proveedor
        $mail->SMTPAuth   = true;                                 // Habilitar autenticación SMTP
        $mail->Username   = 'tucorreo@gmail.com';                 // Tu correo de Domu o personal
        $mail->Password   = 'moxz catq xkcv coml';        // Tu contraseña de aplicación (No tu contraseña normal)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       // Cifrado TLS seguro
        $mail->Port       = 587;                                  // Puerto de conexión
        $mail->CharSet    = 'UTF-8';

        // Destinatarios
        $mail->setFrom('tucorreo@gmail.com', 'Domu Inmobiliaria');
        $mail->addAddress($correo_cliente, $nombre_cliente);      // Correo del cliente que agendó
        $mail->addReplyTo('soporte@domu.com', 'Soporte Domu');     // Por si el cliente responde

        // Diseño HTML del Correo Electrónico
        $mail->isHTML(true);
        $mail->Subject = '📅 Tu solicitud de recorrido en Domu está bajo revisión';
        
        $mail->Body = "
        <div style='font-family: sans-serif; background-color: #f9fafb; padding: 40px; color: #1f2937;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;'>
                <div style='background-color: #111827; padding: 24px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: bold;'>Domu</h1>
                </div>
                <div style='padding: 32px;'>
                    <h2 style='font-size: 20px; font-weight: bold; margin-top: 0; color: #111827;'>¡Hola, {$nombre_cliente}!</h2>
                    <p style='font-size: 14px; line-height: 1.5; color: #4b5563;'>Hemos recibido con éxito tu solicitud para agendar un recorrido presencial. Nuestro equipo administrativo validará la disponibilidad del asesor y te confirmará a la brevedad.</p>
                    
                    <div style='background-color: #f3f4f6; border-radius: 12px; padding: 20px; margin: 24px 0;'>
                        <h3 style='margin-top: 0; font-size: 14px; color: #374151; text-transform: uppercase; letter-spacing: 0.05em;'>Detalles de la Cita propuesta:</h3>
                        <p style='margin: 6px 0; font-size: 14px;'><strong>Inmueble:</strong> {$titulo_propiedad}</p>
                        <p style='margin: 6px 0; font-size: 14px;'><strong>Fecha:</strong> ".date('d/m/Y', strtotime($fecha_cita))."</p>
                        <p style='margin: 6px 0; font-size: 14px;'><strong>Hora:</strong> {$hora_cita} hrs</p>
                        ".(!empty($comentarios) ? "<p style='margin: 6px 0; font-size: 14px;'><strong>Tus notas:</strong> <i>{$comentarios}</i></p>" : "")."
                    </div>

                    <div style='border-left: 4px solid #6366f1; padding-left: 16px; margin: 24px 0;'>
                        <p style='font-size: 12px; color: #6b7280; margin: 0;'><strong>Nota del sistema:</strong> El estado actual de esta cita es <span style='color: #b45309; font-weight: bold;'>PENDIENTE</span>. Te llegará un segundo correo en cuanto un asesor apruebe el horario.</p>
                    </div>

                    <p style='font-size: 14px; color: #4b5563; margin-bottom: 0;'>Atentamente,<br><strong>El equipo de Domu</strong></p>
                </div>
                <div style='background-color: #f9fafb; padding: 16px; text-align: center; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af;'>
                    © 2026 Domu Inmobiliaria. Todos los derechos reservados.
                </div>
            </div>
        </div>
        ";

        // Enviar el correo físicamente
        $mail->send();

        // 3. Redireccionar con éxito total de vuelta al catálogo o detalle
        header("Location: ../propiedad_detalle.php?id=" . $propiedad_id . "&mensaje=cita_solicitada_y_correo_enviado");
        exit;

    } catch (Exception $e) {
        // Manejo de errores por si falla PHPMailer pero los datos sí se guardaron
        die("La cita se guardó en la BD, pero el correo no se pudo enviar. Detalles del error: {$mail->ErrorInfo}");
    } catch (PDOException $e) {
        die("❌ Error en la Base de Datos: " . $e->getMessage());
    }
}