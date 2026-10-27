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
$size = $input['size'] ?? 'M'; // Taglia di default
$quantity = $input['quantity'] ?? 1;

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID prodotto mancante']);
    exit;
}

global $connessione;

try {
    // Controlla se il prodotto esiste
    $stmt = $connessione->prepare("SELECT nome_prodotto FROM Prodotto WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Prodotto non trovato']);
        exit;
    }
    
    // Controlla lo stock disponibile per la taglia scelta
    $stmt = $connessione->prepare("SELECT quantita FROM Magazzino WHERE id_prodotto = ? AND taglia = ? LIMIT 1");
    $stmt->bind_param("is", $product_id, $size);
    $stmt->execute();
    $result = $stmt->get_result();
    $magazzino = $result->fetch_assoc();
    $stmt->close();
    $stock_disponibile = $magazzino ? intval($magazzino['quantita']) : 0;

    // Controlla se il prodotto è già nel carrello
    $stmt = $connessione->prepare("SELECT quantita_prodotto FROM Carrello WHERE id_utente = ? AND id_prodotto = ? AND taglia_prodotto = ?");
    $stmt->bind_param("iis", $user_id, $product_id, $size);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_item = $result->fetch_assoc();
    $stmt->close();

    if ($existing_item) {
        $new_quantity = $existing_item['quantita_prodotto'] + $quantity;
        if ($new_quantity > $stock_disponibile) {
            echo json_encode(['success' => false, 'message' => 'Stock insufficiente per la taglia scelta']);
            exit;
        }
        $stmt = $connessione->prepare("UPDATE Carrello SET quantita_prodotto = ? WHERE id_utente = ? AND id_prodotto = ? AND taglia_prodotto = ?");
        $stmt->bind_param("iiis", $new_quantity, $user_id, $product_id, $size);
        $action = 'aggiornato';
    } else {
        if ($quantity > $stock_disponibile) {
            echo json_encode(['success' => false, 'message' => 'Stock insufficiente per la taglia scelta']);
            exit;
        }
        $stmt = $connessione->prepare("INSERT INTO Carrello (id_utente, id_prodotto, taglia_prodotto, quantita_prodotto) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $user_id, $product_id, $size, $quantity);
        $action = 'aggiunto';
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Conta gli elementi totali nel carrello
        $stmt = $connessione->prepare("SELECT COUNT(*) as count FROM Carrello WHERE id_utente = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $cart_count = $row['count'];
        $stmt->close();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Prodotto ' . $action . ' al carrello',
            'cart_count' => $cart_count
        ]);
    } else {
        throw new Exception('Errore nell\'aggiunta del prodotto');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore del server: ' . $e->getMessage()]);
}
?>
