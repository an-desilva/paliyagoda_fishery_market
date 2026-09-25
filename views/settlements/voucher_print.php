<?php
// views/settlements/voucher_print.php
// Official Printable Supplier Settlement Sheet (මුදලාලි සැසඳුම් පත්‍රය)

requireAuth();
$db = getDB();

$settlementId = intval($_GET['id'] ?? 0);

if ($settlementId <= 0) {
    setFlash('danger', 'Invalid Settlement ID.');
    header('Location: index.php?page=settlement_calculate');
    exit;
}

// Fetch Settlement details
$stmtS = $db->prepare("
    SELECT s.*, sup.name AS supplier_name, sup.supplier_code, sup.boat_name, sup.harbor, sup.phone,
           c.consignment_no, c.lorry_number, c.arrival_date
    FROM settlements s
    JOIN suppliers sup ON s.supplier_id = sup.id
    JOIN consignments c ON s.consignment_id = c.id
    WHERE s.id = :id LIMIT 1
");
$stmtS->execute([':id' => $settlementId]);
$settlement = $stmtS->fetch();

if (!$settlement) {
    setFlash('danger', 'Settlement voucher not found.');
    header('Location: index.php?page=settlement_calculate');
    exit;
}

// Fetch fish items settled in this consignment
$stmtFish = $db->prepare("
    SELECT f.tag_code, f.species, f.grade, f.net_weight, ii.unit_price, ii.line_total
    FROM fish_items f
    JOIN invoice_items ii ON f.id = ii.fish_item_id
    WHERE f.consignment_id = :cid
");
$stmtFish->execute([':cid' => $settlement['consignment_id']]);
$fishItems = $stmtFish->fetchAll();
?>

<div class="space-y-6">
    <!-- Action Controls (Hidden on Print) -->
    <div class="no-print bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-file-invoice-dollar text-teal-400"></i>
                <span>Settlement Voucher <?= $settlement['settlement_no'] ?></span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Issued to Boat Owner <span class="text-white font-bold"><?= htmlspecialchars($settlement['supplier_name']) ?></span>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-6 py-3 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white font-black text-sm shadow-xl shadow-teal-900/40 border border-teal-400/30 flex items-center gap-2 transition-all hover:scale-105">
                <i class="fa-solid fa-print text-lg"></i>
                <span>PRINT SUPPLIER VOUCHER</span>
            </button>
            <a href="index.php?page=settlement_calculate" class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs border border-slate-700">
                Back to Settlements
            </a>
        </div>
    </div>

    <!-- Official Printable Voucher Document -->
    <div class="bg-white text-black p-8 rounded-2xl shadow-2xl max-w-3xl mx-auto font-sans text-xs border-2 border-black">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b-2 border-black pb-4">
            <div>
                <h1 class="text-xl font-black uppercase tracking-tight">PELIYAGODA BILL FISH TRADE</h1>
                <div class="text-xs font-bold text-slate-800">පෑලියගොඩ එක්සත් මත්ස්‍ය වෙළඳ සංකීර්ණය</div>
                <div class="text-[10px] text-slate-600">Peliyagoda Central Fish Market, Stall #04 | Tel: 011-2910042</div>
            </div>
            <div class="text-right border-l-2 border-black pl-4">
                <div class="text-xs font-bold text-teal-800 uppercase">OFFICIAL SETTLEMENT SHEET</div>
                <div class="text-base font-mono font-bold"><?= $settlement['settlement_no'] ?></div>
                <div class="text-[10px]"><?= date('Y-m-d H:i', strtotime($settlement['settlement_date'])) ?></div>
            </div>
        </div>

        <!-- Supplier & Shipment Info -->
        <div class="grid grid-cols-2 gap-4 my-4 p-3 bg-slate-100 border border-black text-[11px]">
            <div>
                <span class="font-bold text-slate-600 block text-[9px] uppercase">Boat Owner / Supplier (මුදලාලි):</span>
                <span class="font-bold text-sm"><?= htmlspecialchars($settlement['supplier_name']) ?></span>
                <span class="block">Boat: <?= htmlspecialchars($settlement['boat_name'] ?? 'N/A') ?> (<?= htmlspecialchars($settlement['harbor']) ?> Harbor)</span>
                <span class="block">Phone: <?= htmlspecialchars($settlement['phone'] ?? 'N/A') ?></span>
            </div>
            <div>
                <span class="font-bold text-slate-600 block text-[9px] uppercase">Lorry Shipment Details:</span>
                <span class="font-bold">Lorry #: <?= htmlspecialchars($settlement['lorry_number']) ?></span>
                <span class="block">Consignment #: <?= $settlement['consignment_no'] ?></span>
                <span class="block">Arrival Date: <?= date('Y-m-d H:i', strtotime($settlement['arrival_date'])) ?></span>
            </div>
        </div>

        <!-- Fish Sold Table -->
        <div class="my-4">
            <h3 class="font-bold text-xs uppercase mb-2">Fish Auction Revenue Breakdown</h3>
            <table class="w-full text-left text-[11px] border border-black">
                <thead>
                    <tr class="bg-slate-200 border-b border-black uppercase text-[9px] font-bold">
                        <th class="p-2 border-r border-black">Tag Code</th>
                        <th class="p-2 border-r border-black">Species</th>
                        <th class="p-2 border-r border-black text-center">Grade</th>
                        <th class="p-2 border-r border-black text-right">Net Weight</th>
                        <th class="p-2 border-r border-black text-right">Auction Price</th>
                        <th class="p-2 text-right">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300">
                    <?php foreach ($fishItems as $item): ?>
                        <tr>
                            <td class="p-2 font-mono font-bold border-r border-black"><?= $item['tag_code'] ?></td>
                            <td class="p-2 border-r border-black"><?= htmlspecialchars($item['species']) ?></td>
                            <td class="p-2 border-r border-black text-center font-bold text-[10px]"><?= $item['grade'] ?></td>
                            <td class="p-2 border-r border-black text-right font-mono font-bold"><?= number_format($item['net_weight'], 2) ?> kg</td>
                            <td class="p-2 border-r border-black text-right font-mono">Rs. <?= number_format($item['unit_price'], 2) ?></td>
                            <td class="p-2 text-right font-mono font-bold">Rs. <?= number_format($item['line_total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Reconciliation Calculation Table -->
        <div class="grid grid-cols-2 gap-6 my-4 pt-2 border-t-2 border-black">
            <div class="space-y-1 text-[11px]">
                <div class="font-bold text-xs uppercase border-b border-black pb-1 mb-2">Itemized Deductions Summary</div>
                <div class="flex justify-between"><span>Mudiyala Commission (<?= $settlement['commission_rate'] ?>%):</span> <span class="font-mono">Rs. <?= number_format($settlement['commission_amount'], 2) ?></span></div>
                <div class="flex justify-between"><span>Lorry Freight Cost:</span> <span class="font-mono">Rs. <?= number_format($settlement['freight_deduction'], 2) ?></span></div>
                <div class="flex justify-between"><span>Helper Batta (ගෝලයාගේ බටා):</span> <span class="font-mono">Rs. <?= number_format($settlement['helper_batta_deduction'], 2) ?></span></div>
                <div class="flex justify-between"><span>Coolie Charges:</span> <span class="font-mono">Rs. <?= number_format($settlement['coolie_deduction'], 2) ?></span></div>
                <div class="flex justify-between"><span>Boat Owner Loan Deduction:</span> <span class="font-mono">Rs. <?= number_format($settlement['loan_deduction'], 2) ?></span></div>
            </div>

            <div class="bg-slate-100 p-4 border border-black flex flex-col justify-between text-right">
                <div>
                    <div class="text-[10px] font-bold uppercase text-slate-600">Gross Sales Revenue</div>
                    <div class="text-base font-black font-mono">Rs. <?= number_format($settlement['gross_revenue'], 2) ?></div>
                </div>

                <div class="pt-3 border-t-2 border-black">
                    <div class="text-[10px] font-bold uppercase text-slate-800">NET CASH PAYABLE TO BOAT OWNER</div>
                    <div class="text-2xl font-black font-mono text-teal-800">
                        Rs. <?= number_format($settlement['net_payable'], 2) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="grid grid-cols-2 gap-8 mt-12 pt-6 border-t border-dashed border-black text-center text-[10px]">
            <div>
                <div class="border-t border-black pt-1 font-bold">Main Mudiyala Cashier Signature & Stamp</div>
            </div>
            <div>
                <div class="border-t border-black pt-1 font-bold">Boat Owner / Helper Cash Acknowledgment</div>
            </div>
        </div>

    </div>
</div>
