<?php
// views/billing/pos.php
// High-Speed Auction POS Billing Counter Screen

requireAuth();
$db = getDB();

// Fetch active buyers for select picker
$stmtBuyers = $db->query("SELECT id, buyer_code, name, credit_limit, current_credit_balance, status FROM buyers WHERE status = 'active' ORDER BY name ASC");
$buyers = $stmtBuyers->fetchAll();
?>

<div class="space-y-4 select-none">
    <!-- POS Header & Shortcut Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 p-4 rounded-2xl border border-slate-800 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-500 flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-emerald-500/20">
                <i class="fa-solid fa-bolt-lightning text-amber-300 animate-pulse"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                    <span>AUCTION POS BILLING COUNTER</span>
                    <span class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full font-bold">RAPID ENTRY</span>
                </h1>
                <p class="text-xs text-slate-400">
                    Scan or enter Fish Tag Code, set winning auction price per kg, and issue gate pass.
                </p>
            </div>
        </div>

        <!-- Keyboard Shortcuts Hint -->
        <div class="flex items-center gap-2 text-[11px] text-slate-400 bg-slate-950 px-3 py-2 rounded-xl border border-slate-800">
            <span class="font-mono bg-slate-800 text-cyan-300 px-2 py-0.5 rounded border border-slate-700 font-bold">Enter</span> Add Tag
            <span class="text-slate-600">|</span>
            <span class="font-mono bg-slate-800 text-emerald-300 px-2 py-0.5 rounded border border-slate-700 font-bold">Shift+Enter</span> Checkout
        </div>
    </div>

    <!-- Main POS Grid (12 Columns) -->
    <form action="actions/billing/create_invoice.php" method="POST" id="posForm" class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- Left Section: Item Scanner & Billing Table (7 cols) -->
        <div class="lg:col-span-7 bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-4 flex flex-col justify-between">
            <div>
                <!-- Tag Input Bar -->
                <div class="bg-slate-950 p-4 rounded-xl border border-cyan-500/40 shadow-inner space-y-3">
                    <label for="tag_input" class="block text-xs font-extrabold text-cyan-300 uppercase tracking-wider">
                        Scan or Type Fish Tag Code (e.g. TAG-2026-0001)
                    </label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                <i class="fa-solid fa-barcode text-lg"></i>
                            </div>
                            <input type="text" id="tag_input" autofocus placeholder="Type Tag Code & press Enter..."
                                class="w-full pl-10 pr-3 py-3 border border-slate-700 rounded-xl bg-slate-900 text-white font-mono text-base font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 uppercase">
                        </div>
                        <button type="button" onclick="lookupAndAddTag()"
                            class="px-5 py-3 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl transition-all shadow-md flex items-center gap-1.5">
                            <i class="fa-solid fa-plus text-sm"></i>
                            <span>ADD FISH</span>
                        </button>
                    </div>
                    <div id="tag-error-msg" class="hidden text-xs text-red-400 font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span id="tag-error-text">Tag not found.</span>
                    </div>
                </div>

                <!-- POS Line Items Table -->
                <div class="mt-4 overflow-x-auto max-h-[380px]">
                    <table class="w-full text-left text-xs" id="posTable">
                        <thead class="sticky top-0 bg-slate-950 text-slate-400 uppercase tracking-wider border-b border-slate-800">
                            <tr>
                                <th class="p-3">Tag Code</th>
                                <th class="p-3">Species & Grade</th>
                                <th class="p-3 text-right">Net Weight</th>
                                <th class="p-3 text-right">Auction Price (LKR/kg)</th>
                                <th class="p-3 text-right">Line Total</th>
                                <th class="p-3 text-center">Remove</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60" id="posTableBody">
                            <!-- Rows injected dynamically via JS -->
                            <tr id="emptyRow">
                                <td colspan="6" class="py-12 text-center text-slate-500 font-medium">
                                    No fish items added to current bill yet.<br>Scan or type a Tag Code above to start.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table Subtotal Footer Summary -->
            <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                <div class="text-xs text-slate-400">
                    Total Items: <span id="pos-items-count" class="font-bold text-white font-mono">0</span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 mr-2">Fish Auction Subtotal:</span>
                    <span id="pos-subtotal-display" class="text-lg font-extrabold text-cyan-300 font-mono">Rs. 0.00</span>
                </div>
            </div>
        </div>

        <!-- Right Section: Buyer Selection & Financial Checkout (5 cols) -->
        <div class="lg:col-span-5 bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-5 flex flex-col justify-between">
            <div class="space-y-4">
                <h3 class="text-sm font-extrabold text-white flex items-center gap-2 pb-3 border-b border-slate-800">
                    <i class="fa-solid fa-user-check text-emerald-400"></i>
                    <span>Buyer & Payment Settlement</span>
                </h3>

                <!-- Buyer Selector -->
                <div>
                    <label for="buyer_id" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        Select Merchant / Buyer (ලබාගන්නා වෙළෙන්දා) <span class="text-red-400">*</span>
                    </label>
                    <select id="buyer_id" name="buyer_id" required onchange="onBuyerChange(this.value)"
                        class="block w-full py-3 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white font-bold text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- Choose Wholesale Buyer --</option>
                        <?php foreach ($buyers as $b): ?>
                            <option value="<?= $b['id'] ?>">
                                <?= htmlspecialchars($b['name']) ?> (Code: <?= $b['buyer_code'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Buyer Credit Limit Live Card -->
                <div id="buyer-credit-card" class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-400">Credit Limit:</span>
                        <span id="buyer-credit-limit" class="font-mono font-semibold text-slate-200">Rs. 0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-400">Current Outstanding Balance:</span>
                        <span id="buyer-credit-balance" class="font-mono font-semibold text-amber-400">Rs. 0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-800/80">
                        <span class="font-bold text-slate-300">Available Credit Balance:</span>
                        <span id="buyer-credit-available" class="font-mono font-bold text-emerald-400">Rs. 0.00</span>
                    </div>
                </div>

                <!-- Additional Fees -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="handling_fee" class="block text-[11px] font-bold text-slate-400 uppercase">
                            Handling Fee (LKR)
                        </label>
                        <input type="number" step="50" id="handling_fee" name="handling_fee" value="250.00" oninput="calculateGrandTotal()"
                            class="block w-full py-2 px-3 border border-slate-700 rounded-lg bg-slate-950 text-white font-mono text-sm focus:outline-none focus:ring-1 focus:ring-cyan-500">
                    </div>
                    <div>
                        <label for="cutting_fee" class="block text-[11px] font-bold text-slate-400 uppercase">
                            Cutting Fee (LKR)
                        </label>
                        <input type="number" step="50" id="cutting_fee" name="cutting_fee" value="0.00" oninput="calculateGrandTotal()"
                            class="block w-full py-2 px-3 border border-slate-700 rounded-lg bg-slate-950 text-white font-mono text-sm focus:outline-none focus:ring-1 focus:ring-cyan-500">
                    </div>
                </div>

                <!-- Payment Mode Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Payment Mode / ගෙවීමේ ක්‍රමය
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer border border-slate-700 rounded-xl p-3 bg-slate-950 text-center hover:border-emerald-500 transition-all has-[:checked]:bg-emerald-950/60 has-[:checked]:border-emerald-500">
                            <input type="radio" name="payment_mode" value="cash" checked onchange="calculateGrandTotal()" class="sr-only">
                            <span class="block text-xs font-bold text-emerald-300"><i class="fa-solid fa-money-bill-1 mr-1"></i>Cash Bill</span>
                            <span class="block text-[10px] text-slate-400">තනි මුදලින්</span>
                        </label>
                        <label class="cursor-pointer border border-slate-700 rounded-xl p-3 bg-slate-950 text-center hover:border-purple-500 transition-all has-[:checked]:bg-purple-950/60 has-[:checked]:border-purple-500">
                            <input type="radio" name="payment_mode" value="credit" onchange="calculateGrandTotal()" class="sr-only">
                            <span class="block text-xs font-bold text-purple-300"><i class="fa-solid fa-book-bookmark mr-1"></i>Credit Ledger</span>
                            <span class="block text-[10px] text-slate-400">ණය පොතට</span>
                        </label>
                    </div>
                </div>

                <!-- Grand Total Display Card -->
                <div class="bg-gradient-to-br from-slate-950 to-slate-900 p-5 rounded-xl border border-emerald-500/50 shadow-2xl text-center space-y-1">
                    <div class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Grand Total Amount</div>
                    <div id="pos-grand-total" class="text-3xl font-black text-emerald-400 font-mono tracking-tight">Rs. 0.00</div>
                    <div id="credit-warning-text" class="hidden text-xs text-rose-400 font-bold mt-1">
                        <i class="fa-solid fa-triangle-exclamation"></i> Warning: Bill exceeds buyer credit limit!
                    </div>
                </div>
            </div>

            <!-- Checkout Button -->
            <button type="submit" id="btn-checkout"
                class="w-full py-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-base shadow-xl shadow-emerald-900/40 border border-emerald-400/30 transition-all hover:scale-[1.01] flex items-center justify-center gap-2">
                <i class="fa-solid fa-print text-lg"></i>
                <span>ISSUE INVOICE & GATE PASS (Shift+Enter)</span>
            </button>
        </div>
    </form>
</div>

<!-- POS Calculator Scripts -->
<script src="assets/js/pos_calculator.js"></script>
