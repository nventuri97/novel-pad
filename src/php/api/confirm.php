<?php
header('Content-Type: application/json'); 

include '../utils/db-client.php';
include '../utils/user.php';

ob_start();

$auth_db = 'authentication_db';
$novels_db = 'novels_db';

$response = ['success' => false, 'message' => ''];

try{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['token'];

        $auth_conn = db_client::get_connection($auth_db);
        $auth_stmt = $auth_conn->prepare("SELECT id FROM users WHERE verification_token = :token");
        $auth_stmt->bindParam(':token', $token);
        $auth_stmt->execute();

        if ($auth_stmt->rowCount() === 0) {
            $response['message'] = "Invalid token.";
            echo json_encode($response);
            exit;
        }
        
        $auth_user = $auth_stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $auth_user['id'];
        $auth_stmt = $auth_conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id=:id");
        $auth_stmt->bindParam(':id', $user_id);
        $auth_stmt->execute();

        $novels_conn = db_client::get_connection($novels_db);
        $novels_stmt = $novels_conn->prepare("SELECT * FROM user_profiles WHERE user_id = :user_id");
        $novels_stmt->bindParam(':user_id', $user_id);
        $novels_stmt->execute();

        $user = $novels_stmt->fetch(PDO::FETCH_ASSOC);
        $username = $user['username'];
        $email = $user['email'];
        $full_name = $user['full_name'];

        $response['success'] = true;
        $response['message'] = "Registration completed successfully!";
    
        // $user = new User($user_id, $username, $email, $full_name, false, false);
        session_start();
    
        $_SESSION['user'] = $user;
    } else {
        $response['message'] = "Invalid request method.";
    }
}catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
}

ob_end_clean();
echo json_encode($response);
?>