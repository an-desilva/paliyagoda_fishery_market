<?php
// views/billing/credit_ledger.php
// Buyer Credit Ledger & Recovery Management Screen

requireAuth();
$db = getDB();

// Fetch all buyers with credit status
$stmtBuyers = $db->query("
    SELECT b.*, 
           (b.credit_limit - b.current_credit_balance) AS available_credit,
           (SELECT COUNT(id) FROM invoices WHERE buyer_id = b.id AND payment_mode = 'credit') AS total_credit_bills
    FROM buyers b
    ORDER BY b.current_credit_balance DESC
");
$buyers = $stmtBuyers->fetchAll();

// Fetch recent credit payment transactions
$stmtPayments = $db->query("
    SELECT cp.*, b.name AS buyer_name, b.buyer_code, u.full_name AS cashier_name
    FROM credit_payments cp
    JOIN buyers b ON cp.buyer_id = b.id
    JOIN users u ON cp.received_by = u.id
    ORDER BY cp.id DESC LIMIT 10
");
$recentPayments = $stmtPayments->fetchAll();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-book-bookmark text-indigo-400"></i>
                <span>Buyer Credit Ledger & Recovery</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Monitor wholesale merchant credit balances, credit limit enforcement, and log cash recovery payments.
            </p>
        </div>
    </div>

    <!-- Buyer Credit Cards / Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <div class="p-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-users text-cyan-400"></i>
                <span>Wholesale Merchants Ledger</span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-950 text-slate-400 border-b border-slate-800 uppercase tracking-wider">
                        <th class="p-4">Buyer Code</th>
                        <th class="p-4">Merchant Name</th>
                        <th class="p-4">Phone</th>
                        <th class="p-4 text-right">Credit Limit</th>
                        <th class="p-4 text-right">Current Credit Balance</th>
                        <th class="p-4 text-right">Available Credit</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-right">Record Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <?php foreach ($buyers as $b): ?>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="p-4 font-mono font-bold text-cyan-300"><?= $b['buyer_code'] ?></td>
                            <td class="p-4 font-bold text-white"><?= htmlspecialchars($b['name']) ?></td>
                            <td class="p-4 text-slate-400"><?= htmlspecialchars($b['phone'] ?? 'N/A') ?></td>
                            <td class="p-4 text-right font-mono font-semibold text-slate-300"><?= formatLKR($b['credit_limit']) ?></td>
                            <td class="p-4 text-right font-mono font-bold <?= $b['current_credit_balance'] > 0 ? 'text-amber-400' : 'text-slate-400' ?>">
                                <?= formatLKR($b['current_credit_balance']) ?>
                            </td>
                            <td class="p-4 text-right font-mono font-bold <?= $b['available_credit'] > 0 ? 'text-emerald-400' : 'text-red-400' ?>">
                                <?= formatLKR(max(0, $b['available_credit'])) ?>
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase <?= $b['status'] === 'active' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/30' ?>">
                                    <?= $b['status'] ?>
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <?php if ($b['current_credit_balance'] > 0): ?>
                                    <button onclick="openPaymentModal(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['name'])) ?>', <?= $b['current_credit_balance'] ?>)"
                                        class="px-3 py-1.5 bg-emerald-600/30 hover:bg-emerald-600/50 text-emerald-300 rounded-lg text-[11px] font-bold border border-emerald-500/30 transition-all flex items-center gap-1.5 ml-auto">
                                        <i class="fa-solid fa-hand-holding-dollar text-xs"></i>
                                        <span>RECEIVE CASH</span>
                                    </button>
                                <?php else: ?>
                                    <span class="text-[11px] text-slate-500 italic">No Outstanding Balance</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Payments Log -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 shadow-xl">
        <h3 class="text-sm font-bold text-white flex items-center gap-2 pb-3 border-b border-slate-800 mb-4">
            <i class="fa-solid fa-clock-rotate-left text-purple-400"></i>
            <span>Recent Credit Recovery Payment Logs</span>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <th class="pb-2">Date & Time</th>
                        <th class="pb-2">Merchant Name</th>
                        <th class="pb-2">Method</th>
                        <th class="pb-2 text-right">Amount Paid</th>
                        <th class="pb-2">Cashier</th>
                        <th class="pb-2">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php if (empty($recentPayments)): ?>
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-500">No credit payments logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentPayments as $p): ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 text-slate-400 font-mono"><?= date('Y-m-d H:i', strtotime($p['payment_date'])) ?></td>
                                <td class="py-3 font-bold text-white"><?= htmlspecialchars($p['buyer_name']) ?></td>
                                <td class="py-3 font-semibold text-cyan-400"><?= htmlspecialchars($p['payment_method']) ?></td>
                                <td class="py-3 text-right font-mono font-bold text-emerald-400"><?= formatLKR($p['amount_paid']) ?></td>
                                <td class="py-3 text-slate-300"><?= htmlspecialchars($p['cashier_name']) ?></td>
                                <td class="py-3 text-slate-400 italic"><?= htmlspecialchars($p['notes'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Recording Credit Recovery Payment -->
<div id="paymentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
    <div class="bg-slate-900 rounded-2xl border border-slate-700 shadow-2xl max-w-md w-full p-6 space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-hand-holding-dollar text-emerald-400"></i>
                <span>Record Buyer Credit Payment</span>
            </h3>
            <button onclick="closePaymentModal()" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="actions/billing/update_ledger.php" method="POST" class="space-y-4">
            <input type="hidden" id="modal_buyer_id" name="buyer_id" value="">

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase">Merchant Name</label>
                <div id="modal_buyer_name" class="text-lg font-black text-white mt-0.5">--</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase">Outstanding Credit Balance</label>
                <div id="modal_buyer_balance" class="text-xl font-mono font-bold text-amber-400 mt-0.5">Rs. 0.00</div>
            </div>

            <div>
                <label for="amount_paid" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                    Amount Paid (LKR) <span class="text-red-400">*</span>
                </label>
                <input type="number" step="100" id="amount_paid" name="amount_paid" required placeholder="0.00"
                    class="block w-full py-3 px-3 border border-slate-700 rounded-xl bg-slate-950 text-emerald-300 font-mono font-bold text-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label for="payment_method" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                    Payment Method
                </label>
                <select id="payment_method" name="payment_method"
                    class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white font-semibold text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="Cash">Cash (මුදලින්)</option>
                    <option value="Bank Transfer">Bank Transfer / Online</option>
                    <option value="Cheque">Cheque Payment</option>
                </select>
            </div>

            <div>
                <label for="notes" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                    Remarks / Receipt Notes
                </label>
                <input type="text" id="notes" name="notes" placeholder="e.g. Counter Cash Recovery Slip #089"
                    class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="closePaymentModal()" class="px-4 py-2.5 bg-slate-800 text-slate-300 rounded-xl font-bold text-xs">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs shadow-lg">
                    Confirm & Update Ledger
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPaymentModal(buyerId, buyerName, balance) {
        document.getElementById('modal_buyer_id').value = buyerId;
        document.getElementById('modal_buyer_name').textContent = buyerName;
        document.getElementById('modal_buyer_balance').textContent = 'Rs. ' + balance.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('amount_paid').value = balance;
        document.getElementById('paymentModal').classList.remove('hidden');
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
    }
</script>
