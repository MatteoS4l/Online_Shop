<?php
require "include/template2.inc.php";
require "include/dbms.inc.php";
require "include/wishlist.inc.php";

require "include/layout-utils.inc.php";
require_once "include/img.inc.php";

session_start();


$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/products-page.html");

// Recupera categorie per i filtri






// --- Sidebar filtri con template ---
$sidebarTpl = new Template("skins/template/filter_sidebar.html");

// Genera blocco generi (checkbox)
$generi = [
    ['val' => 'Uomo', 'label' => 'Uomo'],
    ['val' => 'Donna', 'label' => 'Donna'],
    ['val' => 'Bambino', 'label' => 'Bambini']
];
$genereAttivi = [];
if (isset($_GET['genere'])) {
    if (is_array($_GET['genere'])) {
        $genereAttivi = $_GET['genere'];
    } else {
        $genereAttivi = explode(',', $_GET['genere']);
    }
}
$generiHtml = '';
foreach ($generi as $g) {
    $checked = in_array($g['val'], $genereAttivi) ? 'checked' : '';
    $generiHtml .= '<label style="display:flex;align-items:center;font-weight:normal;font-size:1em;margin-bottom:8px;gap:7px;color:#0a4968;">'
        . '<input type="checkbox" name="genere[]" value="' . $g['val'] . '" ' . $checked . ' onchange="this.form.submit()">'
        . $g['label'] . '</label>';
}
$generiHiddenCat = '';
if (isset($_GET['cat']) && $_GET['cat'] !== '') {
    $generiHiddenCat = '<input type="hidden" name="cat" value="' . htmlspecialchars($_GET['cat']) . '">';
}
$sidebarTpl->setContent("GENERI_CHECKBOXES", $generiHtml);
$sidebarTpl->setContent("GENERI_HIDDEN_CAT", $generiHiddenCat);

// Brand
$brandResult = $connessione->query("SELECT nome_marca FROM Marca ORDER BY nome_marca ASC");
$brandList = [];
if ($brandResult && $brandResult->num_rows > 0) {
    while ($b = $brandResult->fetch_assoc()) {
        $brandList[] = $b['nome_marca'];
    }
}
$brandAttivo = isset($_GET['brand']) ? $_GET['brand'] : '';
$sidebarTpl->setContent("BRAND_SELECTED", $brandAttivo ? htmlspecialchars($brandAttivo) : 'Seleziona un brand...');
$brandListHtml = '';
foreach ($brandList as $brand) {
    $brandListHtml .= '<li class="brand-item" data-brand="' . htmlspecialchars($brand) . '">' .
        '<a href="#" style="display:block;padding:7px 12px;color:#0a4968;text-decoration:none;" onmouseover="this.style.background=\'#e6f0f5\'" onmouseout="this.style.background=\'\'">' .
        htmlspecialchars($brand) . '</a></li>';
}
$sidebarTpl->setContent("BRAND_LIST", $brandListHtml);

// Categorie
$catResult = $connessione->query("SELECT nome_categoria FROM Categoria ORDER BY nome_categoria ASC");
$categorieListHtml = '';
if ($catResult && $catResult->num_rows > 0) {
    $catAttiva = isset($_GET['cat']) ? $_GET['cat'] : '';
    while ($cat = $catResult->fetch_assoc()) {
        $catNome = $cat['nome_categoria'];
        $catNomeClean = preg_replace('/[^A-Za-z0-9 ]/', '', $catNome);
        $catNomeClean = ucwords(strtolower($catNomeClean));
        $genereQuery = '';
        if (!empty($genereAttivi)) {
            foreach ($genereAttivi as $gq) {
                $genereQuery .= '&genere[]=' . urlencode($gq);
            }
        }
        $catUrl = 'product.php?cat=' . urlencode($catNome) . $genereQuery;
        $active = ($catAttiva === $catNome) ? 'style=\"color:#0a4968;font-weight:bold;font-size:16px;\"' : 'style=\"color:#0a4968;\"';
        $categorieListHtml .= '<li><a href="' . $catUrl . '" ' . $active . '>' . htmlspecialchars($catNomeClean) . '</a></li>';
    }
}
$sidebarTpl->setContent("CATEGORIE_LIST", $categorieListHtml);

$body->setContent("CATEGORIE_FILTRI", $sidebarTpl->get());

