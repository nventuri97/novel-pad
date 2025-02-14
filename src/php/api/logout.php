<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/user.php';
include '../utils/db-client.php';

openlog("logout.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Content-Type: text/html");

    echo "<h1>405 Method Not Allowed</h1>";
    echo "<p>The request method is not allowed. This method is not allowed.</p>";
    exit;
}

// Ensure the user is logged in
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

$user = $_SESSION['user'];

$novels_db = 'novels_db';
$response = [
    'success' => false,
    'message' => ''
];


syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  Logout request received');

// Destroy session
session_destroy();

syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User logged out');
// Send a successful response
$response['success'] = true;
$response['message'] = 'Successful logout';

// Return response as JSON
echo json_encode($response);
?>
