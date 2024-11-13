<?php
ob_start();
// Configurazione del database
$host = 'mysql';
$auth_db = 'authentication_db';
$novels_db = 'novels_db';
$user = 'admin';
$pass = 'admin';

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
        $auth_conn = new PDO("mysql:host=$host;dbname=$auth_db", $user, $pass);
        $auth_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        $novels_conn = new PDO("mysql:host=$host;dbname=$novels_db", $user, $pass);
        $novels_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Inserimento dei dati del profilo in `user_profiles` su novels_db
        $novels_stmt = $novels_conn->prepare("INSERT INTO user_profiles (user_id, email, full_name, is_premium) VALUES (:user_id, :email, :full_name, :is_premium)");
        $novels_stmt->bindParam(':user_id', $user_id);
        $novels_stmt->bindParam(':email', $email);
        $novels_stmt->bindParam(':full_name', $full_name);
        $novels_stmt->bindValue(':is_premium', 0, PDO::PARAM_BOOL);  // Imposta utente non premium per default
        $novels_stmt->execute();

        echo "Registration successfully completed!";
        header("Location: user_dashboard.php");
        exit;
    } catch (PDOException $e) {
        echo "Errore: " . $e->getMessage();
    }
}
ob_end_flush();
?>

<!-- Codice HTML per il form di registrazione -->
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione</title>
</head>
<body>
    <h2>Registrazione Nuovo Utente</h2>
    <form method="post" action="register.php">
        <label for="username">Nome utente:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="full_name">Nome completo:</label>
        <input type="text" id="full_name" name="full_name"><br><br>

        <button type="submit">Registrati</button>
    </form>
</body>
</html>
