<?php
header('Content-Type: application/json'); // Ensure response is JSON

include 'novel.php';
include 'user.php';
include 'db-client.php';

session_start();

// Initialize response
$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

if (!isset($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

$novel_conn = db_client::get_connection('novels_db');

try {
    $user = $_SESSION['user'];
    $user_id = $user->get_id();

    $stmt = $novel_conn->prepare('SELECT * FROM novels WHERE user_id = :user_id');
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $novels= $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($novels as $novel) {
        $response['data'][] = (new Novel($novel['id'], 
                                        $novel['title'], 
                                        $novel['description'], 
                                        $user->get_username(), 
                                        $novel['genre'], 
                                        $novel['type'], 
                                        $novel['file_path'], 
                                        $novel['is_premium'], 
                                        $novel['uploaded_at']))->to_array();
    }
    

    $response['success'] = true;
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

echo json_encode($response); // Output the final response
?>
