<?php
// admin/php/admin_change_password.php
header('Content-Type: application/json');

use ZxcvbnPhp\Zxcvbn;

require_once __DIR__ . '/../utils/db-client.php';
require __DIR__.'/../../vendor/autoload.php';

// Load the config if necessary
$config = require_once __DIR__ . '/../utils/config.php';
session_start();
ob_start();

$response = [
    "success" => false,
    "message" => ""
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Location: /error.html?error=" . urlencode('Invalid request method'));
    exit;
}

if (!isset($_SESSION['admin'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] User not authenticated.");

    session_destroy();
    http_response_code(401); // Unauthorized
    header("Location: /error.html?error=" . urlencode('User not authenticated'));
    exit;
}

if(!isset($_SESSION["timeout"]) || $_SESSION["timeout"] < date("Y-m-d H:i:s")) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Session expired.");

    session_destroy();
    http_response_code(419); // Timeout error
    header("Location: /error.html?error=" . urlencode('Session expired'));
    exit;
}

if (!isset($_SESSION['force_password_change'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] force_password_change value missing.");

    session_destroy();
    
    http_response_code(403); // Forbidden
    header("Location: /error.html?error=" . urlencode('Missing some required values'));
    exit;
}
    
$newPassword = $_POST["newPassword"] ?? '';

if (empty($newPassword)) {
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

try {
    // Get the database connection for authentication
    $admin_conn = db_client::get_connection("admin_db");

    // Calculate the hash of the new password (using BCRYPT)
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update the password and set is_verified to true
    $stmt = $admin_conn->prepare("
        UPDATE admins 
        SET password_hash = :password_hash, is_verified = 1
        WHERE id = :id
    ");
    $stmt->bindParam(':password_hash', $newPasswordHash);
    $stmt->bindParam(':id', $_SESSION["admin"]['id'], PDO::PARAM_INT);
    $stmt->execute();

    // Remove the forced password change flag
    unset($_SESSION['force_password_change']);

    $response["success"] = true;
    $response["message"] = "Password changed successfully.";
    
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] An error occurred while processing user data. ".$e->getMessage());

    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

ob_end_clean();
echo json_encode($response);
?>