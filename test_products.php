<?php
// Test rapido per products_handler.php
echo "Test iniziato...<br>";

// Simula una chiamada POST
$_POST['action'] = 'get_products';
$_POST['page'] = 1;
$_POST['per_page'] = 12;
$_POST['view_mode'] = 'grid';
$_POST['filters'] = [];

echo "POST data impostati...<br>";

// Include il file
try {
    ob_start();
    include 'include/php-utils/products_handler.php';
    $output = ob_get_clean();
    
    echo "<h3>Output:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} catch (Exception $e) {
    echo "<h3>Errore:</h3>";
    echo "<p>Messaggio: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Linea: " . $e->getLine() . "</p>";
    echo "<h3>Stack trace:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Controlla se il file di debug è stato creato
if (file_exists('include/php-utils/debug.log')) {
    echo "<h3>Debug log:</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents('include/php-utils/debug.log')) . "</pre>";
}
?>
