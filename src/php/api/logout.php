<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/user.php';
include '../utils/db-client.php';

openlog("logout.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  No user is logged in.');
  
    http_response_code(401); // Unauthorized
    $error_message = urlencode('User not authenticated');
    header("Location: /error.html?error=$error_message");

    exit;
}

$user = $_SESSION['user'];

$novels_db = 'novels_db';
$response = [
    'success' => false,
    'message' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  Logout request received');
        // Ensure the connection is established
        $novels_conn = db_client::get_connection($novels_db);
        $user_id = $user->get_id();

        // Prepare and execute logout query
        $stmt = $novels_conn->prepare("UPDATE user_profiles SET logged_in = :logged_in WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindValue(':logged_in', 0, PDO::PARAM_BOOL);
        $stmt->execute();

        // Unset session variables and destroy session
        session_unset();
        session_destroy();

        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User logged out');
        // Send a successful response
        $response['success'] = true;
        $response['message'] = 'Successful logout';
    } else {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  Invalid request method');

        $response['message'] = 'Invalid request method';
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  Database error: ' . $e->getMessage());
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  An error occured: ' . $e->getMessage());
    $response['message'] = "General error: " . $e->getMessage();
}

// Return response as JSON
echo json_encode($response);
?>
