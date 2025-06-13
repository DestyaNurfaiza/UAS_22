<?php
session_start();
include '../db.php';

// KEAMANAN: Cek apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// --- LOGIKA PENCARIAN ---
$where_clauses = [];
$params = [];
$types = '';

// Filter berdasarkan pencarian username
$search_query = $_GET['q'] ?? '';
if (!empty($search_query)) {
    // Menambahkan klausa WHERE untuk mencari username dan juga role
    $where_clauses[] = "username LIKE ?";
    $params[] = "%" . $search_query . "%";
    $types .= 's';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Query utama dengan filter dinamis
$query_sql = "SELECT id, username, role, saldo, created_at 
              FROM users 
              $where_sql
              ORDER BY created_at DESC";

$stmt = $conn->prepare($query_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
// --- AKHIR LOGIKA PENCARIAN ---

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen User - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            transition: background-color 0.2s, color 0.2s; 
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
                    <a href="users.php" class="sidebar-link active">
                        <i class="fa-solid fa-users"></i>
                        <span>Manajemen User</span>
                    </a>
                    <a href="transactions.php" class="sidebar-link">
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
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Manajemen Pengguna</h2>
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
                        <h3 class="text-lg sm:text-xl font-semibold">Daftar Pengguna Sistem</h3>
                        <form method="GET" action="users.php">
                            <input type="text" name="q" id="userSearchInput" placeholder="Cari username..." value="<?= htmlspecialchars($search_query) ?>" class="w-full md:w-48 px-3 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto text-sm" id="usersTable">
                            <thead class="bg-gray-100">
                                <tr class="text-left text-gray-600 uppercase">
                                    <th class="p-3 font-semibold">User ID</th>
                                    <th class="p-3 font-semibold">Username</th>
                                    <th class="p-3 font-semibold">Role</th>
                                    <th class="p-3 font-semibold">Saldo</th>
                                    <th class="p-3 font-semibold">Tanggal Daftar</th>
                                    <th class="p-3 font-semibold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 text-gray-700 whitespace-nowrap">#<?= $row['id'] ?></td>
                                            <td class="p-3 font-medium text-gray-800 whitespace-nowrap"><?= htmlspecialchars($row['username']) ?></td>
                                            <td class="p-3">
                                                <span class="px-3 py-1 text-xs font-medium rounded-full capitalize <?= $row['role'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-800' ?>">
                                                    <?= htmlspecialchars($row['role']) ?>
                                                </span>
                                            </td>
                                            <td class="p-3 font-semibold text-green-600 whitespace-nowrap">Rp <?= number_format($row['saldo'], 0, ',', '.') ?></td>
                                            <td class="p-3 text-gray-600 whitespace-nowrap"><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                            <td class="p-3 text-center">
                                                <?php if ($_SESSION['user_id'] != $row['id']): ?>
                                                    <form method="POST" onsubmit="confirmDelete(event, '<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>', '<?= $row['id'] ?>')">
                                                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="bg-red-500 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-red-600">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-gray-400 font-medium">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-10 text-gray-500">
                                            Tidak ada pengguna yang cocok dengan kriteria Anda.
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
    // --- SCRIPT UNTUK RESPONSIVITAS & INTERAKTIVITAS ---
    
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

    // CATATAN: Fungsionalitas pencarian sekarang ditangani oleh PHP (server-side)
    // dengan me-reload halaman, mirip seperti halaman transactions.php.
    // Script live-search sebelumnya telah dihapus.
    
    // 2. Notifikasi Toast
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
    });

    // 3. Konfirmasi Hapus dengan SweetAlert
    function confirmDelete(event, username, userId) {
        event.preventDefault(); 
        Swal.fire({
            title: 'Apakah Anda yakin?',
            html: `Pengguna <b>${username}</b> dan semua datanya akan dihapus permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_user.php';
                
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = 'user_id';
                hiddenField.value = userId;
                form.appendChild(hiddenField);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // 4. Menampilkan Notifikasi dari URL
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        const error = urlParams.get('error');

        if (msg === 'user_deleted') {
            Toast.fire({ icon: 'success', title: 'Pengguna berhasil dihapus.' });
            // Menghapus parameter dari URL agar notifikasi tidak muncul lagi saat refresh
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (error) {
            Toast.fire({ icon: 'error', title: error });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
</script>

</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>