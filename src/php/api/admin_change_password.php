<?php
// admin/php/admin_change_password.php

header('Content-Type: application/json');
session_start();

// Verifica che l'admin sia loggato e che debba forzare il cambio password
if (!isset($_SESSION["admin"]) || !isset($_SESSION['force_password_change'])) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Not authorized or password change not required."
    ]);
    exit;
}

require_once __DIR__ . '/../utils/db-client.php';
// Carica anche il config se necessario
$config = require_once __DIR__ . '/../utils/config.php';

$response = [
    "success" => false,
    "message" => ""
];

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $newPassword = $_POST["newPassword"] ?? '';

        if (empty($newPassword)) {
            http_response_code(400);
            $response["message"] = "New password is required.";
            echo json_encode($response);
            exit;
        }

        // (Opzionale) Puoi aggiungere ulteriori controlli sulla complessità della password

        // Ottieni la connessione al database per l'autenticazione
        $auth_conn = db_client::get_connection("authentication_db");

        // Calcola l'hash della nuova password (usando BCRYPT)
        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

        // Aggiorna la password e imposta is_verified a true
        $stmt = $auth_conn->prepare("
            UPDATE admins 
            SET password_hash = :password_hash, is_verified = 1
            WHERE id = :id
        ");
        $stmt->bindParam(':password_hash', $newPasswordHash);
        $stmt->bindParam(':id', $_SESSION["admin"]['id'], PDO::PARAM_INT);
        $stmt->execute();

        // Rimuovi il flag di cambio password forzato
        unset($_SESSION['force_password_change']);

        $response["success"] = true;
        $response["message"] = "Password changed successfully.";
        echo json_encode($response);
        exit;
    } else {
        http_response_code(405);
        $response["message"] = "Method not allowed.";
        echo json_encode($response);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response["message"] = "Database error: " . $e->getMessage();
    echo json_encode($response);
    exit;
}
