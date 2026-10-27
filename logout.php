<?php
session_start();
require_once "include/auth.inc.php";

// Effettua il logout dell'utente
logoutUser();

// Reindirizza alla home page con un messaggio di conferma
header('Location: index.php?message=logout_success');
exit;
?>