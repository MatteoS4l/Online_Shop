<?php
session_start();



require_once '../dbms.inc.php';
require_once '../wishlist.inc.php';

header('Content-Type: application/json');

// Log per debug
error_log("Wishlist AJAX chiamato - SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'undefined'));

// Verifica connessione database
if ($connessione->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Errore connessione database: ' . $connessione->connect_error]);
    exit;
}

// Verifica che la richiesta sia POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

// Ottieni dati dalla richiesta
$input = json_decode(file_get_contents('php://input'), true);

// Debug input
error_log("Input ricevuto: " . json_encode($input));

$action = $input['action'] ?? '';
$product_id = intval($input['product_id'] ?? 0);

// Verifica autenticazione (per ora usiamo id utente fisso per test)
$user_id = $_SESSION['user_id'] ?? 1; // Sostituisci con il sistema di auth reale

error_log("Action: $action, Product ID: $product_id, User ID: $user_id");

// Solo alcune azioni richiedono product_id
if (in_array($action, ['add', 'remove', 'toggle', 'check']) && !$product_id) {
    echo json_encode(['success' => false, 'message' => 'ID prodotto non valido']);
    exit;
}

$result = [];

try {
    switch ($action) {
        case 'add':
            $result = addToWishlist($product_id, $user_id);
            break;
        
        case 'remove':
            $result = removeFromWishlist($product_id, $user_id);
            break;
        
        case 'toggle':
            if (isInWishlist($product_id, $user_id)) {
                $result = removeFromWishlist($product_id, $user_id);
                $result['action'] = 'removed';
            } else {
                $result = addToWishlist($product_id, $user_id);
                $result['action'] = 'added';
            }
            break;
        
        case 'check':
            $result = [
                'success' => true,
                'isInWishlist' => isInWishlist($product_id, $user_id),
                'count' => countUserWishlist($user_id)
            ];
            break;
        
        case 'clear_all':
            // Svuota tutti i preferiti dell'utente
            $del = $connessione->prepare("DELETE FROM Preferiti WHERE id_utente = ?");
            $del->bind_param("i", $user_id);
            $ok = $del->execute();
            $result = [
                'success' => $ok,
                'message' => $ok ? 'Lista svuotata' : 'Errore nello svuotare la lista'
            ];
            break;
        default:
            $result = ['success' => false, 'message' => 'Azione non valida: ' . $action];
            break;
    }
} catch (Exception $e) {
    $result = ['success' => false, 'message' => 'Errore interno: ' . $e->getMessage()];
    error_log("Errore wishlist: " . $e->getMessage());
}

// Aggiungi sempre il conteggio aggiornato
if ($result['success']) {
    $result['count'] = countUserWishlist($user_id);
}

echo json_encode($result);
?>
