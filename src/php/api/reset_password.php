<?php
header('Content-Type: application/json'); // Ensure the response is JSON

include '../utils/db-client.php';

// Enable output buffering to prevent accidental output
ob_start();
openlog("reset_password.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$auth_db = 'authentication_db';
$novel_db = 'novels_db';

$response = ['success' => false, 'message' => '', 'email' => '', 'nickname' => '']; // Default response structure
$request= $_SERVER['REQUEST_METHOD'];

try {
    switch ($request) {
        case 'POST':
            syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Received request to verify reset token.");
            if (!isset($_POST['reset_token']) || !isset($_POST['id'])) {
                syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Required parameters missing.");

                $response['message'] = "Required parameters missing.";
                echo json_encode($response);
                exit;
            }
            
            $reset_token = $_POST['reset_token'];
            $user_id = $_POST['id'];

            $auth_conn = db_client::get_connection($auth_db);
            $stmt = $auth_conn->prepare("SELECT email, reset_token_expiry FROM users WHERE id = :id AND reset_token = :reset_token");
            $stmt->bindParam(':id', $user_id);
            $stmt->bindParam(':reset_token', $reset_token);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $novel_conn = db_client::get_connection($novel_db);
            $stmt = $novel_conn->prepare("SELECT nickname FROM user_profiles WHERE user_id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();

            $novel_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && strtotime($user['reset_token_expiry']) > time()) {
                syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Token is valid.");

                $response['success'] = true;
                $response['message'] = "Token is valid.";
                $response['email'] = $novel_user['email'];
                $response['nickname'] = $novel_user['nickname'];
            } else {
                syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Invalid or expired token.");

                $response['message'] = "Invalid or expired token.";
            }
            break;
            
        case 'PUT':
            syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Received request to reset password.");
            parse_str(file_get_contents("php://input"), $_PUT);
            if (!isset($_PUT['password']) || !isset($_PUT['id'])) {
                syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Required parameters missing.");

                $response['message'] = "Required parameters missing.";
                echo json_encode($response);
                exit;
            }
            
            $password = $_PUT['password'];
            $user_id = $_PUT['id'];

            syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Invalidating token after password reset.");
            $expiry = date('Y-m-d H:i:s', strtotime('now')); // Invalid token

            $auth_conn = db_client::get_connection($auth_db);
            $stmt = $auth_conn->prepare("UPDATE users SET password_hash = :password, reset_token = NULL, reset_token_expiry = :expiry WHERE id = :id");
            $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
            $stmt->bindParam(':expiry', $expiry);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();

            syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Password reset successfully.");

            $response['success'] = true;
            $response['message'] = "Password reset successfully.";
            break;
        default:
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Invalid request method.");

            $response['message'] = "Invalid request method.";
            break;
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Database error: " . $e->getMessage());
    
    $response['message'] = "Database error: " . $e->getMessage();
}

// Output the response as JSON
ob_end_clean(); // Clear any accidental output
echo json_encode($response);
?>