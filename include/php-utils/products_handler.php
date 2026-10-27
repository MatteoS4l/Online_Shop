<?php
// Determina il path base del progetto
$base_path = dirname(dirname(__DIR__));
error_log("Base path determined: " . $base_path);

require_once $base_path . '/include/template2.inc.php';
require_once $base_path . '/include/dbms.inc.php';
require_once $base_path . '/include/wishlist.inc.php';
require_once $base_path . '/include/img.inc.php';

session_start();

header('Content-Type: application/json');

if ($_POST['action'] !== 'get_products') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Azione non valida']);
    exit;
}

try {
    error_log("=== DEBUG: Starting products_handler.php ===");
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - Starting products_handler.php\n", FILE_APPEND);
    
    $page = intval($_POST['page'] ?? 1);
    $per_page = intval($_POST['per_page'] ?? 12);
    $view_mode = $_POST['view_mode'] ?? 'grid';
    $filters = $_POST['filters'] ?? [];
    
    error_log("=== DEBUG: Parameters received ===");
    file_put_contents(__DIR__ . '/debug.log', "Parameters: page=$page, per_page=$per_page, view_mode=$view_mode\n", FILE_APPEND);
    
    $offset = ($page - 1) * $per_page;
    
    // Build the query
    error_log("=== DEBUG: Building queries ===");
    file_put_contents(__DIR__ . '/debug.log', "Building queries...\n", FILE_APPEND);
    $query = buildProductsQuery($filters);
    $countQuery = buildProductsCountQuery($filters);
    
    error_log("=== DEBUG: Count query built ===");
    file_put_contents(__DIR__ . '/debug.log', "Count query: " . $countQuery . "\n", FILE_APPEND);
    
    // Get total count
    $totalResult = $connessione->query($countQuery);
    if (!$totalResult) {
        throw new Exception("Errore nella count query: " . $connessione->error);
    }
    
    $totalCount = $totalResult->fetch_row()[0];
    $totalPages = ceil($totalCount / $per_page);
    
    error_log("Total count: $totalCount, Total pages: $totalPages");
    
    // Get products with pagination
    $query .= " LIMIT $offset, $per_page";
    
    error_log("Main query: " . $query);
    
    $result = $connessione->query($query);
    
    if (!$result) {
        throw new Exception("Errore nella query: " . $connessione->error);
    }
    
    $products = [];
    $user_id = $_SESSION['user_id'] ?? null;
    
    error_log("Processing " . $result->num_rows . " products");
    
    while ($row = $result->fetch_assoc()) {
        error_log("Processing product ID: " . $row['id']);
        $product = formatProductForDisplay($row, $user_id, $view_mode);
        $products[] = $product;
    }
    
    $hasMore = ($page * $per_page) < $totalCount;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'products' => $products,
            'has_more' => $hasMore,
            'total_count' => $totalCount,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'pagination_html' => generatePaginationHTML($page, $totalPages)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("=== ERROR in products_handler.php ===");
    error_log("Error message: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/debug.log', "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server',
        'debug' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

function buildProductsQuery($filters) {
    global $connessione;
    
    $query = "
        SELECT 
            p.id,
            p.nome_prodotto as nome,
            p.prezzo,
            p.descrizione,
            COALESCE(SUM(m.quantita), 0) as quantita_magazzino,
            NOW() as data_inserimento,
            c.nome_categoria as categoria_nome,
            p.genere,
            ma.nome_marca as marca_nome,
            GROUP_CONCAT(DISTINCT ip.url_immagine ORDER BY ip.id SEPARATOR ',') as immagini,
            0 as rating_medio,
            0 as numero_recensioni,
            pr.sconto_percentuale as percentuale_sconto,
            pr.descrizione as promozione_descrizione,
            pr.data_inizio as promozione_inizio,
            pr.data_fine as promozione_fine,
            CASE 
                WHEN pr.sconto_percentuale IS NOT NULL 
                THEN p.prezzo * (1 - pr.sconto_percentuale / 100)
                ELSE p.prezzo
            END as prezzo_finale
        FROM Prodotto p
        LEFT JOIN Categoria c ON p.id_categoria = c.id
        LEFT JOIN Marca ma ON p.id_marca = ma.id
        LEFT JOIN Immagine_Prodotto ip ON p.id = ip.id_prodotto
        LEFT JOIN Magazzino m ON p.id = m.id_prodotto
        LEFT JOIN Promozione pr ON p.id_promozione = pr.id 
            AND CURDATE() BETWEEN pr.data_inizio AND pr.data_fine
        WHERE 1=1
    ";
    
    // Apply filters
    if (!empty($filters['category']) && $filters['category'] !== 'all') {
        $category = $connessione->real_escape_string($filters['category']);
        switch ($category) {
            case 'men':
                $query .= " AND p.genere = 'uomo'";
                break;
            case 'women':
                $query .= " AND p.genere = 'donna'";
                break;
            case 'bags':
                $query .= " AND c.nome_categoria LIKE '%borsa%'";
                break;
            case 'shoes':
                $query .= " AND c.nome_categoria LIKE '%scarpe%'";
                break;
            case 'watches':
                $query .= " AND c.nome_categoria LIKE '%orologi%'";
                break;
        }
    }
    
    // Search filter
    if (!empty($filters['search'])) {
        $search = $connessione->real_escape_string($filters['search']);
        $query .= " AND (p.nome_prodotto LIKE '%$search%' OR p.descrizione LIKE '%$search%' OR c.nome_categoria LIKE '%$search%' OR ma.nome_marca LIKE '%$search%')";
    }
    
    // Price filter
    if (!empty($filters['price']) && $filters['price'] !== 'all') {
        $price = $filters['price'];
        if ($price === '200+') {
            $query .= " AND p.prezzo >= 200";
        } else {
            $priceRange = explode('-', $price);
            if (count($priceRange) === 2) {
                $minPrice = floatval($priceRange[0]);
                $maxPrice = floatval($priceRange[1]);
                $query .= " AND p.prezzo BETWEEN $minPrice AND $maxPrice";
            }
        }
    }
    
    // Tag filter (assuming we have a tag system)
    if (!empty($filters['tag'])) {
        $tag = $connessione->real_escape_string($filters['tag']);
        $query .= " AND (p.descrizione LIKE '%$tag%' OR c.nome_categoria LIKE '%$tag%')";
    }
    
    $query .= " GROUP BY p.id";
    
    // Apply sorting
    $sort = $filters['sort'] ?? 'default';
    switch ($sort) {
        case 'popularity':
            $query .= " ORDER BY numero_recensioni DESC, rating_medio DESC";
            break;
        case 'rating':
            $query .= " ORDER BY rating_medio DESC, numero_recensioni DESC";
            break;
        case 'newest':
            $query .= " ORDER BY p.id DESC";
            break;
        case 'price_asc':
            $query .= " ORDER BY prezzo_finale ASC";
            break;
        case 'price_desc':
            $query .= " ORDER BY prezzo_finale DESC";
            break;
        case 'default':
        default:
            $query .= " ORDER BY p.id DESC";
            break;
    }
    
    return $query;
}

function buildProductsCountQuery($filters) {
    global $connessione;
    
    $query = "
        SELECT COUNT(DISTINCT p.id)
        FROM Prodotto p
        LEFT JOIN Categoria c ON p.id_categoria = c.id
        LEFT JOIN Marca ma ON p.id_marca = ma.id
        WHERE 1=1
    ";
    
    // Apply the same filters as main query
    if (!empty($filters['category']) && $filters['category'] !== 'all') {
        $category = $connessione->real_escape_string($filters['category']);
        switch ($category) {
            case 'men':
                $query .= " AND p.genere = 'uomo'";
                break;
            case 'women':
                $query .= " AND p.genere = 'donna'";
                break;
            case 'bags':
                $query .= " AND c.nome_categoria LIKE '%borsa%'";
                break;
            case 'shoes':
                $query .= " AND c.nome_categoria LIKE '%scarpe%'";
                break;
            case 'watches':
                $query .= " AND c.nome_categoria LIKE '%orologi%'";
                break;
        }
    }
    
    if (!empty($filters['search'])) {
        $search = $connessione->real_escape_string($filters['search']);
        $query .= " AND (p.nome_prodotto LIKE '%$search%' OR p.descrizione LIKE '%$search%' OR c.nome_categoria LIKE '%$search%' OR ma.nome_marca LIKE '%$search%')";
    }
    
    if (!empty($filters['price']) && $filters['price'] !== 'all') {
        $price = $filters['price'];
        if ($price === '200+') {
            $query .= " AND p.prezzo >= 200";
        } else {
            $priceRange = explode('-', $price);
            if (count($priceRange) === 2) {
                $minPrice = floatval($priceRange[0]);
                $maxPrice = floatval($priceRange[1]);
                $query .= " AND p.prezzo BETWEEN $minPrice AND $maxPrice";
            }
        }
    }
    
    if (!empty($filters['tag'])) {
        $tag = $connessione->real_escape_string($filters['tag']);
        $query .= " AND (p.descrizione LIKE '%$tag%' OR c.nome_categoria LIKE '%$tag%')";
    }
    
    return $query;
}

function formatProductForDisplay($row, $user_id, $view_mode) {
    error_log("formatProductForDisplay: Starting for product " . $row['id']);
    
    // Format price
    $prezzo_originale = floatval($row['prezzo']);
    $prezzo_finale = floatval($row['prezzo_finale']);
    $old_price_html = '';
    $promotion_text = '';
    $promotion_display = 'display: none;';
    $price_class = '';
    
    // Check for promotion
    if (!empty($row['percentuale_sconto'])) {
        $sconto = floatval($row['percentuale_sconto']);
        $promotion_text = "-{$sconto}%";
        $promotion_display = 'display: block;';
        $old_price_html = '<span class="stext-106 cl6 txt-line-through p-l-5">€' . number_format($prezzo_originale, 2, ',', '.') . '</span>';
        $price_class = 'cl10'; // Different color for discounted price
    }
    
    $prezzo_display = '€' . number_format($prezzo_finale, 2, ',', '.');
    
    // Get main image
    $immagini = explode(',', $row['immagini']);
    if (!empty($immagini[0]) && trim($immagini[0]) !== '') {
        // Se l'immagine ha già un path completo, usala così com'è
        if (strpos($immagini[0], 'skins/') === 0 || strpos($immagini[0], '/') === 0) {
            $main_image = trim($immagini[0]);
        } else {
            // Altrimenti, aggiungi il path base delle immagini
            $image_name = trim($immagini[0]);
            // Se l'immagine non ha estensione, prova ad aggiungere .jpg
            if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $image_name)) {
                $image_name .= '.jpg';
            }
            $main_image = PRODUCTS_IMAGE_PATH . $image_name;
        }
    } else {
        // Usa un'immagine placeholder esistente
        $main_image = PRODUCTS_IMAGE_PATH . 'product-01.jpg';
    }
    
    // Format rating
    $rating_medio = floatval($row['rating_medio']);
    $rating_html = generateStarRating($rating_medio);
    
    // Check if in wishlist
    $in_wishlist = false;
    if ($user_id) {
        try {
            $in_wishlist = isInWishlist($row['id'], $user_id);
        } catch (Exception $e) {
            error_log("Error checking wishlist: " . $e->getMessage());
            $in_wishlist = false;
        }
    }
    
    // Determine filter classes
    $filter_classes = [];
    if ($row['genere'] === 'donna') $filter_classes[] = 'women';
    if ($row['genere'] === 'uomo') $filter_classes[] = 'men';
    if (stripos($row['categoria_nome'], 'borsa') !== false) $filter_classes[] = 'bags';
    if (stripos($row['categoria_nome'], 'scarpe') !== false) $filter_classes[] = 'shoes';
    if (stripos($row['categoria_nome'], 'orologi') !== false) $filter_classes[] = 'watches';
    
    // Stock status
    $stock_status = $row['quantita_magazzino'] > 10 ? 'Disponibile' : 
                   ($row['quantita_magazzino'] > 0 ? 'Solo ' . $row['quantita_magazzino'] . ' rimasti' : 'Esaurito');
    
    // Choose template based on view mode
    $base_path = dirname(dirname(__DIR__));
    $template_file = $base_path . '/skins/template/dtml/product-grid-item.html';
    
    error_log("Using template file: " . $template_file);
    
    if (!file_exists($template_file)) {
        throw new Exception("Template file not found: " . $template_file);
    }
    
    $template = new Template($template_file);
    $template->setContent("PRODUCT_ID", $row['id']);
    $template->setContent("PRODUCT_NAME", htmlspecialchars($row['nome']));
    $template->setContent("PRODUCT_BRAND", htmlspecialchars($row['marca_nome']));
    $template->setContent("PRODUCT_IMAGE", $main_image);
    $template->setContent("PRODUCT_PRICE", $prezzo_display);
    $template->setContent("PRODUCT_DESCRIPTION", htmlspecialchars(substr($row['descrizione'], 0, 150) . '...'));
    $template->setContent("PRODUCT_RATING_HTML", $rating_html);
    $template->setContent("REVIEWS_COUNT", $row['numero_recensioni']);
    $template->setContent("OLD_PRICE_HTML", $old_price_html);
    $template->setContent("PROMOTION_TEXT", $promotion_text);
    $template->setContent("PROMOTION_DISPLAY", $promotion_display);
    $template->setContent("PRICE_CLASS", $price_class);
    $template->setContent("STOCK_STATUS", $stock_status);
    $template->setContent("PRODUCT_FILTER_CLASSES", implode(' ', $filter_classes));
    $template->setContent("IN_WISHLIST", $in_wishlist ? 'true' : 'false');
    
    return [
        'id' => $row['id'],
        'html' => $template->get()
    ];
}

