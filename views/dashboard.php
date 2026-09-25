<?php
// views/dashboard.php
// Peliyagoda Wholesale Fish Market Operations Dashboard

requireAuth();
$db = getDB();

// 1. KPI Aggregations
// Today's Traded Weight & Revenue
$commRate = DEFAULT_COMMISSION_RATE;
$stmtToday = $db->query("
    SELECT 
        COUNT(id) AS total_invoices,
        COALESCE(SUM(grand_total), 0) AS total_revenue,
        COALESCE(SUM(subtotal * ({$commRate}/100)), 0) AS est_commission
    FROM invoices
    WHERE DATE(created_at) = CURDATE() AND status = 'paid'
");
$todayStats = $stmtToday->fetch();

// Today's total fish weight sold
$stmtWeight = $db->query("
    SELECT COALESCE(SUM(ii.weight_kg), 0) AS total_kg
    FROM invoice_items ii
    JOIN invoices i ON ii.invoice_id = i.id
    WHERE DATE(i.created_at) = CURDATE() AND i.status = 'paid'
");
$todayWeight = (float)($stmtWeight->fetch()['total_kg'] ?? 0);

// Active Lorries (Consignments not yet settled)
$stmtLorries = $db->query("SELECT COUNT(id) AS active_cnt FROM consignments WHERE status != 'settled'");
$activeLorries = (int)($stmtLorries->fetch()['active_cnt'] ?? 0);

// Uncollected Buyer Credit Total
$stmtCredit = $db->query("SELECT COALESCE(SUM(current_credit_balance), 0) AS total_credit FROM buyers WHERE current_credit_balance > 0");
$totalUncollectedCredit = (float)($stmtCredit->fetch()['total_credit'] ?? 0);

// 2. Recent Invoices
$stmtRecentInv = $db->query("
    SELECT i.*, b.name AS buyer_name, u.full_name AS cashier_name 
    FROM invoices i
    JOIN buyers b ON i.buyer_id = b.id
    JOIN users u ON i.issued_by = u.id
    ORDER BY i.id DESC LIMIT 6
");
$recentInvoices = $stmtRecentInv->fetchAll();

// 3. Active Consignments
$stmtActiveCons = $db->query("
    SELECT c.*, s.name AS supplier_name, s.boat_name,
           (SELECT COUNT(id) FROM fish_items WHERE consignment_id = c.id) AS total_graded
    FROM consignments c
    JOIN suppliers s ON c.supplier_id = s.id
    ORDER BY c.id DESC LIMIT 5
");
$activeConsignments = $stmtActiveCons->fetchAll();
?>

<div class="space-y-6">
    <!-- Top Welcome & Date Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-900/80 p-6 rounded-2xl border border-slate-800 shadow-lg backdrop-blur-md">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>Peliyagoda Fish Market Dashboard</span>
                <span class="text-xs bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 px-2.5 py-0.5 rounded-full font-bold uppercase">Live Counter</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Real-time tracking for Lorry offloading, Helper Batta disbursements, auction POS sales, and boat owner settlements.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php?page=billing_pos" class="px-5 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-sm shadow-xl shadow-emerald-900/40 border border-emerald-400/30 flex items-center gap-2 transition-all hover:scale-105">
                <i class="fa-solid fa-bolt-lightning text-amber-300 animate-bounce"></i>
                <span>LAUNCH AUCTION POS</span>
            </a>
            <a href="index.php?page=consignments_new" class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 flex items-center gap-2 transition-all">
                <i class="fa-solid fa-truck-ramp-box text-amber-400"></i>
                <span>OFFLOAD LORRY</span>
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Today Traded Weight -->
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl flex items-center justify-between relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-cyan-500/10 rounded-full blur-xl group-hover:bg-cyan-500/20 transition-all"></div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Today's Traded Weight</div>
                <div class="text-2xl font-black text-cyan-300 mt-1"><?= formatKg($todayWeight) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Net Pelagic Fish Sold</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-xl font-bold border border-cyan-500/30">
                <i class="fa-solid fa-weight-hanging"></i>
            </div>
        </div>

        <!-- Today Gross Revenue -->
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl flex items-center justify-between relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition-all"></div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Today's Gross Sales</div>
                <div class="text-2xl font-black text-emerald-400 mt-1"><?= formatLKR($todayStats['total_revenue']) ?></div>
                <div class="text-[11px] text-slate-400 mt-1"><?= $todayStats['total_invoices'] ?> Auction Bills Issued</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold border border-emerald-500/30">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Active Lorries Offloading -->
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl flex items-center justify-between relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/10 rounded-full blur-xl group-hover:bg-amber-500/20 transition-all"></div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Offloading Lorries</div>
                <div class="text-2xl font-black text-amber-400 mt-1"><?= $activeLorries ?> Lorries</div>
                <div class="text-[11px] text-slate-400 mt-1">Harbor Shipments In-Market</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl font-bold border border-amber-500/30">
                <i class="fa-solid fa-truck-front"></i>
            </div>
        </div>

        <!-- Uncollected Buyer Credit -->
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl flex items-center justify-between relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-rose-500/10 rounded-full blur-xl group-hover:bg-rose-500/20 transition-all"></div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Uncollected Credit Ledger</div>
                <div class="text-2xl font-black text-rose-400 mt-1"><?= formatLKR($totalUncollectedCredit) ?></div>
                <div class="text-[11px] text-slate-400 mt-1">Merchant Credit Ledger Balance</div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-xl font-bold border border-rose-500/30">
                <i class="fa-solid fa-book-bookmark"></i>
            </div>
        </div>
    </div>

    <!-- Active Tables & Operational Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Auction Bills (Invoices) -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-emerald-400"></i>
                        <span>Recent Auction POS Invoices</span>
                    </h3>
                    <a href="index.php?page=billing_pos" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300">New Bill +</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-800 uppercase tracking-wider">
                                <th class="pb-2">Invoice #</th>
                                <th class="pb-2">Buyer</th>
                                <th class="pb-2">Mode</th>
                                <th class="pb-2 text-right">Grand Total</th>
                                <th class="pb-2 text-center">Gate Pass</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <?php if (empty($recentInvoices)): ?>
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-500">No invoices issued today yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentInvoices as $inv): ?>
                                    <tr class="hover:bg-slate-800/40 transition-colors">
                                        <td class="py-3 font-mono font-bold text-cyan-300">
                                            <a href="index.php?page=invoice_print&id=<?= $inv['id'] ?>" class="hover:underline">
                                                <?= $inv['invoice_no'] ?>
                                            </a>
                                        </td>
                                        <td class="py-3 font-semibold text-slate-200"><?= htmlspecialchars($inv['buyer_name']) ?></td>
                                        <td class="py-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $inv['payment_mode'] === 'cash' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-purple-500/20 text-purple-300 border border-purple-500/30' ?>">
                                                <?= $inv['payment_mode'] ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-right font-bold text-white"><?= formatLKR($inv['grand_total']) ?></td>
                                        <td class="py-3 text-center">
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

        <!-- Active Lorries In Market -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-truck-front text-amber-400"></i>
                        <span>Active Lorries & Shipments</span>
                    </h3>
                    <a href="index.php?page=consignments_list" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-800 uppercase tracking-wider">
                                <th class="pb-2">Lorry #</th>
                                <th class="pb-2">Boat Owner</th>
                                <th class="pb-2">Harbor</th>
                                <th class="pb-2 text-center">Graded</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <?php if (empty($activeConsignments)): ?>
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-500">No active consignments.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activeConsignments as $c): ?>
                                    <tr class="hover:bg-slate-800/40 transition-colors">
                                        <td class="py-3 font-mono font-bold text-amber-300"><?= htmlspecialchars($c['lorry_number']) ?></td>
                                        <td class="py-3 font-semibold text-slate-200">
                                            <?= htmlspecialchars($c['supplier_name']) ?>
                                            <span class="block text-[10px] text-slate-400 font-normal"><?= htmlspecialchars($c['boat_name'] ?? '') ?></span>
                                        </td>
                                        <td class="py-3 text-slate-300"><?= htmlspecialchars($c['harbor_origin']) ?></td>
                                        <td class="py-3 text-center font-bold text-cyan-400"><?= $c['total_graded'] ?> Fish</td>
                                        <td class="py-3 text-right">
                                            <a href="index.php?page=grading_entry&consignment_id=<?= $c['id'] ?>" class="px-2 py-1 bg-cyan-600/30 hover:bg-cyan-600/50 text-cyan-300 rounded font-semibold text-[11px] border border-cyan-500/30">
                                                Grade / Weigh
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
    </div>
</div>
