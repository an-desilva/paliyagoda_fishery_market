<?php
// views/billing/invoice_print.php
// Dual Printable View: 80mm Thermal Receipt & A5 Exit Gate Pass (ලබාදුන් ලදුපත / ගේට්ටු පත්‍රිකාව)

requireAuth();
$db = getDB();

$invoiceId = intval($_GET['id'] ?? 0);

if ($invoiceId <= 0) {
    setFlash('danger', 'Invalid Invoice ID.');
    header('Location: index.php?page=billing_pos');
    exit;
}

// Fetch Invoice details with Buyer and Cashier
$stmtInv = $db->prepare("
    SELECT i.*, b.name AS buyer_name, b.buyer_code, b.phone AS buyer_phone, u.full_name AS cashier_name
    FROM invoices i
    JOIN buyers b ON i.buyer_id = b.id
    JOIN users u ON i.issued_by = u.id
    WHERE i.id = :id
    LIMIT 1
");
$stmtInv->execute([':id' => $invoiceId]);
$invoice = $stmtInv->fetch();

if (!$invoice) {
    setFlash('danger', 'Invoice not found.');
    header('Location: index.php?page=billing_pos');
    exit;
}

// Fetch line items with fish details
$stmtItems = $db->prepare("
    SELECT ii.*, f.tag_code, f.species, f.grade, f.gross_weight, f.tare_weight, c.lorry_number, c.harbor_origin
    FROM invoice_items ii
    JOIN fish_items f ON ii.fish_item_id = f.id
    JOIN consignments c ON f.consignment_id = c.id
    WHERE ii.invoice_id = :inv_id
");
$stmtItems->execute([':inv_id' => $invoiceId]);
$items = $stmtItems->fetchAll();
?>

<div class="space-y-6">
    <!-- Screen Header & Print Controls (Hidden during print) -->
    <div class="no-print bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-emerald-400 uppercase tracking-wider">
                <a href="index.php?page=billing_pos" class="hover:underline">POS Counter</a>
                <span>/</span>
                <span>Print Gate Pass & Receipt</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-receipt text-emerald-400"></i>
                <span>Invoice <?= $invoice['invoice_no'] ?></span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Issued to <span class="text-white font-bold"><?= htmlspecialchars($invoice['buyer_name']) ?></span> (<?= strtoupper($invoice['payment_mode']) ?> BILL)
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-sm shadow-xl shadow-emerald-900/40 border border-emerald-400/30 flex items-center gap-2 transition-all hover:scale-105">
                <i class="fa-solid fa-print text-lg"></i>
                <span>PRINT GATE PASS / RECEIPT</span>
            </button>
            <a href="index.php?page=billing_pos" class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs border border-slate-700">
                Back to POS Counter
            </a>
        </div>
    </div>

    <!-- Printable Container Wrapper -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        
        <!-- Left: 80mm Thermal Receipt Preview -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl no-print-wrapper">
            <div class="text-xs font-bold text-slate-400 uppercase mb-4 text-center pb-2 border-b border-slate-800">
                80mm Thermal Counter Receipt (තපාල ලදුපත)
            </div>

            <div class="thermal-receipt bg-white text-black p-4 rounded shadow-2xl mx-auto font-mono text-xs max-w-[320px]">
                <div class="text-center font-bold text-sm uppercase">
                    PELIYAGODA FISH TRADE<br>
                    <span class="text-[10px]">පෑලියගොඩ මත්ස්‍ය වෙළඳ සංකීර්ණය</span>
                </div>
                <div class="text-center text-[10px] mt-1 border-b border-black pb-2">
                    Tel: 011-2910042 / 077-1234567<br>
                    Counter #04 | Cashier: <?= htmlspecialchars($invoice['cashier_name']) ?>
                </div>

                <div class="mt-2 text-[11px] space-y-0.5">
                    <div class="flex justify-between"><span>Inv #:</span> <span class="font-bold"><?= $invoice['invoice_no'] ?></span></div>
                    <div class="flex justify-between"><span>Date:</span> <span><?= date('Y-m-d H:i', strtotime($invoice['created_at'])) ?></span></div>
                    <div class="flex justify-between"><span>Buyer:</span> <span class="font-bold"><?= htmlspecialchars($invoice['buyer_name']) ?></span></div>
                    <div class="flex justify-between"><span>Mode:</span> <span class="font-bold uppercase"><?= $invoice['payment_mode'] ?></span></div>
                </div>

                <div class="receipt-divider border-t border-dashed border-black my-2"></div>

                <table class="w-full text-left text-[10px]">
                    <thead>
                        <tr class="border-b border-black">
                            <th>TAG / SPECIES</th>
                            <th class="text-right">KG</th>
                            <th class="text-right">PRICE</th>
                            <th class="text-right">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <span class="font-bold"><?= $item['tag_code'] ?></span><br>
                                    <span class="text-[9px]"><?= $item['species'] ?></span>
                                </td>
                                <td class="text-right font-bold"><?= number_format($item['weight_kg'], 2) ?></td>
                                <td class="text-right"><?= number_format($item['unit_price'], 0) ?></td>
                                <td class="text-right font-bold"><?= number_format($item['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="receipt-divider border-t border-dashed border-black my-2"></div>

                <div class="space-y-1 text-[11px]">
                    <div class="flex justify-between"><span>Subtotal:</span> <span>Rs. <?= number_format($invoice['subtotal'], 2) ?></span></div>
                    <?php if ($invoice['handling_fee'] > 0): ?>
                        <div class="flex justify-between"><span>Handling Fee:</span> <span>Rs. <?= number_format($invoice['handling_fee'], 2) ?></span></div>
                    <?php endif; ?>
                    <?php if ($invoice['cutting_fee'] > 0): ?>
                        <div class="flex justify-between"><span>Cutting Fee:</span> <span>Rs. <?= number_format($invoice['cutting_fee'], 2) ?></span></div>
                    <?php endif; ?>
                    <div class="flex justify-between text-sm font-bold border-t border-black pt-1">
                        <span>GRAND TOTAL:</span>
                        <span>Rs. <?= number_format($invoice['grand_total'], 2) ?></span>
                    </div>
                </div>

                <div class="receipt-divider border-t border-dashed border-black my-2"></div>

                <div class="text-center space-y-1">
                    <div class="text-[9px]">Gate Security Hash:</div>
                    <div class="font-bold tracking-widest text-[11px] font-mono border border-black py-1 bg-slate-100">
                        <?= $invoice['security_hash'] ?>
                    </div>
                    <div class="text-[8px] mt-2 italic">Thank you for your business! / ස්තුතියි!</div>
                </div>
            </div>
        </div>

        <!-- Right: Official A5 Exit Gate Pass Layout -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl">
            <div class="text-xs font-bold text-slate-400 uppercase mb-4 text-center pb-2 border-b border-slate-800">
                A5 Official Market Exit Gate Pass (ගේට්ටු පත්‍රිකාව)
            </div>

            <div class="gate-pass-a5 bg-white text-black p-6 rounded shadow-2xl mx-auto font-sans text-xs max-w-[500px] border-2 border-black">
                <div class="flex items-center justify-between border-b-2 border-black pb-3">
                    <div>
                        <h2 class="text-lg font-black uppercase">PELIYAGODA FISH MARKET</h2>
                        <div class="text-[10px] font-bold">පෑලියගොඩ එක්සත් මත්ස්‍ය වෙළඳ සංකීර්ණය</div>
                        <div class="text-[9px]">OFFICIAL EXIT GATE PASS & AUCTION INVOICE</div>
                    </div>
                    <div class="text-right border-l-2 border-black pl-3">
                        <div class="text-[10px] font-bold text-red-600">EXIT PASS</div>
                        <div class="text-sm font-mono font-bold"><?= $invoice['invoice_no'] ?></div>
                        <div class="text-[9px]"><?= date('Y-m-d H:i', strtotime($invoice['created_at'])) ?></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 my-3 text-[11px] bg-slate-100 p-2 border border-black">
                    <div>
                        <span class="font-bold block text-slate-600 text-[9px] uppercase">Buyer / Merchant:</span>
                        <span class="font-bold text-sm"><?= htmlspecialchars($invoice['buyer_name']) ?></span>
                        <span class="block text-[10px]">Code: <?= $invoice['buyer_code'] ?></span>
                    </div>
                    <div>
                        <span class="font-bold block text-slate-600 text-[9px] uppercase">Payment Terms:</span>
                        <span class="font-bold uppercase text-sm <?= $invoice['payment_mode'] === 'cash' ? 'text-emerald-700' : 'text-purple-700' ?>">
                            <?= $invoice['payment_mode'] ?> BILL
                        </span>
                        <span class="block text-[10px]">Cashier: <?= htmlspecialchars($invoice['cashier_name']) ?></span>
                    </div>
                </div>

                <table class="w-full text-left text-[11px] border border-black my-3">
                    <thead>
                        <tr class="bg-slate-200 border-b border-black text-[10px] uppercase font-bold">
                            <th class="p-1.5 border-r border-black">Tag Code</th>
                            <th class="p-1.5 border-r border-black">Species & Grade</th>
                            <th class="p-1.5 border-r border-black text-right">Weight</th>
                            <th class="p-1.5 border-r border-black text-right">Price/kg</th>
                            <th class="p-1.5 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr class="border-b border-slate-300">
                                <td class="p-1.5 font-mono font-bold border-r border-black"><?= $item['tag_code'] ?></td>
                                <td class="p-1.5 border-r border-black">
                                    <?= $item['species'] ?>
                                    <span class="block text-[9px] text-slate-600 font-semibold"><?= $item['grade'] ?> (Lorry: <?= $item['lorry_number'] ?>)</span>
                                </td>
                                <td class="p-1.5 border-r border-black text-right font-bold font-mono"><?= number_format($item['weight_kg'], 2) ?> kg</td>
                                <td class="p-1.5 border-r border-black text-right font-mono">Rs. <?= number_format($item['unit_price'], 2) ?></td>
                                <td class="p-1.5 text-right font-bold font-mono">Rs. <?= number_format($item['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="flex justify-between items-end border-t-2 border-black pt-3">
                    <div class="space-y-1">
                        <div class="text-[9px] font-bold uppercase">Gate Security Verification Code:</div>
                        <div class="font-mono text-sm font-black border-2 border-black px-3 py-1 bg-yellow-100 text-center tracking-widest">
                            <?= $invoice['security_hash'] ?>
                        </div>
                        <div class="text-[8px] text-slate-600 mt-1">Verified by Peliyagoda Gate Security Officer</div>
                    </div>

                    <div class="text-right space-y-1">
                        <div class="text-[10px]">Subtotal: Rs. <?= number_format($invoice['subtotal'], 2) ?></div>
                        <div class="text-[10px]">Handling & Cutting: Rs. <?= number_format($invoice['handling_fee'] + $invoice['cutting_fee'], 2) ?></div>
                        <div class="text-base font-black border-t border-black pt-1">
                            GRAND TOTAL: Rs. <?= number_format($invoice['grand_total'], 2) ?>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8 mt-8 pt-6 border-t border-dashed border-black text-center text-[10px]">
                    <div>
                        <div class="border-t border-black pt-1 font-bold">Authorized Cashier Signature</div>
                    </div>
                    <div>
                        <div class="border-t border-black pt-1 font-bold">Gate Security Verification Officer Stamp</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
