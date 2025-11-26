<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

function getMailer() {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.hostinger.com';                     
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = 'noreply@devify.live';                     
        $mail->Password   = 'Devify@123';                               
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
        $mail->Port       = 465;                                    

        //Recipients
        $mail->setFrom('noreply@devify.live', 'Smart Test System');

        return $mail;
    } catch (Exception $e) {
        return null;
    }
}
?>
