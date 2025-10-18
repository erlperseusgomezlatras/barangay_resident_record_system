<?php
$servername = "localhost";
$username = "root";   // default for XAMPP
$password = "";       // leave blank unless you set one
$database = "1_barangay_record";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
