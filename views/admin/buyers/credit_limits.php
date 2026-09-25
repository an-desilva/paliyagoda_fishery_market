<?php
// views/admin/buyers/credit_limits.php
// Executive Buyer Credit Limit & Defaulter Freeze Governance Panel

require_once __DIR__ . '/../../../includes/auth_check.php';
requireRole(['admin']);

$db = getDB();

// Fetch buyers
$stmtBuyers = $db->query("SELECT * FROM buyers ORDER BY current_credit_balance DESC");
$buyers = $stmtBuyers->fetchAll();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-rose-400 uppercase tracking-wider">
                <span>Executive Governance</span>
                <span>/</span>
                <span>Defaulter Freeze</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-user-lock text-rose-400"></i>
                <span>Buyer Credit Limits & Defaulter Freeze Control</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Set buyer credit limits, monitor rolling debts, and toggle Defaulter Freeze (`blocked`) status.
            </p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-950 text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <th class="p-4">Buyer Code</th>
                        <th class="p-4">Merchant Name</th>
                        <th class="p-4 text-right">Current Credit Balance</th>
                        <th class="p-4 text-right">Allowed Credit Limit</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-right">Governance Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <?php foreach ($buyers as $b): ?>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="p-4 font-mono font-bold text-cyan-300"><?= $b['buyer_code'] ?></td>
                            <td class="p-4 font-bold text-white"><?= htmlspecialchars($b['name']) ?></td>
                            <td class="p-4 text-right font-mono font-bold <?= $b['current_credit_balance'] > $b['credit_limit'] ? 'text-red-400' : 'text-amber-400' ?>">
                                <?= formatLKR($b['current_credit_balance']) ?>
                            </td>
                            <td class="p-4 text-right font-mono font-bold text-emerald-400">
                                <?= formatLKR($b['credit_limit']) ?>
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase <?= $b['status'] === 'active' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/30 animate-pulse' ?>">
                                    <?= $b['status'] === 'blocked' ? 'DEFAULTER FROZEN' : 'ACTIVE' ?>
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <form action="actions/admin/update_credit.php" method="POST" class="flex items-center justify-end gap-2">
                                    <input type="hidden" name="buyer_id" value="<?= $b['id'] ?>">
                                    <input type="number" step="10000" name="credit_limit" value="<?= $b['credit_limit'] ?>"
                                        class="w-32 py-1 px-2 border border-slate-700 rounded bg-slate-950 text-emerald-300 font-mono text-right text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    
                                    <select name="status" class="py-1 px-2 border border-slate-700 rounded bg-slate-950 text-xs font-bold font-mono">
                                        <option value="active" <?= $b['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="blocked" <?= $b['status'] === 'blocked' ? 'selected' : '' ?>>Blocked (Freeze)</option>
                                    </select>

                                    <button type="submit" class="px-3 py-1 bg-cyan-600 hover:bg-cyan-500 text-white rounded text-xs font-bold">
                                        Save
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
