<?php
header('Content-Type: application/json'); // Ensure response is JSON

require '../utils/db-client.php';

session_start();
ob_start();
openlog("admin_logout.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);


$response = [
    'success' => false,
    'message' => ''
];

// Allow only PUT requests
if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Content-Type: text/html");

    echo "<h1>405 Method Not Allowed</h1>";
    echo "<p>The request method is not allowed. This method is not allowed.</p>";
    exit;
}

// Ensure the admin is logged in
if (!isset($_SESSION['admin'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] No admin is logged in.');
  
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

$admin = $_SESSION['admin'];

syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] Logout request received');

// Update the admin's is_logged status
try {
    $admin_conn = db_client::get_connection("admin_db");
    $updateStmt = $admin_conn->prepare("UPDATE admins SET is_logged = 0 WHERE id = :id");
    $updateStmt->bindValue(':id', $admin['id'], PDO::PARAM_INT);
    $updateStmt->execute();
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] DB error: ' . $e->getMessage());
}

// Unset session variables and destroy session
session_unset();
session_destroy();

syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] Admin logged out');

// Send a successful response
$response['success'] = true;
$response['message'] = 'Successful logout';

ob_end_clean();
echo json_encode($response);
