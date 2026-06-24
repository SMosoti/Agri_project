<?php
$DB_HOST = "127.0.0.1";
$DB_PORT = "3307"; // Change to 3307 if needed
$DB_NAME = "Agri_Project";
$DB_USER = "root";
$DB_PASS = "mokoro";


try{
    $conn = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h1 style='color: green;'>✅ Database Connected Successfully!</h1>";
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>❌ Connection failed: " . $e->getMessage() . "</h1>";
}
?>