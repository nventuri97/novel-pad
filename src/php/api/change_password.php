<?php
// admin/php/admin_change_password.php
header('Content-Type: application/json');

use ZxcvbnPhp\Zxcvbn;

require __DIR__ . '/../utils/user.php';
require __DIR__ . '/../utils/db-client.php';
require __DIR__.'/../../vendor/autoload.php';

// Load the config if necessary
$config = require __DIR__ . '/../utils/config.php';
session_start();
ob_start();
openlog("change_password.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);


$response = [
    "success" => false,
    "message" => ""
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Content-Type: text/html");

    echo "<h1>405 Method Not Allowed</h1>";
    echo "<p>The request method is not allowed. This method is not allowed.</p>";
    exit;
}

if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] User not authenticated.");

    session_destroy();
    http_response_code(401); // Unauthorized
    header("Content-Type: text/html");

    echo "<h1>401 User not authenticated</h1>";
    echo "<p>The user is not authorized.</p>";
    exit;
}

if(!isset($_SESSION["timeout"]) || $_SESSION["timeout"] < date("Y-m-d H:i:s")) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Session expired.");

    session_destroy();
    http_response_code(419); // Timeout error
    header("Content-Type: text/html");

    echo "<h1>419 Session expired</h1>";
    echo "<p>The user session is expired, try to login again.</p>";
    exit;
}

$currentPassword = $_POST["currentPassword"] ?? '';
$newPassword = $_POST["newPassword"] ?? '';

if (!is_string($currentPassword) || !is_string($newPassword)) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Passwords must be string');

    http_response_code(400);
    $response["message"] = "Password must be a string.";
    echo json_encode($response);
    exit;
}

if (empty($currentPassword) || empty($newPassword)) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Attempt to change password with current password or new password empty');
    
    http_response_code(400);
    $response["message"] = "New password is required.";
    echo json_encode($response);
    exit;
}

// Server side password validation
// Password must be at least 8 characters long
if (strlen($newPassword) < 8) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password too short.');

    $response['message'] = "Password must be at least 8 characters long.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// Password must contain at least one uppercase letter, one lowercase letter, one number, and no special characters
$password_regex='/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/';
if (preg_match($password_regex, $newPassword)){
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password too weak.');

    $response['message'] = "Password must agree password policy.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// Check password strength using zxcvbn
$zxcvbn = new Zxcvbn();
$result = $zxcvbn->passwordStrength($newPassword, $userInputs = [$_SESSION["user"]->get_email(), $_SESSION["user"]->get_nickname()]);
if ($result['score']<4){
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password too weak.');

    $response['message'] = "Password too weak.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

if ($currentPassword === $newPassword){
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  New password corresponds to current one.');

    $response['message'] = "New password must be different from the current one";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// Update the session timeout
$_SESSION["timeout"] = date("Y-m-d H:i:s", strtotime('+30 minutes'));

try {
    // Get the database connection for authentication
    $auth_conn = db_client::get_connection("authentication_db");
    $user_id=$_SESSION["user"]->get_id();

    $stmt = $auth_conn->prepare("SELECT password_hash FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $storedPassword = $stmt->fetchColumn();

    if (!$storedPassword || !password_verify($currentPassword, $storedPassword)) {
        syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Incorrect password.');

        $response["message"] = "Incorrect password.";
        echo json_encode($response);
        ob_end_flush();
        exit;
    }

    // Calculate the hash of the new password (using BCRYPT)
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update the password and set is_verified to true
    $stmt = $auth_conn->prepare("
        UPDATE users
        SET password_hash = :password_hash
        WHERE id = :id
    ");
    $stmt->bindParam(':password_hash', $newPasswordHash);
    $stmt->bindParam(':id', $_SESSION["user"]->get_id(), PDO::PARAM_INT);
    $stmt->execute();

    session_regenerate_id(true);
    $response["success"] = true;
    $response["message"] = "Password changed successfully.";
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] An error occurred while processing user data. ".$e->getMessage());

    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

ob_end_clean();
echo json_encode($response);
exit;
?>