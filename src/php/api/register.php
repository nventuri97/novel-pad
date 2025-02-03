<?php
header('Content-Type: application/json'); // Ensure the response is JSON

include '../utils/db-client.php';
include '../utils/user.php';
require '../utils/mail-client.php';

// Enable output buffering to prevent accidental output
ob_start();

$auth_db = 'authentication_db';
$novels_db = 'novels_db';

$response = ['success' => false, 'message' => '']; // Default response structure

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $email = $_POST['email'];
        $full_name = $_POST['full_name'] ?? '';

        // Basic validation
        if (empty($username) || empty($password) || empty($email)) {
            $response['message'] = "Username, password, and email are required.";
            echo json_encode($response);
            exit;
        }

        // Database operations
        $auth_conn = db_client::get_connection($auth_db);
        $novels_conn = db_client::get_connection($novels_db);

        // Check if the username already exists
        $check_username_stmt = $auth_conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $check_username_stmt->bindParam(':username', $username);
        $check_username_stmt->execute();
        $username_exists = $check_username_stmt->fetchColumn() > 0;

        // Check if the email already exists
        $check_email_stmt = $novels_conn->prepare("SELECT COUNT(*) FROM user_profiles WHERE email = :email");
        $check_email_stmt->bindParam(':email', $email);
        $check_email_stmt->execute();
        $email_exists = $check_email_stmt->fetchColumn() > 0;

        if ($email_exists || $username_exists) {
            $response['message'] = "Email or username already exists. Please try again.";
            echo json_encode($response);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        // Generate a random token for email verification
        $token = bin2hex(random_bytes(16));

        // Insert the new user into the authentication database
        $auth_stmt = $auth_conn->prepare("INSERT INTO users (username, password_hash, verification_token) VALUES (:username, :password_hash, :verification_token)");
        $auth_stmt->bindParam(':username', $username);
        $auth_stmt->bindParam(':password_hash', $passwordHash);
        $auth_stmt->bindParam(':verification_token', $token);
        $auth_stmt->execute();

        $user_id = $auth_conn->lastInsertId();

        // Insert the new user into the novels database
        $novels_stmt = $novels_conn->prepare("INSERT INTO user_profiles (user_id, email, full_name, is_premium, logged_in) VALUES (:user_id, :email, :full_name, :is_premium, :logged_in)");
        $novels_stmt->bindParam(':user_id', $user_id);
        $novels_stmt->bindParam(':email', $email);
        $novels_stmt->bindParam(':full_name', $full_name);
        $novels_stmt->bindValue(':is_premium', 0, PDO::PARAM_BOOL);
        $novels_stmt->bindValue(':logged_in', 0, PDO::PARAM_BOOL);
        $novels_stmt->execute();

        // Send verification email
        echo "Sending verification email...";
        sendVerificationMail($email, $token);
        $mailSent = sendVerificationMail($email, $token);
        if (!$mailSent) {
            $response['message'] = "Failed to send verification email.";
        } else {
            echo "Verification email sent.";

            // Successful verification message
            $response['success'] = true;
            $response['message'] = "Verification mail send correctly!";
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
