<?php
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require __DIR__.'/../vendor/autoload.php';
// Take dynamic configuration from docker environment 
$config = include 'config.php';

function sendVerificationMail($user_mail, $token) {
    global $config;

    try {
        $mail = new PHPMailer(true);
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_user'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_encryption'];
        $mail->Port = intval($config['smtp_port']);

        // Recipients
        $mail->setFrom($mail->Username, 'Novelpad');
        $mail->addAddress($user_mail); // Replace with the user's email

        // Content
        $mail->isHTML(true);
        $confirmationUrl = $config['novelpad_url'] . '/confirm.html?token=' . urlencode($token);

        $mail->Subject = 'Subscription Confirmation';
        $mail->Body = '<p>Thank you for subscribing! Please confirm your subscription by clicking <a href="' . htmlspecialchars($confirmationUrl) . '">here</a>.</p>';
        $mail->AltBody = 'Thank you for subscribing! Please confirm your subscription by visiting this link: ' . $confirmationUrl;

        $result=$mail->send();
        echo 'Confirmation email sent successfully.';
        $mail->smtpClose();

        return $result;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

function sendRecoveryPwdMail($user_mail, $token){
    
}

?>