<?php
// Connessione al database
// We need two different databases: one for the user data and one for the novels.
$host = 'mysql';
$db = 'authentication';
$user = 'admin';
$pass = 'admin';
$conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Convalida input
    if (empty($username) || empty($password)) {
        echo "Nome utente e password sono obbligatori.";
        exit;
    }

    // Hash della password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Inserimento nel database
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :password)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->execute();
        echo "Registrazione avvenuta con successo!";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Codice errore per duplicati (username già in uso)
            echo "Nome utente già in uso. Scegli un altro nome.";
        } else {
            echo "Errore: " . $e->getMessage();
        }
    }
}
?>

<!-- Form di registrazione -->
<form method="post" action="register.php">
    <label>Nome utente:</label>
    <input type="text" name="username" required>
    <label>Password:</label>
    <input type="password" name="password" required>
    <button type="submit">Registrati</button>
</form>
