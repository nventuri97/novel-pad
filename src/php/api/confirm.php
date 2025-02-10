<?php
header('Content-Type: application/json'); 

include '../utils/db-client.php';
include '../utils/user.php';

ob_start();
openlog("confirm.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$auth_db = 'authentication_db';
$novels_db = 'novels_db';

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    $error_message = urlencode('Invalid request method');
    header("Location: /error.html?error=$error_message");
    exit;
}

try{
    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  User registration confirmation request received.");

    $token = $_POST['token'];

    $auth_conn = db_client::get_connection($auth_db);
    $auth_stmt = $auth_conn->prepare("SELECT id FROM users WHERE verification_token = :token");
    $auth_stmt->bindParam(':token', $token);
    $auth_stmt->execute();

    if ($auth_stmt->rowCount() === 0) {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  User sent invalid token.");

        $response['message'] = "Invalid token.";
        echo json_encode($response);
        exit;
    }
    
    $auth_user = $auth_stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $auth_user['id'];
    $auth_stmt = $auth_conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id=:id");
    $auth_stmt->bindParam(':id', $user_id);
    $auth_stmt->execute();

    $response['success'] = true;
    $response['message'] = "Registration completed successfully!";

    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  User registration completed successfully.");
}catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Database error: ". $e->getMessage());

    $response['message'] = "Database error: " . $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
?>