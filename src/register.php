<?php
include 'db-client.php';
include 'user.php';
ob_start();
// Configurazione del database
$auth_db = 'authentication_db';
$novels_db = 'novels_db';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dati dell'utente inviati tramite POST
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];

    // Validazione di base
    if (empty($username) || empty($password) || empty($email)) {
        echo "Nome utente, password ed email sono obbligatori.";
        exit;
    }

    try {
        // Connessione al database di autenticazione
        $auth_conn = db_client::get_connection($auth_db);

        // Creazione di un salt univoco
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Inserimento dati di autenticazione in `authentication_db`
        $auth_stmt = $auth_conn->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)");
        $auth_stmt->bindParam(':username', $username);
        $auth_stmt->bindParam(':password_hash', $passwordHash);
        $auth_stmt->execute();

        // Ottiene l'ID dell'utente appena creato
        $user_id = $auth_conn->lastInsertId();

        // Connessione al database novels_db per i dati del profilo
        $novels_conn = db_client::get_connection($novels_db);

        // Inserimento dei dati del profilo in `user_profiles` su novels_db
        $novels_stmt = $novels_conn->prepare("INSERT INTO user_profiles (user_id, email, full_name, is_premium) VALUES (:user_id, :email, :full_name, :is_premium)");
        $novels_stmt->bindParam(':user_id', $user_id);
        $novels_stmt->bindParam(':email', $email);
        $novels_stmt->bindParam(':full_name', $full_name);
        $novels_stmt->bindValue(':is_premium', 0, PDO::PARAM_BOOL);  // Imposta utente non premium per default
        $novels_stmt->execute();

        echo "Registration successfully completed!";
        

        $user = new User($user_id, $username, $email, $full_name, 0);
        
        // echo $user->get_full_name();
        session_start();
        $_SESSION["user"] = $user;
        header("Location: user_dashboard.php");
        exit;
    } catch (PDOException $e) {
        echo "Errore: " . $e->getMessage();
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User registration</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <div class="register-container">
            <h2>Register</h2>
            <form id="registerForm" action="register.php" method="POST">
                <div class="error-message" id="error-message" style="display: none;"></div>
                <div class="success-message" id="success-message" style="display: none;"></div>

                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter a password" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>

                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="Enter your full name">

                <button type="submit">Register</button>
            </form>
        </div>

        <script>
            document.getElementById('registerForm').addEventListener('submit', function(event) {
                // Prevent submission if there are validation errors
                const errorMessage = document.getElementById('error-message');
                const successMessage = document.getElementById('success-message');
                errorMessage.style.display = 'none';
                successMessage.style.display = 'none';

                // Get form field values
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value.trim();
                const email = document.getElementById('email').value.trim();

                // Example client-side validation
                if (username.length < 4) {
                    event.preventDefault();
                    errorMessage.textContent = "Username must be at least 4 characters.";
                    errorMessage.style.display = 'block';
                    return;
                }

                if (password.length < 6) {
                    event.preventDefault();
                    errorMessage.textContent = "Password must be at least 6 characters.";
                    errorMessage.style.display = 'block';
                    return;
                }

                if (!email.includes('@')) {
                    event.preventDefault();
                    errorMessage.textContent = "Please enter a valid email address.";
                    errorMessage.style.display = 'block';
                    return;
                }

                // Show a success message before submission (optional)
                successMessage.textContent = "All data is valid. Submitting...";
                successMessage.style.display = 'block';
            });
        </script>
    </body>
</html>

