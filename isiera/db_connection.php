<?php
$servername = "localhost";
$username = "u474266573_isierauser"; // Change if using a different database user
$password = "Isieranisanpablo_01"; // Change if your MySQL user has a password
$dbname = "u474266573_TapInTime"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
