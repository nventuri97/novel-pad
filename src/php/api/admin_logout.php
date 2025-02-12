<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/admin.php';
include '../utils/db-client.php';

openlog("admin_logout.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

// Ensure the admin is logged in
if (!isset($_SESSION['admin'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] No admin is logged in.');
  
    http_response_code(401); // Unauthorized
    $error_message = urlencode('Admin not authenticated');
    header("Location: /error.html?error=$error_message");
    exit;
}

$admin = $_SESSION['admin'];
$auth_db = 'authentication_db';

$response = [
    'success' => false,
    'message' => ''
];

try {
    // Allow POST or PUT for logout
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] Logout request received');
        // Get database connection
        $auth_conn = db_client::get_connection($auth_db);
        $admin_id = $admin['id'];

        // Prepare and execute logout query for admins
        $stmt = $auth_conn->prepare("UPDATE admins SET logged_in = :logged_in WHERE id = :id");
        $stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
        $stmt->bindValue(':logged_in', 0, PDO::PARAM_BOOL);
        $stmt->execute();

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
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] Database error: ' . $e->getMessage());
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . ' - - [' . date("Y-m-d H:i:s") . '] An error occurred: ' . $e->getMessage());
    $response['message'] = "General error: " . $e->getMessage();
}

// Return response as JSON
echo json_encode($response);
?>
