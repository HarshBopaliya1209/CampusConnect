<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "campus_connect";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

?>