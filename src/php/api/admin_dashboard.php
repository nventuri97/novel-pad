<?php
// admin_dashboard.php

header('Content-Type: application/json');

require '../utils/db-client.php';

openlog("admin_dashboard.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();
ob_start();

$response = [
    "success" => false,
    "message" => "",
];

// Check if the admin is logged in
if (!isset($_SESSION['admin'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Access not authenticated.");

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

$request = $_SERVER['REQUEST_METHOD'];

try {
    switch($request) {
        case 'GET':
            // Retrieve the admin's email (assuming that the session contains an Admin object or individual variables)
            $adminEmail = $_SESSION['admin']['email'] ?? '';

            $auth_conn = db_client::get_connection("authentication_db");        

            // Retrieve all users and their profiles
            $stmt = $auth_conn->prepare("
                SELECT u.email, p.nickname, p.is_premium
                FROM authentication_db.users u
                JOIN novels_db.user_profiles p ON u.id = p.user_id
                WHERE u.is_verified = 1
            ");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response["success"]    = true;
            $response["users"]      = $users;
            $response["adminEmail"] = $adminEmail;  // Pass the admin's email to the frontend
            break;
        case 'POST':
            // Toggle is_premium
            $nickname = $_POST['nickname'] ?? '';
            $newStatus = $_POST['newStatus'] ?? '';

            if (empty($nickname) || $newStatus === '') {
                http_response_code(400);
                $response["message"] = "Missing data (email or newStatus).";
                echo json_encode($response);
                exit;
            }

            $boolValue = ($newStatus === 'true') ? 1 : 0;

            $novels_conn = db_client::get_connection("novels_db");

            $update = $novels_conn->prepare("
                UPDATE user_profiles
                SET is_premium = :val
                WHERE nickname = :nickname
            ");
            $update->bindParam(':val', $boolValue, PDO::PARAM_INT);
            $update->bindParam(':nickname', $nickname);
            $update->execute();

            $response["success"] = true;
            $response["message"] = "Premium status updated.";
            break;
        default:
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Invalid request method.");

            http_response_code(405); // HTTP method not allowed
            header("Content-Type: text/html");

            echo "<h1>405 Method Not Allowed</h1>";
            echo "<p>The request method is not allowed. This method is not allowed.</p>";
            exit;
    }
}
catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] An error occurred while processing user data. ".$e->getMessage());

    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

ob_end_clean();
echo json_encode($response);
exit;
?>