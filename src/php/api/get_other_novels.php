<?php
header('Content-Type: application/json');

include '../utils/novel.php';
include '../utils/user.php';
include '../utils/db-client.php';

session_start();

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

if (!isset($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    $error_message = urlencode('User not authenticated');
    header("Location: /error.html?error=$error_message");
    exit;
}

try {
    $novel_conn = db_client::get_connection('novels_db');

    if (!$novel_conn) {
        throw new Exception('Failed to connect to novels_db');
    }

    $user = $_SESSION['user'];
    $user_id = $user->get_id();
    $is_premium = $user->is_premium();

    error_log("DEBUG get_other_novels.php: user_id = " . $user_id);

    
    if ($is_premium) {
        $stmt = $novel_conn->prepare(
            'SELECT n.*, u.full_name AS author_name
             FROM novels n
             JOIN user_profiles u ON n.user_id = u.user_id
             WHERE n.user_id != :user_id'
        );
    } else {
        $stmt = $novel_conn->prepare(
            'SELECT n.*, u.full_name AS author_name
             FROM novels n
             JOIN user_profiles u ON n.user_id = u.user_id
             WHERE n.user_id != :user_id
               AND n.is_premium = 0'
        );
    }
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $novels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($novels as $novel) {
        $author_name = $novel['author_name'] ?? 'Unknown Author';
        $dir_name = basename(dirname($novel['file_path']));
        $file_name = basename($novel['file_path']);

        $file_location = $dir_name . '/' . $file_name;
        
        if ($novel['type'] === 'short_story') {
            $link = 'php/api/read_novel.php?file=' . $dir_name .'/' . urlencode($file_name);
        } else {
            $link = 'php/api/download_novel.php?file=' . $dir_name .'/' . urlencode($file_name);
        }

        $response['data'][] = (new Novel(
            $novel['id'],
            $novel['title'],
            $author_name,
            $novel['genre'],
            $novel['type'],
            $link,
            $novel['is_premium'],
            $novel['uploaded_at']
        ))->to_array();
    }

    $response['success'] = true;

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in get_other_novels.php: " . $e->getMessage());
    $response['message'] = 'An error occurred while processing other novels.';
}

echo json_encode($response);

exit;
?>
