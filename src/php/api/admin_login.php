<?php
// Assicurati che non ci siano spazi o righe vuote prima di questo tag

// Disabilita la visualizzazione degli errori (in produzione)
// Nota: in fase di sviluppo potresti voler abilitare gli errori, ma fai attenzione che non vengano stampati nella risposta JSON.
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json'); // Assicura che la risposta sia in JSON

// Includi eventuali librerie o file di utilità specifici per gli admin.
require_once __DIR__ . '/../utils/admin.php';
require_once __DIR__ . '/../utils/db-client.php';
$config = require_once __DIR__ . '/../utils/config.php';

// Configurazioni per la gestione delle sessioni (opzionali)
// ini_set("session.cookie_httponly", 1);
// ini_set("session.cookie_secure", 1);
// ini_set("session.use_strict_mode", 1);

ob_start();  // Avvia il buffering per catturare eventuali output indesiderati
openlog("admin_login.php", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$response = [
    'success' => false,
    'message' => ''
];

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Admin login attempt");
        
        // Recupera email, password e risposta reCAPTCHA
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

        // Verifica reCAPTCHA
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

        // Ottieni la connessione al database per l'autenticazione
        $auth_conn = db_client::get_connection("authentication_db");

        // Recupera l'admin dalla tabella "admins"
        // Ora selezioniamo anche il campo is_verified
        $stmt = $auth_conn->prepare(
            "SELECT id, email, password_hash, is_verified FROM admins WHERE email = :email"
        );
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Admin inserted wrong email");
            $response["message"] = "Wrong credentials.";
        } else if (password_verify($password, $admin["password_hash"])) {
            syslog(LOG_INFO, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] Admin logged in");
            
            // Avvia la sessione e salva le informazioni dell'admin (id, email, is_verified)
            session_start();
            $_SESSION["admin"] = [
                'id'    => $admin['id'],
                'email' => $admin['email'],
                'is_verified' => $admin['is_verified']
            ];
            
            // Aggiorna il database impostando logged_in = 1 per l'admin
            $updateStmt = $auth_conn->prepare("UPDATE admins SET logged_in = 1 WHERE id = :id");
            $updateStmt->bindParam(':id', $admin['id'], PDO::PARAM_INT);
            $updateStmt->execute();
            
            // Se l'admin non è ancora verificato, obbliga a cambiare password
            if (!$admin['is_verified']) {
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
        http_response_code(405); // Metodo HTTP non consentito
        $error_message = urlencode('Invalid request method');
        header("Location: /error.html?error=$error_message");
        exit;
    }
} catch (PDOException $e) {
    syslog(LOG_ERR, $_SERVER["REMOTE_ADDR"] . " - - [" . date("Y-m-d H:i:s") . "] " . $e->getMessage());
    $response["message"] = "Database error";
}

ob_clean();
echo json_encode($response);
ob_end_flush();
exit;
