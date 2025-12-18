<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "capstone2";
$port = 3306;

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    // DO NOT die() or echo here
    $conn = null;
}
    