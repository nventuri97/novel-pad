<?php
header('Content-Type: application/json'); // Ensure the response is JSON
use ZxcvbnPhp\Zxcvbn;

require '../utils/db-client.php';
require __DIR__.'/../../vendor/autoload.php';

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
            $email = $_PUT['email'];
            $nickname = $_PUT['nickname'];

            // Server side password validation
            // Password must be at least 8 characters long
            if (strlen($password) < 8) {
                syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password too short.');

                $response['message'] = "Password must be at least 8 characters long.";
                echo json_encode($response);
                ob_end_flush();
                exit;
            }

            // Password must contain at least one uppercase letter, one lowercase letter, one number, and no special characters
            $password_regex='/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/';
            if (preg_match($password_regex, $password)){
                syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password too weak.');

                $response['message'] = "Password must agree password policy.";
                echo json_encode($response);
                ob_end_flush();
                exit;
            }

            // Check password strength using zxcvbn
            $zxcvbn = new Zxcvbn();
            $result = $zxcvbn->passwordStrength($password, $userInputs = [$email, $nickname]);
            if ($result['score']<4){
                syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password too weak.');

                $response['message'] = "Password too weak.";
                echo json_encode($response);
                ob_end_flush();
                exit;
            }

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

            http_response_code(405); // HTTP method not allowed
            header("Content-Type: text/html");

            echo "<h1>405 Method Not Allowed</h1>";
            echo "<p>The request method is not allowed. This method is not allowed.</p>";
            exit;
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Database error: " . $e->getMessage());
    
    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

// Output the response as JSON
ob_end_clean(); // Clear any accidental output
echo json_encode($response);
?>