// Conteggio preferiti per l'header
$user_id = $_SESSION['user_id'] ?? null;
$wishlist_count = $user_id ? countUserWishlist($user_id) : 0;

// Carica il modal template
$modal = new Template("skins/template/product-modal.html");

// Gestione parametri URL
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
$slice = isset($_GET['slice']) ? (int)$_GET['slice'] : 0;

// Calcola offset per la query
$offset = ($page - 1) * $limit;


// Filtro categoria se presente
$catFilter = '';
if (isset($_GET['cat']) && $_GET['cat'] !== '') {
    $catParam = $connessione->real_escape_string($_GET['cat']);
    $catFilter = " AND c.nome_categoria = '$catParam'";
}

// Filtro genere se presente
$genereFilter = '';
if (!empty($genereAttivi)) {
    // Prepara valori sicuri per SQL
    $genereVals = array_map(function($g) use ($connessione) {
        return "'" . $connessione->real_escape_string($g) . "'";
    }, $genereAttivi);
    $genereFilter = " AND p.genere IN (" . implode(",", $genereVals) . ")";
}

// Filtro brand se presente
$brandFilter = '';
if (!empty($brandAttivo)) {
    $brandParam = $connessione->real_escape_string($brandAttivo);
    $brandFilter = " AND m_brand.nome_marca = '" . $brandParam . "'";
}

// Filtro ricerca per nome prodotto
$searchFilter = '';
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchParam = $connessione->real_escape_string($_GET['search']);
    $searchFilter = " AND p.nome_prodotto LIKE '%$searchParam%'";
}

// Query per contare il totale dei prodotti
$totalQuery = "SELECT COUNT(DISTINCT p.id) as total 
               FROM Prodotto p 
               LEFT JOIN Categoria c ON p.id_categoria = c.id
               LEFT JOIN Marca m_brand ON p.id_marca = m_brand.id
               LEFT JOIN Magazzino m ON p.id = m.id_prodotto 
               WHERE 1=1 $catFilter $genereFilter $brandFilter";

$totalQuery = "SELECT COUNT(DISTINCT p.id) as total 
               FROM Prodotto p 
               LEFT JOIN Categoria c ON p.id_categoria = c.id
               LEFT JOIN Marca m_brand ON p.id_marca = m_brand.id
               LEFT JOIN Magazzino m ON p.id = m.id_prodotto 
               WHERE 1=1 $catFilter $genereFilter $brandFilter $searchFilter";

$totalResult = $connessione->query($totalQuery);
$totalProducts = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $limit);

// Query per recuperare i prodotti
$productsQuery = "SELECT DISTINCT p.id, p.nome_prodotto, p.descrizione, p.prezzo,
                  c.nome_categoria, m_brand.nome_marca,
                  CASE WHEN p.id_promozione IS NOT NULL THEN 
                      p.prezzo * (1 - pr.sconto_percentuale / 100)
                      ELSE p.prezzo END as prezzo_finale,
                  CASE WHEN p.id_promozione IS NOT NULL THEN 1 ELSE 0 END as in_promozione
                  FROM Prodotto p
                  LEFT JOIN Categoria c ON p.id_categoria = c.id
                  LEFT JOIN Marca m_brand ON p.id_marca = m_brand.id
                  LEFT JOIN Magazzino m ON p.id = m.id_prodotto
                  LEFT JOIN Promozione pr ON p.id_promozione = pr.id 
                      AND pr.data_inizio <= CURDATE() 
                      AND pr.data_fine >= CURDATE()
                  WHERE 1=1 $catFilter $genereFilter $brandFilter
                  ORDER BY p.id DESC
                  LIMIT $limit OFFSET $offset";

