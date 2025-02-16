<?php
header('Content-Type: application/json'); // Ensure the response is JSON

use ZxcvbnPhp\Zxcvbn;

require '../utils/db-client.php';
require '../utils/user.php';
require '../utils/mail-client.php';
require __DIR__.'/../../vendor/autoload.php';

// Enable output buffering to prevent accidental output
ob_start();
openlog("register.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$auth_db = 'authentication_db';
$novels_db = 'novels_db';
$config = include '../utils/config.php';

$response = ['success' => false, 'message' => '']; // Default response structure

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Content-Type: text/html");

    echo "<h1>405 Method Not Allowed</h1>";
    echo "<p>The request method is not allowed. This method is not allowed.</p>";
    exit;
}

$password = $_POST['password'] ?? '';
$email = $_POST['email'] ?? '';
$nickname = $_POST['nickname'] ?? '';
$recaptcha_response = $_POST["recaptcharesponse"] ?? '';

// Basic validation
if (empty($password) || empty($email) || empty($nickname) || empty($recaptcha_response)) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  User attempted to register with missing fields.');

    $response['message'] = "Please fill all the fields.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Invalid email format');

    $response['message'] = "Invalid email format.";
    echo json_encode($response);
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

// Server side password validation
// Password must be at least 8 characters long
if (strlen($password) < 8) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password too short.');

    $response['message'] = "Password must be at least 8 characters long.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// Password must contain at least one uppercase letter, one lowercase letter, one number, and no special characters
$password_regex='/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/';
if (preg_match($password_regex, $password)){
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password too weak.');

    $response['message'] = "Password must agree password policy.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// Check password strength using zxcvbn
$zxcvbn = new Zxcvbn();
$result = $zxcvbn->passwordStrength($password, $userInputs = [$email, $nickname]);
if ($result['score']<4){
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password too weak.');

    $response['message'] = "Password too weak.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

if (strlen($nickname) < 3 || strlen($nickname) > 20) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Nickname too long or too short.');

    $response['message'] = "Nickname must be between 3 and 20 characters long.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

syslog(LOG_INFO, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  User requested to register');

try {
    $novels_conn = db_client::get_connection($novels_db);

    // Check if the nickname already exists
    $check_nickname_stmt = $novels_conn->prepare("SELECT COUNT(*) FROM user_profiles WHERE nickname = :nickname");
    $check_nickname_stmt->bindParam(':nickname', $nickname);
    $check_nickname_stmt->execute();
    $nickname_exists = $check_nickname_stmt->fetchColumn() > 0;

    if ($nickname_exists) {
        syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Nickname already exists. Please try again.');

        $response['message'] = "This nickname already exists, please choose another one.";
        echo json_encode($response);
        exit;
    }

    // Database operations
    $auth_conn = db_client::get_connection($auth_db);

    // Check if the email already exists
    $check_email_stmt = $auth_conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $check_email_stmt->bindParam(':email', $email);
    $check_email_stmt->execute();
    $email_exists = $check_email_stmt->fetchColumn() > 0;

    if ($email_exists) {
        syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Email already exists. Send allert mail.');

        $mailSent = sendAllertMail($email,'user');
        if (!$mailSent) {
            syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Failed to send allert email.');

            $response['message'] = "Failed to send verification email.";
        } else {
            syslog(LOG_INFO, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Allert email sent.');

            // Successful verification message
            $response['success'] = true;
            $response['message'] = "Verification mail send correctly!";
        }
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    // Generate a random token for email verification
    $token = bin2hex(random_bytes(16));

    // Insert the new user into the authentication database
    $auth_stmt = $auth_conn->prepare("INSERT INTO users (email, password_hash, verification_token) VALUES (:email, :password_hash, :verification_token)");
    $auth_stmt->bindParam(':email', $email);
    $auth_stmt->bindParam(':password_hash', $passwordHash);
    $auth_stmt->bindParam(':verification_token', $token);
    $auth_stmt->execute();

    $user_id = $auth_conn->lastInsertId();

    // Insert the new user into the novels database
    $novels_stmt = $novels_conn->prepare("INSERT INTO user_profiles (user_id, nickname, is_premium) VALUES (:user_id, :nickname, :is_premium)");
    $novels_stmt->bindParam(':user_id', $user_id);
    $novels_stmt->bindParam(':nickname', $nickname);
    $novels_stmt->bindValue(':is_premium', 0, PDO::PARAM_BOOL);
    $novels_stmt->execute();

    // Send verification email
    $mailSent = sendVerificationMail($email, $token);
    if (!$mailSent) {
        syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Failed to send verification email.');

        $response['message'] = "Failed to send verification email.";
    } else {
        syslog(LOG_INFO, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Verification email sent.');

        // Successful verification message
        $response['success'] = true;
        $response['message'] = "Verification mail send correctly!";
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Database error: ' . $e->getMessage());
    
    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred while processing user data.';
}

// Output the response as JSON
ob_end_clean(); // Clear any accidental output
echo json_encode($response);
?>
