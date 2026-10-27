<?php
require "include/template2.inc.php";
require "include/dbms.inc.php";

session_start();

global $connessione;

$main= new Template("skins/template/dtml/layout.html");
$body = new Template("skins/template/home.html");

$main->setContent("BODY", $body->get());
$main->close();


?>