<?php
// views/admin/dashboard.php
// Executive Admin Dashboard (Main Mudiyala Oversight Panel)

require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['admin']);

$db = getDB();

// 1. Executive Aggregations
$commRate = (float)getSetting('default_commission_rate', 6.00);

$stmtSales = $db->query("
    SELECT 
        COUNT(id) AS total_invoices,
        COALESCE(SUM(grand_total), 0) AS gross_revenue,
        COALESCE(SUM(subtotal), 0) AS gross_subtotal,
        COALESCE(SUM(handling_fee + cutting_fee), 0) AS total_fees
    FROM invoices WHERE status = 'paid'
");
$allSales = $stmtSales->fetch();

$stmtToday = $db->query("
    SELECT 
        COALESCE(SUM(grand_total), 0) AS today_revenue,
        COALESCE(SUM(subtotal), 0) AS today_subtotal
    FROM invoices WHERE DATE(created_at) = CURDATE() AND status = 'paid'
");
$todaySales = $stmtToday->fetch();

$todayCommission = round(((float)$todaySales['today_subtotal']) * ($commRate / 100), 2);
$totalCommissionYield = round(((float)$allSales['gross_subtotal']) * ($commRate / 100), 2);

// Total Weight Sold
$stmtWeight = $db->query("SELECT COALESCE(SUM(weight_kg), 0) AS total_kg FROM invoice_items");
$totalTradedKg = (float)($stmtWeight->fetch()['total_kg'] ?? 0);

// Total Loan Advances Outstanding
$stmtLoans = $db->query("SELECT COALESCE(SUM(current_loan_balance), 0) AS total_loans FROM suppliers");
$totalOutstandingLoans = (float)($stmtLoans->fetch()['total_loans'] ?? 0);

// Total Uncollected Buyer Credit
$stmtCredit = $db->query("SELECT COALESCE(SUM(current_credit_balance), 0) AS total_credit FROM buyers WHERE current_credit_balance > 0");
$totalUncollectedCredit = (float)($stmtCredit->fetch()['total_credit'] ?? 0);

// Total Helper Batta Cash Paid Out
$stmtBatta = $db->query("SELECT COALESCE(SUM(helper_batta), 0) AS total_batta FROM consignments");
$totalHelperBatta = (float)($stmtBatta->fetch()['total_batta'] ?? 0);

// Blocked Defaulter Buyers Count
$stmtBlocked = $db->query("SELECT COUNT(id) AS cnt FROM buyers WHERE status = 'blocked'");
$blockedBuyersCnt = (int)($stmtBlocked->fetch()['cnt'] ?? 0);
?>

<div class="space-y-6">
    <!-- Top Welcome Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 p-6 rounded-2xl border border-cyan-500/30 shadow-2xl">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-cyan-400 uppercase tracking-wider">
                <i class="fa-solid fa-user-shield"></i>
                <span>Executive Governance Desk</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <span>Peliyagoda Market Admin Control Panel</span>
                <span class="text-xs bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 px-2.5 py-0.5 rounded-full font-bold">Main Mudiyala</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Real-time financial yield, Commission earnings (<?= number_format($commRate, 2) ?>%), Boat Owner Loans, and Defaulter governance.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="index.php?page=admin_settings_commission" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-cyan-300 font-bold text-xs rounded-xl border border-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-cyan-400"></i>
                <span>System Rates Settings</span>
            </a>
            <a href="index.php?page=admin_buyers_credit" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow border border-rose-400/30 flex items-center gap-2">
                <i class="fa-solid fa-ban"></i>
                <span>Defaulter Freeze (<?= $blockedBuyersCnt ?>)</span>
            </a>
        </div>
    </div>

    <!-- Executive Financial Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Today Commission Yield -->
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase">Today's Commission Yield</div>
                <div class="text-2xl font-black text-emerald-400 mt-1"><?= formatLKR($todayCommission) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Calculated @ <?= number_format($commRate, 1) ?>% Rate</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold border border-emerald-500/30">
                <i class="fa-solid fa-percent"></i>
            </div>
        </div>

        <!-- Total Outstanding Loans -->
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase">Boat Owner Loan Advances</div>
                <div class="text-2xl font-black text-amber-400 mt-1"><?= formatLKR($totalOutstandingLoans) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Pre-season advances to be recovered</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl font-bold border border-amber-500/30">
                <i class="fa-solid fa-hand-holding-hand"></i>
            </div>
        </div>

        <!-- Total Uncollected Credit -->
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase">Merchant Credit Ledger</div>
                <div class="text-2xl font-black text-rose-400 mt-1"><?= formatLKR($totalUncollectedCredit) ?></div>
                <div class="text-[11px] text-slate-400 mt-1"><?= $blockedBuyersCnt ?> Frozen Defaulter Accounts</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-xl font-bold border border-rose-500/30">
                <i class="fa-solid fa-book-bookmark"></i>
            </div>
        </div>

        <!-- Helper Batta Cash Outflow -->
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase">Helper Batta Disbursed</div>
                <div class="text-2xl font-black text-cyan-300 mt-1"><?= formatLKR($totalHelperBatta) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Total Lorry Helper Cash paid</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-xl font-bold border border-cyan-500/30">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </div>
        </div>
    </div>

    <!-- Quick Admin Management Modules Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Buyer Credit & Freeze Governance -->
        <a href="index.php?page=admin_buyers_credit" class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-cyan-500/50 transition-all shadow-xl group">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl font-bold border border-indigo-500/30 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <i class="fa-solid fa-arrow-right text-slate-600 group-hover:text-cyan-400 transition-colors"></i>
            </div>
            <h3 class="text-base font-bold text-white mt-4">Buyer Credit & Freeze Control</h3>
            <p class="text-xs text-slate-400 mt-1">Manage merchant credit limits and immediately FREEZE defaulting buyers from taking bill fish.</p>
        </a>

        <!-- Boat Owner Loans -->
        <a href="index.php?page=admin_suppliers_loans" class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-cyan-500/50 transition-all shadow-xl group">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl font-bold border border-amber-500/30 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <i class="fa-solid fa-arrow-right text-slate-600 group-hover:text-cyan-400 transition-colors"></i>
            </div>
            <h3 class="text-base font-bold text-white mt-4">Boat Owner Loan Advances</h3>
            <p class="text-xs text-slate-400 mt-1">Disburse pre-season loans to boat owners and track automated settlement recoveries.</p>
        </a>

        <!-- System Settings & Rates -->
        <a href="index.php?page=admin_settings_commission" class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-cyan-500/50 transition-all shadow-xl group">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center text-xl font-bold border border-teal-500/30 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <i class="fa-solid fa-arrow-right text-slate-600 group-hover:text-cyan-400 transition-colors"></i>
            </div>
            <h3 class="text-base font-bold text-white mt-4">Commission & Tariff Rates</h3>
            <p class="text-xs text-slate-400 mt-1">Configure default Mudiyala commission rate, handling fees, and baseline helper batta rates.</p>
        </a>
    </div>
</div>
