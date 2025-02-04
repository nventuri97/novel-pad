<?php
header('Content-Type: application/json'); // Assicurati che la risposta sia in JSON

include '../utils/novel.php';
include '../utils/user.php';
include '../utils/db-client.php';

openlog("get_other_novels.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

// Inizializza la risposta
$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

// Verifica se l'utente è autenticato
if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User not authenticated tried to get novels.');

    http_response_code(401); // Non autorizzato
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

try {
    // Ottieni la connessione al database
    $novel_conn = db_client::get_connection('novels_db');

    if (!$novel_conn) {
        throw new Exception('Failed to connect to novels_db');
    }

    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User requested to get novels of other authors.');

    // Ottieni l'utente dalla sessione
    $user = $_SESSION['user'];
    $user_id = $user->get_id();
    $is_premium = $user->is_premium();

    // Se l'utente è premium, mostriamo tutte le novel eccetto le proprie.
    // Se l'utente NON è premium, mostriamo solo le novel free (is_premium=0) degli altri.
    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  Retrieving novels from DB according to premium policy.');
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

    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  Processing novels.');
    foreach ($novels as $novel) {
        // Gestisci casi in cui author_name potrebbe essere null
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

    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  Novels processed successfully.');
    $response['success'] = true;

} catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  An error occurred while processing other novels: ' . $e->getMessage());
    
    http_response_code(500);
    error_log("Error in get_other_novels.php: " . $e->getMessage());
    $response['message'] = 'An error occurred while processing other novels.';
}

echo json_encode($response);

exit;
?>
