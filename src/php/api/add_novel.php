<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

include '../utils/user.php';
include '../utils/db-client.php';

ob_start();
openlog("add_novel.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$novel_conn = db_client::get_connection("novels_db");

$response = [
    'success' => false,
    'message' => ''
];

if (!isset($_SESSION['user'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] User try to add novel without log in.");
  
    http_response_code(401); // Unauthorized
    $error_message = urlencode('User not authenticated');
    header("Location: /error.html?error=$error_message");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    session_start();

    syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  User is trying to add a novel.");

    // Get the user ID from session
    $user_id = $_SESSION["user"]->get_id();

    // Collect form data
    $title = $_POST['title'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $type = $_POST['type'] ?? '';
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;

    // Handle file upload
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'] ?? null;

        // Define allowed file types and size limits
        $allowedTypes = ['application/pdf', 'text/html'];
        if (!in_array($file['type'], $allowedTypes)) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] Invalid file type.");

            $response["message"] = "Invalid file type.";
            echo json_encode($response);
            ob_end_flush();
            exit;
        }

        if(($type == 'full_novel' && $file['type'] != 'application/pdf')) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  This type of file is not allowed.");

            $response["message"] = "This type of file is not allowed. Pleas upload a pdf.";
            echo json_encode($response);
            ob_end_flush();
            exit;
        }

        if(($type == 'short_story' && $file['type'] != 'text/html')) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "]  This type of file is not allowed.");

            $response["message"] = "Error occured: not an html file.";
            echo json_encode($response);
            ob_end_flush();
            exit;
        }

        // Ensure the uploads directory exists
        $uploadDir = '/var/www/private/uploads/'. $_SESSION["user"]->get_username() . '/';
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

            $response["message"] = "Failed to upload the file." . $file['tmp_name'] . " and " . $filePath;
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
        $response["message"] = "Error: " . $e->getMessage();
    }
}

closelog();
echo json_encode($response);
ob_end_flush();
exit;
?>
