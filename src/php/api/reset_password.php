<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; style-src 'self' 'unsafe-inline'; frame-src 'self' https://www.google.com/recaptcha/");
header('Content-Type: application/json'); // Ensure the response is JSON
use ZxcvbnPhp\Zxcvbn;

require '../utils/db-client.php';
require __DIR__.'/../../vendor/autoload.php';

// Enable output buffering to prevent accidental output
ob_start();
openlog("reset_password.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$auth_db = 'authentication_db';

$response = ['success' => false, 'message' => '']; // Default response structure
$request= $_SERVER['REQUEST_METHOD'];

try {
    $auth_conn = db_client::get_connection($auth_db);
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

            // check if the token is valid string
            if (!is_string($reset_token)) {
                syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Invalid token.");

                $response['message'] = "Invalid token.";
                echo json_encode($response);
                exit;
            }

            $stmt = $auth_conn->prepare("SELECT reset_token_expiry FROM users WHERE reset_token = :reset_token");
            $stmt->bindParam(':reset_token', $reset_token);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && strtotime($user['reset_token_expiry']) > time()) {
                syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Token is valid.");

                $response['success'] = true;
                $response['message'] = "Token is valid.";
            } else {
                syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Invalid or expired token.");

                $response['message'] = "Invalid or expired token.";
            }
            break;
            
        case 'PUT':
            syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Received request to reset password.");
            parse_str(file_get_contents("php://input"), $_PUT);
            if (!isset($_PUT['password'])) {
                syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Required parameters missing.");

                $response['message'] = "Required parameters missing.";
                echo json_encode($response);
                exit;
            }
            
            $password = $_PUT['password'];
            $reset_token = $_PUT['reset_token'];

            // Server side password validation
            // Password must be at least 8 characters long
            if (!is_string($password) || strlen($password) < 8) {
                syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password too short.');

                $response['message'] = "Password must be a string at least 8 characters long.";
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

            $stmt = $auth_conn->prepare("SELECT id, email FROM users WHERE reset_token = :reset_token");
            $stmt->bindParam(':reset_token', $reset_token);
            $stmt->execute();

            $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$user_info){
                syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Invalid token.");

                $response['message'] = "Invalid token.";
                echo json_encode($response);
                exit;
            }

            $user_id = $user_info['id'];
            $novel_conn = db_client::get_connection('novels_db');
            $novel_stmt = $novel_conn->prepare("SELECT nickname FROM user_profiles WHERE user_id = :id");
            $novel_stmt->bindParam(':id', $user_id);
            $novel_stmt->execute();

            $nickname = $novel_stmt->fetch(PDO::FETCH_ASSOC)['nickname'];

            // Check password strength using zxcvbn
            $zxcvbn = new Zxcvbn();
            $result = $zxcvbn->passwordStrength($password, $userInputs = [$user_info["email"], $nickname]);
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
} catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Database error: " . $e->getMessage());
    
    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

// Output the response as JSON
ob_end_clean(); // Clear any accidental output
echo json_encode($response);
?>