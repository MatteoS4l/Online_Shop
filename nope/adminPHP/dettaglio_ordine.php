<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../../dump/error403.html');
    exit;
}
require_once '../../include/dbms.inc.php';
require_once '../../include/img.inc.php';
require_once '../../include/template2.inc.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    echo '<div class="alert alert-danger">ID ordine non valido.</div>';
    exit;
}

// Template separato per dettaglio ordine
$main = new Template("../layout_admin.html");
$body = new Template("../dettaglio_ordine.html");

// Query dettagli ordine
$query = "SELECT o.*, u.nome, u.cognome, u.email, c.azienda AS corriere, c.prezzo AS prezzo_corriere, c.giorni_consegna, cp.codice_coupon, cp.sconto_percentuale, mp.tipo_pagamento, isped.indirizzo, isped.citta, isped.regione, isped.provincia, isped.CAP
FROM Ordine o
JOIN Utente u ON o.id_utente = u.id
JOIN Corriere c ON o.id_corriere = c.id
LEFT JOIN Coupon cp ON o.id_coupon = cp.id
JOIN Metodo_Pagamento mp ON o.id_metodo_pagamento = mp.id
JOIN Indirizzo_Spedizione isped ON o.id_indirizzo_spedizione = isped.id
WHERE o.id = $order_id";
$res = mysqli_query($connessione, $query);
if (!$res || mysqli_num_rows($res) == 0) {
    echo '<div class="alert alert-danger">Ordine non trovato.</div>';
    exit;
}
$order = mysqli_fetch_assoc($res);

// Query prodotti nell'ordine
$query = "SELECT p.id, p.nome_prodotto, p.prezzo, oo.quantita_prodotto, img.url_immagine
FROM Oggetto_Ordine oo
JOIN Prodotto p ON oo.id_prodotto = p.id
LEFT JOIN Immagine_Prodotto img ON p.id = img.id_prodotto
WHERE oo.id_ordine = $order_id";
$res = mysqli_query($connessione, $query);
$products_html = '<table class="table"><thead><tr><th>Immagine</th><th>Nome</th><th>Prezzo</th><th>Quantità</th><th>Totale</th></tr></thead><tbody>';
while ($row = mysqli_fetch_assoc($res)) {
    $img = !empty($row['url_immagine']) ? ('../../' . PRODUCTS_IMAGE_PATH . $row['url_immagine']) : ('../../' . PRODUCTS_IMAGE_PATH . 'default.jpg');
    $products_html .= '<tr>';
    $products_html .= '<td><img src="' . $img . '" alt="' . htmlspecialchars($row['nome_prodotto']) . '" style="width:40px;height:40px;object-fit:cover;"></td>';
    $products_html .= '<td>' . htmlspecialchars($row['nome_prodotto']) . '</td>';
    $products_html .= '<td>' . $row['prezzo'] . '€</td>';
    $products_html .= '<td>' . $row['quantita_prodotto'] . '</td>';
    $products_html .= '<td>' . number_format($row['prezzo'] * $row['quantita_prodotto'], 2) . '€</td>';
    $products_html .= '</tr>';
}
$products_html .= '</tbody></table>';

// Stato spedizione
$stato = '';
$oggi = new DateTime();
$data_spedizione = (!empty($order['data_spedizione']) && $order['data_spedizione'] !== '0000-00-00') ? new DateTime($order['data_spedizione']) : null;
$giorni_consegna = isset($order['giorni_consegna']) ? (int)$order['giorni_consegna'] : 0;
if ($data_spedizione === null) {
    $stato = '<span class="badge bg-danger">In preparazione</span>';
} else {
    $data_consegna = clone $data_spedizione;
    $data_consegna->modify('+' . $giorni_consegna . ' days');
    if ($oggi >= $data_consegna) {
        $stato = '<span class="badge bg-success">Consegnato</span>';
    } else {
        $stato = '<span class="badge bg-warning text-dark">Spedito</span>';
    }
}

// Popola i placeholder
$body->setContent("ORDER_ID", $order_id);
$body->setContent("CUSTOMER_DATA", htmlspecialchars($order['nome']) . ' ' . htmlspecialchars($order['cognome']) . ' (' . htmlspecialchars($order['email']) . ')');
$body->setContent("SHIPPING_ADDRESS", htmlspecialchars($order['indirizzo']) . ', ' . htmlspecialchars($order['citta']) . ' (' . htmlspecialchars($order['provincia']) . '), ' . htmlspecialchars($order['regione']) . ' - ' . htmlspecialchars($order['CAP']));
$body->setContent("COURIER", htmlspecialchars($order['corriere']) . ' (' . number_format($order['prezzo_corriere'], 2) . '€), consegna stimata: ' . $order['giorni_consegna'] . ' giorni');
$body->setContent("PAYMENT_METHOD", htmlspecialchars($order['tipo_pagamento']));
$body->setContent("COUPON", !empty($order['codice_coupon']) ? htmlspecialchars($order['codice_coupon']) . ' (' . $order['sconto_percentuale'] . '%)' : 'Nessuno');
$body->setContent("SHIPPING_STATUS", $stato);
$body->setContent("ORDER_PRODUCTS", $products_html);
$body->setContent("ORDER_TOTAL", number_format($order['prezzo_ordine'], 2) . '€');
$main->setContent("BODY", $body->get());
$main->close();
?>

