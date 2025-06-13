<?php
session_start();
include '../db.php';

// Redirect jika belum login atau bukan user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Proses form jika dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // REVISI: Kembali ke proses integer, menghapus titik dari nominal
    $amount = (int)str_replace('.', '', $_POST['amount']);
    $method = $_POST['method'] ?? '';

    if ($amount >= 10000 && !empty($method)) {
        // Simpan ke database
        $stmt = $conn->prepare("INSERT INTO topup_requests (user_id, amount, method) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $user_id, $amount, $method);
        $stmt->execute();
        $stmt->close();
        
        // REVISI: Pesan sukses dengan format integer
        $success_message = "Permintaan top up sebesar Rp " . number_format($amount, 0, ',', '.') . " telah dibuat.<br>Silakan lakukan pembayaran menggunakan: <b>" . htmlspecialchars($method) . "</b>.";
        $_SESSION['message_type'] = 'success';
        $_SESSION['message'] = $success_message;

        header("Location: transaksi.php");
        exit;
    } else {
        $_SESSION['message_type'] = 'error';
        $_SESSION['message'] = "Nominal top up minimal Rp 10.000 dan metode pembayaran wajib diisi.";
        header("Location: topup.php");
        exit;
    }
}

// Data untuk metode pembayaran (logika VA acak tetap ada)
$va_banks = [
    'BCA' => '1122',
    'Mandiri' => '6677',
    'BNI' => '3344',
    'BRI' => '8888'
];
$other_methods = ['Gerai Retail (Indomaret/Alfamart)', 'Kartu Kredit/Debit'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Top Up Saldo - Dompetkur</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
      body { font-family: 'Inter', sans-serif; }
      .method-label:has(input:checked), .bank-label:has(input:checked) {
          border-color: #6D28D9; background-color: #F5F3FF;
          box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
      }
      .method-label:has(input:checked) span, .bank-label:has(input:checked) span { color: #5B21B6; }
      .radio-custom-circle {
          width: 1.25rem; height: 1.25rem; border: 2px solid #D1D5DB; border-radius: 9999px;
          display: flex; align-items: center; justify-content: center; flex-shrink: 0;
      }
      .radio-custom-circle-inner {
          width: 0.625rem; height: 0.625rem; background-color: #6D28D9; border-radius: 9999px;
          transform: scale(0); transition: transform 0.2s;
      }
      input:checked + .radio-custom-circle .radio-custom-circle-inner { transform: scale(1); }
      input:checked + .radio-custom-circle { border-color: #6D28D9; }
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
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2 sm:mb-0">Top Up Saldo</h2>
        <a href="index.php" class="text-sm text-purple-600 hover:underline font-semibold">‹ Kembali ke Dashboard</a>
      </div>

      <?php if (isset($_SESSION['message'])): ?>
        <div class="p-4 mb-4 rounded-lg <?= $_SESSION['message_type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>" role="alert">
            <?= $_SESSION['message']; unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        </div>
      <?php endif; ?>

      <div class="bg-white p-6 sm:p-8 rounded-xl shadow-md">
        <form id="topup-form" method="POST" class="space-y-6">
          <input type="hidden" name="method" id="final_method" required>

          <div>
            <label for="amount" class="block text-base sm:text-lg font-semibold text-gray-700">Jumlah Top Up</label>
            <div class="mt-2 relative">
              <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 font-bold text-lg sm:text-xl">Rp</span>
              <input type="text" id="amount" name="amount" class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-xl sm:text-2xl font-bold focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition" placeholder="0" required oninput="formatRupiah(this)">
            </div>
             <p class="text-xs text-gray-500 mt-2">Nominal top up minimal Rp 10.000.</p>
          </div>

          <div>
            <h3 class="text-base sm:text-lg font-semibold text-gray-700 mb-3">Pilih Metode Pembayaran</h3>
            <div class="space-y-4">
              
              <label class="method-label flex items-center border-2 border-gray-200 p-4 rounded-lg cursor-pointer">
                  <input type="radio" name="method_choice" value="va" class="hidden">
                  <div class="radio-custom-circle mr-4"><div class="radio-custom-circle-inner"></div></div>
                  <span class="font-semibold text-gray-700">Transfer Virtual Account</span>
              </label>
              
              <div id="bank-options" class="hidden pl-8 pt-2 pb-1 space-y-3">
                  <p class="text-sm font-semibold text-gray-600">Pilih Bank:</p>
                  <?php foreach ($va_banks as $bank => $prefix): ?>
                  <label class="bank-label flex items-center border-2 border-transparent p-3 rounded-lg cursor-pointer hover:bg-gray-50">
                      <input type="radio" name="bank_choice" data-prefix="<?= $prefix ?>" data-bank-name="<?= $bank ?>" class="hidden">
                      <div class="radio-custom-circle mr-4"><div class="radio-custom-circle-inner"></div></div>
                      <span class="font-semibold text-gray-700"><?= $bank ?>: <span class="font-bold text-purple-700" id="va-display-<?= strtolower($bank) ?>"></span></span>
                  </label>
                  <?php endforeach; ?>
              </div>

              <?php foreach ($other_methods as $method): ?>
                <label class="method-label flex items-center border-2 border-gray-200 p-4 rounded-lg cursor-pointer">
                    <input type="radio" name="method_choice" value="<?= htmlspecialchars($method) ?>" class="hidden">
                    <div class="radio-custom-circle mr-4"><div class="radio-custom-circle-inner"></div></div>
                    <span class="font-semibold text-gray-700"><?= htmlspecialchars($method) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <button type="submit" class="w-full bg-purple-600 text-white font-bold py-3 sm:py-4 px-6 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-4 focus:ring-purple-300">
            Buat Permintaan Top Up
          </button>
        </form>
      </div>
    </div>
  </main>
  
  <footer class="text-center py-5 mt-8 text-sm text-gray-500">
      &copy; <?= date('Y') ?> Dompetkur — All rights reserved.
  </footer>
  
  <script>
    // REVISI: Fungsi format Rupiah kembali ke format integer
    function formatRupiah(input) {
      let value = input.value.replace(/[^,\d]/g, '').toString();
      let split = value.split(',');
      let sisa = split[0].length % 3;
      let rupiah = split[0].substr(0, sisa);
      let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

      if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
      }
      input.value = rupiah;
    }

    // Logika Virtual Account Dinamis (TETAP SAMA)
    const methodChoices = document.querySelectorAll('input[name="method_choice"]');
    const bankOptionsDiv = document.getElementById('bank-options');
    const bankChoices = document.querySelectorAll('input[name="bank_choice"]');
    const finalMethodInput = document.getElementById('final_method');
    const topupForm = document.getElementById('topup-form');
    let generatedVAs = {};

    function generateRandomVA(prefix) {
      const randomPart = Math.floor(10000000 + Math.random() * 90000000).toString();
      return prefix + randomPart;
    }

    methodChoices.forEach(radio => {
        radio.addEventListener('change', (e) => {
            const selectedMethod = e.target.value;
            
            if (selectedMethod === 'va') {
                bankOptionsDiv.classList.remove('hidden');
                bankChoices.forEach(bankRadio => {
                    const bankName = bankRadio.dataset.bankName.toLowerCase();
                    const vaDisplay = document.getElementById(`va-display-${bankName}`);
                    
                    if (!generatedVAs[bankName]) {
                        generatedVAs[bankName] = generateRandomVA(bankRadio.dataset.prefix);
                    }
                    vaDisplay.textContent = generatedVAs[bankName];
                    bankRadio.required = true;
                });
                finalMethodInput.value = '';
            } else {
                bankOptionsDiv.classList.add('hidden');
                bankChoices.forEach(bank => {
                    bank.required = false;
                    bank.checked = false;
                });
                finalMethodInput.value = selectedMethod;
            }
        });
    });

    bankChoices.forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (e.target.checked) {
                const bankName = e.target.dataset.bankName;
                const vaNumber = document.getElementById(`va-display-${bankName.toLowerCase()}`).textContent;
                finalMethodInput.value = `VA ${bankName}: ${vaNumber}`;
            }
        });
    });

    topupForm.addEventListener('submit', (e) => {
        const selectedMethod = document.querySelector('input[name="method_choice"]:checked');
        if (selectedMethod && selectedMethod.value === 'va') {
            const selectedBank = document.querySelector('input[name="bank_choice"]:checked');
            if (!selectedBank) {
                e.preventDefault();
                alert('Silakan pilih salah satu bank untuk transfer Virtual Account.');
            }
        }
    });
  </script>
</body>
</html>