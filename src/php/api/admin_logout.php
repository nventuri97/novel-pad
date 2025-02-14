<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/db-client.php';

openlog("admin_logout.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();
ob_start();

// Ensure the admin is logged in
if (!isset($_SESSION['admin'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] No admin is logged in.');
  
    http_response_code(401); // Unauthorized
    $error_message = urlencode('Admin not authenticated');
    header("Location: /error.html?error=$error_message");
    exit;
}

$admin = $_SESSION['admin'];

$response = [
    'success' => false,
    'message' => ''
];

    // Allow POST or PUT for logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] Logout request received');

    // Unset session variables and destroy session
    session_unset();
    session_destroy();

    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] Admin logged out');
    // Send a successful response
    $response['success'] = true;
    $response['message'] = 'Successful logout';
} else {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] Invalid request method');
    $response['message'] = 'Invalid request method';
}

ob_end_clean();
// Return response as JSON
echo json_encode($response);
?>
