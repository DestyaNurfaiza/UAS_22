<?php
session_start();
include '../db.php';

// KEAMANAN: Cek apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// --- LOGIKA FILTER DAN PENCARIAN ---
$where_clauses = [];
$params = [];
$types = '';

// Filter berdasarkan tipe transaksi
$filter_tipe = $_GET['tipe'] ?? 'semua';
if ($filter_tipe === 'pemasukan' || $filter_tipe === 'pengeluaran') {
    $where_clauses[] = "t.tipe = ?";
    $params[] = $filter_tipe;
    $types .= 's';
}

// Filter berdasarkan pencarian username
$search_query = $_GET['q'] ?? '';
if (!empty($search_query)) {
    $where_clauses[] = "u.username LIKE ?";
    $params[] = "%" . $search_query . "%";
    $types .= 's';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Query utama dengan filter dinamis
$query_sql = "SELECT t.id, u.username, t.tipe, t.jumlah, t.deskripsi, t.tanggal 
              FROM transaksi t 
              JOIN users u ON t.user_id = u.id 
              $where_sql
              ORDER BY t.tanggal DESC";

$stmt = $conn->prepare($query_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
// --- AKHIR LOGIKA FILTER ---

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Semua Transaksi - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        .sidebar-link { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            padding: 0.75rem 1rem; 
            border-radius: 0.5rem; 
            transition: background-color 0.2s, 
            color 0.2s; 
        }
        .sidebar-link.active { 
            background-color: #EDE9FE; 
            color: #6D28D9; 
            font-weight: 600; 
        }
        .sidebar-link:not(.active):hover { 
            background-color: #F3F4F6; 
        }
        .sidebar-link i {
            width: 1.25rem; 
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="relative min-h-screen md:flex">
        <div id="backdrop" onclick="closeSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-20 md:hidden hidden"></div>

        <aside id="sidebar" class="w-64 bg-white shadow-lg fixed inset-y-0 left-0 z-30 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out">
            <div class="flex flex-col h-full">
                <div class="p-6">
                    <h1 class="text-2xl font-bold text-purple-700">Admin Panel</h1>
                </div>
                <nav class="flex-grow p-4 space-y-2">
                    <a href="dashboard.php" class="sidebar-link">
                        <i class="fa-solid fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="users.php" class="sidebar-link">
                        <i class="fa-solid fa-users"></i>
                        <span>Manajemen User</span>
                    </a>
                    <a href="transactions.php" class="sidebar-link active">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Semua Transaksi</span>
                    </a>
                </nav>
                <div class="p-4">
                    <a href="../logout.php" class="sidebar-link hover:bg-red-50 hover:text-red-600">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </aside>

        <div class="flex-1">
            <header class="bg-white shadow-md p-4 md:p-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <button id="hamburger" class="md:hidden text-gray-600 hover:text-purple-700">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Riwayat Transaksi</h2>
                    </div>
                     <div class="text-right">
                        <p class="font-semibold text-sm sm:text-base"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                        <p class="text-xs sm:text-sm text-gray-500">Administrator</p>
                    </div>
                </div>
            </header>

            <main class="p-4 sm:p-6 md:p-8">
                <div class="bg-white p-4 sm:p-6 rounded-lg shadow-lg">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 gap-4">
                        <h3 class="text-lg sm:text-xl font-semibold">Semua Transaksi Pengguna</h3>
                        <form method="GET" action="transactions.php" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <input type="text" name="q" id="searchInput" placeholder="Cari username..." value="<?= htmlspecialchars($search_query) ?>" class="w-full sm:w-48 px-3 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <div class="flex items-center justify-around sm:justify-start sm:space-x-2 bg-gray-100 p-1 rounded-md">
                                <button type="submit" name="tipe" value="semua" class="flex-1 sm:flex-none text-center px-3 py-1.5 rounded-md text-sm font-medium <?= $filter_tipe === 'semua' ? 'bg-purple-600 text-white shadow' : 'bg-transparent text-gray-700 hover:bg-gray-200' ?>">Semua</button>
                                <button type="submit" name="tipe" value="pemasukan" class="flex-1 sm:flex-none text-center px-3 py-1.5 rounded-md text-sm font-medium <?= $filter_tipe === 'pemasukan' ? 'bg-purple-600 text-white shadow' : 'bg-transparent text-gray-700 hover:bg-gray-200' ?>">Pemasukan</button>
                                <button type="submit" name="tipe" value="pengeluaran" class="flex-1 sm:flex-none text-center px-3 py-1.5 rounded-md text-sm font-medium <?= $filter_tipe === 'pengeluaran' ? 'bg-purple-600 text-white shadow' : 'bg-transparent text-gray-700 hover:bg-gray-200' ?>">Pengeluaran</button>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full table-auto text-sm" id="transactionsTable">
                            <thead class="bg-gray-100">
                                <tr class="text-left text-gray-600 uppercase">
                                    <th class="p-3 font-semibold">Tanggal</th>
                                    <th class="p-3 font-semibold">Username</th>
                                    <th class="p-3 font-semibold">Tipe</th>
                                    <th class="p-3 font-semibold">Jumlah</th>
                                    <th class="p-3 font-semibold">Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 text-gray-600 whitespace-nowrap"><?= date('d M Y, H:i', strtotime($row['tanggal'])) ?></td>
                                            <td class="p-3 font-medium text-gray-800 whitespace-nowrap"><?= htmlspecialchars($row['username']) ?></td>
                                            <td class="p-3">
                                                <span class="px-3 py-1 text-xs font-medium rounded-full capitalize <?= $row['tipe'] === 'pemasukan' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                    <?= htmlspecialchars($row['tipe']) ?>
                                                </span>
                                            </td>
                                            <td class="p-3 font-semibold whitespace-nowrap <?= $row['tipe'] === 'pemasukan' ? 'text-green-600' : 'text-red-600' ?>">
                                                <?= ($row['tipe'] === 'pemasukan' ? '+ ' : '- ') ?>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?>
                                            </td>
                                            <td class="p-3 text-gray-700"><?= htmlspecialchars($row['deskripsi']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-10 text-gray-500">
                                            Tidak ada transaksi yang cocok dengan kriteria Anda.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

<script>
    // --- SCRIPT UNTUK RESPONSIVITAS ---

    // 1. Sidebar Toggle untuk Mobile
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');
    const hamburger = document.getElementById('hamburger');

    hamburger.addEventListener('click', () => {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
    });

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
    }

    // Catatan: Fungsionalitas pencarian pada halaman ini menggunakan submit form PHP,
    // bukan live search JavaScript, karena lebih efisien untuk potentially large datasets
    // dan sudah terintegrasi dengan filter tipe transaksi.
</script>

</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>