<?php
// includes/sidebar.php
// Dynamic Role-Based Sidebar Navigation Menu (RBAC Aware)

require_once __DIR__ . '/../includes/auth_check.php';

$user = currentUser();
$currentPage = $_GET['page'] ?? 'dashboard';
$isAdmin = hasRole('admin');
?>
<aside class="w-64 bg-slate-900 border-r border-slate-800 flex-shrink-0 hidden md:flex flex-col justify-between select-none">
    <div class="py-4 px-3 space-y-6 overflow-y-auto">
        <!-- Quick POS Launcher Badge -->
        <div class="px-2">
            <a href="index.php?page=billing_pos" class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm shadow-lg shadow-emerald-900/30 border border-emerald-400/30 transition-all hover:scale-[1.02]">
                <i class="fa-solid fa-bolt-lightning text-amber-300 animate-pulse"></i>
                <span>AUCTION POS BILLING</span>
            </a>
        </div>

        <nav class="space-y-1">
            <!-- Executive Governance (Admin Only) -->
            <?php if ($isAdmin): ?>
                <div class="px-3 mb-2 text-[10px] font-bold text-cyan-400 uppercase tracking-wider flex items-center justify-between">
                    <span>Executive Admin Governance</span>
                    <i class="fa-solid fa-user-shield text-[10px]"></i>
                </div>

                <!-- Executive Admin Dashboard -->
                <a href="index.php?page=admin_dashboard" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-xs font-semibold transition-all <?= $currentPage === 'admin_dashboard' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-chart-line w-4 text-center text-cyan-400"></i>
                    <span>Admin Control Panel</span>
                </a>

                <!-- Buyer Defaulter Freeze -->
                <a href="index.php?page=admin_buyers_credit" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-xs font-semibold transition-all <?= $currentPage === 'admin_buyers_credit' ? 'bg-rose-600/20 text-rose-300 border border-rose-500/30' : 'text-slate-300 hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-user-lock w-4 text-center text-rose-400"></i>
                    <span>Defaulter Freeze & Limits</span>
                </a>

                <!-- Boat Owner Loans -->
                <a href="index.php?page=admin_suppliers_loans" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-xs font-semibold transition-all <?= $currentPage === 'admin_suppliers_loans' ? 'bg-amber-600/20 text-amber-300 border border-amber-500/30' : 'text-slate-300 hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-hand-holding-hand w-4 text-center text-amber-400"></i>
                    <span>Boat Owner Loans</span>
                </a>

                <!-- Commission & Tariff Settings -->
                <a href="index.php?page=admin_settings_commission" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-xs font-semibold transition-all <?= $currentPage === 'admin_settings_commission' ? 'bg-teal-600/20 text-teal-300 border border-teal-500/30' : 'text-slate-300 hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-sliders w-4 text-center text-teal-400"></i>
                    <span>Commission & Rates</span>
                </a>

                <!-- Staff User Accounts -->
                <a href="index.php?page=admin_users" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-xs font-semibold transition-all <?= $currentPage === 'admin_users' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-users-gear w-4 text-center text-indigo-400"></i>
                    <span>Staff User Accounts</span>
                </a>
            <?php endif; ?>

            <div class="px-3 pt-4 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Operational Modules</div>

            <!-- Dashboard -->
            <a href="index.php?page=dashboard" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all <?= $currentPage === 'dashboard' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' ?>">
                <i class="fa-solid fa-chart-pie w-5 text-center text-cyan-400"></i>
                <span>Market Counter Overview</span>
            </a>

            <!-- Consignment Arrival & Helper Batta -->
            <a href="index.php?page=consignments_list" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all <?= in_array($currentPage, ['consignments_list', 'consignments_new']) ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' ?>">
                <i class="fa-solid fa-truck-ramp-box w-5 text-center text-amber-400"></i>
                <span>Lorry Offloading & Batta</span>
            </a>

            <!-- Fish Grading & Tagging Board -->
            <a href="index.php?page=grading_entry" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all <?= $currentPage === 'grading_entry' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' ?>">
                <i class="fa-solid fa-weight-scale w-5 text-center text-emerald-400"></i>
                <span>Weighing & Grading</span>
            </a>

            <!-- Buyer Credit Ledger -->
            <a href="index.php?page=credit_ledger" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all <?= $currentPage === 'credit_ledger' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' ?>">
                <i class="fa-solid fa-book-bookmark w-5 text-center text-indigo-400"></i>
                <span>Buyer Credit Ledger</span>
            </a>

            <!-- EOD Supplier Settlement -->
            <a href="index.php?page=settlement_calculate" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all <?= in_array($currentPage, ['settlement_calculate', 'settlement_voucher']) ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' ?>">
                <i class="fa-solid fa-file-invoice-dollar w-5 text-center text-teal-400"></i>
                <span>Supplier Settlement</span>
            </a>

            <div class="px-3 pt-4 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Reports & Audit</div>

            <!-- Daily Summary Report -->
            <a href="index.php?page=report_daily" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all <?= $currentPage === 'report_daily' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' ?>">
                <i class="fa-solid fa-square-poll-vertical w-5 text-center text-purple-400"></i>
                <span>Daily Sales & Revenue</span>
            </a>

            <!-- Lorry Expenses Audit -->
            <a href="index.php?page=report_lorry" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all <?= $currentPage === 'report_lorry' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' ?>">
                <i class="fa-solid fa-receipt w-5 text-center text-rose-400"></i>
                <span>Freight & Batta Audit</span>
            </a>

            <?php if ($isAdmin): ?>
                <!-- Finalized Settlements Audit -->
                <a href="index.php?page=admin_reports_settlements" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all <?= $currentPage === 'admin_reports_settlements' ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' ?>">
                    <i class="fa-solid fa-file-contract w-5 text-center text-teal-400"></i>
                    <span>Settlements Audit</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Bottom info card -->
    <div class="p-4 border-t border-slate-800 bg-slate-900/50">
        <div class="bg-slate-800/80 p-3 rounded-lg border border-slate-700/60">
            <div class="text-[11px] font-bold text-slate-300">Peliyagoda Market Desk</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Mudiyala Cash Counter #04</div>
            <div class="mt-2 text-[9px] font-mono text-cyan-400">System v<?= APP_VERSION ?> | PHP 8.2 PDO</div>
        </div>
    </div>
</aside>
