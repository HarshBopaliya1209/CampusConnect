<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer files
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// Email configuration
require __DIR__ . '/email_config.php';


function sendEmail($to, $subject, $message)
{
    $mail = new PHPMailer(true);

    try
    {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        // Sender
        $mail->setFrom(
            MAIL_FROM_EMAIL,
            MAIL_FROM_NAME
        );

        // Receiver
        $mail->addAddress($to);

        // Email format
        $mail->isHTML(true);

        // Subject
        $mail->Subject = $subject;

        // Message
        $mail->Body = $message;

        // Plain-text version
        $mail->AltBody = strip_tags($message);

        // Send email
        $mail->send();

        return true;
    }
    catch (Exception $e)
    {
        echo "<pre>";
        echo "EMAIL ERROR:\n";
        echo $mail->ErrorInfo;
        echo "</pre>";

        return false;
    }
}

?>