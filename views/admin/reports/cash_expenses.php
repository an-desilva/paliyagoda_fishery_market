<?php
// views/admin/reports/cash_expenses.php
// Detailed Audit Log of Helper Batta (ගෝලයාගේ බටා) & Petty Cash Disbursements

require_once __DIR__ . '/../../../includes/auth_check.php';
requireRole(['admin']);

$db = getDB();

$stmt = $db->query("
    SELECT c.*, s.name AS supplier_name, s.boat_name, s.harbor
    FROM consignments c
    JOIN suppliers s ON c.supplier_id = s.id
    ORDER BY c.id DESC
");
$consignments = $stmt->fetchAll();

$totalFreight = 0;
$totalBatta = 0;
$totalCoolie = 0;

foreach ($consignments as $c) {
    $totalFreight += (float)$c['freight_cost'];
    $totalBatta += (float)$c['helper_batta'];
    $totalCoolie += (float)$c['coolie_charges'];
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-rose-400 uppercase tracking-wider">
                <span>Executive Audit</span>
                <span>/</span>
                <span>Petty Cash Outflow</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-money-bill-transfer text-amber-400"></i>
                <span>Helper Batta & Petty Cash Disbursement Audit</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Detailed audit trail of instant cash disbursements for Helper Batta (ගෝලයාගේ බටා), Freight, and Coolie handling fees.
            </p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-amber-400 uppercase">Helper Batta Cash Paid Out</div>
            <div class="text-2xl font-black text-amber-400 mt-1"><?= formatLKR($totalBatta) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Drawer Petty Cash Vouchers</div>
        </div>

        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-slate-400 uppercase">Total Lorry Freight Logged</div>
            <div class="text-2xl font-black text-white mt-1"><?= formatLKR($totalFreight) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Inward Transport Costs</div>
        </div>

        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-cyan-400 uppercase">Total Coolie Handling</div>
            <div class="text-2xl font-black text-cyan-400 mt-1"><?= formatLKR($totalCoolie) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Market Floor Unloading</div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-950 text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <th class="p-4">Arrival Date</th>
                        <th class="p-4">Consignment #</th>
                        <th class="p-4">Lorry Registration #</th>
                        <th class="p-4">Harbor & Boat</th>
                        <th class="p-4">Boat Owner</th>
                        <th class="p-4 text-right">Helper Batta (ගෝලයාගේ බටා)</th>
                        <th class="p-4 text-right">Freight Cost</th>
                        <th class="p-4 text-right">Coolie Fees</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <?php foreach ($consignments as $c): ?>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="p-4 text-slate-400 font-mono"><?= date('Y-m-d H:i', strtotime($c['arrival_date'])) ?></td>
                            <td class="p-4 font-mono font-bold text-cyan-400"><?= $c['consignment_no'] ?></td>
                            <td class="p-4 font-mono font-bold text-amber-300"><?= htmlspecialchars($c['lorry_number']) ?></td>
                            <td class="p-4 font-semibold text-slate-200">
                                <?= htmlspecialchars($c['harbor_origin']) ?>
                                <span class="block text-[10px] text-slate-400 font-normal"><?= htmlspecialchars($c['boat_name'] ?? 'N/A') ?></span>
                            </td>
                            <td class="p-4 text-slate-300"><?= htmlspecialchars($c['supplier_name']) ?></td>
                            <td class="p-4 text-right font-mono font-bold text-amber-400"><?= formatLKR($c['helper_batta']) ?></td>
                            <td class="p-4 text-right font-mono text-slate-200"><?= formatLKR($c['freight_cost']) ?></td>
                            <td class="p-4 text-right font-mono text-slate-400"><?= formatLKR($c['coolie_charges']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
