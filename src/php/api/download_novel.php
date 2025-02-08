<?php
include '../utils/novel.php';
include '../utils/user.php';
include '../utils/db-client.php';

openlog("download_novel.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User not authenticated tried to download a novel.');

    session_destroy();
    http_response_code(401); // Unauthorized
    $error_message = urlencode('User not authenticated');
    header("Location: /error.html?error=$error_message");
    exit;
}

if($_SESSION["timeout"] < date("Y-m-d H:i:s")) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Session expired.");

    session_destroy();
    http_response_code(401); // Unauthorized
    $error_message = urlencode('Session expired');
    header("Location: /error.html?error=$error_message");
    exit;
}

if (!isset($_GET['file'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User tried to download novel without specifying file.');

    http_response_code(400);
    exit('No file specified.');
}

syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User requested to download a novel.');

$_SESSION["timeout"] = date("Y-m-d H:i:s", strtotime('+30 minutes'));

$file = $_GET['file'];
$file_path = "/var/www/private/uploads/" . $file;

if (file_exists($file_path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);

    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User downloaded requested novel');
    exit;
} else {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"].' - - [' . date("Y-m-d H:i:s") . ']  User tried to download non-existent file.');

    http_response_code(404);
    exit('File not found.');
}
?>
