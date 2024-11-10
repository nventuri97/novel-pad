<?php
session_start();
$user='admin';
$pass='admin';
$host='mysql';
$db='authentication';
$conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password_hash, is_premium FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Login riuscito, inizializza la sessione
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_premium'] = $user['is_premium'];
        echo "Login avvenuto con successo!";
        // Reindirizzamento alla dashboard
        header("Location: user_dashboard.php");
        exit;
    } else {
        echo "Nome utente o password errati.";
    }
}
?>

<!-- Form di login -->
<form method="post" action="login.php">
    <label>Nome utente:</label>
    <input type="text" name="username" required>
    <label>Password:</label>
    <input type="password" name="password" required>
    <button type="submit">Accedi</button>
</form>
