<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Proses ubah password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Ambil password lama dari DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($old_pass, $user['password'])) {
        $_SESSION['message_type'] = "error";
        $_SESSION['message'] = "Password lama yang Anda masukkan salah.";
    } elseif (strlen($new_pass) < 6) {
        $_SESSION['message_type'] = "error";
        $_SESSION['message'] = "Password baru minimal harus 6 karakter.";
    } elseif ($new_pass !== $confirm_pass) {
        $_SESSION['message_type'] = "error";
        $_SESSION['message'] = "Konfirmasi password baru tidak cocok.";
    } else {
        $new_hashed = password_hash($new_pass, PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new_hashed, $user_id);
        if ($update->execute()) {
            $_SESSION['message_type'] = "success";
            $_SESSION['message'] = "Password Anda telah berhasil diubah.";
        } else {
            $_SESSION['message_type'] = "error";
            $_SESSION['message'] = "Terjadi kesalahan. Gagal mengubah password.";
        }
        $update->close();
    }
    $stmt->close();
    header("Location: ubah_password.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ubah Password - Dompetkur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
      body { 
        font-family: 'Inter', sans-serif; 
      }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

  <header class="bg-white shadow-sm px-4 sm:px-6 py-4 flex justify-between items-center sticky top-0 z-10">
      <a href="index.php" class="text-xl sm:text-2xl font-bold text-purple-700">Dompetkur</a>
      <div class="flex items-center text-sm sm:text-base">
          <span>Halo, <span class="font-semibold"><?= htmlspecialchars($username) ?></span></span>
          <a href="../logout.php" class="ml-4 text-red-500 hover:text-red-700 font-semibold">Logout</a>
      </div>
  </header>

  <main class="flex-grow container mx-auto px-4 sm:px-6 py-6 sm:py-8">
    <div class="max-w-2xl mx-auto">
      <div class="flex flex-col items-start sm:flex-row sm:justify-between sm:items-center mb-6">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2 sm:mb-0">Ubah Password</h2>
        <a href="index.php" class="text-sm text-purple-600 hover:underline font-semibold">‹ Kembali ke Dashboard</a>
      </div>

      <?php if (isset($_SESSION['message'])): ?>
        <div class="p-4 mb-4 rounded-lg text-sm <?= $_SESSION['message_type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>" role="alert">
            <?= $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        </div>
      <?php endif; ?>

      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
        <form method="POST" class="space-y-6">
          
          <div>
            <label for="old_password" class="block font-semibold text-gray-700">Password Lama</label>
            <div class="relative mt-1">
              <input type="password" id="old_password" name="old_password" required class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition pr-10">
              <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-purple-600 password-toggle-btn">
                  <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          
          <div>
            <label for="new_password" class="block font-semibold text-gray-700">Password Baru</label>
            <div class="relative mt-1">
              <input type="password" id="new_password" name="new_password" required class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition pr-10">
              <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-purple-600 password-toggle-btn">
                  <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          
          <div>
            <label for="confirm_password" class="block font-semibold text-gray-700">Konfirmasi Password Baru</label>
            <div class="relative mt-1">
              <input type="password" id="confirm_password" name="confirm_password" required class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition pr-10">
              <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-purple-600 password-toggle-btn">
                  <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          
          <button type="submit" class="w-full bg-purple-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-4 focus:ring-purple-300 transition-all duration-300 transform hover:scale-[1.02]">
            Simpan Perubahan
          </button>
        </form>
      </div>
    </div>
  </main>

  <footer class="text-center py-5 mt-8 text-sm text-gray-500">
      &copy; <?= date('Y') ?> Dompetkur — All rights reserved.
  </footer>

  <script>
    // Logika untuk fitur tampilkan/sembunyikan password
    document.addEventListener('DOMContentLoaded', function() {
      const toggleButtons = document.querySelectorAll('.password-toggle-btn');

      toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
          // Dapatkan input field yang berhubungan dengan tombol ini
          const input = this.previousElementSibling;
          // Dapatkan ikon di dalam tombol
          const icon = this.querySelector('i');

          if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
          } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
          }
        });
      });
    });
  </script>

</body>
</html>