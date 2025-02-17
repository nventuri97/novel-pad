<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require '../utils/user.php';
require '../utils/db-client.php';

ob_start();
openlog("add_novel.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);
session_start();

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Content-Type: text/html");

    echo "<h1>405 Method Not Allowed</h1>";
    echo "<p>The request method is not allowed. This method is not allowed.</p>";
    exit;
}

if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] User try to add novel without log in.");
  
    session_destroy();
    http_response_code(401); // Unauthorized
    header("Content-Type: text/html");

    echo "<h1>401 User not authenticated</h1>";
    echo "<p>The user is not authorized.</p>";
    exit;
}

if(!isset($_SESSION["timeout"]) || $_SESSION["timeout"] < date("Y-m-d H:i:s")) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Session expired.");

    session_destroy();
    http_response_code(419); // Timeout error
    header("Content-Type: text/html");

    echo "<h1>419 Session expired</h1>";
    echo "<p>The user session is expired, try to login again.</p>";
    exit;
}

syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  User is trying to add a novel.");

// Update the session timeout
$_SESSION["timeout"] = date("Y-m-d H:i:s", strtotime('+30 minutes'));

// Get the user ID from session
$user_id = $_SESSION["user"]->get_id();

// Collect form data
$title = $_POST['title'] ?? '';
$genre = $_POST['genre'] ?? '';
$type = $_POST['type'] ?? '';
$is_premium = isset($_POST['is_premium']) ? 1 : 0;

if (!isset($title) || !is_string($title) || strlen($title) < 3 || strlen($title) > 30) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Nickname too long or too short.');

    $response['message'] = "Title must be a string between 3 and 30 characters long.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

if (!preg_match('/^[a-zA-Z0-9\s]+$/', $title)) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Invalid novel title.");

    $response["message"] = "Invalid title. Only letters, numbers, and spaces are allowed.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

$genresEnum = [
    "fantasy", "science_fiction", "romance", "mystery", "horror",
    "thriller", "historical", "non-fiction", "young_adult", "adventure"
];

if (!isset($genre) || !is_string($genre) || !in_array($genre, $genresEnum, true)) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Invalid genre: " . $genre);
    
    $response["message"] = "Invalid genre.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// check of novel type
if (!isset($type) || !is_string($type) || ($type !== 'full_novel' && $type !== 'short_story')) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Invalid novel type: " . $type);
    
    $response["message"] = "Invalid novel type.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}


// Handle file upload
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['file'] ?? null;

    // Define allowed file types and size limits
    $allowedTypes = ['application/pdf', 'text/html'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Invalid file type.");

        $response["message"] = "Invalid file type.";
        echo json_encode($response);
        ob_end_flush();
        exit;
    }

    if ($file['size'] > $maxFileSize) {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] File too large.");
        
        $response["message"] = "The uploaded file exceeds the maximum allowed size of 5MB.";
        echo json_encode($response);
        ob_end_flush();
        exit;
    }

    if(($type === 'full_novel' && $file['type'] !== 'application/pdf')) {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  This type of file is not allowed.");

        $response["message"] = "This type of file is not allowed. Pleas upload a pdf.";
        echo json_encode($response);
        ob_end_flush();
        exit;
    }

    if(($type === 'short_story' && $file['type'] !== 'text/html')) {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  This type of file is not allowed.");

        $response["message"] = "Error occured: not an html file.";
        echo json_encode($response);
        ob_end_flush();
        exit;
    }

    $dir_name = hash('sha256', $_SESSION["user"]->get_nickname());
    // Ensure the uploads directory exists
    $uploadDir = '/var/www/private/uploads/'. $dir_name . '/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Failed to create upload directory.");

            $response["message"] = "Failed to create upload directory.";
            echo json_encode($response);
            ob_end_flush();
            exit;
        }
    }

    // Save file to a directory (ensure appropriate directory exists and has write permissions)
    $filePath = $uploadDir . basename($file['name']);
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Failed to upload the file.");

        $response["message"] = "Failed to upload the file.";
        echo json_encode($response);
        ob_end_flush();
        exit;
    }
} else {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  File upload error.");

    $response["message"] = "File upload error.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// Insert novel data into the database
try {
    $novel_conn = db_client::get_connection("novels_db");

    $stmt = $novel_conn->prepare(
        "INSERT INTO novels (title, genre, type, file_path, is_premium, user_id) 
        VALUES (:title, :genre, :type, :file_path, :is_premium, :user_id)");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':genre', $genre);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':file_path', $filePath);
    $stmt->bindParam(':is_premium', $is_premium);
    $stmt->bindParam(':user_id', $user_id);

    $stmt->execute();

    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Novel added successfully");

    $response["success"] = true;
    $response["message"] = "Novel added successfully!";
} catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  Error: " . $e->getMessage());
    
    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

closelog();
echo json_encode($response);
ob_end_flush();
exit;
?>
