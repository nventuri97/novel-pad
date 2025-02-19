<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; style-src 'self' 'unsafe-inline'; frame-src 'self' https://www.google.com/recaptcha/; frame-ancestor 'self'");
header('Content-Type: application/json');

use ZxcvbnPhp\Zxcvbn;

require __DIR__ . '/../utils/db-client.php';
require __DIR__.'/../../vendor/autoload.php';

// Load the config if necessary
$config = require_once __DIR__ . '/../utils/config.php';
session_start();
ob_start();
openlog("admin_change_password.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);


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

if (!isset($_SESSION['admin'])) {
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

if (!isset($_SESSION['force_password_change'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] force_password_change value missing.");

    session_destroy();
    
    http_response_code(403); // Forbidden
    header("Content-Type: text/html");

    echo "<h1>403 Forbidden</h1>";
    echo "<p>Forbidden.</p>";
    exit;
}

$currentPassword = $_POST["currentPassword"] ?? '';
$newPassword = $_POST["newPassword"] ?? '';

// check if passwords are strings
if (!is_string($currentPassword) || !is_string($newPassword)) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Passwords must be strings');

    http_response_code(400);
    $response["message"] = "Passwords must be strings.";
    echo json_encode($response);
    exit;
}

$_SESSION["timeout"] = date("Y-m-d H:i:s", strtotime("+30 minutes"));

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
$result = $zxcvbn->passwordStrength($newPassword, $userInputs = [$_SESSION["admin"]['email']]);
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

try {
    // Get the database connection for authentication
    $admin_conn = db_client::get_connection("admin_db");

    $stmt = $admin_conn->prepare("SELECT password_hash FROM admins WHERE id = :id");
    $stmt->bindParam(':id', $_SESSION["admin"]['id'], PDO::PARAM_INT);
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
    $passwordExpiry = date('Y-m-d H:i:s', strtotime('+90 days'));

    // Update the password and set is_verified to true
    $stmt = $admin_conn->prepare("
        UPDATE admins 
        SET password_hash = :password_hash, is_verified = 1, password_expiry = :password_expiry
        WHERE id = :id
    ");
    $stmt->bindParam(':password_hash', $newPasswordHash);
    $stmt->bindParam(':password_expiry', $passwordExpiry);
    $stmt->bindParam(':id', $_SESSION["admin"]['id'], PDO::PARAM_INT);
    $stmt->execute();

    // Remove the forced password change flag
    if(isset($_SESSION['force_password_change']))
        $_SESSION['force_password_change']=false;

    session_regenerate_id(true);
    $response["success"] = true;
    $response["message"] = "Password changed successfully.";
    
}catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] An error occurred while processing user data. ".$e->getMessage());

    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

ob_end_clean();
echo json_encode($response);
?>