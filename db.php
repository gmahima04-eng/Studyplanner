<?php
session_start();  // IMPORTANT: must be FIRST line

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "studyplan_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}
?>
