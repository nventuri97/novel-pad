<?php
include 'user.php';
include 'db-client.php';
ini_set("session.cookie_httponly", 1); // Prevents JavaScript from accessing session cookies
ini_set("session.cookie_secure", 1); // Ensures cookies are sent only over HTTPS
ini_set("session.use_strict_mode", 1); // Prevents session fixation attacks
session_start();
ob_start();

$auth_conn = db_client::get_connection("authentication_db");

$novel_conn = db_client::get_connection("novels_db");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Retrieve user from authentication_db
    $stmt = $auth_conn->prepare(
        "SELECT id, password_hash FROM users WHERE username = :username"
    );
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password_hash"])) {
        // Login successful, retrieve premium status from novels_db
        $user_id = $user["id"];

        // Correct query to retrieve is_premium from user_profiles
        $novel_stmt = $novel_conn->prepare(
            "SELECT * FROM user_profiles WHERE user_id = :user_id"
        );
        $novel_stmt->bindParam(":user_id", $user_id);
        $novel_stmt->execute();
        $novel_user = $novel_stmt->fetch(PDO::FETCH_ASSOC);
        $user= new User($novel_user["user_id"], $username, $novel_user["email"], $novel_user["full_name"], $novel_user["is_premium"]);

        if ($novel_user) {
            // Save session information
            $_SESSION["user"] = $user;
            echo "Login succed!";
            // Redirecting to the dashboard
            header("Location: user_dashboard.php");
            exit();
        } else {
            echo "Error retrieving premium information.";
        }
    } else {
        echo "Wrong username or password.";
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <div class="login-container">
            <h2>Login</h2>
            <form id="loginForm" action="login.php" method="POST">
                <div class="error-message" id="error-message" style="display: none;"></div>

                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>

                <button type="submit">Login</button>
            </form>
        </div>

        <script>
            document.getElementById('loginForm').addEventListener('submit', function(event) {
                const errorMessage = document.getElementById('error-message');
                errorMessage.style.display = 'none';

                // Get field values
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value.trim();

                // Example validation
                if (username === '' || password === '') {
                    event.preventDefault();
                    errorMessage.textContent = "Both fields are required.";
                    errorMessage.style.display = 'block';
                    return;
                }
            });
        </script>
    </body>
</html>

