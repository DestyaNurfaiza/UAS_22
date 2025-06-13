<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    header("Location: users.php?error=Permintaan tidak valid.");
    exit;
}

$user_id_to_delete = (int)$_POST['user_id'];

if ($user_id_to_delete === (int)$_SESSION['user_id']) {
    header("Location: users.php?error=Anda tidak dapat menghapus akun Anda sendiri.");
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
$stmt->bind_param("i", $user_id_to_delete);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        header("Location: users.php?msg=user_deleted");
    } else {
        header("Location: users.php?error=Pengguna tidak ditemukan atau bukan user biasa.");
    }
} else {
    header("Location: users.php?error=Terjadi kesalahan pada database saat menghapus.");
}

$stmt->close();
$conn->close();
exit;
?>