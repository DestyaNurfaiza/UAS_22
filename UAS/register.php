<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        echo "<script>alert('Username dan password tidak boleh kosong!'); window.history.back();</script>";
        exit;
    }

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Username sudah digunakan. Silakan pilih yang lain.'); window.history.back();</script>";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user';

    $insert = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $insert->bind_param("sss", $username, $hashed_password, $role);

    if ($insert->execute()) {
        $_SESSION['user_id'] = $insert->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        header("Location: user/index.php");
    } else {
        echo "<script>alert('Registrasi gagal. Coba lagi.'); window.history.back();</script>";
    }
}
?>
