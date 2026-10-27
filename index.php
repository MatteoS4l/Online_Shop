<?php
require "include/template2.inc.php";
require "include/dbms.inc.php";
require "include/rating.inc.php";
require "include/wishlist.inc.php";
require "include/layout-utils.inc.php";

session_start();

$main = new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/home.html");

// Genera il contenuto HTML per tutte le sezioni prodotti usando i template
$bestSellerHTML = generateBestSellersHTML();
$saleProductsHTML = generateSaleProductsHTML();
$topRatedProductsHTML = generateTopRatedProductsHTML();

// Conteggio preferiti per l'header
$user_id = $_SESSION['user_id'] ?? null;
$wishlist_count = $user_id ? countUserWishlist($user_id) : 0;

// Carica il modal template
$modal = new Template("skins/template/product-modal.html");

// Sostituisci i placeholder nel template con i dati reali
$body->setContent("BEST_SELLERS", $bestSellerHTML);
$body->setContent("SALE_PRODUCTS", $saleProductsHTML);
$body->setContent("TOP_RATE_PRODUCTS", $topRatedProductsHTML);

$main->setContent("NUMBER_NOTY_FAV", $wishlist_count);
$main->setContent("PRODUCT_MODAL", $modal->get());

// Imposta i link del layout in base all'autenticazione
setLayoutAccountInfo($main);

$main->setContent("BODY", $body->get());
$main->close();

?>
