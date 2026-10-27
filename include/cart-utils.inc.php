<?php
// Funzioni utility per la gestione del carrello

/**
 * Genera l'HTML della preview del carrello per l'header
 */
function generateCartPreviewHTML($user_id = null) {
    global $connessione;
    
    if (!$user_id) {
        // Carrello vuoto per utenti non loggati
        return generateEmptyCartHTML();
    }
    
    // Recupera gli elementi del carrello con informazioni prodotto
    $stmt = $connessione->prepare("
        SELECT c.id, c.quantita_prodotto, c.taglia_prodotto, 
               p.nome_prodotto, p.prezzo, p.id as product_id,
               ip.url_immagine as immagine_principale
        FROM Carrello c 
        JOIN Prodotto p ON c.id_prodotto = p.id 
        LEFT JOIN Immagine_Prodotto ip ON p.id = ip.id_prodotto
        WHERE c.id_utente = ?
        GROUP BY c.id, c.quantita_prodotto, c.taglia_prodotto, p.nome_prodotto, p.prezzo, p.id
        ORDER BY c.id DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($cart_items)) {
        return generateEmptyCartHTML();
    }
    
    $html = '';
    $total = 0;
    
    foreach ($cart_items as $item) {
        $subtotal = $item['prezzo'] * $item['quantita_prodotto'];
        $total += $subtotal;
        
        // Determina l'immagine da usare
        $image_src = $item['immagine_principale'] 
            ? "skins/template/images/" . $item['immagine_principale']
            : "skins/template/images/item-cart-01.jpg"; // Immagine di default
        
        $html .= '
        <li class="header-cart-item flex-w flex-t m-b-12" data-product-id="' . $item['product_id'] . '" data-size="' . htmlspecialchars($item['taglia_prodotto']) . '">
            <div class="header-cart-item-img">
                <img src="' . $image_src . '" alt="IMG">
            </div>

            <div class="header-cart-item-txt p-t-8">
                <a href="product-detail.php?id=' . $item['product_id'] . '" class="header-cart-item-name m-b-18 hov-cl1 trans-04">
                    ' . htmlspecialchars($item['nome_prodotto']) . '
                </a>

                <span class="header-cart-item-info">
                    ' . $item['quantita_prodotto'] . ' x €' . number_format($item['prezzo'], 2) . '
                </span>
            </div>
        </li>';
    }
    
    // Aggiungi il totale e i bottoni
    $html .= '
        </ul>
        
        <div class="w-full">
            <div class="header-cart-total w-full p-tb-40">
                Totale: €' . number_format($total, 2) . '
            </div>

            <div class="header-cart-buttons flex-w w-full">
                <a href="carrello.php" class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3 p-lr-15 trans-04 m-r-8 m-b-10">
                    Visualizza Carrello
                </a>

                <a href="checkout.php" class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3 p-lr-15 trans-04 m-b-10">
                    Checkout
                </a>
            </div>
        </div>';
    
    return $html;
}

/**
 * Genera HTML per carrello vuoto
 */
function generateEmptyCartHTML() {
    return '
        <li class="header-cart-item-empty">
            <div class="text-center p-tb-40">
                <i class="zmdi zmdi-shopping-cart-plus" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                <p class="stext-107 cl6">Il tuo carrello è vuoto</p>
                <a href="product.php" class="stext-101 cl1 hov-cl2 trans-04">
                    Inizia a fare shopping
                </a>
            </div>
        </li>
        </ul>
        
        <div class="w-full">
            <div class="header-cart-total w-full p-tb-40">
                Totale: €0.00
            </div>

            <div class="header-cart-buttons flex-w w-full">
                <a href="product.php" class="flex-c-m stext-101 cl0 size-107 bg3 bor2 hov-btn3 p-lr-15 trans-04 m-b-10">
                    Inizia Shopping
                </a>
            </div>
        </div>';
}

/**
 * Prepara i dati del carrello per il template
 */
function prepareCartData($cart_items) {
    $total = 0;
    $template_items = [];
    
    foreach ($cart_items as $item) {
        $subtotal = $item['prezzo'] * $item['quantita_prodotto'];
        $total += $subtotal;
        
        // Determina l'immagine da usare
        $image_src = $item['immagine_principale'] 
            ? "skins/template/images/" . $item['immagine_principale']
            : "skins/template/images/item-cart-01.jpg";
        
        $template_items[] = [
            'PRODUCT_ID' => $item['product_id'],
            'PRODUCT_NAME' => htmlspecialchars($item['nome_prodotto']),
            'SIZE' => htmlspecialchars($item['taglia_prodotto']),
            'PRICE' => number_format($item['prezzo'], 2),
            'QUANTITY' => $item['quantita_prodotto'],
            'SUBTOTAL' => number_format($subtotal, 2),
            'IMAGE_SRC' => $image_src
        ];
    }
    
    return [
        'items' => $template_items,
        'total' => $total,
        'count' => count($cart_items)
    ];
}

/**
 * Conta il numero di elementi nel carrello dell'utente
 */
function countUserCart($user_id) {
    global $connessione;
    
    if (!$user_id) {
        return 0;
    }
    
    $stmt = $connessione->prepare("SELECT COUNT(*) as count FROM Carrello WHERE id_utente = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'];
}
?>
