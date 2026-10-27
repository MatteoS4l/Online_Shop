<?php
// Pagina di gestione promozioni
// TODO: implementare gestione promozioni
include_once "../../include/auth.inc.php";
include_once "../../include/template2.inc.php";



$success = "";
$error = "";

include_once "../../include/dbms.inc.php";

// Gestione inserimento nuova promozione
if (isset($_POST['add_promozione'])) {
    $nome = trim($_POST['nome_promozione'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $sconto = $_POST['sconto'] ?? '';
    $data_inizio = $_POST['data_inizio'] ?? '';
    $data_fine = $_POST['data_fine'] ?? '';
    $id_prodotti = $_POST['prodotti'] ?? [];
    if ($nome && $descrizione && $sconto && $data_inizio && $data_fine && !empty($id_prodotti)) {
        $check = $connessione->prepare("SELECT id FROM Promozione WHERE nome_promozione = ?");
        $check->bind_param("s", $nome);
        $check->execute();
        $check->store_result();
        if ($check->num_rows == 0) {
            $stmt = $connessione->prepare("INSERT INTO Promozione (nome_promozione, descrizione, sconto_percentuale, data_inizio, data_fine) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdss", $nome, $descrizione, $sconto, $data_inizio, $data_fine);
            if ($stmt->execute()) {
                $id_promozione = $stmt->insert_id;
            } else {
                $error = "Errore nell'aggiunta della promozione.";
                $id_promozione = null;
            }
            $stmt->close();
        } else {
            // Recupera l'id della promozione già esistente
            $check_id = $connessione->prepare("SELECT id FROM Promozione WHERE nome_promozione = ?");
            $check_id->bind_param("s", $nome);
            $check_id->execute();
            $check_id->bind_result($id_promozione);
            $check_id->fetch();
            $check_id->close();
            $error = "Promozione già esistente, associata al prodotto.";
        }
        $check->close();
        // Se abbiamo un id promozione valido, aggiorna tutti i prodotti selezionati
        if (!empty($id_promozione)) {
            $successi = 0;
            $errori = 0;
            foreach ($id_prodotti as $id_prodotto) {
                $update = $connessione->prepare("UPDATE Prodotto SET id_promozione = ? WHERE id = ?");
                $update->bind_param("ii", $id_promozione, $id_prodotto);
                if ($update->execute()) {
                    $successi++;
                } else {
                    $errori++;
                }
                $update->close();
            }
            if ($successi > 0) {
                $success = "Promozione associata a $successi prodotto/i!";
            }
            if ($errori > 0) {
                $error = "Errore nell'associazione su $errori prodotto/i.";
            }
        }
    } else {
        $error = "Compila tutti i campi obbligatori.";
    }
}

// Recupero promozioni per visualizzazione/gestione
$promozioni = [];
$promo_result = $connessione->query("SELECT id, nome_promozione, descrizione, sconto_percentuale, data_inizio, data_fine FROM Promozione");
while ($row = $promo_result->fetch_assoc()) {
    $promozioni[] = $row;
}

$promo_rows = "";
foreach ($promozioni as $promo) {
    $promo_rows .= '<tr>';
    $promo_rows .= '<td>' . htmlspecialchars($promo['nome_promozione']) . '</td>';
    $promo_rows .= '<td>' . htmlspecialchars($promo['descrizione']) . '</td>';
    $promo_rows .= '<td>' . htmlspecialchars($promo['sconto_percentuale']) . '%</td>';
    $promo_rows .= '<td>' . htmlspecialchars($promo['data_inizio']) . '</td>';
    $promo_rows .= '<td>' . htmlspecialchars($promo['data_fine']) . '</td>';
    $promo_rows .= '</tr>';
}

// Promozioni attive: data_inizio <= oggi <= data_fine
$promo_attive_rows = "";
$oggi = date('Y-m-d');
foreach ($promozioni as $promo) {
    if ($promo['data_inizio'] <= $oggi && $promo['data_fine'] >= $oggi) {
        $promo_attive_rows .= '<tr>';
        $promo_attive_rows .= '<td class="fw-bold text-primary">' . htmlspecialchars($promo['nome_promozione']) . '</td>';
        $promo_attive_rows .= '<td>' . htmlspecialchars($promo['descrizione']) . '</td>';
        $promo_attive_rows .= '<td><span class="badge bg-success">' . htmlspecialchars($promo['sconto_percentuale']) . '%</span></td>';
        $promo_attive_rows .= '<td class="text-secondary">' . htmlspecialchars($promo['data_inizio']) . '</td>';
        $promo_attive_rows .= '<td class="text-secondary">' . htmlspecialchars($promo['data_fine']) . '</td>';
        $promo_attive_rows .= '</tr>';
    }
}

$main = new Template("../layout_admin.html");
$body = new Template("../adminPromozione.html");
$body->setContent("PROMO_SUCCESS", $success);
$body->setContent("PROMO_ERROR", $error);
$body->setContent("PROMOZIONE_ROWS", $promo_rows);
$body->setContent("PROMO_LIST", $promo_attive_rows);
// ...existing code...
$prodotti = [];
// Recupero anche id_promozione per sapere se il prodotto ha già una promo
$prod_result = $connessione->query("SELECT id, nome_prodotto, id_promozione FROM Prodotto");
if ($prod_result) {
    while ($row = $prod_result->fetch_assoc()) {
        $prodotti[] = $row;
    }
}
$product_checkboxes = "";
foreach ($prodotti as $prod) {
    $disabled = !empty($prod['id_promozione']) ? 'disabled' : '';
    $product_checkboxes .= '<div class="form-check">';
    $product_checkboxes .= '<input class="form-check-input" type="checkbox" name="prodotti[]" id="prod_' . $prod['id'] . '" value="' . $prod['id'] . '" ' . $disabled . '>';
    $product_checkboxes .= '<label class="form-check-label" for="prod_' . $prod['id'] . '">' . htmlspecialchars($prod['nome_prodotto']);
    if ($disabled) {
        $product_checkboxes .= ' <span class="badge bg-warning text-dark">Già in promozione</span>';
    }
    $product_checkboxes .= '</label>';
    $product_checkboxes .= '</div>';
}
$body->setContent("PRODUCT_CHECKBOXES", $product_checkboxes);
$main->setContent("BODY", $body->get());
$main->close();
