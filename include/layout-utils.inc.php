<?php
// Utility per impostare i placeholder del layout in base allo stato di autenticazione

require_once 'wishlist.inc.php';
require_once 'cart-utils.inc.php';

function setLayoutAccountInfo($template) {
    global $connessione;
    
    if (isset($_SESSION['user_id'])) {
        // Utente loggato - link al profilo
        $template->setContent("ACCOUNT_LINK", "account.php");
        $template->setContent("ACCOUNT_TEXT", "Il Mio Account");
        
        // Recupera il numero di elementi nei preferiti
        $user_id = $_SESSION['user_id'];
        
        // Count preferiti usando la funzione esistente
        try {
            $num_preferiti = countUserWishlist($user_id);
            // Debug: forza il conteggio se la funzione non funziona
            if ($num_preferiti === false || $num_preferiti === null) {
                $num_preferiti = 0;
            }
        } catch (Exception $e) {
            // Se c'è un errore, imposta a 0 per ora
            $num_preferiti = 0;
        }
        
        // Count carrello usando la nuova funzione
        $num_carrello = countUserCart($user_id);
        
        $template->setContent("NUMBER_NOTY_FAV", $num_preferiti);
        $template->setContent("NUMBER_NOTY_CART", $num_carrello);
        
        // Genera la preview del carrello
        $cart_preview_html = generateCartPreviewHTML($user_id);
        $template->setContent("CART_PREVIEW_CONTENT", $cart_preview_html);
        
    } else {
        // Utente non loggato - link al login
        $template->setContent("ACCOUNT_LINK", "login.php");
        $template->setContent("ACCOUNT_TEXT", "Accedi");
        $template->setContent("NUMBER_NOTY_FAV", "0");
        $template->setContent("NUMBER_NOTY_CART", "0");
        
        // Carrello vuoto per utenti non loggati
        $cart_preview_html = generateEmptyCartHTML();
        $template->setContent("CART_PREVIEW_CONTENT", $cart_preview_html);
    }
}
?>
