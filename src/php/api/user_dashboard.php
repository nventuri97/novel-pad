<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/user.php';
include '../utils/db-client.php';

openlog("user_dashboard.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

// Initialize response
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    $error_message = urlencode('Invalid request method');
    header("Location: /error.html?error=$error_message");
    exit;
}

if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] User not authenticated.");

    session_destroy();
    http_response_code(401); // Unauthorized
    // $response['message'] = 'An error occurred while processing user data.';
    // echo json_encode($response);
    header("Location: /error.html?error=Unauthorized");
    exit;
}

if($_SESSION["timeout"] < date("Y-m-d H:i:s")) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Session expired.");

    session_destroy();
    http_response_code(401); // Unauthorized
    $error_message = urlencode('Session expired');
    header("Location: /error.html?error=$error_message");
    exit;
}

// Update the session timeout
$_SESSION["timeout"] = date("Y-m-d H:i:s", strtotime('+30 minutes'));

try {
    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] User authenticated.");
    $user = $_SESSION['user'];
    $response['success'] = true;
    $response['data'] = $user->to_array(); // Ensure this function returns an associative array
} catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] An error occurred while processing user data. ".$e->getMessage());

    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

echo json_encode($response); // Output the final response
?>
