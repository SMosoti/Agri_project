<?php
$conn = new mysqli("localhost", "root", "password");

// Creating database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS Agri_Project";
if ($conn->query($sql) === TRUE) {
    echo "✅ Database 'Agri_Project' is ready<br>";
}

// Select the database
$conn->select_db("Agri_Project");
echo "✅ Now connected to database: Agri_Project<br>";
?>