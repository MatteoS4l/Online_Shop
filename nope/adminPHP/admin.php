<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../../dump/error403.html');
    exit;
}

require_once '../../include/dbms.inc.php';
require_once '../../include/img.inc.php';
require_once '../../include/template2.inc.php';

// ORDERS TABLE
$orders_html = '';

$query = "SELECT o.id, o.data_ordine, o.data_spedizione, o.prezzo_ordine, o.id_corriere, c.giorni_consegna FROM Ordine o LEFT JOIN Corriere c ON o.id_corriere = c.id ORDER BY o.data_ordine DESC";
$res = mysqli_query($connessione, $query);
$orders_html .= '<table class="table"><thead><tr><th>ID</th><th>Data</th><th>Stato Spedizione</th><th>Totale</th></tr></thead><tbody>';
while ($row = mysqli_fetch_assoc($res)) {
    $stato = '';
    $oggi = new DateTime();
    $data_spedizione = (!empty($row['data_spedizione']) && $row['data_spedizione'] !== '0000-00-00') ? new DateTime($row['data_spedizione']) : null;
    $giorni_consegna = isset($row['giorni_consegna']) ? (int)$row['giorni_consegna'] : 0;
    if ($data_spedizione === null) {
        $stato = 'In preparazione';
    } else {
        $data_consegna = clone $data_spedizione;
        $data_consegna->modify('+' . $giorni_consegna . ' days');
        if ($oggi >= $data_consegna) {
            $stato = 'Consegnato';
        } else {
            $stato = 'Spedito';
        }
    }
    $orders_html .= '<tr style="cursor:pointer;" onclick="window.location.href=\'dettaglio_ordine.php?id=' . $row['id'] . '\'">';
    $orders_html .= '<td>' . $row['id'] . '</td>';
    $orders_html .= '<td>' . $row['data_ordine'] . '</td>';
    $badge = '';
    if ($stato === 'In preparazione') {
        $badge = '<span class="badge bg-danger">' . $stato . '</span>';
    } elseif ($stato === 'Spedito') {
        $badge = '<span class="badge bg-warning text-dark">' . $stato . '</span>';
    } elseif ($stato === 'Consegnato') {
        $badge = '<span class="badge bg-success">' . $stato . '</span>';
    } else {
        $badge = $stato;
    }
    $orders_html .= '<td>' . $badge . '</td>';
    $orders_html .= '<td>' . $row['prezzo_ordine'] . '</td>';
    $orders_html .= '</tr>';
}
$orders_html .= '</tbody></table>';

// TOP 5 BEST SELLERS
$best_html = '';
$query = "SELECT p.id, p.nome_prodotto, img.url_immagine, SUM(o.quantita_prodotto) AS venduti FROM Prodotto p JOIN Oggetto_Ordine o ON p.id = o.id_prodotto LEFT JOIN Immagine_Prodotto img ON p.id = img.id_prodotto GROUP BY p.id ORDER BY venduti DESC LIMIT 5";
$res = mysqli_query($connessione, $query);
$best_html .= '<ul class="list-group">';
while ($row = mysqli_fetch_assoc($res)) {
    $img = !empty($row['url_immagine']) ? ('../../' . PRODUCTS_IMAGE_PATH . $row['url_immagine']) : ('../../' . PRODUCTS_IMAGE_PATH . 'default.jpg');
    $best_html .= '<li class="list-group-item d-flex align-items-center">';
    $best_html .= '<img src="' . $img . '" alt="' . htmlspecialchars($row['nome_prodotto']) . '" style="width:40px;height:40px;object-fit:cover;margin-right:10px;">';
    $best_html .= htmlspecialchars($row['nome_prodotto']) . ' <span class="badge bg-primary ms-auto">' . $row['venduti'] . '</span>';
    $best_html .= '</li>';
}
$best_html .= '</ul>';

// PRODUCTS TABLE WITH DELETE BUTTON
$products_html = '';
$query = "SELECT p.id, p.nome_prodotto, p.prezzo, img.url_immagine FROM Prodotto p LEFT JOIN Immagine_Prodotto img ON p.id = img.id_prodotto GROUP BY p.id";
$res = mysqli_query($connessione, $query);
$products_html .= '<table class="table"><thead><tr><th>ID</th><th>Immagine</th><th>Nome</th><th>Prezzo</th><th>Azioni</th></tr></thead><tbody>';
while ($row = mysqli_fetch_assoc($res)) {
    $img = !empty($row['url_immagine']) ? ('../../' . PRODUCTS_IMAGE_PATH . $row['url_immagine']) : ('../../' . PRODUCTS_IMAGE_PATH . 'default.jpg');
    $products_html .= '<tr>';
    $products_html .= '<td>' . $row['id'] . '</td>';
    $products_html .= '<td><img src="' . $img . '" alt="' . htmlspecialchars($row['nome_prodotto']) . '" style="width:40px;height:40px;object-fit:cover;"></td>';
    $products_html .= '<td>' . htmlspecialchars($row['nome_prodotto']) . '</td>';
    $products_html .= '<td>' . $row['prezzo'] . '</td>';
    $products_html .= '<td><button onclick="deleteProduct(' . $row['id'] . ')" class="btn btn-danger btn-sm">🗑️</button></td>';
    $products_html .= '</tr>';
}
$products_html .= '</tbody></table>';
$products_html .= '<script>\nfunction deleteProduct(id) {\n    if (confirm("Sei sicuro di voler eliminare questo prodotto?")) {\n        window.location.href = "delete_product.php?id=" + id;\n    }\n}\n</script>';

$main = new Template("../layout_admin.html");
$body = new Template("../index.html");


$body->setContent("ORDERS_TABLE", '<div class="mb-5"><div class="fw-bold h4 mb-4">Tabella Ordini</div>' . $orders_html . '</div>');
$body->setContent("BEST_SELLERS_LIST", '<div class="mb-5"><div class="fw-bold h4 mb-4">Top 5 Best Seller</div>' . $best_html . '</div>');
$body->setContent("PRODUCTS_TABLE", '<div class="mb-5"><div class="fw-bold h4 mb-4">Tabella Prodotti</div>' . $products_html . '</div>');

$main->setContent("BODY", $body->get());
$main->close();
?>
