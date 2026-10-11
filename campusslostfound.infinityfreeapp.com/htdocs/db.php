<?php
$host = "sql210.infinityfree.com";
$username = "if0_43068313";
$password = "hlQIkKjv1eT0Ke";
$database = "if0_43068313_campus_lost_found";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>