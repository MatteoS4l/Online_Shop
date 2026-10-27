<?php
session_start();
require_once '../dbms.inc.php';
require_once '../auth.inc.php';
require_once '../cart-utils.inc.php';

// Verifica che sia una richiesta valida
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}

$response = ['success' => false];

try {
    $user_id = null;
    if (isLoggedIn()) {
        $user_id = getUserId();
    }
    
    // Genera l'HTML della preview del carrello
    $cart_html = generateCartPreviewHTML($user_id);
    
    $response['success'] = true;
    $response['html'] = $cart_html;
    
} catch (Exception $e) {
    $response['error'] = 'Errore del server: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
