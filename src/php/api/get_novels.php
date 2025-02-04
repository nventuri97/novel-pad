<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/novel.php';
include '../utils/user.php';
include '../utils/db-client.php';

openlog("get_novels.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

// Initialize response
$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User not authenticated tried to get novels.');

    http_response_code(401); // Unauthorized
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User requested to get novels.');

$novel_conn = db_client::get_connection('novels_db');

try {
    $user = $_SESSION['user'];
    $user_id = $user->get_id();

    $stmt = $novel_conn->prepare('SELECT * FROM novels WHERE user_id = :user_id');
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $novels= $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($novels as $novel) {
        $file_name =  basename($novel['file_path']);
        
        if ($novel['type'] === 'short_story') {
            syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User requested to get short novel: ' . $file_name);

            $link = 'php/api/read_novel.php?file=' . $user->get_username() .'/' . urlencode($file_name);
        } else {
            syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User requested to get full novel: ' . $file_name);

            $link = 'php/api/download_novel.php?file=' . $user->get_username() .'/' . urlencode($file_name);
        }

        $response['data'][] = (new Novel(
            $novel['id'],
            $novel['title'],
            $user->get_username(),
            $novel['genre'],
            $novel['type'],
            $link,
            $novel['is_premium'],
            $novel['uploaded_at']
        ))->to_array();
    }

    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User novels retrieved successfully.');
    $response['success'] = true;
} catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  An error occurred while processing user data: ' . $e->getMessage());

    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

echo json_encode($response); // Output the final response
?>
