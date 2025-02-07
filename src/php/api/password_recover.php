<?php
header('Content-Type: application/json'); // Ensure the response is JSON

include '../utils/db-client.php';
require '../utils/mail-client.php';

// Enable output buffering to prevent accidental output
ob_start();
openlog("password_recover.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$auth_db = 'authentication_db';
$novel_db = 'novels_db';

$response = ['success' => false, 'message' => '']; // Default response structure

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'];

        // Basic validation
        if (empty($email)) {
            syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password recover attempt without email.');

            $response['message'] = "Email is required.";
            echo json_encode($response);
            exit;
        }

        syslog(LOG_INFO, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password recovery request.');

        // Database operations
        $auth_conn = db_client::get_connection($auth_db);
        $auth_stmt = $auth_conn->prepare("SELECT id FROM users WHERE email = :email");
        $auth_stmt->bindParam(':email', $email);
        $auth_stmt->execute();

        if ($auth_stmt->rowCount() === 0) { //TODO: wrong email (?)
            syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password recover attempt with invalid recovery token.');

            $response['message'] = "Invalid token.";
            echo json_encode($response);
            exit;
        }

        $user = $auth_stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['id'];
        $token = bin2hex(random_bytes(16)); // Secure random token
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

        // Save token and expiry to the database
        $update_stmt = $auth_conn->prepare("UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE id = :id");
        $update_stmt->bindParam(':token', $token);
        $update_stmt->bindParam(':expiry', $expiry);
        $update_stmt->bindParam(':id', $user_id);
        $update_stmt->execute();


        // Send verification email
        echo "Sending email...";
        $mailSent = sendRecoveryPwdMail($email, $token, $user_id);
        if (!$mailSent) {
            syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Failed to send password recovery email.');

            $response['message'] = "Failed to send verification email.";
        } else {
            syslog(LOG_INFO, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password recovery email sent.');

            // Successful verification message
            $response['success'] = true;
            $response['message'] = "Mail to password recovery send correctly!";
        }
    } else {
        syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Invalid request method.');

        $response['message'] = "Invalid request method.";
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Database error: ' . $e->getMessage());
    
    $response['message'] = "Database error: " . $e->getMessage();
}

// Output the response as JSON
ob_end_clean(); // Clear any accidental output
echo json_encode($response);
?>