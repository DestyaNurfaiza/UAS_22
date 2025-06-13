<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Query yang sudah baik untuk menggabungkan dua tabel
$query_transaksi = $conn->prepare(
    "SELECT 'transaksi' as source, deskripsi, jumlah, tipe, tanggal 
     FROM transaksi WHERE user_id = ?
     UNION ALL
     SELECT 'topup' as source, CONCAT('Permintaan Top Up via ', method) as deskripsi, amount as jumlah, status as tipe, request_date as tanggal
     FROM topup_requests WHERE user_id = ?
     ORDER BY tanggal DESC"
);
$query_transaksi->bind_param("ii", $user_id, $user_id);
$query_transaksi->execute();
$riwayat_transaksi = $query_transaksi->get_result();

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Transaksi - Dompetkur</title>
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
    <div class="max-w-4xl mx-auto">
      
      <div class="flex flex-col items-start sm:flex-row sm:justify-between sm:items-center mb-6">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2 sm:mb-0">Riwayat Transaksi</h2>
        <a href="index.php" class="text-sm text-purple-600 hover:underline font-semibold">‹ Kembali ke Dashboard</a>
      </div>
      
      <?php if (isset($_SESSION['message'])): ?>
        <div class="p-4 mb-4 rounded-lg bg-green-100 text-green-800" role="alert">
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
      <?php endif; ?>

      <div class="bg-white p-4 sm:p-6 rounded-xl shadow-md">
        <div class="space-y-5">
          <?php if ($riwayat_transaksi->num_rows > 0): ?>
              <?php while($row = $riwayat_transaksi->fetch_assoc()): ?>
                  <?php
                      // Logika untuk menentukan ikon dan warna berdasarkan tipe transaksi
                      $icon = '';
                      $iconColor = '';
                      $bgColor = '';
                      $amountPrefix = '';
                      $amountColor = '';
                      $statusText = '';
                      $statusColor = '';

                      if ($row['source'] === 'transaksi') {
                          if ($row['tipe'] == 'pemasukan') {
                              $icon = 'fa-arrow-down';
                              $iconColor = 'text-green-600';
                              $bgColor = 'bg-green-100';
                              $amountPrefix = '+';
                              $amountColor = 'text-green-600';
                          } else { // pengeluaran
                              $icon = 'fa-arrow-up';
                              $iconColor = 'text-red-600';
                              $bgColor = 'bg-red-100';
                              $amountPrefix = '-';
                              $amountColor = 'text-red-500';
                          }
                      } else { // topup_requests
                          $status = $row['tipe'];
                          $amountPrefix = '+';
                          $amountColor = 'text-blue-500';

                          if ($status === 'confirmed') {
                              $icon = 'fa-check';
                              $iconColor = 'text-green-600';
                              $bgColor = 'bg-green-100';
                              $statusText = 'Berhasil';
                              $statusColor = 'text-green-600';
                          } else if ($status === 'pending') {
                              $icon = 'fa-clock';
                              $iconColor = 'text-yellow-600';
                              $bgColor = 'bg-yellow-100';
                              $statusText = 'Pending';
                              $statusColor = 'text-yellow-600';
                          } else { // rejected
                              $icon = 'fa-times';
                              $iconColor = 'text-red-600';
                              $bgColor = 'bg-red-100';
                              $statusText = 'Ditolak';
                              $statusColor = 'text-red-600';
                          }
                      }
                  ?>
                  <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-5 last:border-b-0">
                      <div class="flex items-start gap-4">
                          <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center <?= $bgColor ?>">
                              <i class="fas <?= $icon ?> <?= $iconColor ?>"></i>
                          </div>
                          <div>
                              <p class="font-bold text-gray-800 text-base leading-tight"><?= htmlspecialchars($row['deskripsi']) ?></p>
                              <p class="text-sm text-gray-500 mt-1"><?= date('d F Y, H:i', strtotime($row['tanggal'])) ?></p>
                          </div>
                      </div>
                      <div class="text-right flex-shrink-0">
                          <p class="font-bold text-base sm:text-lg <?= $amountColor ?>">
                              <?= $amountPrefix ?>Rp<?= number_format($row['jumlah'], 0, ',', '.') ?>
                          </p>
                          <?php if ($statusText): ?>
                              <p class="text-xs font-semibold capitalize <?= $statusColor ?>"><?= $statusText ?></p>
                          <?php endif; ?>
                      </div>
                  </div>
              <?php endwhile; ?>
          <?php else: ?>
              <div class="text-center py-12">
                  <i class="fas fa-search-dollar fa-3x text-gray-300"></i>
                  <p class="text-gray-500 mt-4">Belum ada riwayat transaksi.</p>
              </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
  
  <footer class="text-center py-5 mt-8 text-sm text-gray-500">
      &copy; <?= date('Y') ?> Dompetkur — All rights reserved.
  </footer>

</body>
</html>