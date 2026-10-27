<?php
require "include/template2.inc.php";
require "include/dbms.inc.php";
require "include/layout-utils.inc.php";

session_start();

// Se l'utente è già loggato, reindirizza alla homepage
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Processa il form di recupero password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!empty($email)) {
        // Verifica se l'email esiste nel database
        $stmt = $connessione->prepare("SELECT id FROM Utente WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Email trovata - in un'implementazione reale qui invieresti l'email
            // Per ora simuliamo l'invio e reindirizzamo al login con messaggio di successo
            
            // Genera un token di reset (in un'implementazione reale lo salveresti nel database)
            $reset_token = bin2hex(random_bytes(32));
            
            // TODO: Qui andresti l'invio della email con il link di reset
            // mail($email, "Reset Password", "Clicca qui per reimpostare la password: reset-password.php?token=$reset_token");
            
            // Reindirizza al login con messaggio di successo
            header('Location: login.php?message=email_sent');
            exit;
        }
        
        // Anche se l'email non esiste, per sicurezza mostriamo lo stesso messaggio
        // (per non rivelare quali email sono registrate)
        header('Location: login.php?message=email_sent');
        exit;
        
        $stmt->close();
    }
}

$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/dtml/forgot-password.html");

// Imposta i link del layout in base all'autenticazione
setLayoutAccountInfo($main);

$main->setContent("BODY", $body->get());
$main->close();
?>
