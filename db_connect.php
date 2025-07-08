<?php
// db_connect.php
$servername = "localhost";
$username = "root";
$password = "";
$database = "riti";

// Create connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("System temporarily unavailable. Please try again later.");
}
?>