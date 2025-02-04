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

if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] User not authenticated.");

    http_response_code(401); // Unauthorized
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

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
