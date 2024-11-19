<?php
header('Content-Type: application/json'); // Ensure the response is JSON

include 'db-client.php';
include 'user.php';

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
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $auth_stmt = $auth_conn->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)");
        $auth_stmt->bindParam(':username', $username);
        $auth_stmt->bindParam(':password_hash', $passwordHash);
        $auth_stmt->execute();

        $user_id = $auth_conn->lastInsertId();

        $novels_conn = db_client::get_connection($novels_db);
        $novels_stmt = $novels_conn->prepare("INSERT INTO user_profiles (user_id, email, full_name, is_premium, logged_in) VALUES (:user_id, :email, :full_name, :is_premium, :logged_in)");
        $novels_stmt->bindParam(':user_id', $user_id);
        $novels_stmt->bindParam(':email', $email);
        $novels_stmt->bindParam(':full_name', $full_name);
        $novels_stmt->bindValue(':is_premium', 0, PDO::PARAM_BOOL);
        $novels_stmt->bindValue(':logged_in', 1, PDO::PARAM_BOOL);
        $novels_stmt->execute();

        // Successful registration
        $response['success'] = true;
        $response['message'] = "Registration completed successfully!";

        $user = new User($user_id, $username, $email, $full_name, false, true);
        session_start();

        $_SESSION['user'] = $user;
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
