<?php
session_start();
require_once "include/dbms.inc.php";
require_once "include/template2.inc.php";
require_once "include/auth.inc.php";
require_once "include/layout-utils.inc.php";

// Se l'utente è già loggato, reindirizza alla home
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

global $connessione;

$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/signup.html");

// Imposta le informazioni del layout
setLayoutAccountInfo($main);

$errors = [];
$success_message = '';

// Gestione della sottomissione del form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera i dati dal form
    $nome = $_POST['nome'] ?? '';
    $cognome = $_POST['cognome'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validazione conferma password lato server
    if ($password !== $confirm_password) {
        $errors[] = "Le password non coincidono";
    }
    
    // Se non ci sono errori di conferma password, procedi con la registrazione
    if (empty($errors)) {
        $result = registerUser($nome, $cognome, $email, $password);
        
        if ($result['success']) {
            // Registrazione riuscita
            $success_message = $result['message'];
            
            // Login automatico dopo la registrazione
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['user_name'] = $nome;
            $_SESSION['user_surname'] = $cognome;
            $_SESSION['user_email'] = $email;
            
            // Redirect immediato alla home
            header("Location: index.php");
            exit;
        } else {
            // Registrazione fallita
            $errors = $result['errors'];
        }
    }
}

// Prepara i messaggi per il template
if (!empty($errors)) {
    $error_html = '<div class="alert alert-danger"><ul class="mb-0">';
    foreach ($errors as $error) {
        $error_html .= '<li>' . htmlspecialchars($error) . '</li>';
    }
    $error_html .= '</ul></div>';
    $body->setContent("MESSAGE", $error_html);
} elseif (!empty($success_message)) {
    $success_html = '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
    $body->setContent("MESSAGE", $success_html);
} else {
    $body->setContent("MESSAGE", "");
}

// Mantieni i valori inseriti in caso di errore
$body->setContent("NOME_VALUE", htmlspecialchars($_POST['nome'] ?? ''));
$body->setContent("COGNOME_VALUE", htmlspecialchars($_POST['cognome'] ?? ''));
$body->setContent("EMAIL_VALUE", htmlspecialchars($_POST['email'] ?? ''));

$main->setContent("BODY", $body->get());
$main->close();
?>