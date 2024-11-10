<?php
session_start();
$user='admin';
$pass='admin';
$host='mysql';
$auth_db='authentication_db';
$auth_conn = new PDO("mysql:host=$host;dbname=$auth_db", $user, $pass);
$auth_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$novel_db='novels_db';
$novel_conn = new PDO("mysql:host=$host;dbname=$novel_db", $user, $pass);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $auth_conn->prepare("SELECT id, password_hash FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Login riuscito, recupera lo stato premium dal database novels_db
        $user_id = $user['id'];
        
        // Query per recuperare il premium status dall'altro database
        $novel_stmt = $novel_conn->prepare("SELECT is_premium FROM users WHERE user_id = :user_id");
        $novel_stmt->bindParam(':user_id', $user_id);
        $novel_stmt->execute();
        $novel_user = $novel_stmt->fetch(PDO::FETCH_ASSOC);

        if ($novel_user) {
            // Salva le informazioni nella sessione
            $_SESSION['user_id'] = $user_id;
            $_SESSION['is_premium'] = $novel_user['is_premium'];
            echo "Login succed!";
            // Reindirizzamento alla dashboard
            header("Location: user_dashboard.php");
            exit;
        } else {
            echo "Errore nel recupero delle informazioni premium.";
        }
    } else {
        echo "Wrong username or password.";
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
