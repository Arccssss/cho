<?php
// Database connection for cho_consent_system
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cho_consent_system';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
