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

// Verify that the admin is logged in and needs to force password change
if (!isset($_SESSION["admin"]) || !isset($_SESSION['force_password_change'])) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Not authorized or password change not required."
    ]);
    exit;
}

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
        
    } else {
        http_response_code(405);
        $response["message"] = "Method not allowed.";
        echo json_encode($response);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response["message"] = "Database error: " . $e->getMessage();
    echo json_encode($response);
    exit;
}

ob_end_clean();
echo json_encode($response);
?>