$productsQuery = "SELECT DISTINCT p.id, p.nome_prodotto, p.descrizione, p.prezzo,
                  c.nome_categoria, m_brand.nome_marca,
                  CASE WHEN p.id_promozione IS NOT NULL AND pr.id IS NOT NULL THEN 
                      p.prezzo * (1 - pr.sconto_percentuale / 100)
                      ELSE p.prezzo END as prezzo_finale,
                  CASE WHEN p.id_promozione IS NOT NULL AND pr.id IS NOT NULL THEN 1 ELSE 0 END as in_promozione
                  FROM Prodotto p
                  LEFT JOIN Categoria c ON p.id_categoria = c.id
                  LEFT JOIN Marca m_brand ON p.id_marca = m_brand.id
                  LEFT JOIN Magazzino m ON p.id = m.id_prodotto
                  LEFT JOIN Promozione pr ON p.id_promozione = pr.id 
                      AND pr.data_inizio <= CURDATE() 
                      AND pr.data_fine >= CURDATE()
                  WHERE 1=1 $catFilter $genereFilter $brandFilter $searchFilter
                  ORDER BY p.id DESC
                  LIMIT $limit OFFSET $offset";

$productsResult = $connessione->query($productsQuery);

// Genera HTML per i prodotti
$productsHtml = "";
if ($productsResult && $productsResult->num_rows > 0) {
    while ($product = $productsResult->fetch_assoc()) {
        // Crea il template PRIMA di usarlo
        $productTemplate = new Template("skins/template/dtml/product-grid-item.html");
        // Recupera le taglie disponibili e la quantità residua dal magazzino
        $taglieQuery = "SELECT taglia, quantita FROM Magazzino WHERE id_prodotto = {$product['id']} AND quantita > 0 ORDER BY taglia ASC";
        $taglieResult = $connessione->query($taglieQuery);
        $taglieOptionsHtml = '';
        if ($taglieResult && $taglieResult->num_rows > 0) {
            while ($t = $taglieResult->fetch_assoc()) {
                $taglia = htmlspecialchars($t['taglia']);
                $quantita = intval($t['quantita']);
                $taglieOptionsHtml .= "<option value='$taglia'>$taglia (Disponibili: $quantita)</option>";
            }
        } else {
            $taglieOptionsHtml = "<option disabled>Nessuna taglia disponibile</option>";
        }

        $productTemplate->setContent("TAGLIE_OPTIONS_HTML", $taglieOptionsHtml);
        // Recupera la prima immagine del prodotto
        $imageQuery = "SELECT url_immagine FROM Immagine_Prodotto WHERE id_prodotto = {$product['id']} ORDER BY id ASC LIMIT 1";
        $imageResult = $connessione->query($imageQuery);
        $imageUrl = $imageResult && $imageResult->num_rows > 0 ? 
                   $imageResult->fetch_assoc()['url_immagine'] : 
                   'default.jpg';
        // Usa il path universale delle immagini prodotto
        if (strpos($imageUrl, PRODUCTS_IMAGE_PATH) !== 0) {
            $imageUrl = PRODUCTS_IMAGE_PATH . ltrim($imageUrl, '/');
        }
        $productTemplate->setContent("PRODUCT_ID", $product['id']);
        $productTemplate->setContent("PRODUCT_NAME", htmlspecialchars($product['nome_prodotto']));
        $productTemplate->setContent("PRODUCT_IMAGE", $imageUrl);
        $productTemplate->setContent("PRODUCT_BRAND", htmlspecialchars($product['nome_marca'] ?? 'N/A'));
        $productTemplate->setContent("PRODUCT_PRICE", "€" . number_format($product['prezzo_finale'], 2, ',', '.'));
        $productTemplate->setContent("PRODUCT_LINK", "shop.php?id=" . $product['id']);
        // Placeholder per filtri (non usati ma necessari)
        $productTemplate->setContent("PRODUCT_FILTER_CLASSES", "");
        // Rating e recensioni
        $recQuery = "SELECT COUNT(*) as num, AVG(valutazione) as media FROM Recensione WHERE id_prodotto = {$product['id']}";
        $recResult = $connessione->query($recQuery);
        $recData = $recResult ? $recResult->fetch_assoc() : ["num" => 0, "media" => 0];
        $numRec = (int)$recData['num'];
        $mediaRec = round((float)$recData['media'], 1);
        // Genera stelline
        $stars = '';
        for ($s = 1; $s <= 5; $s++) {
            if ($mediaRec >= $s) {
                $stars .= '<i class="zmdi zmdi-star"></i>';
            } elseif ($mediaRec >= $s - 0.5) {
                $stars .= '<i class="zmdi zmdi-star-half"></i>';
            } else {
                $stars .= '<i class="zmdi zmdi-star-outline"></i>';
            }
        }
        $productTemplate->setContent("PRODUCT_RATING_HTML", $stars);
        $productTemplate->setContent("REVIEWS_COUNT", $numRec);
        // Stock status
        $productTemplate->setContent("STOCK_STATUS", "Disponibile");
        // Wishlist
        $productTemplate->setContent("IN_WISHLIST", "false");
        // Gestione prezzo e promozioni
        if ($product['in_promozione']) {
            // Mostra prezzo originale barrato e prezzo scontato
            $productTemplate->setContent("OLD_PRICE_HTML", "<span class='stext-105 cl3 txt-strike p-l-5' style='text-decoration: line-through; color: #888;'>€" . number_format($product['prezzo'], 2, ',', '.') . "</span>");
            $productTemplate->setContent("PRODUCT_PRICE", "€" . number_format($product['prezzo_finale'], 2, ',', '.'));
            $productTemplate->setContent("PROMOTION_DISPLAY", "");
            $productTemplate->setContent("PROMOTION_TEXT", "OFFERTA");
            // Percentuale di sconto
            $promoQuery = "SELECT sconto_percentuale FROM Promozione WHERE id = (SELECT id_promozione FROM Prodotto WHERE id = {$product['id']}) LIMIT 1";
            $promoResult = $connessione->query($promoQuery);
            $promoData = $promoResult && $promoResult->num_rows > 0 ? $promoResult->fetch_assoc() : ["sconto_percentuale" => 0];
            $sconto = (float)$promoData['sconto_percentuale'];
            $productTemplate->setContent("PROMOTION_PERCENT", $sconto > 0 ? "-$sconto%" : "");
        } else {
            // Mostra solo il prezzo normale
            $productTemplate->setContent("OLD_PRICE_HTML", "");
            $productTemplate->setContent("PRODUCT_PRICE", "€" . number_format($product['prezzo_finale'], 2, ',', '.'));
            $productTemplate->setContent("PROMOTION_DISPLAY", "display:none;");
            $productTemplate->setContent("PROMOTION_TEXT", "");
            $productTemplate->setContent("PROMOTION_PERCENT", "");
        }
        $productsHtml .= $productTemplate->get();
    }
} else {
    $productsHtml = '<div class="col-12 text-center p-t-50"><p class="stext-102 cl3">Nessun prodotto trovato</p></div>';
}

