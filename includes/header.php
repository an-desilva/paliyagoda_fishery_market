<?php
// includes/header.php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$user = currentUser();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Peliyagoda Bill Fish Trade</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        ocean: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        fish: {
                            gold: '#f59e0b',
                            red: '#ef4444',
                            emerald: '#10b981',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100 flex flex-col min-h-screen">

    <!-- Top Navigation Header -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40 shadow-xl">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Left: Branding -->
                <div class="flex items-center space-x-3">
                    <a href="index.php?page=dashboard" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-600 to-blue-600 flex items-center justify-center shadow-lg shadow-cyan-500/20 group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-fish-fins text-white text-xl"></i>
                        </div>
                        <div>
                            <span class="text-lg font-extrabold tracking-tight bg-gradient-to-r from-cyan-400 via-sky-300 to-blue-400 bg-clip-text text-transparent">
                                PELIYAGODA FISH TRADE
                            </span>
                            <span class="block text-[10px] font-semibold tracking-wider text-cyan-400 uppercase">
                                පෑලියගොඩ එක්සත් මත්ස්‍ය වෙළඳ සංකීර්ණය
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Right Side: System Online Badge & Admin User Info / Logout -->
                <div class="flex items-center space-x-4 sm:space-x-6">
                    <div class="hidden sm:flex items-center space-x-2 bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700/60 text-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-slate-300 font-medium">System Online</span>
                        <span class="text-slate-600">|</span>
                        <span id="live-time-display" class="font-mono text-cyan-300 font-semibold">--:--:--</span>
                    </div>

                    <?php if ($user): ?>
                        <div class="flex items-center space-x-3 pl-3 border-l border-slate-800">
                            <div class="text-right hidden sm:block">
                                <div class="text-xs font-bold text-slate-200"><?= htmlspecialchars($user['full_name']) ?></div>
                                <div class="text-[10px] font-medium text-cyan-400 uppercase tracking-wider">
                                    <i class="fa-solid fa-user-shield text-[9px] mr-1"></i><?= $user['role'] ?>
                                </div>
                            </div>
                            <div class="w-9 h-9 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-cyan-400 font-bold">
                                <?= strtoupper(substr($user['username'], 0, 2)) ?>
                            </div>
                            <a href="actions/auth_logout.php" title="Logout" class="p-2 text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded-lg transition-colors">
                                <i class="fa-solid fa-power-off text-sm"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="index.php?page=login" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg font-semibold text-xs transition-all">
                            Sign In
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container with Sidebar -->
    <div class="flex-1 flex overflow-hidden">
        <?php if ($user): ?>
            <?php include __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>

        <!-- Content Area -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-950">
            <!-- Flash Message Banner -->
            <?php if ($flash): ?>
                <div class="mb-6 p-4 rounded-xl border flex items-center justify-between shadow-lg transition-all <?= $flash['type'] === 'success' ? 'bg-emerald-950/80 border-emerald-500/50 text-emerald-200' : ($flash['type'] === 'danger' ? 'bg-red-950/80 border-red-500/50 text-red-200' : 'bg-blue-950/80 border-blue-500/50 text-blue-200') ?>">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-emerald-400' : ($flash['type'] === 'danger' ? 'fa-triangle-exclamation text-red-400' : 'fa-circle-info text-blue-400') ?> text-xl"></i>
                        <span class="text-sm font-medium"><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-200">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>
