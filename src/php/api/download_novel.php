<?php
include '../utils/novel.php';
include '../utils/user.php';
include '../utils/db-client.php';

session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401); // Unauthorized
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

if (!isset($_GET['file'])) {
    http_response_code(400);
    exit('No file specified.');
}

$file = $_GET['file'];
$file_path = "/var/www/private/uploads/" . $file;

if (file_exists($file_path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
} else {
    http_response_code(404);
    exit('File not found.');
}
?>
