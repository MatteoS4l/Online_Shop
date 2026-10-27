<?php
// dettagli-ordine.php - Dettaglio di un ordine per utente loggato
session_start();
require_once 'include/dbms.inc.php';
require_once 'include/template2.inc.php';
require_once 'include/auth.inc.php';
require_once 'include/layout-utils.inc.php';
require_once 'include/img.inc.php';

requireAuth('order.php', 'Accedi per visualizzare i tuoi ordini');

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id_ordine']) ? intval($_GET['id_ordine']) : 0;

$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/dettagli-ordine.html");

// Recupera dati ordine

$stmt = $connessione->prepare("SELECT o.id, o.data_ordine, o.prezzo_ordine, o.data_spedizione, o.id_indirizzo_spedizione, o.id_corriere, o.id_metodo_pagamento, c.azienda AS nome_corriere, m.tipo_pagamento AS nome_pagamento FROM ordine o JOIN corriere c ON o.id_corriere = c.id JOIN metodo_pagamento m ON o.id_metodo_pagamento = m.id WHERE o.id = ? AND o.id_utente = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$ordine = $result->fetch_assoc();
$stmt->close();

if (!$ordine) {
    $body->setContent("SHOW_ORDER", "none");
    $body->setContent("SHOW_ERROR", "block");
    $body->setContent("ERROR_MSG", "Ordine non trovato o non autorizzato.");
} else {
    $body->setContent("SHOW_ORDER", "block");
    $body->setContent("SHOW_ERROR", "none");
    $body->setContent("ID_ORDINE", $ordine['id']);
    $body->setContent("DATA_ORDINE", $ordine['data_ordine']);
    $body->setContent("TOTALE_ORDINE", number_format($ordine['prezzo_ordine'], 2));
    $body->setContent("NOME_CORRIERE", $ordine['nome_corriere']);
    $body->setContent("NOME_PAGAMENTO", $ordine['nome_pagamento']);
    // Stato ordine dinamico
    $stato_ordine = "In preparazione";
    if (!empty($ordine['data_spedizione'])) {
        $oggi = new DateTime();
        $oggi_str = $oggi->format('Y-m-d');
        $dataSped = strval($ordine['data_spedizione']);
        if ($dataSped <= $oggi_str) {
            $stato_ordine = "Spedito";
        }
    }
    $body->setContent("STATO_ORDINE", $stato_ordine);
    // Indirizzo spedizione
    $indirizzo = "-";
    if (!empty($ordine['id_indirizzo_spedizione'])) {
        $stmt = $connessione->prepare("SELECT indirizzo FROM indirizzo_spedizione WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $ordine['id_indirizzo_spedizione']);
        $stmt->execute();
        $res_addr = $stmt->get_result();
        if ($row_addr = $res_addr->fetch_assoc()) {
            $indirizzo = $row_addr['indirizzo'];
        }
        $stmt->close();
    }
    $body->setContent("INDIRIZZO_SPEDIZIONE", $indirizzo);
    // Recupera prodotti dell'ordine
    $stmt = $connessione->prepare("SELECT p.id AS id_prodotto, p.nome_prodotto, d.quantita_prodotto, p.prezzo, (SELECT ip.url_immagine FROM immagine_prodotto ip WHERE ip.id_prodotto = p.id LIMIT 1) AS url_immagine FROM oggetto_ordine d JOIN prodotto p ON d.id_prodotto = p.id WHERE d.id_ordine = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($item = $result->fetch_assoc()) {
        $itemTpl = new Template("skins/template/dettaglio-ordine-item.html");
        // Path immagine prodotto (fallback se vuoto)
        if (!empty($item['url_immagine'])) {
            $img = PRODUCTS_IMAGE_PATH . $item['url_immagine'];
        } else {
            $img = PRODUCTS_IMAGE_PATH . 'no-image.png';
        }
        $itemTpl->setContent("IMMAGINE_PRODOTTO", $img);
        $itemTpl->setContent("NOME_PRODOTTO", $item['nome_prodotto']);
        $itemTpl->setContent("QUANTITA", $item['quantita_prodotto']);
        $itemTpl->setContent("PREZZO_UNITARIO", number_format($item['prezzo'], 2));
        $itemTpl->setContent("SUBTOTALE", number_format($item['prezzo'] * $item['quantita_prodotto'], 2));
        $itemTpl->setContent("ID_PRODOTTO", $item['id_prodotto']);
        $body->setContent("ELEMENTO_PRODOTTO", $itemTpl->get());
    }
    $stmt->close();
}

setLayoutAccountInfo($main);
$main->setContent("BODY", $body->get());
$main->close();
