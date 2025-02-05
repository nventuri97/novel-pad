<?php

include '../utils/novel.php';
include '../utils/user.php';
include '../utils/db-client.php';

openlog("read_novel.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User not authenticated tried to read novel.');

    http_response_code(401); // Unauthorized
    $error_message = urlencode('User not authenticated');
    header("Location: /error.html?error=$error_message");
    exit;
}

if (!isset($_GET['file'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User tried to read novel without specifying file.');

    http_response_code(400);
    exit('No file specified.');
}
syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User requested to read a novel.');

$file = $_GET['file'];
$file_path = "/var/www/private/uploads/" . $file;

if (file_exists($file_path)) {
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
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User tried to read non-existent file.');
    
    http_response_code(404);
    echo $_GET['file'];
    exit('File not found.');
}
?>
