<?php
$host = 'localhost';
$user = 'root'; // Sesuaikan dengan user database Yang Mulia
$pass = '';     // Sesuaikan dengan password database
$db   = 'eduguide_ai';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}
?>