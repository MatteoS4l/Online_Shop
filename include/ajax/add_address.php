session_start();
<?php
// add_address.php - Aggiunge un nuovo indirizzo per l'utente loggato
ob_clean();
header('Content-Type: application/json');
session_start();
require_once '../dbms.inc.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utente non autenticato']);
    exit;
}

$user_id = $_SESSION['user_id'];

$indirizzo = trim($_POST['indirizzo'] ?? '');
$citta = trim($_POST['citta'] ?? '');
$regione = trim($_POST['regione'] ?? '');
$provincia = trim($_POST['provincia'] ?? '');
$cap = trim($_POST['cap'] ?? '');

if ($indirizzo === '' || $citta === '' || $regione === '' || $provincia === '' || $cap === '') {
    echo json_encode(['success' => false, 'error' => 'Tutti i campi sono obbligatori']);
    exit;
}



$stmt = $connessione->prepare("INSERT INTO Indirizzo_Spedizione (id_utente, indirizzo, citta, regione, provincia, CAP) VALUES (?, ?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param('isssss', $user_id, $indirizzo, $citta, $regione, $provincia, $cap);
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        echo json_encode(['success' => true, 'id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Errore nel salvataggio']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Errore di connessione DB']);
}
