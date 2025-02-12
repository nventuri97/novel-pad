<?php
header('Content-Type: application/json');

// Include the DB client and mail client 
include '../utils/db-client.php';
require '../utils/mail-client.php';

ob_start();
openlog("admin_recover_password.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$auth_db = 'authentication_db';
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';

        // Basic validation: email is required
        if (empty($email)) {
            syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . '] Admin password recovery attempt without email.');
            $response['message'] = "Email is required.";
            echo json_encode($response);
            exit;
        }

        syslog(LOG_INFO, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . '] Admin password recovery request.');

        // Get the connection and check if there is an admin with this email
        $auth_conn = db_client::get_connection($auth_db);
        $stmt = $auth_conn->prepare("SELECT id FROM admins WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . '] Admin recovery attempt with invalid email.');
            $response['message'] = "Email not found.";
            echo json_encode($response);
            exit;
        }

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        $admin_id = $admin['id'];
        $token = bin2hex(random_bytes(16)); // generate a secure token
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // token valid for 1 hour

        // Update the admin record to save token and expiry
        $update_stmt = $auth_conn->prepare("UPDATE admins SET reset_token = :token, reset_token_expiry = :expiry WHERE id = :id");
        $update_stmt->bindParam(':token', $token);
        $update_stmt->bindParam(':expiry', $expiry);
        $update_stmt->bindParam(':id', $admin_id);
        $update_stmt->execute();

        // Send the password recovery email; you can customize the message if necessary
        $mailSent = sendRecoveryPwdMail($email, $token, $admin_id);
        if (!$mailSent) {
            syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . '] Failed to send admin recovery email.');
            $response['message'] = "Failed to send recovery email.";
        } else {
            syslog(LOG_INFO, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . '] Admin recovery email sent.');
            $response['success'] = true;
            $response['message'] = "Recovery email sent successfully!";
        }
    } else {
        syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . '] Invalid request method.');
        $response['message'] = "Invalid request method.";
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . '] Database error: ' . $e->getMessage());
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . '] Error: ' . $e->getMessage());
    $response['message'] = "Error: " . $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
exit;
