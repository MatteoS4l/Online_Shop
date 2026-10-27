<?php
session_start();
require_once 'include/dbms.inc.php';
require_once 'include/template2.inc.php';
require_once 'include/auth.inc.php';
require_once 'include/layout-utils.inc.php';
require_once 'include/img.inc.php';

// Richiedi autenticazione per accedere ai preferiti
requireAuth('wishlist.php', 'Accedi per visualizzare i tuoi preferiti');

$user_id = $_SESSION['user_id'];

$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/wishlist.html");

global $connessione;

// Recupera tutti i preferiti dell'utente
$stmt = $connessione->prepare("
    SELECT 
        p.id,
        p.nome_prodotto,
        p.prezzo,
        pref.data_aggiunta,
        ip.url_immagine as immagine_principale
    FROM Preferiti pref
    JOIN Prodotto p ON pref.id_prodotto = p.id
    LEFT JOIN Immagine_Prodotto ip ON p.id = ip.id_prodotto
    WHERE pref.id_utente = ?
    GROUP BY p.id, p.nome_prodotto, p.prezzo, pref.data_aggiunta
    ORDER BY pref.data_aggiunta DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wishlist_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$wishlist_count = count($wishlist_items);

if (!empty($wishlist_items)) {
    foreach ($wishlist_items as $item) {
        $wishlist_elem = new Template("skins/template/dtml/wishlist-item.html");
        
        // ID prodotto
        $wishlist_elem->setContent("PRODUCT_ID", $item['id']);
        
        // Nome prodotto
        $wishlist_elem->setContent("PRODUCT_NAME", $item['nome_prodotto']);
        
        // Immagine
        $image_url = $item['immagine_principale'] ? PRODUCTS_IMAGE_PATH . $item['immagine_principale'] : PRODUCTS_IMAGE_PATH . 'default.jpg';
        $wishlist_elem->setContent("PRODUCT_IMAGE", $image_url);
        
        // Prezzo
        $wishlist_elem->setContent("PRODUCT_PRICE", number_format($item['prezzo'], 2));
        
        // Data aggiunta
        $wishlist_elem->setContent("DATE_ADDED", date('d/m/Y', strtotime($item['data_aggiunta'])));
        
        // Aggiungo l'elemento alla wishlist seguendo il pattern PROGETTO
        $body->setContent("elemento_wishlist", $wishlist_elem->get());
    }
}

// Imposta il contatore nella pagina
$body->setContent("WISHLIST_COUNT", $wishlist_count);

// Conta i preferiti per l'header
$main->setContent("NUMBER_NOTY_FAV", $wishlist_count);

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

// Imposta le informazioni del layout
setLayoutAccountInfo($main);

$main->setContent("BODY", $body->get());
$main->close();
?>
