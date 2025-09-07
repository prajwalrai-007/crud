<?php
// db.php
$host = '127.0.0.1';
$db   = 'crud_demo';
$user = 'root';     // change if needed
$pass = '';         // change if needed
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  exit('DB Connection failed: ' . $e->getMessage());
}
