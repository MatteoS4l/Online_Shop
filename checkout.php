<?php
// checkout.php - Pagina di checkout per mioecom

// Include base e sessione
require_once __DIR__ . "/include/template2.inc.php";
require_once __DIR__ . "/include/dbms.inc.php";
require_once __DIR__ . "/include/auth.inc.php";
require_once __DIR__ . "/include/layout-utils.inc.php";
require_once __DIR__ . "/include/img.inc.php";
require_once __DIR__ . "/include/wishlist.inc.php";
session_start();

// Controllo autenticazione
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

// Template

// ...existing code...
$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/checkout.html");
$body->setContent("SPAZIO_INIZIO", "<div style='margin-top:32px;'></div>");

$user_id = $_SESSION['user_id'];

// Notifiche carrello e preferiti
$cart_count = countUserCart($user_id);
$wishlist_count = countUserWishlist($user_id);
$main->setContent("NUMBER_NOTY_CART", $cart_count);
$main->setContent("NUMBER_NOTY_FAV", $wishlist_count);

// Breadcrumb e titolo pagina
$main->setContent("BREADCRUMB_TEXT", "Checkout");
$main->setContent("PAGE_TITLE", "Checkout");

// Indirizzi utente


$indirizzi = [];
$stmt = $connessione->prepare("SELECT id, indirizzo, citta, regione, provincia, CAP FROM Indirizzo_Spedizione WHERE id_utente = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $indirizzi[] = $row;
            }
        }
    }
    $stmt->close();
}
$indirizzi_html = "";
if (!empty($indirizzi)) {
    foreach ($indirizzi as $ind) {
        $indirizzi_html .= "<option value='" . $ind['id'] . "'>" . htmlspecialchars($ind['indirizzo']) . ", " . htmlspecialchars($ind['citta']) . " (" . htmlspecialchars($ind['regione']) . ", " . htmlspecialchars($ind['provincia']) . ") - " . htmlspecialchars($ind['CAP']) . "</option>";
    }
} else {
    $indirizzi_html .= "<option value=''>Nessun indirizzo disponibile</option>";
}
$body->setContent("INDIRIZZI_UTENTE", $indirizzi_html);

// Corrieri


$corrieri = [];
$res = $connessione->query("SELECT id, azienda, giorni_consegna, prezzo FROM Corriere");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $corrieri[] = $row;
    }
}
$corrieri_html = "";
if (!empty($corrieri)) {
    foreach ($corrieri as $cor) {
        $corrieri_html .= "<tr><td><input type='radio' name='corriere' value='" . $cor['id'] . "' required></td><td>" . htmlspecialchars($cor['azienda']) . "</td><td>" . htmlspecialchars($cor['giorni_consegna']) . " giorni</td><td>€ " . number_format($cor['prezzo'], 2) . "</td></tr>";
    }
} else {
    $corrieri_html .= "<tr><td colspan='4'>Nessun corriere disponibile</td></tr>";
}
$body->setContent("CORRIERI_CHECKOUT", $corrieri_html);

// Pagamenti

// Recupera metodi di pagamento dal DB
$pagamenti = [];

$res = $connessione->query("SELECT id, tipo_pagamento FROM Metodo_Pagamento");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pagamenti[] = $row;
    }
}
$pagamenti_html = "";
foreach ($pagamenti as $pag) {
    $pagamenti_html .= "<tr><td><input type='radio' name='pagamento' value='" . $pag['id'] . "' required></td><td>" . htmlspecialchars($pag['tipo_pagamento']) . "</td></tr>";
}
$body->setContent("PAGAMENTI_CHECKOUT", $pagamenti_html);

// Riepilogo carrello

$carrello_html = "<ul class='list-group mb-3'>";


$stmt = $connessione->prepare("SELECT c.quantita_prodotto, c.taglia_prodotto, p.nome_prodotto, p.prezzo, p.id as product_id FROM Carrello c JOIN Prodotto p ON c.id_prodotto = p.id WHERE c.id_utente = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $totale = 0;
    $prodotti = $result->fetch_all(MYSQLI_ASSOC);
    if (!empty($prodotti)) {
        foreach ($prodotti as $item) {
            // Recupera l'immagine dal DB
            $img_url = "skins/template/images/item-cart-01.jpg";
            $img_stmt = $connessione->prepare("SELECT url_immagine FROM Immagine_Prodotto WHERE id_prodotto = ? ORDER BY id ASC LIMIT 1");
            $img_stmt->bind_param("i", $item['product_id']);
            if ($img_stmt->execute()) {
                $img_res = $img_stmt->get_result();
                if ($img_row = $img_res->fetch_assoc()) {
                    $img_url = "skins/template/images/" . $img_row['url_immagine'];
                }
            }
            $img_stmt->close();

            $carrello_html .= "<li class='list-group-item d-flex flex-column justify-content-center align-items-center text-center'>"
                . "<img src='" . htmlspecialchars($img_url) . "' alt='Immagine prodotto' class='mb-2' style='max-width:80px; max-height:80px; object-fit:cover; border-radius:8px;'>"
                . "<div class='fw-bold mb-1'>" . htmlspecialchars($item['nome_prodotto']) . "</div>"
                . "<div class='mb-1'>Taglia: " . htmlspecialchars($item['taglia_prodotto']) . "</div>"
                . "<div class='mb-1'>x" . intval($item['quantita_prodotto']) . "</div>"
                . "<div class='mb-1'>€ " . number_format($item['prezzo'] * $item['quantita_prodotto'], 2) . "</div>"
            . "</li>";
            $totale += $item['prezzo'] * $item['quantita_prodotto'];
        }
        // Applica sconto coupon se presente
        if (isset($_SESSION['coupon']) && !empty($_SESSION['coupon']['sconto_percentuale'])) {
            $sconto = $_SESSION['coupon']['sconto_percentuale'];
            $importo_sconto = $totale * ($sconto / 100);
            $totale_scontato = $totale - $importo_sconto;
            $carrello_html .= "<li class='list-group-item text-center text-success'>Coupon <span>-" . number_format($sconto, 0) . "%</span> <span>(-€ " . number_format($importo_sconto, 2) . ")</span></li>";
            $carrello_html .= "<li class='list-group-item text-center fw-bold'>Totale scontato <span>€ " . number_format($totale_scontato, 2) . "</span></li>";
        } else {
            $carrello_html .= "<li class='list-group-item text-center fw-bold'>Totale <span>€ " . number_format($totale, 2) . "</span></li>";
        }
    } else {
        $carrello_html .= "<li class='list-group-item'>Carrello vuoto</li>";
    }
} else {
    $carrello_html .= "<li class='list-group-item'>Carrello vuoto</li>";
}
$stmt->close();
$carrello_html .= "</ul>";
$body->setContent("RIEPILOGO_CARRELLO", $carrello_html);

// Mostra feedback coupon se presente
$coupon_feedback = '';
if (isset($_SESSION['coupon_feedback'])) {
    $coupon_feedback = $_SESSION['coupon_feedback'];
    unset($_SESSION['coupon_feedback']);
}
$coupon_disabled = '';
if (isset($_SESSION['coupon']) && !empty($_SESSION['coupon']['sconto_percentuale'])) {
    $coupon_disabled = 'disabled';
}
$body->setContent("COUPON_FEEDBACK", $coupon_feedback);
$body->setContent("COUPON_DISABLED", $coupon_disabled);
$body->setContent("SPAZIO_FINE", "<div style='margin-bottom:32px;'></div>");

$main->setContent("BODY", $body->get());
$main->close();
