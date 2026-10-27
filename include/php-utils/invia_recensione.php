<?php
// invia_recensione.php - Inserisce una recensione prodotto
session_start();
require_once '../dbms.inc.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Utente non autenticato']);
    exit;
}

$user_id = $_SESSION['user_id'];
$id_prodotto = isset($_POST['id_prodotto']) ? intval($_POST['id_prodotto']) : 0;
$testo = isset($_POST['testo']) ? trim($_POST['testo']) : '';
$stelle = isset($_POST['stelle']) ? intval($_POST['stelle']) : 0;

if ($id_prodotto <= 0 || $stelle < 1 || $stelle > 5) {
    echo json_encode(['success' => false, 'msg' => 'Dati non validi']);
    exit;
}

// Controlla se già recensito
$stmt = $connessione->prepare("SELECT id FROM Recensione WHERE id_utente = ? AND id_prodotto = ?");
$stmt->bind_param("ii", $user_id, $id_prodotto);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'msg' => 'Hai già recensito questo prodotto']);
    $stmt->close();
    exit;
}
$stmt->close();

// Inserisci recensione
$stmt = $connessione->prepare("INSERT INTO Recensione (id_utente, id_prodotto, testo_recensione, valutazione, data_recensione) VALUES (?, ?, ?, ?, CURDATE())");
$stmt->bind_param("iisi", $user_id, $id_prodotto, $testo, $stelle);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'msg' => 'Recensione inserita']);
} else {
    echo json_encode(['success' => false, 'msg' => 'Errore inserimento']);
}
$stmt->close();
