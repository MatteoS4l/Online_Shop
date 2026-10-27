<?php
require_once "include/template2.inc.php";
require_once "include/dbms.inc.php";
require_once "include/auth.inc.php";
require_once "include/wishlist.inc.php";
require_once "include/layout-utils.inc.php";

session_start();

global $connessione;


$main= new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/contact.html");

// Imposta i placeholder della barra superiore (account, carrello, wishlist)
setLayoutAccountInfo($main);

$main->setContent("BODY", $body->get());
$main->close();


?>