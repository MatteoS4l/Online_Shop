<?php
session_start();
require_once "include/template2.inc.php";
require_once "include/dbms.inc.php";
require_once "include/auth.inc.php";
require_once "include/wishlist.inc.php";
require_once "include/layout-utils.inc.php";

// Se l'utente non è loggato, reindirizza al login
requireAuth('account.php', 'Accedi per visualizzare il tuo account');

global $connessione;

$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/account.html");

// Conteggio preferiti per l'header
$user_id = $_SESSION['user_id'] ?? null;
$wishlist_count = $user_id ? countUserWishlist($user_id) : 0;
$main->setContent("NUMBER_NOTY_FAV", $wishlist_count);

// Imposta i link del layout in base all'autenticazione
setLayoutAccountInfo($main);

// Recupera i dati dell'utente usando le funzioni di auth
$userData = getUserData();

if ($userData) {
    // Popola il template con i dati dell'utente
    $body->setContent("USER_NAME", htmlspecialchars($userData['name']));
    $body->setContent("USER_SURNAME", htmlspecialchars($userData['surname']));
    $body->setContent("USER_EMAIL", htmlspecialchars($userData['email']));
    
    // Dato che la tabella Utente non ha il campo data_registrazione, 
    // mostro una data generica o potremmo aggiungere il campo in futuro
    $body->setContent("MEMBER_SINCE", "Gennaio 2024");
    
} else {
    // Se non trova i dati utente nella sessione, forza logout
    logoutUser();
    header('Location: login.php?error=Sessione scaduta');
    exit;
}

$main->setContent("BODY", $body->get());
$main->close();
?>