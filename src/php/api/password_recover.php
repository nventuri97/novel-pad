<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; style-src 'self' 'unsafe-inline'; frame-src 'self' https://www.google.com/recaptcha/");
header('Content-Type: application/json'); // Ensure the response is JSON

include '../utils/db-client.php';
require '../utils/mail-client.php';

// Enable output buffering to prevent accidental output
ob_start();
openlog("password_recover.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$auth_db = 'authentication_db';
$novel_db = 'novels_db';

$response = ['success' => false, 'message' => '']; // Default response structure

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Invalid request method");

    http_response_code(405); // HTTP method not allowed
    header("Content-Type: text/html");

    echo "<h1>405 Method Not Allowed</h1>";
    echo "<p>The request method is not allowed. This method is not allowed.</p>";
    exit;
}
    
$email = $_POST['email'] ?? '';
$recaptcha_response = $_POST["recaptcharesponse"] ?? '';

// Basic validation
if (empty($email) || empty($recaptcha_response)) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Empty email or recaptcha');

    $response['message'] = "Please fill all the fields.";
    echo json_encode($response);
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

// check if the email is valid
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Invalid email format");
    $response["message"] = "Invalid email format.";
    echo json_encode($response);
    exit;
}

syslog(LOG_INFO, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password recovery request.');

try {

    // Database operations
    $auth_conn = db_client::get_connection($auth_db);
    $auth_stmt = $auth_conn->prepare("SELECT id FROM users WHERE email = :email");
    $auth_stmt->bindParam(':email', $email);
    $auth_stmt->execute();

    if ($auth_stmt->rowCount() === 0) {
        syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password recover attempt with invalid email.');

        $response['success'] = true;
        $response['message'] = "Mail to password recovery send correctly!";
        echo json_encode($response);
        exit;
    }

    $user = $auth_stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $user['id'];
    $token = bin2hex(random_bytes(16)); // Secure random token
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

    // Save token and expiry to the database
    $update_stmt = $auth_conn->prepare("UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE id = :id");
    $update_stmt->bindParam(':token', $token);
    $update_stmt->bindParam(':expiry', $expiry);
    $update_stmt->bindParam(':id', $user_id);
    $update_stmt->execute();


    // Send verification email
    echo "Sending email...";
    $mailSent = sendRecoveryPwdMail($email, $token, $user_id);
    if (!$mailSent) {
        syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Failed to send password recovery email.');

        $response['message'] = "Failed to send verification email.";
    } else {
        syslog(LOG_INFO, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Password recovery email sent.');

        // Successful verification message
        $response['success'] = true;
        $response['message'] = "Mail to password recovery send correctly!";
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