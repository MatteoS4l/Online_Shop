<?php
require_once 'img.inc.php';

// ==================== SISTEMA PREFERITI ====================

// Funzione per verificare se un prodotto è nei preferiti dell'utente
function isInWishlist($id_prodotto, $id_utente) {
    global $connessione;
    
    if (!$id_utente) return false;
    
    $query = "SELECT id FROM Preferiti WHERE id_utente = ? AND id_prodotto = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("ii", $id_utente, $id_prodotto);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

// Funzione per aggiungere un prodotto ai preferiti
function addToWishlist($id_prodotto, $id_utente) {
    global $connessione;
    
    if (!$id_utente) {
        return ['success' => false, 'message' => 'Devi essere loggato per aggiungere ai preferiti'];
    }
    
    // Verifica se già esiste
    if (isInWishlist($id_prodotto, $id_utente)) {
        return ['success' => false, 'message' => 'Prodotto già nei preferiti'];
    }
    
    $query = "INSERT INTO Preferiti (id_utente, id_prodotto) VALUES (?, ?)";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("ii", $id_utente, $id_prodotto);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Prodotto aggiunto ai preferiti'];
    } else {
        return ['success' => false, 'message' => 'Errore nell\'aggiungere ai preferiti'];
    }
}

// Funzione per rimuovere un prodotto dai preferiti
function removeFromWishlist($id_prodotto, $id_utente) {
    global $connessione;
    
    if (!$id_utente) {
        return ['success' => false, 'message' => 'Devi essere loggato'];
    }
    
    $query = "DELETE FROM Preferiti WHERE id_utente = ? AND id_prodotto = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("ii", $id_utente, $id_prodotto);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Prodotto rimosso dai preferiti'];
    } else {
        return ['success' => false, 'message' => 'Errore nel rimuovere dai preferiti'];
    }
}

// Funzione per ottenere tutti i preferiti di un utente
function getUserWishlist($id_utente) {
    global $connessione;
    
    if (!$id_utente) return [];
    
    $query = "SELECT 
                p.id,
                p.nome_prodotto,
                p.prezzo,
                pref.data_aggiunta,
                (SELECT ip.url_immagine 
                 FROM Immagine_Prodotto ip 
                 WHERE ip.id_prodotto = p.id 
                 LIMIT 1) as prima_immagine
              FROM Preferiti pref
              JOIN Prodotto p ON pref.id_prodotto = p.id
              WHERE pref.id_utente = ?
              ORDER BY pref.data_aggiunta DESC";
    
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $id_utente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $preferiti = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['prima_immagine'] && strpos($row['prima_immagine'], 'skins/') !== 0) {
            $row['prima_immagine'] = PRODUCTS_IMAGE_PATH . $row['prima_immagine'];
        }
        $preferiti[] = $row;
    }
    
    return $preferiti;
}

// Funzione per contare i preferiti di un utente
function countUserWishlist($id_utente) {
    global $connessione;
    
    if (!$id_utente) return 0;
    
    $query = "SELECT COUNT(*) as count FROM Preferiti WHERE id_utente = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $id_utente);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

// Funzione per ottenere gli ID dei prodotti nei preferiti dell'utente
function getUserWishlistIds($id_utente) {
    global $connessione;
    
    if (!$id_utente) return [];
    
    $query = "SELECT id_prodotto FROM Preferiti WHERE id_utente = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $id_utente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $wishlist_ids = [];
    while ($row = $result->fetch_assoc()) {
        $wishlist_ids[] = intval($row['id_prodotto']);
    }
    
    return $wishlist_ids;
}
?>
