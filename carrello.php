<?php
session_start();
require_once 'include/dbms.inc.php';
require_once 'include/template2.inc.php';
require_once 'include/auth.inc.php';
require_once 'include/layout-utils.inc.php';
require_once 'include/img.inc.php';

// Richiedi autenticazione per accedere al carrello
requireAuth('carrello.php', 'Accedi per visualizzare il tuo carrello');

$user_id = $_SESSION['user_id'];

// Gestisci aggiornamento carrello se richiesto
if ($_POST['action'] ?? '' === 'update_cart') {
    error_log('🔄 Ricevuta richiesta update_cart');
    error_log('📦 POST data: ' . print_r($_POST, true));
    
    $updates = json_decode($_POST['updates'] ?? '[]', true);
    error_log('📊 Updates parsed: ' . print_r($updates, true));
    
    if (!empty($updates)) {
        global $connessione;
        
        foreach ($updates as $update) {
            $product_id = intval($update['product_id']);
            $quantity = intval($update['quantity']);
            $size = $update['size'];
            
            error_log("📝 Aggiornamento: Product ID=$product_id, Quantity=$quantity, Size=$size");
            
            if ($product_id > 0 && $quantity > 0 && !empty($size)) {
                // Aggiorna la quantità nel database
                $stmt = $connessione->prepare("UPDATE Carrello SET quantita_prodotto = ? WHERE id_utente = ? AND id_prodotto = ? AND taglia_prodotto = ?");
                $stmt->bind_param("iiis", $quantity, $user_id, $product_id, $size);
                $success = $stmt->execute();
                $stmt->close();
                
                error_log("✅ Update result: " . ($success ? 'SUCCESS' : 'FAILED'));
            }
        }
    }
    
    // Redirect per evitare resubmit del form
    header("Location: carrello.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/shopping-cart.html");

global $connessione;

// Recupera gli elementi nel carrello dell'utente con immagini
$stmt = $connessione->prepare("
    SELECT c.id, c.quantita_prodotto, c.taglia_prodotto, 
           p.nome_prodotto, p.prezzo, p.id as product_id, p.id_promozione,
           (SELECT url_immagine FROM Immagine_Prodotto WHERE id_prodotto = p.id LIMIT 1) as immagine_principale
    FROM Carrello c 
    JOIN Prodotto p ON c.id_prodotto = p.id 
    WHERE c.id_utente = ?
    ORDER BY c.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_price = 0;

if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        $cart_elem = new Template("skins/template/dtml/cart-item.html");
        
        // ID, quantità e taglia
        $cart_elem->setContent("ID_PRODOTTO", $item['product_id']);
        $cart_elem->setContent("QUANTITA", $item['quantita_prodotto']);
        $cart_elem->setContent("TAGLIA_PRODOTTO", $item['taglia_prodotto']);
        
        // Quantità massima impostabile dal magazzino
        $stmt = $connessione->prepare("SELECT quantita FROM Magazzino WHERE id_prodotto = ? AND taglia = ?");
        $stmt->bind_param("is", $item['product_id'], $item['taglia_prodotto']);
        $stmt->execute();
        $result = $stmt->get_result();
        $magazzino = $result->fetch_assoc();
        $stmt->close();
        $cart_elem->setContent("Q_MAX", $magazzino['quantita'] ?? 0);
        
        // Immagine
        $image_url = $item['immagine_principale'] ? PRODUCTS_IMAGE_PATH . $item['immagine_principale'] : PRODUCTS_IMAGE_PATH . 'placeholder.jpg';
        $cart_elem->setContent("URL_IMMAGINE", $image_url);
        
        // Nome prodotto
        $cart_elem->setContent("NOME_PRODOTTO", $item['nome_prodotto']);
        
        // Prezzo con eventuale promozione
        if ($item['id_promozione']) {
            // Calcola prezzo scontato
            $stmt = $connessione->prepare("SELECT sconto_percentuale FROM Promozione WHERE id = ?");
            $stmt->bind_param("i", $item['id_promozione']);
            $stmt->execute();
            $result = $stmt->get_result();
            $promo = $result->fetch_assoc();
            $stmt->close();
            
            $sconto = intval($promo['sconto_percentuale']);
            $prezzo_scontato = intval($item['prezzo']) - ($sconto * intval($item['prezzo']) / 100);
            $cart_elem->setContent("PREZZO_ORIGINALE", $item['prezzo']);
            $cart_elem->setContent("PREZZO_SINGOLO", $prezzo_scontato);
            $subtotal = floatval($prezzo_scontato) * floatval($item['quantita_prodotto']);
        } else {
            $cart_elem->setContent("PREZZO_SINGOLO", $item['prezzo']);
            $subtotal = floatval($item['prezzo']) * floatval($item['quantita_prodotto']);
        }
        
        $cart_elem->setContent("PREZZO_SINGOLO_TOT", number_format($subtotal, 2));
        $total_price += $subtotal;
        
        // Aggiungo l'elemento al carrello seguendo il pattern PROGETTO
        $body->setContent("elemento_carrello", $cart_elem->get());
    }
}

$body->setContent("CART_TOTAL", number_format($total_price, 2));
$body->setContent("CART_SUBTOTAL", number_format($total_price, 2));

// Conta gli elementi nel carrello per l'header
$stmt = $connessione->prepare("SELECT COUNT(*) as count FROM Carrello WHERE id_utente = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cart_count = $row['count'];
    $stmt->close();
} else {
    $cart_count = 0;
}
$main->setContent("NUMBER_NOTY_CART", $cart_count);

// Conta i preferiti per l'header
$stmt = $connessione->prepare("SELECT COUNT(*) as count FROM Preferiti WHERE id_utente = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $wishlist_count = $row['count'];
    $stmt->close();
} else {
    $wishlist_count = 0;
}
$main->setContent("NUMBER_NOTY_FAV", $wishlist_count);

// Imposta le informazioni del layout
setLayoutAccountInfo($main);

$main->setContent("BODY", $body->get());
$main->close();
?>
