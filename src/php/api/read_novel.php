<?php

include '../utils/novel.php';
include '../utils/user.php';
include '../utils/db-client.php';

openlog("read_novel.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Location: /error.html?error=" . urlencode('Invalid request method'));
    exit;
}

if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] User not authenticated tried to read a novel.");

    session_destroy();
    http_response_code(401); // Unauthorized
    header("Location: /error.html?error=" . urlencode('User not authenticated'));
    exit;
}

if(!isset($_SESSION["timeout"]) || $_SESSION["timeout"] < date("Y-m-d H:i:s")) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Session expired.");

    session_destroy();
    http_response_code(419); // Timeout error
    header("Location: /error.html?error=" . urlencode('Session expired'));
    exit;
}

if (!isset($_GET['file'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User tried to read novel without specifying file.');

    http_response_code(400);
    exit('No file specified.');
}

$user = $_SESSION['user'];
$is_premium = $user->is_premium();

syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User requested to read a novel.');

// Update the session timeout
$_SESSION["timeout"] = date("Y-m-d H:i:s", strtotime('+30 minutes'));

$ok_dir= realpath("/var/www/private/uploads/");
$file = $_GET['file'];
$file_path = realpath($ok_dir. DIRECTORY_SEPARATOR . $file);

if (str_starts_with($file_path, $ok_dir.DIRECTORY_SEPARATOR) && file_exists($file_path)) {
    
    // Check if the user is not premium and the file is premium
    if(!$is_premium) {
        // Get the novel from the DB
        try{
            $novel_conn = db_client::get_connection('novels_db');

            if (!$novel_conn) {
                throw new Exception('Failed to connect to novels_db');
            }

            // Check if the novel is premium
            $stmt = $novel_conn->prepare('SELECT * FROM novels WHERE file_path = :file_path');
            $stmt->bindParam(':file_path', $file_path);
            $stmt->execute();

            $novel = $stmt->fetch(PDO::FETCH_ASSOC);

            if($novel['is_premium'] === 1) {
                syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User tried to read premium novel without premium account.');
                http_response_code(403);
                header("Location: /user_dashboard.html");
                exit('You need to be a premium user to read this novel.');
            }

        }catch (Exception $e) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  An error occurred while processing other novels: ' . $e->getMessage());
            
            http_response_code(500);
            $response['message'] = 'An error occurred while processing other novels.';
        }
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_path);
    finfo_close($finfo);

    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, no-store, must-revalidate'); 
    header('Pragma: no-cache'); 
    header('Expires: 0');

    readfile($file_path);
    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User read requested novel '. basename($file));
    exit;
} else {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User tried to read non-existent file. File path is: ' . $file_path);
    
    http_response_code(404);
    exit('File not found.');
}
?>
