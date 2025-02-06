<?php
header('Content-Type: application/json'); // Ensure the response is JSON

include '../utils/db-client.php';
include '../utils/user.php';
require '../utils/mail-client.php';

// Enable output buffering to prevent accidental output
ob_start();
openlog("register.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$auth_db = 'authentication_db';
$novels_db = 'novels_db';
$config = include '../utils/config.php';

$response = ['success' => false, 'message' => '']; // Default response structure

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $email = $_POST['email'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $recaptcha_response = $_POST["recaptcharesponse"] ?? '';

        // Basic validation
        if (empty($password) || empty($email) || empty($full_name) || empty($recaptcha_response)) {
            syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  User attempted to register with missing fields.');

            $response['message'] = "Please fill all the fields.";
            echo json_encode($response);
            ob_end_flush();
            exit;
        }

        $username = explode('@', $_POST['email'])[0]; // Use the email as the username

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

        syslog(LOG_INFO, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  User requested to register');
        // Database operations
        $auth_conn = db_client::get_connection($auth_db);
        $novels_conn = db_client::get_connection($novels_db);

        // Check if the username already exists
        $check_username_stmt = $auth_conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $check_username_stmt->bindParam(':username', $username);
        $check_username_stmt->execute();
        $username_exists = $check_username_stmt->fetchColumn() > 0;

        // Check if the email already exists
        $check_email_stmt = $novels_conn->prepare("SELECT COUNT(*) FROM user_profiles WHERE email = :email");
        $check_email_stmt->bindParam(':email', $email);
        $check_email_stmt->execute();
        $email_exists = $check_email_stmt->fetchColumn() > 0;

        if ($email_exists || $username_exists) {
            syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Email or username already exists. Please try again.');

            $response['message'] = "Email or username already exists. Please try again.";
            echo json_encode($response);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        // Generate a random token for email verification
        $token = bin2hex(random_bytes(16));

        // Insert the new user into the authentication database
        $auth_stmt = $auth_conn->prepare("INSERT INTO users (username, password_hash, verification_token) VALUES (:username, :password_hash, :verification_token)");
        $auth_stmt->bindParam(':username', $username);
        $auth_stmt->bindParam(':password_hash', $passwordHash);
        $auth_stmt->bindParam(':verification_token', $token);
        $auth_stmt->execute();

        $user_id = $auth_conn->lastInsertId();

        // Insert the new user into the novels database
        $novels_stmt = $novels_conn->prepare("INSERT INTO user_profiles (user_id, email, full_name, is_premium, logged_in) VALUES (:user_id, :email, :full_name, :is_premium, :logged_in)");
        $novels_stmt->bindParam(':user_id', $user_id);
        $novels_stmt->bindParam(':email', $email);
        $novels_stmt->bindParam(':full_name', $full_name);
        $novels_stmt->bindValue(':is_premium', 0, PDO::PARAM_BOOL);
        $novels_stmt->bindValue(':logged_in', 0, PDO::PARAM_BOOL);
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
    } else {
        syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Invalid request method.');

        $response['message'] = "Invalid request method.";
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER['REMOTE_ADDR'] . ' - - [' . date("Y-m-d H:i:s") . ']  Database error: ' . $e->getMessage());
    
    $response['message'] = "Database error: " . $e->getMessage();
}

// Output the response as JSON
ob_end_clean(); // Clear any accidental output
echo json_encode($response);
?>
