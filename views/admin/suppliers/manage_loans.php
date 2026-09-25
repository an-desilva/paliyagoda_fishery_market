<?php
// views/admin/suppliers/manage_loans.php
// Boat Owner Loan Advances & EOD Auto-Recovery Management Panel

require_once __DIR__ . '/../../../includes/auth_check.php';
requireRole(['admin']);

$db = getDB();

// Fetch boat owners
$stmtSuppliers = $db->query("SELECT * FROM suppliers ORDER BY current_loan_balance DESC");
$suppliers = $stmtSuppliers->fetchAll();

// Fetch recent loan transactions
$stmtLoans = $db->query("
    SELECT l.*, s.name AS supplier_name, s.boat_name, u.full_name AS issuer_name
    FROM supplier_loans l
    JOIN suppliers s ON l.supplier_id = s.id
    JOIN users u ON l.issued_by = u.id
    ORDER BY l.id DESC LIMIT 15
");
$loanLogs = $stmtLoans->fetchAll();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-amber-400 uppercase tracking-wider">
                <span>Executive Governance</span>
                <span>/</span>
                <span>Boat Owner Loans</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-hand-holding-hand text-amber-400"></i>
                <span>Boat Owner Pre-Season Loans & Advances</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Disburse fuel, bait, and ice advances to boat owners. Loans are auto-recovered during Mudiyala EOD settlements.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Issue Loan Advance Form (5 cols) -->
        <div class="lg:col-span-5 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-sm font-extrabold text-white pb-3 border-b border-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-amber-400"></i>
                <span>Disburse Loan Advance / Cash Advance</span>
            </h3>

            <form action="actions/admin/manage_loans.php" method="POST" class="space-y-4">
                <div>
                    <label for="supplier_id" class="block text-xs font-bold text-slate-300 uppercase mb-1">
                        Select Boat Owner / Supplier <span class="text-red-400">*</span>
                    </label>
                    <select id="supplier_id" name="supplier_id" required
                        class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white font-bold text-xs focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="">-- Choose Boat Owner --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>">
                                <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['boat_name'] ?? 'No Boat') ?> - Current Loan: <?= formatLKR($s['current_loan_balance']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="amount" class="block text-xs font-bold text-slate-300 uppercase mb-1">
                        Loan Amount (LKR) <span class="text-red-400">*</span>
                    </label>
                    <input type="number" step="1000" id="amount" name="amount" required placeholder="50000.00"
                        class="block w-full py-3 px-3 border border-slate-700 rounded-xl bg-slate-950 text-amber-300 font-mono font-bold text-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>

                <div>
                    <label for="type" class="block text-xs font-bold text-slate-300 uppercase mb-1">
                        Transaction Type
                    </label>
                    <select id="type" name="type"
                        class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white font-bold text-xs">
                        <option value="disbursement">Disbursement (New Loan Advance given to Boat Owner)</option>
                        <option value="repayment">Repayment (Manual Cash Loan Repayment by Boat Owner)</option>
                    </select>
                </div>

                <div>
                    <label for="notes" class="block text-xs font-bold text-slate-300 uppercase mb-1">
                        Purpose / Loan Notes
                    </label>
                    <input type="text" id="notes" name="notes" placeholder="e.g. Fuel & Ice advance for Sayura-02 fishing trip"
                        class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white text-xs">
                </div>

                <button type="submit"
                    class="w-full py-3.5 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-extrabold text-xs shadow-lg transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check-double"></i>
                    <span>LOG LOAN ADVANCE TRANSACTION</span>
                </button>
            </form>
        </div>

        <!-- Right: Recent Loan Transactions Audit (7 cols) -->
        <div class="lg:col-span-7 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-white pb-3 border-b border-slate-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-cyan-400"></i>
                    <span>Loan Advance & Recovery Log</span>
                </h3>

                <div class="overflow-x-auto max-h-[420px]">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-slate-950 text-slate-400 uppercase border-b border-slate-800">
                            <tr>
                                <th class="p-3">Date</th>
                                <th class="p-3">Boat Owner</th>
                                <th class="p-3">Type</th>
                                <th class="p-3 text-right">Amount</th>
                                <th class="p-3">Issued By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <?php foreach ($loanLogs as $log): ?>
                                <tr class="hover:bg-slate-800/40">
                                    <td class="p-3 text-slate-400 font-mono"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                                    <td class="p-3 font-bold text-white"><?= htmlspecialchars($log['supplier_name']) ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $log['type'] === 'disbursement' ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-300' ?>">
                                            <?= $log['type'] ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold <?= $log['type'] === 'disbursement' ? 'text-amber-400' : 'text-emerald-400' ?>">
                                        <?= formatLKR($log['amount']) ?>
                                    </td>
                                    <td class="p-3 text-slate-400"><?= htmlspecialchars($log['issuer_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
