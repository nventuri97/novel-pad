<?php
// admin_dashboard.php

header('Content-Type: application/json');
session_start();
ob_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin'])) {
    http_response_code(403); // Forbidden
    echo json_encode([
        "success" => false,
        "message" => "Not authorized (admin only)."
    ]);
    exit;
}

include '../utils/db-client.php';

$response = [
    "success" => false,
    "message" => "",
];

try {
    $auth_conn = db_client::get_connection("authentication_db");
    $novels_conn = db_client::get_connection("novels_db");

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Retrieve the admin's email (assuming that the session contains an Admin object or individual variables)
        $adminEmail = $_SESSION['admin']['email'] ?? '';

        // Retrieve all users and their profiles
        $stmt = $auth_conn->prepare("
            SELECT u.email, p.nickname, p.is_premium
            FROM authentication_db.users u
            JOIN novels_db.user_profiles p ON u.id = p.user_id
            WHERE u.is_verified = 1
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response["success"]    = true;
        $response["users"]      = $users;
        $response["adminEmail"] = $adminEmail;  // Pass the admin's email to the frontend
        echo json_encode($response);
        exit;
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Toggle is_premium
        $nickname = $_POST['nickname'] ?? '';
        $newStatus = $_POST['newStatus'] ?? '';

        if (empty($nickname) || $newStatus === '') {
            http_response_code(400);
            $response["message"] = "Missing data (email or newStatus).";
            echo json_encode($response);
            exit;
        }

        $boolValue = ($newStatus === 'true') ? 1 : 0;

        $update = $novels_conn->prepare("
            UPDATE user_profiles
            SET is_premium = :val
            WHERE nickname = :nickname
        ");
        $update->bindParam(':val', $boolValue, PDO::PARAM_INT);
        $update->bindParam(':nickname', $nickname);
        $update->execute();

        $response["success"] = true;
        $response["message"] = "Premium status updated.";
        
    }
    else {
        http_response_code(405);
        $response["message"] = "Method not allowed.";
        echo json_encode($response);
        exit;
    }
}
catch (PDOException $e) {
    http_response_code(500);
    $response["message"] = "Database error: " . $e->getMessage();
    echo json_encode($response);
    exit;
}

ob_end_clean();
echo json_encode($response);
exit;
?>