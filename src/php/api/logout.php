<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/user.php';
include '../utils/db-client.php';

openlog("logout.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Location: /error.html?error=" . urlencode('Invalid request method'));
    exit;
}

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] User not authenticated.");

    session_destroy();
    http_response_code(401); // Unauthorized
    header("Location: /error.html?error=" . urlencode('User not authenticated'));
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
