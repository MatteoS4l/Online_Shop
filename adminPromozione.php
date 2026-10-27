<?php
// Pagina di gestione promozioni
// TODO: implementare gestione promozioni
include_once "include/auth.inc.php";
include_once "include/template2.inc.php";
$main = new Template("../adminPromozione.html");
$main->setContent("message", "Qui puoi gestire le promozioni.");
$main->close();
