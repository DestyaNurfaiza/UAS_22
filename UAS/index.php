<?php
session_start();
include 'db.php';

// Logika ini tetap sama, untuk mengarahkan pengguna yang sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } else {
        header("Location: user/index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>Dompetku - Dompet Digital Modern Anda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">

    <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-purple-600 to-blue-500 -z-10"></div>
    
    <div class="flex flex-col min-h-screen">
    
        <div class="container mx-auto px-4 sm:px-6">
            <header class="flex justify-between items-center py-5 sm:py-6">
                <h1 class="text-2xl md:text-3xl font-bold text-white">Dompetku<span class="text-blue-300">.</span></h1>
                <div class="space-x-2 flex items-center">
                    <button onclick="showModal('loginModal')" class="bg-white/20 text-white px-4 py-2 rounded-full font-semibold hover:bg-white/30 backdrop-blur-sm text-sm sm:px-5">
                        Login
                    </button>
                    <button onclick="showModal('registerModal')" class="bg-white text-purple-700 p-2 sm:px-5 sm:py-2 rounded-full font-semibold shadow-lg hover:bg-gray-100 flex items-center text-sm">
                        <i class="fas fa-user-plus text-base sm:hidden"></i>
                        <span class="hidden sm:inline">Daftar Sekarang</span>
                    </button>
                </div>
            </header>

            <main class="flex-grow flex items-center py-12 sm:py-16 md:py-20 lg:py-24">
                <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                    <div class="text-white text-center md:text-left">
                        <h2 class="text-4xl sm:text-5xl md:text-5xl lg:text-6xl xl:text-7xl font-extrabold mb-4 leading-tight">
                            Transaksi Cepat & Aman di Ujung Jari.
                        </h2>
                        <p class="text-base sm:text-lg text-white/80 mb-8 max-w-lg mx-auto md:mx-0">
                            Kelola keuangan Anda dengan mudah. Top up, simpan, dan kirim uang ke mana saja, kapan saja, dengan Dompetku.
                        </p>
                        <button onclick="showModal('registerModal')" class="bg-white text-purple-600 px-6 py-3 sm:px-8 rounded-full font-bold shadow-2xl hover:bg-gray-200">
                            Mulai Sekarang <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                    <div class="mt-8 md:mt-0">
                        <img src="https://cdn-icons-png.flaticon.com/512/2920/2920319.png" alt="Dompet Ilustrasi" class="w-full max-w-[18rem] sm:max-w-sm md:max-w-md lg:max-w-lg mx-auto drop-shadow-2xl">
                    </div>
                </div>
            </main>
        </div>

    </div> <div id="loginModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-20 p-4">
        <form action="login.php" method="POST" class="bg-white text-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-sm relative">
            <button type="button" onclick="closeAllModals()" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-2xl">&times;</button>
            <h3 class="text-2xl font-bold text-center mb-2">Selamat Datang Kembali</h3>
            <p class="text-center text-gray-500 mb-6">Login untuk melanjutkan.</p>
            
            <?php if(isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4 text-sm" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
            <?php endif; ?>

            <div class="space-y-4">
                <input type="text" name="username" placeholder="Username" required class="w-full border-gray-300 border p-3 rounded-lg focus:ring-2 focus:ring-purple-500">
                <input type="password" name="password" placeholder="Password" required class="w-full border-gray-300 border p-3 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <button type="submit" class="bg-purple-600 w-full text-white py-3 rounded-lg mt-6 font-semibold hover:bg-purple-700">Masuk</button>
            <p class="text-center text-sm text-gray-500 mt-4">
                Belum punya akun? <button type="button" onclick="switchModal('loginModal', 'registerModal')" class="font-semibold text-purple-600 hover:underline">Daftar</button>
            </p>
        </form>
    </div>

    <div id="registerModal" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-20 p-4">
        <form action="register.php" method="POST" class="bg-white text-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-sm relative">
            <button type="button" onclick="closeAllModals()" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-2xl">&times;</button>
            <h3 class="text-2xl font-bold text-center mb-2">Buat Akun Baru</h3>
            <p class="text-center text-gray-500 mb-6">Pendaftaran cepat, hanya butuh semenit.</p>
            <div class="space-y-4">
                <input type="text" name="username" placeholder="Pilih Username" required class="w-full border-gray-300 border p-3 rounded-lg focus:ring-2 focus:ring-blue-500">
                <input type="password" name="password" placeholder="Buat Password" required class="w-full border-gray-300 border p-3 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 w-full text-white py-3 rounded-lg mt-6 font-semibold hover:bg-blue-700">Daftar</button>
            <p class="text-center text-sm text-gray-500 mt-4">
                Sudah punya akun? <button type="button" onclick="switchModal('registerModal', 'loginModal')" class="font-semibold text-blue-600 hover:underline">Login</button>
            </p>
        </form>
    </div>

    <script>
        const loginModal = document.getElementById('loginModal');
        const registerModal = document.getElementById('registerModal');

        function showModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function closeAllModals() {
            loginModal.classList.add('hidden');
            registerModal.classList.add('hidden');
        }

        function switchModal(fromId, toId) {
            document.getElementById(fromId).classList.add('hidden');
            document.getElementById(toId).classList.remove('hidden');
        }

        loginModal.addEventListener('click', function(event) {
            if (event.target === loginModal) {
                closeAllModals();
            }
        });
        registerModal.addEventListener('click', function(event) {
             if (event.target === registerModal) {
                closeAllModals();
            }
        });

        <?php if(isset($_GET['error'])): ?>
            showModal('loginModal');
        <?php endif; ?>
    </script>
</body>
</html>