<?php
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require __DIR__.'/../../vendor/autoload.php';
openlog("add_novel.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
// Take dynamic configuration from docker environment 
$config = include 'config.php';

function sendVerificationMail($user_mail, $token) {
    global $config;

    try {
        $mail = new PHPMailer(true);
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Setting up SMTP connection.");
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

        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Sending verification email.");
        $result=$mail->send();
        $mail->smtpClose();

        return $result;
    } catch (Exception $e) {
        syslog(LOG_ERR, "Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

function sendAllertMail($user_mail, $requestType) {
    global $config;

    try {
        $mail = new PHPMailer(true);
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Setting up SMTP connection.");
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

        if ($requestType === 'admin') {
            $mail->Subject = 'Security Alert: Admin Login Attempt Detected';
            $mail->Body = '<p>We detected an admin login attempt using your email address. If this was not you, please change your password immediately on our website to secure your account.</p>';
            $mail->AltBody = 'We detected an admin login attempt using your email address. If this was not you, please change your password immediately on our website to secure your account.';
        } else if ($requestType === 'user') {
            $mail->Subject = 'Security Alert: Registration Attempt Detected';
            $mail->Body = '<p>We detected a registration attempt using your email address. If this was not you, please change your password immediately on our website to secure your account.</p>';
            $mail->AltBody = 'We detected a registration attempt using your email address. If this was not you, please change your password immediately on our website to secure your account.';
        } else if ($requestType === 'admin_blocked'){
            $mail->Subject = 'Security Alert: Admin Account Blocked';
            $mail->Body = '<p>Your account has been blocked due to too many login attempts. Please contact the administrator to unblock your account.</p>';
            $mail->AltBody = 'Your account has been blocked due to too many login attempts. Please contact the administrator to unblock your account.';
        }

        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Sending allert email.");
        $result=$mail->send();
        $mail->smtpClose();

        return $result;
    } catch (Exception $e) {
        syslog(LOG_ERR, "Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

function sendRecoveryPwdMail($user_mail, $token){
    global $config;

    try {
        $mail = new PHPMailer(true);

        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Setting up SMTP connection.");
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
        $resetUrl = $config['novelpad_url'] . '/reset-password.html?token=' . urlencode($token);

        $mail->Subject = 'Password Recovery';
        $mail->Body = '<p>We received your request to recover your password. To reset password click <a href="' . htmlspecialchars($resetUrl) . '">here</a>.</p>';
        $mail->AltBody = 'We received your request to recover your password. To reset password click this link: ' . $resetUrl;

        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Sending recovery password email.");

        $result=$mail->send();
        $mail->smtpClose();

        return $result;
    } catch (Exception $e) {
        syslog(LOG_ERR, "Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
?>