<?php
// database connection

    // development architecture
    $config['localhost']['host'] = "localhost";
    $config['localhost']['user'] = "root";
    $config['localhost']['password'] = "";
    $config['localhost']['db'] = "database";

    // deployment architecture 
    $config['sql.example.com']['host'] = "localhost";
    $config['sql.example.com']['user'] = "root";
    $config['sql.example.com']['password'] = "";
    $config['sql.example.com']['db'] = "database";

    // connection
    $server_name = $_SERVER["SERVER_NAME"] ?? 'localhost';
    
    // Se il server name non è nelle configurazioni, usa localhost come default
    if (!isset($config[$server_name])) {
        $server_name = 'localhost';
    }
    
    $connessione = new mysqli(
        $config[$server_name]['host'],
        $config[$server_name]['user'],
        $config[$server_name]['password'],
        $config[$server_name]['db']
    );

    // check connection
    if ($connessione->connect_error) {
        die('Connection failed: ' . $connessione->connect_error);
    }

    ?>