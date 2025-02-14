<?php

header('Content-Type: application/json'); // Ensure the response is in JSON

// Include any libraries or utility files specific to admins.
require_once __DIR__ . '/../utils/db-client.php';
$config = require_once __DIR__ . '/../utils/config.php';

session_start();
ob_start();  // Start buffering to capture any unwanted output
openlog("admin_login.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$response = [
    'success' => false,
    'message' => ''
];

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Admin login attempt");
        
        // Retrieve email, password, and reCAPTCHA response
        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';
        $recaptcha_response = $_POST["recaptcharesponse"] ?? '';

        if (empty($email) || empty($password) || empty($recaptcha_response)) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Empty email, password or reCAPTCHA");
            $response["message"] = "Please fill all the fields.";
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
            'secret'   => $recaptcha_secret,
            'response' => $recaptcha_response
        ]);
        $recaptcha_result = curl_exec($recaptcha_check);
        curl_close($recaptcha_check);

        $captcha_success = json_decode($recaptcha_result, true);
        if (!is_array($captcha_success)) {
            $captcha_success = ["success" => $captcha_success];
        }
        if (!isset($captcha_success["success"]) || !$captcha_success["success"]) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Wrong CAPTCHA");
            $response["message"] = "reCAPTCHA verification failed.";
            echo json_encode($response);
            ob_end_flush();
            exit;
        }

        // Get the database connection for authentication
        $admin_conn = db_client::get_connection("admin_db");

        // Retrieve the admin from the "admins" table
        // Now we also select the is_verified field
        $stmt = $admin_conn->prepare(
            "SELECT id, password_hash, is_verified, password_expiry FROM admins WHERE email = :email"
        );
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Admin inserted wrong email");
            $response["message"] = "Wrong credentials.";
        } else if (password_verify($password, $admin["password_hash"])) {
            syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Admin logged in");
            
            // Start the session and save the admin's information (id, email, is_verified)
            $_SESSION["admin"] = [
                'id'    => $admin['id'],
                'email' => $email,
                'is_verified' => $admin['is_verified']
            ];
            
            $_SESSION["timeout"] = date("Y-m-d H:i:s", strtotime("+30 minutes"));
            
            // If the admin is not yet verified, force a password change
            if (!$admin['is_verified'] || strtotime($admin['password_expiry']) < date("Y-m-d H:i:s")) {
                $_SESSION['force_password_change'] = true;
                $response["success"] = true;
                $response["message"] = "Password change required.";
                $response["force_password_change"] = true;
            } else {
                $response["success"] = true;
                $response["message"] = "Login succeeded!";
            }
        } else {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Wrong password");
            $response["message"] = "Wrong credentials.";
        }
    } else {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Invalid request method");
        http_response_code(405); // HTTP method not allowed
        $error_message = urlencode('Invalid request method');
        header("Location: /error.html?error=$error_message");
        exit;
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] " . $e->getMessage());
    $response["message"] = "Database error";
}

ob_end_clean();
echo json_encode($response);
exit;
