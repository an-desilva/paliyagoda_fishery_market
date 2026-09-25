<?php
// views/reports/daily_summary.php
// Daily Sales, Gross Revenue, Commissions Earned & Net Payouts Report

requireAuth();
$db = getDB();

$reportDate = $_GET['date'] ?? date('Y-m-d');

// Aggregations for the selected date
$stmtSummary = $db->prepare("
    SELECT 
        COUNT(id) AS invoice_count,
        COALESCE(SUM(subtotal), 0) AS total_subtotal,
        COALESCE(SUM(handling_fee), 0) AS total_handling,
        COALESCE(SUM(cutting_fee), 0) AS total_cutting,
        COALESCE(SUM(grand_total), 0) AS total_grand,
        COALESCE(SUM(CASE WHEN payment_mode = 'cash' THEN grand_total ELSE 0 END), 0) AS cash_sales,
        COALESCE(SUM(CASE WHEN payment_mode = 'credit' THEN grand_total ELSE 0 END), 0) AS credit_sales
    FROM invoices
    WHERE DATE(created_at) = :rdate AND status = 'paid'
");
$stmtSummary->execute([':rdate' => $reportDate]);
$summary = $stmtSummary->fetch();

// Total Traded Weight for selected date
$stmtWeight = $db->prepare("
    SELECT COALESCE(SUM(ii.weight_kg), 0) AS total_kg
    FROM invoice_items ii
    JOIN invoices i ON ii.invoice_id = i.id
    WHERE DATE(i.created_at) = :rdate AND i.status = 'paid'
");
$stmtWeight->execute([':rdate' => $reportDate]);
$tradedWeight = (float)($stmtWeight->fetch()['total_kg'] ?? 0);

// Total Helper Batta paid today
$stmtBatta = $db->prepare("
    SELECT COALESCE(SUM(helper_batta), 0) AS total_batta, COALESCE(SUM(freight_cost), 0) AS total_freight
    FROM consignments
    WHERE DATE(arrival_date) = :rdate
");
$stmtBatta->execute([':rdate' => $reportDate]);
$expStats = $stmtBatta->fetch();

// Invoices List for selected date
$stmtInvoices = $db->prepare("
    SELECT i.*, b.name AS buyer_name, b.buyer_code, u.full_name AS cashier_name
    FROM invoices i
    JOIN buyers b ON i.buyer_id = b.id
    JOIN users u ON i.issued_by = u.id
    WHERE DATE(i.created_at) = :rdate AND i.status = 'paid'
    ORDER BY i.id DESC
");
$stmtInvoices->execute([':rdate' => $reportDate]);
$invoices = $stmtInvoices->fetchAll();
?>

<div class="space-y-6">
    <!-- Header & Date Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-square-poll-vertical text-purple-400"></i>
                <span>Daily Sales & Revenue Report</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Market sales total, cash vs credit split, commission revenues, and helper expense audit.
            </p>
        </div>

        <form method="GET" action="index.php" class="flex items-center gap-2">
            <input type="hidden" name="page" value="report_daily">
            <input type="date" name="date" value="<?= $reportDate ?>" onchange="this.form.submit()"
                class="py-2.5 px-4 border border-purple-500/40 rounded-xl bg-slate-950 text-purple-300 font-bold text-xs focus:outline-none focus:ring-2 focus:ring-purple-500">
            <button type="submit" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow">
                Filter Date
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-slate-400 uppercase">Gross Market Sales</div>
            <div class="text-2xl font-black text-emerald-400 mt-1"><?= formatLKR($summary['total_grand']) ?></div>
            <div class="text-[11px] text-slate-400 mt-1"><?= $summary['invoice_count'] ?> Invoices Issued</div>
        </div>

        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-slate-400 uppercase">Total Traded Weight</div>
            <div class="text-2xl font-black text-cyan-400 mt-1"><?= formatKg($tradedWeight) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Net Pelagic Fish Sold</div>
        </div>

        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-slate-400 uppercase">Cash Sales</div>
            <div class="text-2xl font-black text-teal-400 mt-1"><?= formatLKR($summary['cash_sales']) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Instant Cash Receipts</div>
        </div>

        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-slate-400 uppercase">Credit Ledger Sales</div>
            <div class="text-2xl font-black text-purple-400 mt-1"><?= formatLKR($summary['credit_sales']) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Logged to Merchant Ledgers</div>
        </div>
    </div>

    <!-- Detailed Invoices Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <div class="p-4 bg-slate-950 border-b border-slate-800">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-cyan-400"></i>
                <span>Issued Invoices for <?= date('F j, Y', strtotime($reportDate)) ?></span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-950 text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <th class="p-4">Time</th>
                        <th class="p-4">Invoice #</th>
                        <th class="p-4">Buyer / Merchant</th>
                        <th class="p-4">Payment Mode</th>
                        <th class="p-4 text-right">Subtotal</th>
                        <th class="p-4 text-right">Handling Fee</th>
                        <th class="p-4 text-right">Grand Total</th>
                        <th class="p-4 text-center">Print</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-500 font-medium">No sales recorded on this date.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="p-4 text-slate-400 font-mono"><?= date('H:i:s', strtotime($inv['created_at'])) ?></td>
                                <td class="p-4 font-mono font-bold text-cyan-300"><?= $inv['invoice_no'] ?></td>
                                <td class="p-4 font-bold text-white"><?= htmlspecialchars($inv['buyer_name']) ?></td>
                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase <?= $inv['payment_mode'] === 'cash' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-purple-500/20 text-purple-300' ?>">
                                        <?= $inv['payment_mode'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right font-mono text-slate-300"><?= formatLKR($inv['subtotal']) ?></td>
                                <td class="p-4 text-right font-mono text-slate-400"><?= formatLKR($inv['handling_fee']) ?></td>
                                <td class="p-4 text-right font-mono font-bold text-emerald-400"><?= formatLKR($inv['grand_total']) ?></td>
                                <td class="p-4 text-center">
                                    <a href="index.php?page=invoice_print&id=<?= $inv['id'] ?>" class="p-1.5 rounded bg-slate-800 hover:bg-slate-700 text-cyan-400">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
