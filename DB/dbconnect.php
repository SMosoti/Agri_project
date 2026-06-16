<?php

$DB_HOST = "localhost";
$DB_PORT = "3306";
$DB_NAME = "Agri_Project"; // <-- change this
$DB_USER = "root";
$DB_PASS = "mokoro"; // <-- change this

try {
    $conn = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "Database connected successfully" . PHP_EOL;
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
    
}
?>