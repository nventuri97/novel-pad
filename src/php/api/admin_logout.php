<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/admin.php';
include '../utils/db-client.php';

openlog("admin_logout.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();


$response = [
    'success' => false,
    'message' => ''
];

// Allow only PUT requests
if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Location: /error.html?error=" . urlencode('Invalid request method'));
    exit;
}

// Ensure the admin is logged in
if (!isset($_SESSION['admin'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] No admin is logged in.');
  
    http_response_code(401); // Unauthorized
    $error_message = urlencode('Admin not authenticated');
    header("Location: /error.html?error=$error_message");
    exit;
}

$admin = $_SESSION['admin'];

syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] Logout request received');

// Unset session variables and destroy session
session_unset();
session_destroy();

syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] Admin logged out');

// Send a successful response
$response['success'] = true;
$response['message'] = 'Successful logout';

ob_end_clean();
echo json_encode($response);
?>
