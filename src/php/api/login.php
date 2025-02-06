<?php
header('Content-Type: application/json'); // Ensure response is JSON

include '../utils/user.php';
include '../utils/db-client.php';

// ini_set("session.cookie_httponly", 1); // Prevents JavaScript from accessing session cookies
// ini_set("session.cookie_secure", 1); // Ensures cookies are sent only over HTTPS
// ini_set("session.use_strict_mode", 1); // Prevents session fixation attacks

ob_start();
openlog("login.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$response = [
    'success' => false,
    'message' => ''
];

try{
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Login attempt");
        $username = $_POST["username"] ?? '';
        $password = $_POST["password"] ?? '';
        $recaptcha_response = $_POST["recaptcharesponse"] ?? '';

        if (empty($username) || empty($password || empty($recaptcha_response))) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Empty username or password");

            $response["message"]= "Please fill all the fields.";
            echo json_encode($response);
            ob_end_flush();
            exit;
        }

        // Verify reCAPTCHA
        $recaptcha_secret = "6Ld-uM4qAAAAAM-A8kEw9mGQi8O8hd9vksQnlH14";
        $recaptcha_url = "https://www.google.com/recaptcha/api/siteverify";
        $recaptcha_check = curl_init($recaptcha_url);
        curl_setopt($recaptcha_check, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($recaptcha_check, CURLOPT_POSTFIELDS, [
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_response
        ]);
        $recaptcha_result = curl_exec($recaptcha_check);
        curl_close($recaptcha_check);
        
        $captcha_success = json_decode($recaptcha_result, true);
    
        if (!$captcha_success || !$captcha_success["success"]) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Wrong CAPTCHA");

            $response["message"]= "reCAPTCHA verification failed.";
            echo json_encode($response);
            ob_end_flush();
            exit;
        }

        $auth_conn = db_client::get_connection("authentication_db");

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
          
            $novel_conn = db_client::get_connection("novels_db");

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

        http_response_code(405); // HTTP method not allowed
        $error_message = urlencode('Invalid request method');
        header("Location: /error.html?error=$error_message");
        exit;
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  " . $e->getMessage());

    $response['message'] = "Database error";
}

echo json_encode($response);
ob_end_flush();
exit;
?>



