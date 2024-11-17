<?php
include 'user.php';
include 'db-client.php';
session_start();
if (!isset($_SESSION["user"])) {
    echo "Error: No user data in session.";
    exit;
}
$user= $_SESSION["user"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h2>Welcome, <span id="fullName"></span>!</h2>
        </div>

        <div class="dashboard-content">
            <p>Email: <span id="email"></span></p>
            <p>Full Name: <span id="fullNameText"></span></p>
            <p>Status: <span id="status"></span></p>
        </div>

        <div class="dashboard-actions">
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </div>

    <script>
        // Passing user data from PHP to JavaScript
        const user = <?php echo json_encode($user->to_array()); ?>;
        console.log(user.full_name);

        // Handle null or undefined values using JavaScript
        document.getElementById('fullName').innerText = user.full_name ;
        document.getElementById('email').innerText = user.email;
        document.getElementById('fullNameText').innerText = user.full_name;
        document.getElementById('status').innerText = user.is_premium;
    </script>

</body>
</html>
