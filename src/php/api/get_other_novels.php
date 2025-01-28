<?php
header('Content-Type: application/json'); // Assicurati che la risposta sia in JSON

include '../utils/novel.php';
include '../utils/user.php';
include '../utils/db-client.php';

session_start();

// Inizializza la risposta
$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

// Verifica se l'utente è autenticato
if (!isset($_SESSION['user'])) {
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

    // Ottieni l'utente dalla sessione
    $user = $_SESSION['user'];
    $user_id = $user->get_id();
    $is_premium = $user->is_premium();


    // Log del user_id per debug
    error_log("DEBUG get_other_novels.php: user_id = " . $user_id);

    // Se l'utente è premium, mostriamo tutte le novel eccetto le proprie.
    // Se l'utente NON è premium, mostriamo solo le novel free (is_premium=0) degli altri.
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

    // Log del numero di novel recuperate
    error_log("DEBUG get_other_novels.php: fetched " . count($novels) . " novels.");

    foreach ($novels as $novel) {
        // Log dettagli della novel
        error_log("DEBUG get_other_novels.php: novel ID " . $novel['id'] . ", title: " . $novel['title'] . ", author: " . $novel['author_name']);

        // Gestisci casi in cui author_name potrebbe essere null
        $author_name = $novel['author_name'] ?? 'Unknown Author';

        $response['data'][] = (new Novel(
            $novel['id'],
            $novel['title'],
            $author_name, // Passa 'full_name' come author_name
            $novel['genre'],
            $novel['type'],
            $novel['file_path'],
            $novel['is_premium'],
            $novel['uploaded_at']
        ))->to_array();
    }

    $response['success'] = true;

} catch (Exception $e) {
    http_response_code(500);
    // Log dell'eccezione
    error_log("Error in get_other_novels.php: " . $e->getMessage());
    $response['message'] = 'An error occurred while processing other novels.';
}



// Esegui l'output del JSON
echo json_encode($response);

exit;
?>
