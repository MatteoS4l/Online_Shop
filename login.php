<?php
session_start();
require_once "include/template2.inc.php";
require_once "include/dbms.inc.php";
require_once "include/layout-utils.inc.php";
require_once "include/auth.inc.php";

// Se l'utente è già loggato, reindirizza dove richiesto
if (isLoggedIn()) {
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
    } else {
        header('Location: index.php');
    }
    exit;
}

$error_message = '';
$success_message = '';

// Gestisci messaggi da altre pagine (es. recupero password)
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'email_sent':
            $success_message = 'Email di recupero password inviata! Controlla la tua casella di posta.';
            break;
    }
}

// Gestisci messaggi di errore da altre pagine (es. accesso negato)
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

// Processa il login se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            // Login riuscito - controllo tipo utente tramite DB (nomi corretti)
            $user_id = $_SESSION['user_id'];
            $is_admin = false;
            $sql = "SELECT s.script, u.name FROM User_has_ugroup uhu
                    JOIN Ugroup u ON uhu.id_ugroup = u.id
                    JOIN Ugroup_has_service uhs ON u.id = uhs.ugroup_id
                    JOIN Service s ON uhs.service_id = s.id
                    WHERE uhu.id_utente = ?";
            $stmt = $connessione->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                if (strtolower($row['script']) === 'admin.php' && strtolower($row['name']) === 'administrator') {
                    $is_admin = true;
                    break;
                }
            }
            $_SESSION['user_type'] = $is_admin ? 'admin' : 'user';
            if ($is_admin) {
                header('Location: nope/adminPHP/admin.php');
                exit;
            } else {
                $redirect = $_GET['redirect'] ?? 'index.php';
                header('Location: ' . $redirect);
                exit;
            }
        } else {
            $error_message = $result['error'];
        }
    } else {
        $error_message = 'Inserisci email e password.';
    }
}

// Prepara i messaggi per il template
$message_html = '';

// Messaggio di errore (rosso)
if (!empty($error_message)) {
    $message_html = '<div class="txt-center p-t-20">
                        <span class="stext-105" style="color: #e74c3c; font-weight: bold;">
                            ' . htmlspecialchars($error_message) . '
                        </span>
                    </div>';
}

// Messaggio di successo (verde) - ha priorità sui messaggi di errore
if (!empty($success_message)) {
    $message_html = '<div class="txt-center p-t-20">
                        <span class="stext-105" style="color: #27ae60; font-weight: bold;">
                            ' . htmlspecialchars($success_message) . '
                        </span>
                    </div>';
}

$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/login.html");

$body->setContent("ERROR_MESSAGE", $message_html);

// Imposta i link del layout in base all'autenticazione
setLayoutAccountInfo($main);

$main->setContent("BODY", $body->get());
$main->close();
?>