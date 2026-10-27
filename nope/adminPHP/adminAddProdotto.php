<?php
// Pagina di aggiunta prodotto
// TODO: implementare form e logica di inserimento prodotto
include_once "../../include/auth.inc.php";
include_once "../../include/template2.inc.php";



$success = "";
$error = "";

// Gestione inserimento nuova categoria
if (isset($_POST['add_categoria']) && !empty($_POST['nuova_categoria'])) {
    $nuova_categoria = trim($_POST['nuova_categoria']);
    $check = $connessione->prepare("SELECT id FROM Categoria WHERE nome_categoria = ?");
    $check->bind_param("s", $nuova_categoria);
    $check->execute();
    $check->store_result();
    if ($check->num_rows == 0) {
        $stmt = $connessione->prepare("INSERT INTO Categoria (nome_categoria) VALUES (?)");
        $stmt->bind_param("s", $nuova_categoria);
        if ($stmt->execute()) {
            $success = "Categoria aggiunta!";
        } else {
            $error = "Errore nell'aggiunta della categoria.";
        }
        $stmt->close();
    } else {
        $error = "Categoria già esistente.";
    }
    $check->close();
}

// Gestione inserimento nuova marca
if (isset($_POST['add_marca']) && !empty($_POST['nuova_marca'])) {
    $nuova_marca = trim($_POST['nuova_marca']);
    $check = $connessione->prepare("SELECT id FROM Marca WHERE nome_marca = ?");
    $check->bind_param("s", $nuova_marca);
    $check->execute();
    $check->store_result();
    if ($check->num_rows == 0) {
        $stmt = $connessione->prepare("INSERT INTO Marca (nome_marca) VALUES (?)");
        $stmt->bind_param("s", $nuova_marca);
        if ($stmt->execute()) {
            $success = "Marca aggiunta!";
        } else {
            $error = "Errore nell'aggiunta della marca.";
        }
        $stmt->close();
    } else {
        $error = "Marca già esistente.";
    }
    $check->close();
}

include_once "../../include/dbms.inc.php";

// Recupero categorie e marche per il form
$categorie = [];
$marche = [];
$cat_result = $connessione->query("SELECT id, nome_categoria FROM Categoria");
while ($row = $cat_result->fetch_assoc()) {
    $categorie[] = $row;
}
$mar_result = $connessione->query("SELECT id, nome_marca FROM Marca");
while ($row = $mar_result->fetch_assoc()) {
    $marche[] = $row;
}

$nome = trim($_POST['nome'] ?? '');
$descrizione = trim($_POST['descrizione'] ?? '');
$prezzo = $_POST['prezzo'] ?? '';
$quantita = $_POST['quantita'] ?? '';
$genere = $_POST['genere'] ?? 'uomo';
$taglia = $_POST['taglia'] ?? 'M';
$id_categoria = $_POST['id_categoria'] ?? '';
$id_marca = $_POST['id_marca'] ?? '';
$id_promozione = null;

if ($nome && $descrizione && $prezzo && $quantita && $genere && $taglia && $id_categoria && $id_marca) {
    $query = "INSERT INTO Prodotto (nome_prodotto, descrizione, prezzo, genere, id_categoria, id_marca, id_promozione) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("ssdiiii", $nome, $descrizione, $prezzo, $genere, $id_categoria, $id_marca, $id_promozione);
    if ($stmt->execute()) {
        $id_prodotto = $stmt->insert_id;
        // Gestione immagine
        if (!empty($_FILES['immagine']['name'])) {
            $target_dir = __DIR__ . '/../../skins/template/images/products/';
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_name = basename($_FILES['immagine']['name']);
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES['immagine']['tmp_name'], $target_file)) {
                $img_query = "INSERT INTO Immagine_Prodotto (id_prodotto, url_immagine) VALUES (?, ?)";
                $img_stmt = $connessione->prepare($img_query);
                $img_url = 'products/' . $file_name;
                $img_stmt->bind_param("is", $id_prodotto, $img_url);
                $img_stmt->execute();
            }
        }
        // Gestione magazzino (quantità e taglia)
        $mag_query = "INSERT INTO Magazzino (id_prodotto, quantita, taglia) VALUES (?, ?, ?)";
        $mag_stmt = $connessione->prepare($mag_query);
        $mag_stmt->bind_param("iis", $id_prodotto, $quantita, $taglia);
        $mag_stmt->execute();
        $success = "Prodotto aggiunto con successo!";
    } else {
        $error = "Errore nell'inserimento del prodotto: " . $stmt->error;
    }
    $stmt->close();
} else {
    $error = "Compila tutti i campi obbligatori.";
}


$main = new Template("../layout_admin.html");
$body = new Template("../adminAddProdotto.html");

// Costruisco le option per categoria e marca
$cat_options = "";
foreach ($categorie as $cat) {
    $cat_options .= '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['nome_categoria']) . '</option>';
}
$mar_options = "";
foreach ($marche as $mar) {
    $mar_options .= '<option value="' . $mar['id'] . '">' . htmlspecialchars($mar['nome_marca']) . '</option>';
}

$body->setContent("SUCCESS_MESSAGE", $success);
$body->setContent("ERROR_MESSAGE", $error);
$body->setContent("CATEGORIA_OPTIONS", $cat_options);
$body->setContent("MARCA_OPTIONS", $mar_options);
$main->setContent("BODY", $body->get());
$main->close();
