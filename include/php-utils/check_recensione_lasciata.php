<?php
// check_recensione_lasciata.php - Verifica se l'utente ha già recensito il prodotto
session_start();
require_once '../dbms.inc.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['canReview' => false, 'msg' => 'Utente non autenticato']);
    exit;
}
$user_id = $_SESSION['user_id'];
$id_prodotto = isset($_POST['id_prodotto']) ? intval($_POST['id_prodotto']) : 0;
if ($id_prodotto <= 0) {
    echo json_encode(['canReview' => false, 'msg' => 'ID prodotto non valido']);
    exit;
}
$stmt = $connessione->prepare("SELECT id FROM Recensione WHERE id_utente = ? AND id_prodotto = ?");
$stmt->bind_param("ii", $user_id, $id_prodotto);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['canReview' => false, 'msg' => 'Hai già recensito questo prodotto']);
} else {
    echo json_encode(['canReview' => true]);
}
$stmt->close();
