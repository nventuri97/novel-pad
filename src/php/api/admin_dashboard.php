<?php
// admin_dashboard.php

header('Content-Type: application/json');
session_start();

// Verifica se l'admin è loggato
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

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Recuperiamo anche l'email dell'admin (supponendo che in sessione ci sia un oggetto Admin o singole variabili)
         $adminEmail = $_SESSION['admin']['email'] ?? '';
         $adminVerified= $_SESSION['admin']['is_verified'] ?? '';

        // Oppure, se hai salvato l’email in $_SESSION['admin_email']:
        // $adminEmail = $_SESSION['admin_email'] ?? '';

        // Recupera tutti gli utenti e i loro profili
        $stmt = $auth_conn->prepare("
            SELECT u.email, up.nickname, up.is_premium
            FROM authentication_db.users u
            JOIN novels_db.user_profiles up ON u.id = up.user_id
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response["success"]    = true;
        $response["users"]      = $users;
        $response["adminEmail"] = $adminEmail;  // Passiamo l'email dell'admin al frontend
        echo json_encode($response);
        exit;
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Toggle is_premium
        $email     = $_POST['email']     ?? '';
        $newStatus = $_POST['newStatus'] ?? '';

        if (empty($email) || $newStatus === '') {
            http_response_code(400);
            $response["message"] = "Missing data (email or newStatus).";
            echo json_encode($response);
            exit;
        }

        $boolValue = ($newStatus === 'true') ? 1 : 0;

        $stmt = $auth_conn->prepare("
            SELECT up.user_id
            FROM novels_db.user_profiles up
            JOIN authentication_db.users u ON u.id = up.user_id
            WHERE u.email = :email
        ");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) {
            $response["message"] = "User not found.";
            echo json_encode($response);
            exit;
        }

        $update = $auth_conn->prepare("
            UPDATE novels_db.user_profiles
            SET is_premium = :val
            WHERE user_id = :uid
        ");
        $update->bindParam(':val', $boolValue, PDO::PARAM_INT);
        $update->bindParam(':uid', $profile['user_id'], PDO::PARAM_INT);
        $update->execute();

        $response["success"] = true;
        $response["message"] = "Premium status updated.";
        echo json_encode($response);
        exit;
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
