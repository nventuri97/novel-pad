<?php

header('Content-Type: application/json'); // Ensure the response is in JSON

// Include any libraries or utility files specific to admins.
require __DIR__ . '/../utils/db-client.php';
require __DIR__ . '/../utils/mail-client.php';
$config = require __DIR__ . '/../utils/config.php';

session_start();
ob_start();  // Start buffering to capture any unwanted output
openlog("admin_login.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

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

if (!$captcha_success || !$captcha_success["success"]) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"]. " - - [" . date("Y-m-d H:i:s") . "]  Wrong CAPTCHA");

    $response["message"]= "reCAPTCHA verification failed.";
    echo json_encode($response);
    ob_end_flush();
    exit;
}

// After the success of reCAPTCHA, we send the warning email
sendAllertMail($email, 'admin');

try {
    // Get the database connection for authentication
    $admin_conn = db_client::get_connection("admin_db");

    // Modified to retrieve also tries and is_logged
    $stmt = $admin_conn->prepare(
        "SELECT id, password_hash, is_verified, tries, is_logged, password_expiry FROM admins WHERE email = :email"
    );
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Admin inserted wrong email");
        
        $response["message"] = "Wrong credentials.";
    } 
    // If the admin exists, check if it is blocked or already logged in, then check password.
    else if ($admin["tries"] == 3) {
        // The account is considered blocked
        $response["message"] = "Your account is blocked.";
    }
    else if ($admin["is_logged"]) {
        // Admin is already logged in; we do NOT increment tries here
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Admin tried to login while is_logged is TRUE");
        
        $response["message"] = "Admin already logged in.";
    }
    // Verify correct password
    else if (password_verify($password, $admin["password_hash"])) {
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Admin logged in");
        
        // Start the session and save the admin's information (id, email, is_verified)
        $_SESSION["admin"] = [
            'id'    => $admin['id'],
            'email' => $email,
            'is_verified' => $admin['is_verified']
        ];
        
        $_SESSION["timeout"] = date("Y-m-d H:i:s", strtotime("+30 minutes"));
        
        // If the admin is not yet verified, force password change
        if (!$admin['is_verified'] || strtotime($admin['password_expiry']) > date("Y-m-d H:i:s")) {
            $_SESSION['force_password_change'] = true;
            $response["success"] = true;
            $response["message"] = "Password change required.";
            $response["force_password_change"] = true;
        } else {
            $response["success"] = true;
            $response["message"] = "Login succeeded!";
            session_regenerate_id(true);
        }

        // Reset tries and set is_logged to 1 (true)
        $updateStmt = $admin_conn->prepare("UPDATE admins SET tries = 0, is_logged = 1 WHERE id = :id");
        $updateStmt->bindValue(':id', $admin["id"], PDO::PARAM_INT);
        $updateStmt->execute();
    } 
    // Otherwise the password is wrong
    else {
        syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Wrong password");
        
        // Increment tries
        $newTries = $admin["tries"] + 1;
        if ($newTries > 3) {
            $newTries = 3;
        }
        
        $updateStmt = $admin_conn->prepare("UPDATE admins SET tries = :tries WHERE id = :id");
        $updateStmt->bindValue(':tries', $newTries, PDO::PARAM_INT);
        $updateStmt->bindValue(':id', $admin["id"], PDO::PARAM_INT);
        $updateStmt->execute();
        
        // If it reaches 3, account is blocked
        if ($newTries >= 3) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Admin account blocked due to too many attempts");
            $response["message"] = "Your account is blocked.";
        } else {
            // If it has not yet reached 3 attempts, remain with "Wrong credentials"
            $response["message"] = "Wrong credentials.";
        }
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] " . $e->getMessage());
    
    http_response_code(500); // Internal Server Error
    $response['message'] = 'An error occurred in the database.';
}

ob_end_clean();
echo json_encode($response);
exit;
