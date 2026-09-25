<?php
// views/settlements/calculate.php
// End-of-Day Mudiyala Supplier Reconciliation & Settlement Sheet (මුදලාලි සැසඳුම් පත්‍රය)

requireAuth();
$db = getDB();

$selectedConsignmentId = intval($_GET['consignment_id'] ?? 0);

// Fetch consignments eligible for settlement (status != 'settled')
$stmtCons = $db->query("
    SELECT c.id, c.consignment_no, c.lorry_number, c.harbor_origin, s.name AS supplier_name, s.boat_name
    FROM consignments c
    JOIN suppliers s ON c.supplier_id = s.id
    WHERE c.status != 'settled'
    ORDER BY c.id DESC
");
$consignments = $stmtCons->fetchAll();

if ($selectedConsignmentId <= 0 && !empty($consignments)) {
    $selectedConsignmentId = $consignments[0]['id'];
}

// Fetch sales & consignment details if selected
$consignment = null;
$soldItems = [];
$grossRevenue = 0.00;
$totalSoldKg = 0.00;

if ($selectedConsignmentId > 0) {
    $stmtC = $db->prepare("
        SELECT c.*, s.name AS supplier_name, s.supplier_code, s.boat_name, s.current_loan_balance
        FROM consignments c
        JOIN suppliers s ON c.supplier_id = s.id
        WHERE c.id = :id LIMIT 1
    ");
    $stmtC->execute([':id' => $selectedConsignmentId]);
    $consignment = $stmtC->fetch();

    if ($consignment) {
        $stmtSold = $db->prepare("
            SELECT f.*, ii.unit_price, ii.line_total, i.invoice_no, i.created_at AS sold_at
            FROM fish_items f
            JOIN invoice_items ii ON f.id = ii.fish_item_id
            JOIN invoices i ON ii.invoice_id = i.id
            WHERE f.consignment_id = :cid AND f.status IN ('sold', 'settled')
        ");
        $stmtSold->execute([':cid' => $selectedConsignmentId]);
        $soldItems = $stmtSold->fetchAll();

        foreach ($soldItems as $si) {
            $grossRevenue += (float)$si['line_total'];
            $totalSoldKg += (float)$si['net_weight'];
        }
    }
}
?>

<div class="space-y-6">
    <!-- Header & Consignment Picker -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <div class="text-xs font-semibold text-teal-400 uppercase tracking-wider">Boat Owner Financial Settlement</div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice-dollar text-teal-400"></i>
                <span>Supplier EOD Settlement Sheet (මුදලාලි සැසඳුම් පත්‍රය)</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Net Payable = Gross Sales Revenue - (Commission % + Freight + Helper Batta + Coolie + Loan Deductions).
            </p>
        </div>

        <div class="flex items-center gap-3">
            <label for="consignment_select" class="text-xs font-bold text-slate-300 uppercase whitespace-nowrap">Select Shipment:</label>
            <select id="consignment_select" onchange="location.href='index.php?page=settlement_calculate&consignment_id=' + this.value"
                class="py-2.5 px-4 border border-teal-500/40 rounded-xl bg-slate-950 text-teal-300 font-semibold text-xs focus:outline-none focus:ring-2 focus:ring-teal-500">
                <?php if (empty($consignments)): ?>
                    <option value="">No Pending Consignments to Settle</option>
                <?php else: ?>
                    <?php foreach ($consignments as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedConsignmentId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['supplier_name']) ?> - <?= htmlspecialchars($c['lorry_number']) ?> (<?= $c['consignment_no'] ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <?php if (!$consignment): ?>
        <div class="bg-slate-900 p-8 rounded-2xl border border-slate-800 text-center text-slate-400 space-y-4">
            <i class="fa-solid fa-circle-check text-4xl text-emerald-500"></i>
            <p>All active lorries are fully settled!</p>
        </div>
    <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left: Sold Fish Items Breakdown (7 cols) -->
            <div class="lg:col-span-7 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="text-sm font-extrabold text-white">Sold Fish Auction Breakdown</h3>
                        <span class="text-xs text-slate-400">Boat: <?= htmlspecialchars($consignment['boat_name'] ?? 'N/A') ?> (Harbor: <?= htmlspecialchars($consignment['harbor_origin']) ?>)</span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-400 block">Total Weight Sold:</span>
                        <span class="text-sm font-bold text-emerald-400 font-mono"><?= formatKg($totalSoldKg) ?></span>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[400px]">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-slate-950 text-slate-400 uppercase border-b border-slate-800">
                            <tr>
                                <th class="p-3">Tag Code</th>
                                <th class="p-3">Species</th>
                                <th class="p-3 text-right">Net Weight</th>
                                <th class="p-3 text-right">Auction Price</th>
                                <th class="p-3 text-right">Gross Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <?php if (empty($soldItems)): ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-500 font-medium">
                                        No fish items sold from this consignment yet. Complete auction sales first.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($soldItems as $item): ?>
                                    <tr class="hover:bg-slate-800/40">
                                        <td class="p-3 font-mono font-bold text-cyan-300"><?= $item['tag_code'] ?></td>
                                        <td class="p-3 text-slate-200"><?= htmlspecialchars($item['species']) ?></td>
                                        <td class="p-3 text-right font-mono font-bold text-slate-300"><?= formatKg($item['net_weight']) ?></td>
                                        <td class="p-3 text-right font-mono text-slate-400"><?= formatLKR($item['unit_price']) ?>/kg</td>
                                        <td class="p-3 text-right font-mono font-bold text-emerald-400"><?= formatLKR($item['line_total']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pt-4 border-t border-slate-800 flex justify-between items-center text-sm">
                    <span class="font-bold text-slate-300">Gross Auction Revenue:</span>
                    <span class="font-black text-emerald-400 font-mono text-lg"><?= formatLKR($grossRevenue) ?></span>
                </div>
            </div>

            <!-- Right: Settlement Calculation Sheet (5 cols) -->
            <div class="lg:col-span-5 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl space-y-5">
                <h3 class="text-sm font-extrabold text-white pb-3 border-b border-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-calculator text-teal-400"></i>
                    <span>Reconciliation Sheet Calculation</span>
                </h3>

                <form action="actions/settlements/execute_settle.php" method="POST" class="space-y-4" id="settlementForm">
                    <input type="hidden" name="consignment_id" value="<?= $selectedConsignmentId ?>">

                    <!-- Gross Revenue -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase">Gross Auction Revenue (LKR)</label>
                        <input type="text" id="gross_revenue_display" readonly value="<?= number_format($grossRevenue, 2, '.', '') ?>"
                            class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-emerald-400 font-mono font-bold text-lg">
                    </div>

                    <!-- Commission Rate & Amount -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="commission_rate" class="block text-xs font-bold text-slate-300 uppercase">
                                Commission %
                            </label>
                            <input type="number" step="0.5" id="commission_rate" name="commission_rate" value="6.00" oninput="calculateSettlementNet()"
                                class="block w-full py-2 px-3 border border-slate-700 rounded-lg bg-slate-950 text-white font-mono text-sm focus:outline-none focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase">Commission (LKR)</label>
                            <input type="text" id="commission_amount_display" readonly value="0.00"
                                class="block w-full py-2 px-3 border border-slate-800 rounded-lg bg-slate-950 text-slate-300 font-mono text-sm">
                        </div>
                    </div>

                    <!-- Offloading Deductions (Freight, Helper Batta, Coolie) -->
                    <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-3">
                        <div class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">Shipment Expenses Deductions</div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300">Lorry Freight:</span>
                            <input type="number" step="100" id="freight_deduction" name="freight_deduction" value="<?= $consignment['freight_cost'] ?>" oninput="calculateSettlementNet()"
                                class="w-32 py-1 px-2 border border-slate-700 rounded bg-slate-900 text-slate-200 font-mono text-right text-xs">
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300">Helper Batta (ගෝලයාගේ බටා):</span>
                            <input type="number" step="100" id="helper_batta_deduction" name="helper_batta_deduction" value="<?= $consignment['helper_batta'] ?>" oninput="calculateSettlementNet()"
                                class="w-32 py-1 px-2 border border-slate-700 rounded bg-slate-900 text-amber-300 font-mono text-right text-xs">
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300">Coolie Charges:</span>
                            <input type="number" step="100" id="coolie_deduction" name="coolie_deduction" value="<?= $consignment['coolie_charges'] ?>" oninput="calculateSettlementNet()"
                                class="w-32 py-1 px-2 border border-slate-700 rounded bg-slate-900 text-slate-200 font-mono text-right text-xs">
                        </div>
                    </div>

                    <!-- Loan / Advance Deduction -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="loan_deduction" class="text-xs font-bold text-slate-300 uppercase">
                                Boat Owner Loan Deduction (LKR)
                            </label>
                            <span class="text-[10px] text-amber-400">Loan Bal: <?= formatLKR($consignment['current_loan_balance']) ?></span>
                        </div>
                        <input type="number" step="1000" id="loan_deduction" name="loan_deduction" value="0.00" max="<?= $consignment['current_loan_balance'] ?>" oninput="calculateSettlementNet()"
                            class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-amber-300 font-mono font-bold text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>

                    <!-- Net Payable to Boat Owner Banner -->
                    <div class="bg-gradient-to-br from-teal-950/80 to-slate-950 p-5 rounded-xl border border-teal-500/50 shadow-2xl text-center space-y-1">
                        <div class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">NET PAYABLE TO BOAT OWNER</div>
                        <div id="net-payable-display" class="text-3xl font-black text-teal-300 font-mono">Rs. 0.00</div>
                    </div>

                    <!-- Submit Settlement -->
                    <button type="submit" <?= $grossRevenue <= 0 ? 'disabled' : '' ?>
                        class="w-full py-4 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 disabled:opacity-50 text-white font-black text-sm shadow-xl shadow-teal-900/40 border border-teal-400/30 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-file-signature text-lg"></i>
                        <span>GENERATE EOD SETTLEMENT VOUCHER</span>
                    </button>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
    function calculateSettlementNet() {
        const gross = parseFloat(document.getElementById('gross_revenue_display')?.value) || 0;
        const commRate = parseFloat(document.getElementById('commission_rate')?.value) || 0;
        const commAmount = gross * (commRate / 100);

        const freight = parseFloat(document.getElementById('freight_deduction')?.value) || 0;
        const batta = parseFloat(document.getElementById('helper_batta_deduction')?.value) || 0;
        const coolie = parseFloat(document.getElementById('coolie_deduction')?.value) || 0;
        const loan = parseFloat(document.getElementById('loan_deduction')?.value) || 0;

        const commElem = document.getElementById('commission_amount_display');
        if (commElem) commElem.value = commAmount.toFixed(2);

        const totalDeductions = commAmount + freight + batta + coolie + loan;
        const netPayable = Math.max(0, gross - totalDeductions);

        const netElem = document.getElementById('net-payable-display');
        if (netElem) {
            netElem.textContent = 'Rs. ' + netPayable.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    document.addEventListener('DOMContentLoaded', calculateSettlementNet);
</script>
