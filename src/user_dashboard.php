<?php
include 'user.php';
session_start();
$user = $_SESSION["user"];
if ($user->get_id()!=null) {
    header("Location: login.php");
    exit;
}

echo "Benvenuto nella tua dashboard!";
if ($user->is_premium()) {
    echo "Sei un utente premium.";
} else {
    echo "Non sei un utente premium.";
}

// Mostra opzioni per caricare e gestire i romanzi
?>
