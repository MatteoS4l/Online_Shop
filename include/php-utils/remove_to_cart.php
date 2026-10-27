<?php
session_start();
require_once '../dbms.inc.php';
require_once '../auth.inc.php';

// Verifica che l'utente sia autenticato
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Verifica che sia una richiesta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

// Recupera i dati dalla richiesta
$input = json_decode(file_get_contents('php://input'), true);
$product_id = $input['product_id'] ?? null;
$size = $input['size'] ?? null;

if (!$product_id || !$size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parametri mancanti']);
    exit;
}

global $connessione;

try {
    // Rimuovi il prodotto dal carrello
    $stmt = $connessione->prepare("DELETE FROM Carrello WHERE id_utente = ? AND id_prodotto = ? AND taglia_prodotto = ?");
    $stmt->bind_param("iis", $user_id, $product_id, $size);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected_rows > 0) {
            // Conta gli elementi rimanenti nel carrello
            $stmt = $connessione->prepare("SELECT COUNT(*) as count FROM Carrello WHERE id_utente = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $cart_count = $row['count'];
            $stmt->close();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Prodotto rimosso dal carrello',
                'cart_count' => $cart_count
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Prodotto non trovato nel carrello']);
        }
    } else {
        throw new Exception('Errore nella rimozione del prodotto');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore del server: ' . $e->getMessage()]);
}
?>
