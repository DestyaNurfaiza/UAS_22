<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ambil data user dari database
$stmt = $conn->prepare("SELECT username, role, saldo, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Profil Saya - Dompetkur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
      body { 
        font-family: 'Inter', sans-serif; 
      }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

  <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-10">
      <a href="index.php" class="text-2xl font-bold text-purple-700">Dompetkur</a>
      <div class="flex items-center text-sm">
          <span>Halo, <span class="font-semibold"><?= htmlspecialchars($username) ?></span></span>
          <a href="../logout.php" class="ml-4 text-red-500 hover:text-red-700 font-semibold">Logout</a>
      </div>
  </header>

  <main class="flex-grow container mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-gray-800">Profil Saya</h2>
        <a href="index.php" class="text-sm text-purple-600 hover:underline font-semibold">‹ Kembali ke Dashboard</a>
      </div>

      <div class="bg-white p-8 rounded-xl shadow-md">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Informasi Akun</h3>
        <div class="space-y-5">
          <div class="flex justify-between items-center border-b pb-3">
            <span class="font-semibold text-gray-600">Username</span>
            <span class="font-semibold text-gray-800"><?= htmlspecialchars($user['username']) ?></span>
          </div>
          <div class="flex justify-between items-center border-b pb-3">
            <span class="font-semibold text-gray-600">Saldo Saat Ini</span>
            <span class="font-bold text-green-600 text-lg">Rp <?= number_format($user['saldo'], 0, ',', '.') ?></span>
          </div>
          <div class="flex justify-between items-center border-b pb-3">
            <span class="font-semibold text-gray-600">Tanggal Bergabung</span>
            <span class="text-gray-800"><?= date('d F Y', strtotime($user['created_at'])) ?></span>
          </div>
        </div>

        <div class="mt-8 text-center">
          <a href="ubah_password.php" class="w-full inline-block bg-purple-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-purple-700 transition-colors">
            Ubah Password
          </a>
        </div>
      </div>
    </div>
  </main>
  
  <footer class="text-center py-5 mt-8 text-sm text-gray-500">
      &copy; <?= date('Y') ?> Dompetkur — All rights reserved.
  </footer>

</body>
</html>