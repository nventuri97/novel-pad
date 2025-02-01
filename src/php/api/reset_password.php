<?php
header('Content-Type: application/json'); // Ensure the response is JSON

include '../utils/db-client.php';

// Enable output buffering to prevent accidental output
ob_start();

$auth_db = 'authentication_db';
$novel_db = 'novels_db';

$response = ['success' => false, 'message' => '', 'username' => '', 'email' => '', 'full_name' => '']; // Default response structure
$request= $_SERVER['REQUEST_METHOD'];

try {
    switch ($request) {
        case 'POST':
            if (!isset($_POST['reset_token']) || !isset($_POST['id'])) {
                $response['message'] = "Required parameters missing.";
                echo json_encode($response);
                exit;
            }
            
            $reset_token = $_POST['reset_token'];
            $user_id = $_POST['id'];

            $auth_conn = db_client::get_connection($auth_db);
            $stmt = $auth_conn->prepare("SELECT username, reset_token_expiry FROM users WHERE id = :id AND reset_token = :reset_token");
            $stmt->bindParam(':id', $user_id);
            $stmt->bindParam(':reset_token', $reset_token);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $novel_conn = db_client::get_connection($novel_db);
            $stmt = $novel_conn->prepare("SELECT email, full_name FROM user_profiles WHERE user_id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();

            $novel_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && strtotime($user['reset_token_expiry']) > time()) {
                $response['success'] = true;
                $response['message'] = "Token is valid.";
                $response['username'] = $user['username'];
                $response['email'] = $novel_user['email'];
                $response['full_name'] = $novel_user['full_name'];
            } else {
                $response['message'] = "Invalid token or token expired.";
            }
            break;
            
        case 'PUT':
            parse_str(file_get_contents("php://input"), $_PUT);
            if (!isset($_PUT['password']) || !isset($_PUT['id'])) {
                $response['message'] = "Required parameters missing.";
                echo json_encode($response);
                exit;
            }
            
            $password = $_PUT['password'];
            $user_id = $_PUT['id'];

            $expiry = date('Y-m-d H:i:s', strtotime('now')); // Invalid token

            $auth_conn = db_client::get_connection($auth_db);
            $stmt = $auth_conn->prepare("UPDATE users SET password_hash = :password, reset_token = NULL, reset_token_expiry = :expiry WHERE id = :id");
            $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
            $stmt->bindParam(':expiry', $expiry);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();

            $response['success'] = true;
            $response['message'] = "Password reset successfully.";
            break;
        default:
            $response['message'] = "Invalid request method.";
            break;
    }
} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
}

// Output the response as JSON
ob_end_clean(); // Clear any accidental output
echo json_encode($response);
?>