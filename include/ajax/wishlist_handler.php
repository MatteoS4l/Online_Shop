<?php
session_start();
require_once '../dbms.inc.php';
require_once '../wishlist.inc.php';
require_once '../auth.inc.php';

// Verifica che sia una richiesta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}

$action = $_POST['action'] ?? '';

// Per alcune azioni, gestisci utenti non loggati in modo più elegante
if (!isLoggedIn()) {
    if (in_array($action, ['get_count', 'get_user_wishlist'])) {
        // Ritorna una risposta vuota per utenti non loggati
        echo json_encode([
            'success' => true,
            'wishlist' => [],
            'wishlist_count' => 0,
            'user_logged_in' => false
        ]);
        exit;
    } else {
        // Per azioni che richiedono login, ritorna errore
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Devi effettuare il login per gestire i preferiti']);
        exit;
    }
}

$user_id = getUserId();
$action = $_POST['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? 0);

// L'ID prodotto non è richiesto per alcune azioni
if (!$product_id && !in_array($action, ['get_count', 'get_user_wishlist'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID prodotto non valido']);
    exit;
}

$response = ['success' => false];

try {
    // Debug: log dell'operazione
    error_log("Wishlist operation: action=$action, product_id=$product_id, user_id=$user_id");
    
    switch ($action) {
        case 'add':
            $result = addToWishlist($product_id, $user_id);
            if ($result && $result['success']) {
                $response['success'] = true;
                $response['message'] = 'Prodotto aggiunto ai preferiti';
                $response['action'] = 'added';
            } else {
                $response['error'] = $result['message'] ?? 'Errore nell\'aggiunta ai preferiti';
            }
            break;
            
        case 'remove':
            $result = removeFromWishlist($product_id, $user_id);
            if ($result && $result['success']) {
                $response['success'] = true;
                $response['message'] = 'Prodotto rimosso dai preferiti';
                $response['action'] = 'removed';
            } else {
                $response['error'] = $result['message'] ?? 'Errore nella rimozione dai preferiti';
            }
            break;
            
        case 'toggle':
            if (isInWishlist($product_id, $user_id)) {
                $result = removeFromWishlist($product_id, $user_id);
                $response['action'] = 'removed';
                $response['message'] = 'Prodotto rimosso dai preferiti';
            } else {
                $result = addToWishlist($product_id, $user_id);
                $response['action'] = 'added';
                $response['message'] = 'Prodotto aggiunto ai preferiti';
            }
            
            if ($result && $result['success']) {
                $response['success'] = true;
            } else {
                $response['error'] = $result['message'] ?? 'Errore nell\'operazione';
            }
            break;
            
        case 'get_count':
            // Restituisce solo il conteggio dei preferiti
            $response['success'] = true;
            $response['wishlist_count'] = countUserWishlist($user_id);
            break;
            
        case 'get_user_wishlist':
            // Restituisce la lista degli ID dei prodotti nei preferiti
            $response['success'] = true;
            $response['wishlist'] = getUserWishlistIds($user_id);
            $response['wishlist_count'] = count($response['wishlist']);
            $response['user_logged_in'] = true;
            break;
            
        default:
            http_response_code(400);
            $response['error'] = 'Azione non valida';
    }
    
    // Aggiungi sempre il conteggio aggiornato
    if ($response['success']) {
        $response['wishlist_count'] = countUserWishlist($user_id);
    }
    
} catch (Exception $e) {
    $response['error'] = 'Errore del server: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
