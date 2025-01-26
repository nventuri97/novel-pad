<?php
header('Content-Type: application/json'); // Ensure the response is JSON

include 'db-client.php';
include 'user.php';
require 'mail-client.php';

// Enable output buffering to prevent accidental output
ob_start();

$auth_db = 'authentication_db';
$novels_db = 'novels_db';

$response = ['success' => false, 'message' => '']; // Default response structure

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'];

        // Basic validation
        if (empty($username) || empty($password) || empty($email)) {
            $response['message'] = "Username, password, and email are required.";
            echo json_encode($response);
            exit;
        }

        // Database operations
        $auth_conn = db_client::get_connection($auth_db);
        $auth_stmt = $auth_conn->prepare("SELECT id FROM users WHERE email = :email");
        $auth_stmt->bindParam(':email', $email);
        $auth_stmt->execute();

        if ($auth_stmt->rowCount() === 0) {
            $response['message'] = "Invalid token.";
            echo json_encode($response);
            exit;
        }


        // Send verification email
        echo "Sending email...";
        sendRecoveryPwdMail($email, $token);
        $mailSent = sendVerificationMail($email, $token);
        if (!$mailSent) {
            $response['message'] = "Failed to send verification email.";
        } else {
            echo "Recovery password email sent.";

            // Successful verification message
            $response['success'] = true;
            $response['message'] = "Mail to password recovery send correctly!";
        }
    } else {
        $response['message'] = "Invalid request method.";
    }
} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
}

// Output the response as JSON
ob_end_clean(); // Clear any accidental output
echo json_encode($response);
?>