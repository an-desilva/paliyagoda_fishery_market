<?php
// views/admin/reports/settlements_audit.php
// Full Audit Report of Finalized Supplier Settlements

require_once __DIR__ . '/../../../includes/auth_check.php';
requireRole(['admin']);

$db = getDB();

$stmt = $db->query("
    SELECT st.*, s.name AS supplier_name, s.boat_name, s.harbor, c.consignment_no, c.lorry_number
    FROM settlements st
    JOIN suppliers s ON st.supplier_id = s.id
    JOIN consignments c ON st.consignment_id = c.id
    ORDER BY st.id DESC
");
$settlements = $stmt->fetchAll();

$totalGross = 0;
$totalComm = 0;
$totalNetPaid = 0;

foreach ($settlements as $s) {
    $totalGross += (float)$s['gross_revenue'];
    $totalComm += (float)$s['commission_amount'];
    $totalNetPaid += (float)$s['net_payable'];
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-teal-400 uppercase tracking-wider">
                <span>Executive Audit</span>
                <span>/</span>
                <span>Supplier Settlements</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-file-contract text-teal-400"></i>
                <span>Finalized Boat Owner Settlements Audit Log</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Complete audit history of closed consignments, commission earnings, expenses deducted, and net cash payouts.
            </p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-slate-400 uppercase">Gross Auction Revenue</div>
            <div class="text-2xl font-black text-white mt-1"><?= formatLKR($totalGross) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Total Settled Shipments</div>
        </div>

        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-emerald-400 uppercase">Mudiyala Commission Earned</div>
            <div class="text-2xl font-black text-emerald-400 mt-1"><?= formatLKR($totalComm) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Retained Business Income</div>
        </div>

        <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-teal-300 uppercase">Net Payouts to Boat Owners</div>
            <div class="text-2xl font-black text-teal-300 mt-1"><?= formatLKR($totalNetPaid) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Disbursed Payouts</div>
        </div>
    </div>

    <!-- Audit Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-950 text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <th class="p-4">Settlement #</th>
                        <th class="p-4">Date</th>
                        <th class="p-4">Boat Owner</th>
                        <th class="p-4">Lorry / Harbor</th>
                        <th class="p-4 text-right">Gross Sales</th>
                        <th class="p-4 text-right">Commission</th>
                        <th class="p-4 text-right">Loan Deducted</th>
                        <th class="p-4 text-right">Net Paid</th>
                        <th class="p-4 text-center">Voucher</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <?php if (empty($settlements)): ?>
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-500">No finalized settlements logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($settlements as $s): ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="p-4 font-mono font-bold text-teal-300"><?= $s['settlement_no'] ?></td>
                                <td class="p-4 text-slate-400 font-mono"><?= date('Y-m-d H:i', strtotime($s['settlement_date'])) ?></td>
                                <td class="p-4 font-bold text-white"><?= htmlspecialchars($s['supplier_name']) ?></td>
                                <td class="p-4 text-slate-300">
                                    <?= htmlspecialchars($s['lorry_number']) ?>
                                    <span class="block text-[10px] text-slate-400 font-normal"><?= htmlspecialchars($s['harbor']) ?></span>
                                </td>
                                <td class="p-4 text-right font-mono text-slate-300"><?= formatLKR($s['gross_revenue']) ?></td>
                                <td class="p-4 text-right font-mono text-emerald-400"><?= formatLKR($s['commission_amount']) ?> (<?= $s['commission_rate'] ?>%)</td>
                                <td class="p-4 text-right font-mono text-amber-400"><?= formatLKR($s['loan_deduction']) ?></td>
                                <td class="p-4 text-right font-mono font-bold text-teal-300"><?= formatLKR($s['net_payable']) ?></td>
                                <td class="p-4 text-center">
                                    <a href="index.php?page=settlement_voucher&id=<?= $s['id'] ?>" class="p-1.5 rounded bg-slate-800 hover:bg-slate-700 text-teal-400">
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
