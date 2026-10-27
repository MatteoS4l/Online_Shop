<?php
// order.php - Visualizzazione ordini utente
session_start();
require_once 'include/dbms.inc.php';
require_once 'include/template2.inc.php';
require_once 'include/auth.inc.php';
require_once 'include/layout-utils.inc.php';

requireAuth('order.php', 'Accedi per visualizzare i tuoi ordini');

$user_id = $_SESSION['user_id'];
$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/order.html");

// Messaggio di successo dopo checkout
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $body->setContent("SHOW_SUCCESS_MSG", "block");
    $body->setContent("SUCCESS_MSG", "Il tuo ordine è stato registrato con successo!");
} else {
    $body->setContent("SHOW_SUCCESS_MSG", "none");
    $body->setContent("SUCCESS_MSG", "");
}

// Recupera ordini utente
$stmt = $connessione->prepare("SELECT id, data_ordine, prezzo_ordine, data_spedizione FROM ordine WHERE id_utente = ? ORDER BY data_ordine DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!empty($orders)) {
    foreach ($orders as $ordine) {
        $ordineItem = new Template("skins/template/ordine-item.html");
        $ordineItem->setContent("ID_ORDINE", $ordine['id']);
        $ordineItem->setContent("DATA_ORDINE", $ordine['data_ordine']);
        $ordineItem->setContent("TOTALE_ORDINE", number_format($ordine['prezzo_ordine'], 2));
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
        $ordineItem->setContent("STATO_ORDINE", $stato_ordine);
        $body->setContent("ELEMENTO_ORDINE", $ordineItem->get());
    }
    $body->setContent("SHOW_ORDERS_TABLE", "block");
    $body->setContent("SHOW_EMPTY_MSG", "none");
} else {
    $body->setContent("SHOW_ORDERS_TABLE", "none");
    $body->setContent("SHOW_EMPTY_MSG", "block");
}

setLayoutAccountInfo($main);
$main->setContent("BODY", $body->get());
$main->close();
