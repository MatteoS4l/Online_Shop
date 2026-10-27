<?php
session_start();
require_once '../dbms.inc.php';
require_once '../auth.inc.php';
require_once '../cart-utils.inc.php';

// Verifica che sia una richiesta POST e che l'utente sia loggato
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

$user_id = getUserId();
$action = $_POST['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$size = $_POST['size'] ?? 'M';

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID prodotto non valido']);
    exit;
}

$response = ['success' => false];

try {
    global $connessione;
    
    switch ($action) {
        case 'add':
            // Verifica se il prodotto è già nel carrello
            $stmt = $connessione->prepare("SELECT id, quantita_prodotto FROM Carrello WHERE id_utente = ? AND id_prodotto = ? AND taglia_prodotto = ?");
            $stmt->bind_param("iis", $user_id, $product_id, $size);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Aggiorna quantità esistente
                $row = $result->fetch_assoc();
                $new_quantity = $row['quantita_prodotto'] + $quantity;
                $update_stmt = $connessione->prepare("UPDATE Carrello SET quantita_prodotto = ? WHERE id = ?");
                $update_stmt->bind_param("ii", $new_quantity, $row['id']);
                $success = $update_stmt->execute();
                $update_stmt->close();
            } else {
                // Aggiungi nuovo elemento
                $insert_stmt = $connessione->prepare("INSERT INTO Carrello (id_utente, id_prodotto, quantita_prodotto, taglia_prodotto) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param("iiis", $user_id, $product_id, $quantity, $size);
                $success = $insert_stmt->execute();
                $insert_stmt->close();
            }
            
            if ($success) {
                $response['success'] = true;
                $response['message'] = 'Prodotto aggiunto al carrello';
                $response['action'] = 'added';
            } else {
                $response['error'] = 'Errore nell\'aggiunta al carrello';
            }
            $stmt->close();
            break;
            
        case 'remove':
            $stmt = $connessione->prepare("DELETE FROM Carrello WHERE id_utente = ? AND id_prodotto = ? AND taglia_prodotto = ?");
            $stmt->bind_param("iis", $user_id, $product_id, $size);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                $response['success'] = true;
                $response['message'] = 'Prodotto rimosso dal carrello';
                $response['action'] = 'removed';
            } else {
                $response['error'] = 'Errore nella rimozione dal carrello';
            }
            break;
            
        case 'update':
            if ($quantity > 0) {
                $stmt = $connessione->prepare("UPDATE Carrello SET quantita_prodotto = ? WHERE id_utente = ? AND id_prodotto = ? AND taglia_prodotto = ?");
                $stmt->bind_param("iiis", $quantity, $user_id, $product_id, $size);
                $success = $stmt->execute();
                $stmt->close();
                
                if ($success) {
                    $response['success'] = true;
                    $response['message'] = 'Quantità aggiornata';
                    $response['action'] = 'updated';
                } else {
                    $response['error'] = 'Errore nell\'aggiornamento';
                }
            } else {
                // Se quantità è 0, rimuovi l'elemento
                $stmt = $connessione->prepare("DELETE FROM Carrello WHERE id_utente = ? AND id_prodotto = ? AND taglia_prodotto = ?");
                $stmt->bind_param("iis", $user_id, $product_id, $size);
                $success = $stmt->execute();
                $stmt->close();
                
                if ($success) {
                    $response['success'] = true;
                    $response['message'] = 'Prodotto rimosso dal carrello';
                    $response['action'] = 'removed';
                } else {
                    $response['error'] = 'Errore nella rimozione';
                }
            }
            break;
            
        default:
            http_response_code(400);
            $response['error'] = 'Azione non valida';
    }
    
    // Aggiungi sempre il conteggio aggiornato del carrello e la preview
    if ($response['success']) {
        $response['cart_count'] = countUserCart($user_id);
        $response['preview_html'] = generateCartPreviewHTML($user_id);
        
        // Se siamo in modalità update, calcola anche i totali considerando promozioni
        if ($action === 'update' || $action === 'remove') {
            $total_stmt = $connessione->prepare("
                SELECT SUM(
                    CASE 
                        WHEN p.id_promozione IS NOT NULL THEN 
                            c.quantita_prodotto * (p.prezzo - (pr.sconto_percentuale * p.prezzo / 100))
                        ELSE 
                            c.quantita_prodotto * p.prezzo
                    END
                ) as total 
                FROM Carrello c 
                JOIN Prodotto p ON c.id_prodotto = p.id 
                LEFT JOIN Promozione pr ON p.id_promozione = pr.id
                WHERE c.id_utente = ?
            ");
            $total_stmt->bind_param("i", $user_id);
            $total_stmt->execute();
            $total_result = $total_stmt->get_result();
            $total_data = $total_result->fetch_assoc();
            $total_stmt->close();
            
            $response['cart_total'] = number_format($total_data['total'] ?? 0, 2);
            
            // Per update, calcola anche il subtotale del singolo prodotto considerando promozioni
            if ($action === 'update' && $quantity > 0) {
                $price_stmt = $connessione->prepare("SELECT p.prezzo, p.id_promozione FROM Prodotto p WHERE p.id = ?");
                $price_stmt->bind_param("i", $product_id);
                $price_stmt->execute();
                $price_result = $price_stmt->get_result();
                $price_data = $price_result->fetch_assoc();
                $price_stmt->close();
                
                $prezzo_finale = $price_data['prezzo'] ?? 0;
                
                // Se c'è una promozione, calcola il prezzo scontato
                if ($price_data['id_promozione']) {
                    $promo_stmt = $connessione->prepare("SELECT sconto_percentuale FROM Promozione WHERE id = ?");
                    $promo_stmt->bind_param("i", $price_data['id_promozione']);
                    $promo_stmt->execute();
                    $promo_result = $promo_stmt->get_result();
                    $promo_data = $promo_result->fetch_assoc();
                    $promo_stmt->close();
                    
                    if ($promo_data) {
                        $sconto = intval($promo_data['sconto_percentuale']);
                        $prezzo_finale = intval($prezzo_finale) - ($sconto * intval($prezzo_finale) / 100);
                    }
                }
                
                $response['item_subtotal'] = number_format($prezzo_finale * $quantity, 2);
            }
        }
    }
    
} catch (Exception $e) {
    $response['error'] = 'Errore del server: ' . $e->getMessage();
}

// Aggiungi sempre il conteggio totale degli elementi nel carrello alla risposta
if ($response['success']) {
    try {
        $count_stmt = $connessione->prepare("SELECT COUNT(*) as cart_count FROM Carrello WHERE id_utente = ?");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        $count_stmt->close();
        
        $response['cart_count'] = intval($count_data['cart_count']);
    } catch (Exception $e) {
        $response['cart_count'] = 0;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
