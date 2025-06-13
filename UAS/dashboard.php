<?php
session_start();
include '../db.php'; // Pastikan path ini benar

// KEAMANAN: Cek apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// PENGAMBILAN DATA STATISTIK AWAL
$total_pending = $conn->query("SELECT COUNT(id) as total FROM topup_requests WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;
$total_users = $conn->query("SELECT COUNT(id) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'] ?? 0;
$total_saldo = $conn->query("SELECT SUM(saldo) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'] ?? 0;

// PENGAMBILAN DATA TABEL PERMINTAAN TOP UP (dengan filter)
$view_mode = $_GET['view'] ?? 'all'; // Default 'all', bisa 'pending'
$where_clause = "";
if ($view_mode === 'pending') {
    $where_clause = "WHERE tr.status = 'pending'";
}

$query_requests = "SELECT tr.id, tr.user_id, tr.amount, tr.status, tr.request_date, u.username, tr.method 
                   FROM topup_requests tr 
                   JOIN users u ON tr.user_id = u.id 
                   $where_clause
                   ORDER BY tr.request_date DESC";
$result = $conn->query($query_requests);

?>

</script>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Dompet Digital</title>
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
                    <h1 class="text-2xl font-bold text-purple-700">Dompetku<span class="text-blue-300">.</span></h1>
                </div>
                <nav class="flex-grow p-4 space-y-2">
                    <a href="dashboard.php" class="sidebar-link active">
                        <i class="fa-solid fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="users.php" class="sidebar-link">
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
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Dashboard</h2>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-sm sm:text-base"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                        <p class="text-xs sm:text-sm text-gray-500">Administrator</p>
                    </div>
                </div>
            </header>

            <main class="p-4 sm:p-6 md:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-8">
                    <div class="bg-white p-5 sm:p-6 rounded-lg shadow-md">
                        <h3 class="text-sm sm:text-base font-medium text-gray-500">Permintaan Pending</h3>
                        <p id="total-pending-display" class="text-3xl sm:text-4xl font-bold text-orange-500 mt-2"><?= $total_pending ?></p>
                    </div>
                    <div class="bg-white p-5 sm:p-6 rounded-lg shadow-md">
                        <h3 class="text-sm sm:text-base font-medium text-gray-500">Total Pengguna</h3>
                        <p class="text-3xl sm:text-4xl font-bold text-blue-500 mt-2"><?= $total_users ?></p>
                    </div>
                    <div class="bg-white p-5 sm:p-6 rounded-lg shadow-md">
                        <h3 class="text-sm sm:text-base font-medium text-gray-500">Total Saldo Pengguna</h3>
                        <p id="total-saldo-display" class="text-3xl sm:text-4xl font-bold text-green-500 mt-2">Rp <?= number_format($total_saldo, 0, ',', '.') ?></p>
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-6 rounded-lg shadow-lg">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 gap-4">
                         <h3 class="text-lg sm:text-xl font-semibold">Daftar Permintaan Top Up</h3>
                         <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                             <input type="text" id="searchInput" placeholder="Cari username..." class="w-full sm:w-48 px-3 py-1.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                             <div class="flex items-center justify-around sm:justify-start sm:space-x-2 bg-gray-100 p-1 rounded-md">
                                <a href="?view=all" class="flex-1 sm:flex-none text-center px-3 py-1.5 rounded-md text-sm font-medium <?= $view_mode === 'all' ? 'bg-purple-600 text-white shadow' : 'bg-transparent text-gray-700 hover:bg-gray-200' ?>">Semua</a>
                                <a href="?view=pending" class="flex-1 sm:flex-none text-center px-3 py-1.5 rounded-md text-sm font-medium <?= $view_mode === 'pending' ? 'bg-purple-600 text-white shadow' : 'bg-transparent text-gray-700 hover:bg-gray-200' ?>">Pending</a>
                             </div>
                         </div>
                    </div>
                   
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto text-sm" id="topupTable">
                            <thead class="bg-gray-100">
                                <tr class="text-left text-gray-600 uppercase">
                                    <th class="p-3 font-semibold">Username</th>
                                    <th class="p-3 font-semibold">Nominal</th>
                                    <th class="p-3 font-semibold">Metode</th>
                                    <th class="p-3 font-semibold">Tanggal</th>
                                    <th class="p-3 font-semibold">Status</th>
                                    <th class="p-3 font-semibold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-3 font-medium text-gray-800 whitespace-nowrap"><?= htmlspecialchars($row['username']) ?></td>
                                            <td class="p-3 font-semibold text-green-600 whitespace-nowrap">
                                                Rp <?= number_format($row['amount'], 0, ',', '.') ?>
                                            </td><td class="p-3 text-gray-600 whitespace-nowrap"><?= htmlspecialchars($row['method'] ?? '-') ?></td>
                                            <td class="p-3 text-gray-600 whitespace-nowrap"><?= date('d M Y, H:i', strtotime($row['request_date'])) ?></td>
                                            <td class="p-3 status-cell">
                                                <span class="px-3 py-1 text-xs font-medium rounded-full <?= ['pending'=>'bg-orange-100 text-orange-800','confirmed'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800'][$row['status']] ?? 'bg-gray-100 text-gray-800' ?> capitalize">
                                                    <?= htmlspecialchars($row['status']) ?>
                                                </span>
                                            </td>
                                            <td class="p-3 text-center action-cell">
                                                <?php if ($row['status'] === 'pending'): ?>
                                                    <div class="flex flex-col sm:flex-row justify-center gap-2">
                                                        <button onclick="handleAction('confirm', <?= $row['id'] ?>, '<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>', <?= $row['amount'] ?>)" class="bg-blue-500 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-blue-600">
                                                            Konfirmasi
                                                        </button>
                                                        <button onclick="handleAction('reject', <?= $row['id'] ?>, '<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>', <?= $row['amount'] ?>)" class="bg-red-500 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-red-600">
                                                           Tolak
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400 font-medium">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-10" id="no-data-initial">
                                            <p class="text-gray-500">
                                                <?= $view_mode === 'pending' ? 'Tidak ada permintaan top up yang sedang pending.' : 'Belum ada riwayat permintaan top up.' ?>
                                            </p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                 <tr class="hidden" id="no-data-row">
                                     <td colspan="6" class="text-center py-10">
                                         <p class="text-gray-500">Tidak ada data yang cocok dengan pencarian Anda.</p>
                                     </td>
                                 </tr>
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

    // 2. Live Search
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.querySelector("#topupTable tbody");
    const tableRows = tableBody.querySelectorAll("tr:not(#no-data-row)");
    const noDataRow = document.getElementById('no-data-row');
    const initialNoDataRow = document.getElementById('no-data-initial');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleRows = 0;
        tableRows.forEach(row => {
            const username = row.cells[0].textContent.toLowerCase();
            if (username.includes(searchTerm)) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });
        
        if (initialNoDataRow) initialNoDataRow.style.display = 'none';
        noDataRow.style.display = (visibleRows === 0 && tableRows.length > 0) ? '' : 'none';
    });

    // 3. Notifikasi Toast
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    // 4. Proses Aksi Konfirmasi/Tolak dengan AJAX
    function handleAction(action, requestId, username, amount) {
        const actionText = action === 'confirm' ? 'Konfirmasi' : 'Tolak';
        const amountFormatted = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

        Swal.fire({
            title: `Anda yakin ingin ${actionText}?`,
            html: `Permintaan top up dari <b>${username}</b> sebesar <b>${amountFormatted}</b> akan diproses.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: action === 'confirm' ? '#3b82f6' : '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: `Ya, ${actionText}!`,
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                try {
                    // PERUBAHAN DI SINI: Memanggil skrip baru yang lebih andal
                    const response = await fetch('proses_topup.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `request_id=${requestId}&action=${action}`
                    });
                    if (!response.ok) {
                        // Menangani error HTTP seperti 404 atau 500
                        throw new Error(`Server merespon dengan status: ${response.statusText}`);
                    }
                    const data = await response.json();
                    if (!data.success) {
                        // Menangani error logis yang dikirim dari backend
                        throw new Error(data.message);
                    }
                    return data; // Mengembalikan data jika sukses
                } catch (error) {
                    Swal.showValidationMessage(`Permintaan gagal: ${error.message}`);
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                // `result.value` adalah data yang dikembalikan dari preConfirm
                Toast.fire({ icon: 'success', title: result.value.message });
                
                // Perbarui Kartu Statistik (menggunakan data dari `result.value.data`)
                const saldoDisplay = document.getElementById('total-saldo-display');
                const pendingDisplay = document.getElementById('total-pending-display');
                if (saldoDisplay && result.value.data.new_total_saldo !== undefined) {
                    saldoDisplay.textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(result.value.data.new_total_saldo);
                }
                if (pendingDisplay && result.value.data.new_total_pending !== undefined) {
                    pendingDisplay.textContent = result.value.data.new_total_pending;
                }

                // Perbarui Baris Tabel (logika ini tetap sama)
                const row = document.querySelector(`button[onclick*="handleAction('${action}', ${requestId},"]`).closest('tr');
                if (row) {
                    if ('<?= $view_mode ?>' === 'pending') {
                        row.style.transition = 'opacity 0.5s ease';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            // Cek jika tabel menjadi kosong
                            const remainingRows = tableBody.querySelectorAll("tr:not(#no-data-row):not([style*='display: none'])").length;
                            if (initialNoDataRow && remainingRows === 0) {
                                 initialNoDataRow.style.display = '';
                            }
                        }, 500);
                    } else {
                        const statusCell = row.querySelector('.status-cell');
                        const actionCell = row.querySelector('.action-cell');
                        const newStatus = action === 'confirm' ? 'confirmed' : 'rejected';
                        const statusClasses = { 'confirmed': 'bg-green-100 text-green-800', 'rejected': 'bg-red-100 text-red-800' };
                        statusCell.innerHTML = `<span class="px-3 py-1 text-xs font-medium rounded-full ${statusClasses[newStatus]} capitalize">${newStatus}</span>`;
                        actionCell.innerHTML = `<span class="text-gray-400 font-medium">-</span>`;
                    }
                }
            }
        });
    }
</script>
</body>
</html>
<?php $conn->close(); ?>