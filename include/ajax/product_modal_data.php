<?php
session_start();
require_once '../dbms.inc.php';

header('Content-Type: application/json');

$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'ID prodotto non valido']);
    exit;
}

try {
    global $connessione;
    
    // Query principale per il prodotto (senza p.prima_immagine)
    $stmt = $connessione->prepare("
        SELECT 
            p.id,
            p.nome_prodotto as name,
            p.prezzo,
            p.descrizione as description,
            p.id_promozione,
            pr.sconto_percentuale
        FROM Prodotto p
        LEFT JOIN Promozione pr ON p.id_promozione = pr.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Prodotto non trovato']);
        exit;
    }
    $product = $result->fetch_assoc();
    $stmt->close();

    // Recupera la prima immagine del prodotto
    require_once '../img.inc.php';
    $img_stmt = $connessione->prepare("SELECT url_immagine FROM Immagine_Prodotto WHERE id_prodotto = ? ORDER BY id ASC LIMIT 1");
    $img_stmt->bind_param("i", $product_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    $main_image = $img_result && $img_result->num_rows > 0 ? $img_result->fetch_assoc()['url_immagine'] : 'default.jpg';
    // Applica il path universale
    if (strpos($main_image, PRODUCTS_IMAGE_PATH) !== 0) {
        $main_image = PRODUCTS_IMAGE_PATH . ltrim($main_image, '/');
    }
    $img_stmt->close();
    
    // Calcola prezzo finale (con sconto se presente)
    $prezzo_finale = floatval($product['prezzo']);
    if ($product['id_promozione'] && $product['sconto_percentuale']) {
        $sconto = intval($product['sconto_percentuale']);
        $prezzo_finale = $prezzo_finale - ($sconto * $prezzo_finale / 100);
        $product['on_sale'] = true;
        $product['original_price'] = $product['prezzo'];
    }
    
    $product['price'] = number_format($prezzo_finale, 2);
    
    // Recupera immagini aggiuntive
    $images_stmt = $connessione->prepare("
        SELECT url_immagine 
        FROM Immagine_Prodotto 
        WHERE id_prodotto = ? 
        ORDER BY id ASC
    ");
    $images_stmt->bind_param("i", $product_id);
    $images_stmt->execute();
    $images_result = $images_stmt->get_result();
    
    $images = [];
    // Aggiungi la main_image come prima immagine
    if ($main_image) {
        $images[] = $main_image;
    }
    while ($img_row = $images_result->fetch_assoc()) {
        // Applica il path universale
        $img_url = $img_row['url_immagine'];
        if (strpos($img_url, PRODUCTS_IMAGE_PATH) !== 0) {
            $img_url = PRODUCTS_IMAGE_PATH . ltrim($img_url, '/');
        }
        // Evita duplicati se main_image è già inclusa
        if ($img_url !== $main_image) {
            $images[] = $img_url;
        }
    }
    $images_stmt->close();
    
    $product['images'] = $images;
    
    // Taglie disponibili (esempio statico, sostituisci con logica database)
    $product['sizes'] = ['S', 'M', 'L', 'XL'];
    // Stock per taglia (esempio statico, sostituisci con logica database)
    $product['stock_per_size'] = [
        'S' => 3,
        'M' => 5,
        'L' => 2,
        'XL' => 0
    ];
    
    // Rimuovi campi non necessari
    unset($product['prima_immagine']);
    unset($product['prezzo']);
    unset($product['id_promozione']);
    unset($product['sconto_percentuale']);
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Errore interno: ' . $e->getMessage()
    ]);
}
?>
