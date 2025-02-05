<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/user.php';
include '../utils/db-client.php';

// ini_set("session.cookie_httponly", 1); // Prevents JavaScript from accessing session cookies
// ini_set("session.cookie_secure", 1); // Ensures cookies are sent only over HTTPS
// ini_set("session.use_strict_mode", 1); // Prevents session fixation attacks

ob_start();
openlog("login.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$auth_conn = db_client::get_connection("authentication_db");

$novel_conn = db_client::get_connection("novels_db");

$response = [
    'success' => false,
    'message' => ''
];

try{
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Login attempt");
        $username = $_POST["username"];
        $password = $_POST["password"];

        // Retrieve user from authentication_db
        $stmt = $auth_conn->prepare(
            "SELECT id, password_hash, is_verified FROM users WHERE username = :username"
        );
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  User inserted wrong username or password");

            $response["message"]= "Wrong username or password.";
        } else if (!$user["is_verified"]) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  User not verified");

            $response["message"]= "User not verified. Please check your email.";
        } else if ($user && password_verify($password, $user["password_hash"])) {
            syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Retrieving user profile from novels_db");
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
                syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  User logged in");

                // Save session information
                $_SESSION["user"] = $session_user;
                $response["success"] = true;
                $response["message"]="Login succed!";
            } else {
                syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  User already logged in");
                $response["message"]= "Already logged in";
            } 
        } else {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Wrong username or password");

            $response["message"]= "Wrong username or password.";
        }
    } else {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

        $response["message"]= "Invalid request method";
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  " . $e->getMessage());

    $response['message'] = "Database error";
}

echo json_encode($response);
ob_end_flush();
exit;
?>



