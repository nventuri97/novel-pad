<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/user.php';
include '../utils/db-client.php';

session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user'])) {
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

        // Send a successful response
        $response['success'] = true;
        $response['message'] = 'Successful logout';
    } else {
        $response['message'] = 'Invalid request method. Expected PUT.';
    }
} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = "General error: " . $e->getMessage();
}

// Return response as JSON
echo json_encode($response);
?>
