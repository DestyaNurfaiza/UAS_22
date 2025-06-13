<?php
// 1. INISIALISASI & KEAMANAN
// =============================
session_start();
include '../db.php'; // Pastikan path ini benar

// Cek apakah user sudah login dan memiliki role 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    // Jika tidak, alihkan ke halaman login dan hentikan eksekusi skrip
    header("Location: ../index.php");
    exit;
}


// 2. PENGAMBILAN DATA
// =====================
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$saldo = 0; // Default saldo jika tidak ditemukan

// Ambil saldo terkini dari database
// Menggunakan prepared statement untuk keamanan
$query_saldo = $conn->prepare("SELECT saldo FROM users WHERE id = ?");
$query_saldo->bind_param("i", $user_id);
$query_saldo->execute();
$result_saldo = $query_saldo->get_result();
if ($result_saldo->num_rows > 0) {
    $saldo = $result_saldo->fetch_assoc()['saldo'];
}
$query_saldo->close();


// Ambil 5 riwayat transaksi terakhir
$query_transaksi = $conn->prepare("SELECT deskripsi, jumlah, tipe, tanggal FROM transaksi WHERE user_id = ? ORDER BY tanggal DESC LIMIT 5");
$query_transaksi->bind_param("i", $user_id);
$query_transaksi->execute();
$riwayat_transaksi = $query_transaksi->get_result();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Dompetku</title>
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
        <h1 class="text-2xl font-bold text-purple-700">Dompetku<span class="text-blue-300">.</span></h1>
        <div class="flex items-center text-sm">
            <a href="../logout.php" class="ml-4 text-red-500 hover:text-red-700 font-semibold">Logout</a>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-6 py-8">
        
        <h2 class="text-3xl font-bold text-gray-800 mb-6">
            <span>
                Hai, <span class="font-semibold"><?= htmlspecialchars($username) ?>👋</span>
            </span>
        </h2>

        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-8 rounded-2xl shadow-lg mb-8">
            <h3 class="text-lg font-medium opacity-80">Saldo Aktif</h3>
            <p class="text-5xl font-bold mt-2">Rp <?= number_format($saldo, 0, ',', '.') ?></p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            <a href="topup.php" class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300">
                <h3 class="text-xl font-bold text-purple-600 mb-2">Top Up Saldo</h3>
                <p class="text-gray-600">Isi saldo dompet Anda secara instan.</p>
            </a>
            <a href="transaksi.php" class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300">
                <h3 class="text-xl font-bold text-purple-600 mb-2">Riwayat Lengkap</h3>
                <p class="text-gray-600">Lihat semua catatan transaksi Anda.</p>
            </a>
            <a href="profil.php" class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300">
                <h3 class="text-xl font-bold text-purple-600 mb-2">Profil & Pengaturan</h3>
                <p class="text-gray-600">Kelola data dan keamanan akun Anda.</p>
            </a>
        </div>

        <div class="mt-10 bg-white p-6 rounded-xl shadow-md">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Aktivitas Terbaru</h3>
            <div class="space-y-4">
                <?php if ($riwayat_transaksi->num_rows > 0): ?>
                    <?php while($row = $riwayat_transaksi->fetch_assoc()): ?>
                        <div class="flex justify-between items-center border-b border-gray-200 pb-3 last:border-b-0">
                            <div>
                                <p class="font-semibold text-gray-700"><?= htmlspecialchars($row['deskripsi']) ?></p>
                                <p class="text-sm text-gray-500"><?= date('d F Y, H:i', strtotime($row['tanggal'])) ?></p>
                            </div>
                            <p class="font-bold text-lg <?= $row['tipe'] == 'pemasukan' ? 'text-green-500' : 'text-red-500' ?>">
                                <?= $row['tipe'] == 'pemasukan' ? '+' : '-' ?>Rp<?= number_format($row['jumlah'], 0, ',', '.') ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Belum ada aktivitas transaksi.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="text-center py-5 mt-8 text-sm text-gray-500">
        &copy; <?= date('Y') ?> Dompetkur — All rights reserved.
    </footer>

</body>
</html>
<?php
// Menutup koneksi database setelah semua proses selesai
$riwayat_transaksi->close();
$conn->close();
?>