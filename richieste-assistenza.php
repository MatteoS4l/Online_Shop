<?php
require_once "include/template2.inc.php";
require_once "include/dbms.inc.php";
require_once "include/auth.inc.php";
require_once "include/layout-utils.inc.php";

session_start();
global $connessione;


$main = new Template("skins/template/dtml/layout.html");
setLayoutAccountInfo($main);

$body = new Template("skins/template/richiesta_assistenza.html");

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$rows = [];
if ($user_id) {
    $stmt = $connessione->prepare("SELECT id, richiesta, risposta, data_richiesta FROM Messaggio_Assistenza WHERE id_utente = ? ORDER BY data_richiesta DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
}

$table = '';
if (count($rows) > 0) {
    $table .= '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Data</th><th>Richiesta</th><th>Risposta</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $table .= '<tr>';
        $table .= '<td>' . htmlspecialchars($row['data_richiesta']) . '</td>';
        $table .= '<td>' . nl2br(htmlspecialchars($row['richiesta'])) . '</td>';
        $table .= '<td>' . ($row['risposta'] ? nl2br(htmlspecialchars($row['risposta'])) : '<span class="text-warning">In attesa</span>') . '</td>';
        $table .= '</tr>';
    }
    $table .= '</tbody></table></div>';
} else {
    $table .= '<div class="alert alert-info">Non hai ancora richiesto assistenza.</div>';
}

$body->setContent("ASSISTENZA_TABLE", $table);
$main->setContent("BODY", $body->get());
$main->close();
?>