// Genera paginazione
$paginationHtml = "";
if ($totalPages > 1) {
    // Bottone precedente
    if ($page > 1) {
        $prevPage = $page - 1;
        $paginationHtml .= "<a href='product.php?page=$prevPage&limit=$limit' class='flex-c-m how-pagination1 trans-04 m-all-7'>";
        $paginationHtml .= "<i class='zmdi zmdi-chevron-left'></i></a>";
    }
    
    // Numeri pagina
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $activeClass = ($i == $page) ? 'how-pagination1 trans-04 m-all-7 active-pagination1' : 'how-pagination1 trans-04 m-all-7';
        $paginationHtml .= "<a href='product.php?page=$i&limit=$limit' class='flex-c-m $activeClass'>$i</a>";
    }
    
    // Bottone successivo
    if ($page < $totalPages) {
        $nextPage = $page + 1;
        $paginationHtml .= "<a href='product.php?page=$nextPage&limit=$limit' class='flex-c-m how-pagination1 trans-04 m-all-7'>";
        $paginationHtml .= "<i class='zmdi zmdi-chevron-right'></i></a>";
    }
}

// Imposta i contenuti dinamici della pagina
$body->setContent("PAGE_TITLE", "Negozio");
$body->setContent("BREADCRUMB_TEXT", "Prodotti");
$body->setContent("PRODUCTS_COUNT_TEXT", "Mostrati " . min($limit, $totalProducts - $offset) . " di $totalProducts prodotti");
$body->setContent("CURRENT_PAGE", $page);
$body->setContent("TOTAL_PAGES", $totalPages);
$body->setContent("PRODUCTS_GRID", $productsHtml);
$body->setContent("PAGINATION_LINKS", $paginationHtml);

// Imposta il layout
$main->setContent("NUMBER_NOTY_FAV", $wishlist_count);
$main->setContent("PRODUCT_MODAL", $modal->get());

// Imposta i link del layout in base all'autenticazione
setLayoutAccountInfo($main);

$main->setContent("BODY", $body->get());
$main->close();

?>