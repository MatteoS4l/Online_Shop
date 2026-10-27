<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Disabilita output buffering e errori visibili per evitare output extra
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');
require_once '../dbms.inc.php';

$response = ['success' => false, 'message' => '', 'discount' => 0];

// Controllo che la richiesta sia POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../checkout.php');
    exit;
}

if (!isset($_POST['codice_sconto'])) {
    $_SESSION['coupon_feedback'] = 'Codice coupon mancante.';
    header('Location: ../../checkout.php');
    exit;
}

$codice = trim($_POST['codice_sconto']);

if (!$connessione || $connessione->connect_error) {
    $_SESSION['coupon_feedback'] = 'Errore connessione DB: ' . $connessione->connect_error;
    header('Location: ../../checkout.php');
    exit;
}

$stmt = $connessione->prepare("SELECT sconto_percentuale FROM Coupon WHERE codice_coupon = ? LIMIT 1");
if (!$stmt) {
    $_SESSION['coupon_feedback'] = 'Errore query: ' . $connessione->error;
    header('Location: ../../checkout.php');
    exit;
}
$stmt->bind_param('s', $codice);
if (!$stmt->execute()) {
    $_SESSION['coupon_feedback'] = 'Errore esecuzione query: ' . $stmt->error;
    $stmt->close();
    header('Location: ../../checkout.php');
    exit;
}
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($sconto_percentuale);
    $stmt->fetch();
    $_SESSION['coupon'] = [
        'codice' => $codice,
        'sconto_percentuale' => $sconto_percentuale
    ];
    $_SESSION['coupon_feedback'] = 'Coupon applicato: -' . $sconto_percentuale . '%';
} else {
    unset($_SESSION['coupon']);
    $_SESSION['coupon_feedback'] = 'Coupon non valido.';
}

$stmt->close();
header('Location: ../../checkout.php');
exit;
