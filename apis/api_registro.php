<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

header('Content-Type: application/json');
session_start(); 
require_once '../conexion.php'; 

require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

if (isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['curp'])) {
    
    if (isset($_FILES['ine_frente']) && isset($_FILES['ine_reverso'])) {
        
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $curp = strtoupper(trim($_POST['curp']));

        // Generamos un Token único y seguro para el enlace del correo
        $token_verificacion = bin2hex(random_bytes(16)); 

        try {
            // Verificar si el correo ya existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => false, "message" => "Este correo ya está registrado."]);
                exit;
            }

            // Mapeo de carpetas de imágenes
            $directorio_destino = __DIR__ . '/../uploads/ine/'; 
            
            if (!file_exists($directorio_destino)) {
                echo json_encode(["success" => false, "message" => "La carpeta de subidas no existe en: " . $directorio_destino]);
                exit;
            }

            $nombre_frente = time() . '_frente_' . basename($_FILES['ine_frente']['name']);
            $nombre_reverso = time() . '_reverso_' . basename($_FILES['ine_reverso']['name']);

            $subida_frente = move_uploaded_file($_FILES['ine_frente']['tmp_name'], $directorio_destino . $nombre_frente);
            $subida_reverso = move_uploaded_file($_FILES['ine_reverso']['tmp_name'], $directorio_destino . $nombre_reverso);

            if ($subida_frente && $subida_reverso) {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                // =================================================================
                // 🛡️ SOLUCIÓN AQUÍ: Forzamos el Query con 'pendiente' y rol_id = 3
                // =================================================================
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol_id, curp, ine_frente, ine_reverso, estado_cuenta, codigo_verificacion) VALUES (:nombre, :email, :hash, :rol_id, :curp, :ine_frente, :ine_reverso, :estado_cuenta, :codigo)");
                
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':hash', $hash);
                
                // 🔥 CORREGIDO: Forzamos el rol_id como el entero 3 (Cliente)
                $stmt->bindValue(':rol_id', 3, PDO::PARAM_INT); 
                
                $stmt->bindParam(':curp', $curp);
                $stmt->bindParam(':ine_frente', $nombre_frente); 
                $stmt->bindParam(':ine_reverso', $nombre_reverso);
                
                // 🚀 AQUÍ LE DECIMOS A MYSQL QUE SÍ O SÍ GUARDE 'pendiente' EN MINÚSCULAS
                $stmt->bindValue(':estado_cuenta', 'pendiente', PDO::PARAM_STR);
                
                $stmt->bindParam(':codigo', $token_verificacion);

                if ($stmt->execute()) {
                    
                    $_SESSION['email_verificar'] = $email;

                    // ==========================================
                    // 📧 ENVÍO DEL CORREO ELECTRÓNICO CON ENLACE
                    // ==========================================
                    try {
                        $mail = new PHPMailer(true);
                        
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com'; 
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'oficialdomu@gmail.com'; 
                        $mail->Password   = 'moxz catq xkcv coml'; 
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        $mail->CharSet    = 'UTF-8';

                        $mail->setFrom('oficialdomu@gmail.com', 'Domu Inmobiliaria');
                        $mail->addAddress($email, $nombre);

                        $enlace_verificacion = "https://omnivore-relive-erased.ngrok-free.dev/domu_oficial/apis/api_verificar_click.php?token=" . $token_verificacion;

                        $mail->isHTML(true);
                        $mail->Subject = '✉️ Verifica tu correo electrónico - Domu';
                        $mail->Body = "
                        <div style='font-family: sans-serif; padding: 30px; background-color: #f3f4f6;'>
                            <div style='max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center;'>
                                <h2 style='color: #111827; margin-bottom: 5px;'>¡Bienvenido a Domu, {$nombre}!</h2>
                                <p style='color: #4b5563; font-size: 14px;'>Para verificar que este correo te pertenece y es verídico, por favor confirma dando clic en el botón de abajo:</p>
                                
                                <div style='margin: 25px 0;'>
                                    <a href='{$enlace_verificacion}' style='background-color: #4f46e5; color: white; padding: 12px 25px; text-decoration: none; font-weight: bold; border-radius: 8px; display: inline-block;'>Confirmar mi correo electrónico</a>
                                </div>
                                
                                <p style='color: #9ca3af; font-size: 11px;'>Una vez confirmado, el administrador procederá a revisar tus documentos de la INE para validar tu cuenta completamente.</p>
                            </div>
                        </div>";

                        $mail->send();

                        echo json_encode([
                            "success" => true, 
                            "message" => "¡Cuenta creada! Se envió un enlace de verificación a tu correo para confirmar que es verídico."
                        ]);

                    } catch (Exception $mailError) {
                        echo json_encode([
                            "success" => true, 
                            "message" => "Usuario registrado con éxito, pero el correo falló. Detalles: " . $mail->ErrorInfo
                        ]);
                    }

                } else {
                    echo json_encode(["success" => false, "message" => "No se pudieron guardar los datos en la tabla usuarios."]);
                }

            } else {
                echo json_encode(["success" => false, "message" => "Error al mover los archivos de la INE a la carpeta uploads."]);
            }

        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error de Base de Datos: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Faltan las fotos de la INE por cargar."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Faltan datos de texto obligatorios."]);
}
?>