<?php
// conferma_checkout.php - Gestione conferma ordine per mioecom (utility)
$redirect = urlencode('../../include/php-utils/conferma_checkout.php');
session_start();
require_once '../../include/dbms.inc.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php?redirect=$redirect");
    exit();
}

$user_id = $_SESSION['user_id'];
$indirizzo = isset($_POST['indirizzo']) ? $_POST['indirizzo'] : null;
$corriere = isset($_POST['corriere']) ? $_POST['corriere'] : null;
$pagamento = isset($_POST['pagamento']) ? $_POST['pagamento'] : null;
$codice_sconto = isset($_POST['codice_sconto']) ? $_POST['codice_sconto'] : null;

// Leggi prodotti dal carrello DB
$carrello = [];
$stmt = $connessione->prepare("SELECT c.id_prodotto, c.quantita_prodotto, p.prezzo FROM Carrello c JOIN Prodotto p ON c.id_prodotto = p.id WHERE c.id_utente = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $carrello[] = [
            'id' => $row['id_prodotto'],
            'quantita' => $row['quantita_prodotto'],
            'prezzo' => $row['prezzo']
        ];
    }
}
$stmt->close();

if (!$indirizzo || !$corriere || !$pagamento || count($carrello) == 0) {
    $redirect_checkout = urlencode('../../include/php-utils/conferma_checkout.php');
    header("Location: ../../checkout.php?error=1&redirect=$redirect_checkout");
    exit();
}

// Calcola totale e applica eventuale sconto
$totale = 0;
foreach ($carrello as $item) {
    $totale += $item['prezzo'] * $item['quantita'];
}
if ($codice_sconto) {
    $stmt = $connessione->prepare("SELECT sconto_percentuale FROM Coupon WHERE codice_coupon = ?");
    $stmt->bind_param("s", $codice_sconto);
    $stmt->execute();
    $stmt->bind_result($percentuale);
    if ($stmt->fetch()) {
        $totale = $totale * (1 - $percentuale / 100);
    }
    $stmt->close();
}

// Inserisci ordine
$stmt = $connessione->prepare("INSERT INTO Ordine (id_utente, id_corriere, id_coupon, id_metodo_pagamento, id_indirizzo_spedizione, data_ordine, prezzo_ordine) VALUES (?, ?, NULL, ?, ?, NOW(), ?)");
$stmt->bind_param("iiiid", $user_id, $corriere, $pagamento, $indirizzo, $totale);
$stmt->execute();
$ordine_id = $stmt->insert_id;
$stmt->close();

// Inserisci prodotti nell'ordine
foreach ($carrello as $item) {
    $stmt = $connessione->prepare("INSERT INTO Oggetto_Ordine (id_ordine, id_prodotto, quantita_prodotto) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $ordine_id, $item['id'], $item['quantita']);
    $stmt->execute();
    $stmt->close();
}

// Svuota carrello DB
$stmt = $connessione->prepare("DELETE FROM Carrello WHERE id_utente = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Redirect con successo
header("Location: ../../order.php?success=1");
exit();
