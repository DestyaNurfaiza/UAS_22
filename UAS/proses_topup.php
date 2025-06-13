<?php
session_start();
include '../db.php';

// Atur header untuk respons JSON
header('Content-Type: application/json');

// Fungsi untuk mengirim respons JSON dan menghentikan skrip
function json_response($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// 1. KEAMANAN: Pastikan hanya admin yang login yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    json_response(false, "Akses ditolak. Anda harus login sebagai admin.");
}

// 2. VALIDASI INPUT: Pastikan metode POST dan parameter valid
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, "Permintaan harus menggunakan metode POST.");
}

$request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$request_id || !in_array($action, ['confirm', 'reject'])) {
    json_response(false, "Input yang diberikan tidak valid (ID atau Aksi tidak sesuai).");
}

// 3. PROSES DATABASE DENGAN TRANSAKSI
$conn->begin_transaction();

try {
    // Ambil data permintaan top-up dan kunci baris untuk pembaruan
    $query = $conn->prepare("SELECT user_id, amount, status FROM topup_requests WHERE id = ? FOR UPDATE");
    $query->bind_param("i", $request_id);
    $query->execute();
    $request = $query->get_result()->fetch_assoc();
    $query->close();

    if (!$request) {
        throw new Exception("Permintaan top up tidak ditemukan.");
    }

    if ($request['status'] !== 'pending') {
        throw new Exception("Permintaan ini sudah diproses sebelumnya (status: " . $request['status'] . ").");
    }

    $user_id = $request['user_id'];
    $amount = $request['amount'];
    $message = '';

    if ($action === 'confirm') {
        // --- Aksi Konfirmasi ---
        $new_status = 'confirmed';

        // 1. Update status di topup_requests
        $stmt1 = $conn->prepare("UPDATE topup_requests SET status = ? WHERE id = ?");
        $stmt1->bind_param("si", $new_status, $request_id);
        $stmt1->execute();
        $stmt1->close();

        // 2. Update saldo pengguna
        $stmt2 = $conn->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?");
        $stmt2->bind_param("di", $amount, $user_id);
        $stmt2->execute();
        $stmt2->close();

        // 3. Catat ke tabel riwayat transaksi
        $deskripsi = "Top up saldo senilai Rp " . number_format($amount, 0, ',', '.');
        $stmt3 = $conn->prepare("INSERT INTO transaksi (user_id, tipe, jumlah, deskripsi) VALUES (?, 'pemasukan', ?, ?)");
        $stmt3->bind_param("ids", $user_id, $amount, $deskripsi);
        $stmt3->execute();
        $stmt3->close();
        
        $message = "Top up berhasil dikonfirmasi!";

    } else { // action === 'reject'
        // --- Aksi Penolakan ---
        $new_status = 'rejected';
        $stmt = $conn->prepare("UPDATE topup_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $request_id);
        $stmt->execute();
        $stmt->close();
        
        $message = "Top up berhasil ditolak.";
    }

    // Ambil data statistik terbaru untuk dikirim kembali ke dashboard
    $new_total_saldo = $conn->query("SELECT SUM(saldo) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'] ?? 0;
    $new_total_pending = $conn->query("SELECT COUNT(id) as total FROM topup_requests WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;

    // Jika semua query berhasil, commit transaksi
    $conn->commit();
    
    $responseData = [
        'new_total_saldo' => $new_total_saldo,
        'new_total_pending' => $new_total_pending
    ];
    json_response(true, $message, $responseData);

} catch (Exception $e) {
    // Jika terjadi kesalahan, batalkan semua perubahan
    $conn->rollback();
    json_response(false, "Terjadi kesalahan pada database: " . $e->getMessage());
}

$conn->close();
?>