<?php
header('Content-Type: application/json'); // Ensure response is JSON

require '../utils/user.php';
require '../utils/db-client.php';

openlog("user_dashboard.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();
ob_start();

// Initialize response
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Content-Type: text/html");

    echo "<h1>405 Method Not Allowed</h1>";
    echo "<p>The request method is not allowed. This method is not allowed.</p>";
    exit;
}

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
ob_end_flush();
closelog();
exit;
?>
