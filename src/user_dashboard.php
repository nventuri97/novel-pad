<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

echo "Benvenuto nella tua dashboard!";
if ($_SESSION['is_premium']) {
    echo "Sei un utente premium.";
} else {
    echo "Non sei un utente premium.";
}

// Mostra opzioni per caricare e gestire i romanzi
?>
