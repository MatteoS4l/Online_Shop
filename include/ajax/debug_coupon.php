<?php
header('Content-Type: application/json');
session_start();
$response = [
  'post' => $_POST,
  'session' => $_SESSION,
  'php_version' => phpversion(),
  'headers_sent' => headers_sent(),
  'output_buffering' => ini_get('output_buffering'),
];
echo json_encode($response);
