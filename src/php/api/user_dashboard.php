<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/user.php';
include '../utils/db-client.php';

session_start();

// Initialize response
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

if (!isset($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

try {
    $user = $_SESSION['user'];
    $response['success'] = true;
    $response['data'] = $user->to_array(); // Ensure this function returns an associative array
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

echo json_encode($response); // Output the final response
?>
