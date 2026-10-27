<?php
require_once 'img.inc.php';

// Funzione per ottenere i prodotti più venduti
function getBestSellingProducts($limite = 4) {
    global $connessione;
    
    $query = "SELECT 
                p.id,
                p.nome_prodotto,
                p.prezzo,
                COUNT(oo.id_prodotto) as vendite_totali,
                (SELECT ip.url_immagine 
                 FROM Immagine_Prodotto ip 
                 WHERE ip.id_prodotto = p.id 
                 LIMIT 1) as prima_immagine
              FROM Prodotto p 
              LEFT JOIN Oggetto_Ordine oo ON p.id = oo.id_prodotto 
              WHERE p.id IN (SELECT DISTINCT id_prodotto FROM Immagine_Prodotto)
              GROUP BY p.id, p.nome_prodotto, p.prezzo 
              HAVING vendite_totali > 0 
              ORDER BY vendite_totali DESC 
              LIMIT ?";
    
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $prodotti = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['prima_immagine'] && strpos($row['prima_immagine'], 'skins/') !== 0) {
            $row['prima_immagine'] = PRODUCTS_IMAGE_PATH . $row['prima_immagine'];
        }
        $prodotti[] = $row;
    }
    
    return $prodotti;
}

// Funzione per generare HTML dei prodotti best sellers usando template
function generateBestSellersHTML() {
    require_once 'template2.inc.php';
    
    $prodotti = getBestSellingProducts(4);
    $html = '';
    
    foreach ($prodotti as $prodotto) {
        $item = new Template("skins/template/best-seller-item.html");
        $item->setContent("PRODUCT_ID", $prodotto['id']);
        $item->setContent("PRODUCT_NAME", $prodotto['nome_prodotto']);
        $item->setContent("PRODUCT_IMAGE", $prodotto['prima_immagine']);
        $item->setContent("PRODUCT_PRICE", number_format($prodotto['prezzo'], 2));
        $html .= $item->get();
    }
    
    return $html;
}

// Funzione per ottenere prodotti in saldo (per ora prodotti con prezzo < 50€)
function getSaleProducts($limite = 4) {
    global $connessione;
    
    $query = "SELECT 
                p.id,
                p.nome_prodotto,
                p.prezzo,
                (SELECT ip.url_immagine 
                 FROM Immagine_Prodotto ip 
                 WHERE ip.id_prodotto = p.id 
                 LIMIT 1) as prima_immagine
              FROM Prodotto p 
              WHERE p.prezzo < 50 
              AND p.id IN (SELECT DISTINCT id_prodotto FROM Immagine_Prodotto)
              ORDER BY p.prezzo ASC
              LIMIT ?";
    
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $prodotti = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['prima_immagine'] && strpos($row['prima_immagine'], 'skins/') !== 0) {
            $row['prima_immagine'] = PRODUCTS_IMAGE_PATH . $row['prima_immagine'];
        }
        $prodotti[] = $row;
    }
    
    return $prodotti;
}

// Funzione per generare HTML dei prodotti in saldo
function generateSaleProductsHTML() {
    require_once 'template2.inc.php';
    
    $prodotti = getSaleProducts(4);
    $html = '';
    
    foreach ($prodotti as $prodotto) {
        $item = new Template("skins/template/best-seller-item.html");
        $item->setContent("PRODUCT_ID", $prodotto['id']);
        $item->setContent("PRODUCT_NAME", $prodotto['nome_prodotto']);
        $item->setContent("PRODUCT_IMAGE", $prodotto['prima_immagine']);
        $item->setContent("PRODUCT_PRICE", number_format($prodotto['prezzo'], 2));
        $html .= $item->get();
    }
    
    return $html;
}

// Funzione per ottenere prodotti top rated basati sulle recensioni
function getTopRatedProducts($limite = 4) {
    global $connessione;
    
    // Prima verifichiamo se esiste una tabella recensioni
    $query = "SELECT 
                p.id,
                p.nome_prodotto,
                p.prezzo,
                AVG(r.valutazione) as media_recensioni,
                COUNT(r.id) as numero_recensioni,
                (SELECT ip.url_immagine 
                 FROM Immagine_Prodotto ip 
                 WHERE ip.id_prodotto = p.id 
                 LIMIT 1) as prima_immagine
              FROM Prodotto p 
              LEFT JOIN Recensione r ON p.id = r.id_prodotto 
              WHERE p.id IN (SELECT DISTINCT id_prodotto FROM Immagine_Prodotto)
              GROUP BY p.id, p.nome_prodotto, p.prezzo 
              HAVING numero_recensioni > 0 
              ORDER BY media_recensioni DESC, numero_recensioni DESC 
              LIMIT ?";
    
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $prodotti = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['prima_immagine'] && strpos($row['prima_immagine'], 'skins/') !== 0) {
            $row['prima_immagine'] = PRODUCTS_IMAGE_PATH . $row['prima_immagine'];
        }
        $row['media_recensioni'] = round($row['media_recensioni'], 1);
        $prodotti[] = $row;
    }
    
    // Se non ci sono recensioni, prendiamo i prodotti più costosi come fallback
    if (empty($prodotti)) {
        $query = "SELECT 
                    p.id,
                    p.nome_prodotto,
                    p.prezzo,
                    5.0 as media_recensioni,
                    0 as numero_recensioni,
                    (SELECT ip.url_immagine 
                     FROM Immagine_Prodotto ip 
                     WHERE ip.id_prodotto = p.id 
                     LIMIT 1) as prima_immagine
                  FROM Prodotto p 
                  WHERE p.prezzo > 80 
                  AND p.id IN (SELECT DISTINCT id_prodotto FROM Immagine_Prodotto)
                  ORDER BY p.prezzo DESC
                  LIMIT ?";
        
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("i", $limite);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if ($row['prima_immagine'] && strpos($row['prima_immagine'], 'skins/') !== 0) {
                $row['prima_immagine'] = PRODUCTS_IMAGE_PATH . $row['prima_immagine'];
            }
            $prodotti[] = $row;
        }
    }
    
    return $prodotti;
}

// Funzione per generare stelle HTML basate sul rating
function generateStarsHTML($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    
    $html = '<div class="rating-stars p-t-5">';
    
    // Stelle piene
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fa fa-star" style="color: #ffc107; margin-right: 2px;"></i>';
    }
    
    // Stella a metà (se necessaria)
    if ($halfStar) {
        $html .= '<i class="fa fa-star-half-o" style="color: #ffc107; margin-right: 2px;"></i>';
    }
    
    // Stelle vuote
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="fa fa-star-o" style="color: #ddd; margin-right: 2px;"></i>';
    }
    
    $html .= '<span class="stext-107 cl6 m-l-5">(' . $rating . ')</span>';
    $html .= '</div>';
    
    return $html;
}

// Funzione per generare HTML dei prodotti top rated
function generateTopRatedProductsHTML() {
    require_once 'template2.inc.php';
    
    $prodotti = getTopRatedProducts(4);
    $html = '';
    
    foreach ($prodotti as $prodotto) {
        $item = new Template("skins/template/top-rated-item.html");
        $item->setContent("PRODUCT_ID", $prodotto['id']);
        $item->setContent("PRODUCT_NAME", $prodotto['nome_prodotto']);
        $item->setContent("PRODUCT_IMAGE", $prodotto['prima_immagine']);
        $item->setContent("PRODUCT_PRICE", number_format($prodotto['prezzo'], 2));
        $item->setContent("RATING_STARS", generateStarsHTML($prodotto['media_recensioni']));
        $html .= $item->get();
    }
    
    return $html;
}
