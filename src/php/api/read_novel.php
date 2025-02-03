<?php

include '../utils/novel.php';
include '../utils/user.php';
include '../utils/db-client.php';

session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    $error_message = urlencode('User not authenticated');
    header("Location: /error.html?error=$error_message");
    exit;
}

if (!isset($_GET['file'])) {
    http_response_code(400);
    exit('No file specified.');
}

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
    exit;
} else {
    http_response_code(404);
    echo $_GET['file'];
    exit('File not found.');
}
?>
