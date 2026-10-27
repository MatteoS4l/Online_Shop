<?php
require_once '../dbms.inc.php';
require_once '../auth.inc.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato.']);
    exit;
}

if (empty($_POST['richiesta'])) {
    echo json_encode(['success' => false, 'message' => 'Testo richiesta obbligatorio.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$richiesta = trim($_POST['richiesta']);
$data = date('Y-m-d');

$stmt = $connessione->prepare("INSERT INTO Messaggio_Assistenza (id_utente, richiesta, data_richiesta) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $richiesta, $data);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore database.']);
}
$stmt->close();
?>
