<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; style-src 'self' 'unsafe-inline'; frame-src 'self' https://www.google.com/recaptcha/; frame-ancestors 'self'");
header("X-Frame-Options: SAMEORIGIN");
header('Content-Type: application/json');// Ensure response is JSON

require __DIR__ . '/../utils/user.php';
require __DIR__ . '/../utils/db-client.php';
$config = require __DIR__ . '/../utils/config.php';


session_start();
ob_start();
openlog("login.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

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
if (!isset($_SESSION['csrf_token'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "] CSRF token not set in session");

    http_response_code(405); // HTTP method not allowed 
    header("Content-Type: text/html");

    echo "<h1>405 Method Not Allowed</h1>";
    echo "<p>The request method is not allowed. This method is not allowed.</p>";
    exit;
}

if (!isset($_POST['csrf_token'])) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "] CSRF token not set in POST data");

    http_response_code(405); // HTTP method not allowed 
    header("Content-Type: text/html");

    echo "<h1>405 Method Not Allowed</h1>";
    echo "<p>The request method is not allowed. This method is not allowed.</p>";
    exit;
}

if ($_SESSION['csrf_token'] !== $_POST['csrf_token']) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "] Invalid CSRF token");

    http_response_code(405); // HTTP method not allowed 
    header("Content-Type: text/html");

    echo "<h1>405 Method Not Allowed</h1>";
    echo "<p>The request method is not allowed. This method is not allowed.</p>";
    exit;
}
syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "] POST CSRF token: " . $_POST['csrf_token']);
syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "] SESSION CSRF token: " . $_SESSION['csrf_token']);
syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Login attempt");
$email = $_POST["email"] ?? '';
$password = $_POST["password"] ?? '';
$recaptcha_response = $_POST["recaptcharesponse"] ?? '';

if (empty($email) || empty($password) || empty($recaptcha_response)) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Empty email or password");

    $response["message"]= "Please fill all the fields.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// Verify reCAPTCHA
$recaptcha_secret = $config['captcha_key'];
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

// check if the email is valid
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid email");

    $response["message"] = "Wrong credentials or account blocked for too many login attempts.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// check if the password is a string
if (!is_string($password)) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Password must be a string");

    $response["message"]= "Password must be a string.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

try {
    $auth_conn = db_client::get_connection("authentication_db");

    // Retrieve user from authentication_db
    $stmt = $auth_conn->prepare(
        "SELECT id, password_hash, is_verified, login_attempts, last_login_attempt FROM users WHERE email = :email"
    );
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($_SESSION['user']) && $_SESSION['user'] instanceof User && $_SESSION['user']->get_email() !== $email) {   
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]." - - [" . date("Y-m-d H:i:s") . "] User already authenticated.");
    
        $response["message"]= "You are already logged in with another account, please first log out.";
        echo json_encode($response);
        ob_end_flush();
        exit;
    }

    if (!$user) {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  User inserted wrong email");

        $response["message"]= "Wrong credentials or account blocked for too many login attempts.";
        echo json_encode($response);
        ob_end_flush();
        exit;
    }
    
    if (!$user["is_verified"]) {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  User not verified");

        $response["message"]= "Wrong credentials or account blocked for too many login attempts.";
        echo json_encode($response);
        ob_end_flush();
        exit;
    }

    // Check if the user has reached the maximum number of login attempts
    $login_attempts = $user["login_attempts"];
    $last_login_attempt = $user["last_login_attempt"];

    $last_login_time = $last_login_attempt ? strtotime($last_login_attempt) : 0;
    $one_hour_ago = strtotime("-1 hour");

    // Reset login attempts if last login attempt was more than 1 hour ago
    if ($last_login_time < $one_hour_ago) {
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Resetting login attempts");
        $login_attempts = 0;
    }

    if ($login_attempts >= 10) {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] User blocked for too many login attempts");

        $response["message"] = "Wrong credentials or account blocked for too many login attempts.";
        echo json_encode($response);
        ob_end_flush();
        exit;
    }
    if ($user && password_verify($password, $user["password_hash"])) {
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Retrieving user profile from novels_db");
        // Login successful, retrieve premium status from novels_db
        $user_id = $user["id"];

        // Reset login attempts
        $stmt = $auth_conn->prepare(
            "UPDATE users SET login_attempts = 0, last_login_attempt = NULL WHERE email = :email"
        );
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        $novel_conn = db_client::get_connection("novels_db");

        $novel_stmt = $novel_conn->prepare(
            "SELECT * FROM user_profiles WHERE user_id = :user_id"
        );
        $novel_stmt->bindParam(":user_id", $user_id);
        $novel_stmt->execute();
        $novel_user = $novel_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$novel_user) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "]  User profile not found in novels_db");
            $response["message"] = "An error occurred while retrieving your profile.";
            echo json_encode($response);
            ob_end_flush();
            exit;
        }        

        $session_user = new User($novel_user["user_id"], $email, $novel_user["nickname"], $novel_user["is_premium"]);

        $_SESSION["timeout"] = date("Y-m-d H:i:s", strtotime("+30 minutes"));

        // Save session information
        $_SESSION["user"] = $session_user;
        session_regenerate_id(true);
        $response["success"] = true;
        $response["message"]="Login succed!";
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  User logged in");
    } else {
        // Update login attempts
        $login_attempts++;
        $timestamp = date("Y-m-d H:i:s");

        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Wrong password: number of login attempts: " . $login_attempts);

        $stmt = $auth_conn->prepare(
            "UPDATE users SET login_attempts = :login_attempts, last_login_attempt = :last_login_attempt WHERE email = :email"
        );
        $stmt->bindParam(":login_attempts", $login_attempts);
        $stmt->bindParam(":last_login_attempt", $timestamp);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        $response["message"]= "Wrong credentials or account blocked for too many login attempts.";
    }
    echo json_encode($response);
    ob_end_flush();
    exit;

} catch (Exception $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  " . $e->getMessage());

    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
    echo json_encode($response);
    ob_end_flush();
    exit;
}
?>
