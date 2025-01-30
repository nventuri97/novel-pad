<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/user.php';
include '../utils/db-client.php';

// ini_set("session.cookie_httponly", 1); // Prevents JavaScript from accessing session cookies
// ini_set("session.cookie_secure", 1); // Ensures cookies are sent only over HTTPS
// ini_set("session.use_strict_mode", 1); // Prevents session fixation attacks

ob_start();

$auth_conn = db_client::get_connection("authentication_db");

$novel_conn = db_client::get_connection("novels_db");

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Retrieve user from authentication_db
    $stmt = $auth_conn->prepare(
        "SELECT id, password_hash, is_verified FROM users WHERE username = :username"
    );
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user["is_verified"]) {
        $response["message"]= "User not verified. Please check your email.";
    } else if ($user && password_verify($password, $user["password_hash"])) {
        // Login successful, retrieve premium status from novels_db
        $user_id = $user["id"];

        // Correct query to retrieve is_premium from user_profiles
        $novel_stmt = $novel_conn->prepare(
            "SELECT * FROM user_profiles WHERE user_id = :user_id"
        );
        $novel_stmt->bindParam(":user_id", $user_id);
        $novel_stmt->execute();
        $novel_user = $novel_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$novel_user["logged_in"]){
            $session_user = new User($novel_user["user_id"], $username, $novel_user["email"], $novel_user["full_name"], $novel_user["is_premium"], !($novel_user["logged_in"]));
            
            $novel_stmt = $novel_conn->prepare(
                "UPDATE user_profiles SET logged_in = :logged_in WHERE user_id = :user_id"
            );
            $novel_stmt->bindValue(":logged_in", 1, PDO::PARAM_BOOL);
            $novel_stmt->bindParam(":user_id", $user_id);
            $novel_stmt->execute();

            session_start();

            // Save session information
            $_SESSION["user"] = $session_user;
            $response["success"] = true;
            $response["message"]="Login succed!";
        } else {
            $response["message"]= "Already logged in";
        }
        
    } else {
        $response["message"]= "Wrong username or password.";
    }
}
echo json_encode($response);
ob_end_flush();
exit;
?>



