<?php
// backend/procesar_recuperacion.php
require 'conexion.php';

// Importar clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Correo inválido."]);
    exit();
}

try {
    // 1. Verificar si el correo existe en la base de datos
    $stmt = $pdo->prepare("SELECT id, nombre_completo FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // 2. Generar un Token único y su fecha de expiración (1 hora)
        $token = bin2hex(random_bytes(32)); 
        $expiracion = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Guardar el token en la base de datos
        $update = $pdo->prepare("UPDATE usuarios SET token_recuperacion = ?, expiracion_token = ? WHERE email = ?");
        $update->execute([$token, $expiracion, $email]);

        // 3. Preparar el correo con PHPMailer
        $mail = new PHPMailer(true);

        // Configuración del Servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // ---> MODIFICA ESTOS DOS DATOS <---
        $mail->Username   = ''; 
        $mail->Password   = ''; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Remitente y Destinatario
        $mail->setFrom('vaaro777@gmail.com', 'InmobiliariaLibre');
        $mail->addAddress($email, $usuario['nombre_completo']);

        // Contenido del Correo
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Recuperación de Contraseña - InmobiliariaLibre';

        // Enlace que llevará a tu futura página de cambio de contraseña
        $enlace = "http://localhost/EstructuraDir/restablecer.php?token=" . $token;

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
                <div style='background-color: #0d6efd; padding: 20px; text-align: center; color: white;'>
                    <h2>InmobiliariaLibre</h2>
                </div>
                <div style='padding: 20px; color: #333;'>
                    <h3>Hola, {$usuario['nombre_completo']}</h3>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el botón de abajo para crear una nueva:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$enlace}' style='background-color: #198754; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Restablecer mi contraseña</a>
                    </div>
                    <p style='color: #666; font-size: 0.9em;'>Este enlace caducará en 1 hora. Si no solicitaste este cambio, simplemente ignora este correo.</p>
                </div>
            </div>
        ";

        $mail->send();
    }

    // Respuesta genérica (por seguridad, siempre decimos que enviamos el correo, exista o no, para que los hackers no adivinen correos)
    echo json_encode(["status" => "success", "message" => "Si el correo existe en nuestro sistema, hemos enviado un enlace de recuperación. Revisa tu bandeja de entrada o spam."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Error al enviar el correo. Por favor contacta a soporte."]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de base de datos."]);
}
?>
