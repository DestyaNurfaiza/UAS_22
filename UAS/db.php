<?php
$host = "localhost";       // biasanya localhost
$user = "root";            // user MySQL default di XAMPP
$pass = "";                // password kosong (jika tidak diubah)
$db   = "db_dompet";  // nama database kamu

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>

