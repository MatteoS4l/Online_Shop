<?php
// utils.inc.php
// Funzioni di utilità generiche

function criptify($password) {
    return md5(md5(md5(md5(md5($password)))));
}