function generateStarRating($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    $html = '';
    
    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="zmdi zmdi-star" style="color: #717fe0;"></i>';
    }
    
    // Half star
    if ($halfStar) {
        $html .= '<i class="zmdi zmdi-star-half" style="color: #717fe0;"></i>';
    }
    
    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="zmdi zmdi-star-outline" style="color: #717fe0;"></i>';
    }
    
    return $html;
}

function generatePaginationHTML($currentPage, $totalPages) {
    if ($totalPages <= 1) return '';
    
    $html = '';
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<a href="#" class="flex-c-m how-pagination1 trans-04 m-all-7" data-page="' . ($currentPage - 1) . '"><i class="zmdi zmdi-chevron-left"></i></a>';
    }
    
    // Page numbers
    for ($i = $start; $i <= $end; $i++) {
        $activeClass = $i === $currentPage ? 'active-pagination1' : '';
        $html .= '<a href="#" class="flex-c-m how-pagination1 trans-04 m-all-7 ' . $activeClass . '" data-page="' . $i . '">' . $i . '</a>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<a href="#" class="flex-c-m how-pagination1 trans-04 m-all-7" data-page="' . ($currentPage + 1) . '"><i class="zmdi zmdi-chevron-right"></i></a>';
    }
    
    return $html;
}
?>
