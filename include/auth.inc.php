<?php
require_once __DIR__ . '/utils.inc.php';
// Funzioni di utilità per l'autenticazione

/**
 * Verifica se l'utente è loggato
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Ottieni l'ID dell'utente loggato
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Ottieni i dati dell'utente loggato
 */
function getUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'surname' => $_SESSION['user_surname'] ?? '',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

/**
 * Reindirizza al login se l'utente non è autenticato
 */
function requireAuth($redirect_after_login = null, $error_message = 'Accedi per eseguire questa azione') {
    if (!isLoggedIn()) {
        $redirect_url = 'login.php?error=' . urlencode($error_message);
        if ($redirect_after_login) {
            $redirect_url .= '&redirect=' . urlencode($redirect_after_login);
        }
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Genera un hash sicuro per la password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifica se la password corrisponde all'hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Controlla se un'email esiste già nel database
 */
function emailExists($email) {
    global $connessione;
    
    $stmt = $connessione->prepare("SELECT id FROM Utente WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Registra un nuovo utente
 */
function registerUser($nome, $cognome, $email, $password) {
    global $connessione;
    
    // Debug: verifica che la connessione esista
    if (!$connessione) {
        return [
            'success' => false,
            'errors' => ['Errore di connessione al database']
        ];
    }
    
    // Validazione input
    $errors = [];
    
    // Validazione nome
    if (empty(trim($nome))) {
        $errors[] = "Il nome è obbligatorio";
    } elseif (strlen(trim($nome)) < 2) {
        $errors[] = "Il nome deve contenere almeno 2 caratteri";
    } elseif (strlen(trim($nome)) > 50) {
        $errors[] = "Il nome non può superare 50 caratteri";
    }
    
    // Validazione cognome
    if (empty(trim($cognome))) {
        $errors[] = "Il cognome è obbligatorio";
    } elseif (strlen(trim($cognome)) < 2) {
        $errors[] = "Il cognome deve contenere almeno 2 caratteri";
    } elseif (strlen(trim($cognome)) > 50) {
        $errors[] = "Il cognome non può superare 50 caratteri";
    }
    
    // Validazione email
    if (empty(trim($email))) {
        $errors[] = "L'email è obbligatoria";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email non è valida (formato richiesto: esempio@dominio.com)";
    } elseif (strlen($email) > 100) {
        $errors[] = "L'email non può superare 100 caratteri";
    } elseif (emailExists($email)) {
        $errors[] = "Questa email è già registrata";
    }
    
    // Validazione password
    if (empty($password)) {
        $errors[] = "La password è obbligatoria";
    } elseif (strlen($password) < 8) {
        $errors[] = "La password deve contenere almeno 8 caratteri";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "La password deve contenere almeno un numero";
    } elseif (strlen($password) > 100) {
        $errors[] = "La password non può superare 100 caratteri";
    }
    
    // Se ci sono errori, restituiscili
    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
    
    // Inserimento nel database (password criptata)
    try {
        // Prepara i valori per bind_param (devono essere variabili, non risultati di funzioni)
        $nome_clean = trim($nome);
        $cognome_clean = trim($cognome);
        $email_clean = strtolower(trim($email));
        
        $password_hash = criptify($password);
        $stmt = $connessione->prepare("INSERT INTO Utente (nome, cognome, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome_clean, $cognome_clean, $email_clean, $password_hash);
        
        if ($stmt->execute()) {
            $user_id = $connessione->insert_id;
            
            return [
                'success' => true,
                'user_id' => $user_id,
                'message' => 'Registrazione completata con successo!'
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Errore durante la registrazione. Riprova più tardi.']
            ];
        }
    } catch (Exception $e) {
        error_log("Errore registrazione utente: " . $e->getMessage());
        return [
            'success' => false,
            'errors' => ['Errore interno del server. Riprova più tardi.']
        ];
    }
}

/**
 * Effettua il login dell'utente
 */
function loginUser($email, $password) {
    global $connessione;
    
    // Validazione input base
    if (empty(trim($email)) || empty($password)) {
        return [
            'success' => false,
            'error' => 'Email e password sono obbligatori'
        ];
    }
    
    try {
        $stmt = $connessione->prepare("SELECT id, nome, cognome, email, password FROM Utente WHERE email = ?");
        $clean_email = strtolower(trim($email));
        $stmt->bind_param("s", $clean_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Confronto password criptata
            if (criptify($password) === $user['password']) {
                // Login riuscito - imposta sessione
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_surname'] = $user['cognome'];
                $_SESSION['user_email'] = $user['email'];
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'nome' => $user['nome'],
                        'cognome' => $user['cognome'],
                        'email' => $user['email']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Password non corretta'
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => 'Email non trovata'
            ];
        }
    } catch (Exception $e) {
        error_log("Errore login utente: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Errore interno del server'
        ];
    }
}

/**
 * Effettua il logout dell'utente
 */
function logoutUser() {
    // Cancella tutte le variabili di sessione
    $_SESSION = array();
    
    // Cancella il cookie di sessione se esiste
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Distruggi la sessione
    session_destroy();
}
?>
