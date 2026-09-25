<?php
// views/consignments/new.php
// Lorry Offloading & Helper Batta Instant Voucher Form

require_once __DIR__ . '/../../includes/auth_check.php';
requireAuth();

$db = getDB();

// Fetch suppliers
$stmtSup = $db->query("SELECT id, supplier_code, name, boat_name, harbor FROM suppliers ORDER BY name ASC");
$suppliers = $stmtSup->fetchAll();

// Dynamic baseline settings
$defaultBatta = (float)getSetting('default_helper_batta', 3500.00);
$defaultCoolie = (float)getSetting('default_coolie_charge', 2400.00);
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center justify-between bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-cyan-400 uppercase tracking-wider">
                <a href="index.php?page=consignments_list" class="hover:underline">Consignments</a>
                <span>/</span>
                <span>Inward Offloading</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-truck-ramp-box text-amber-400"></i>
                <span>Lorry Arrival & Offloading Entry</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Record harbor shipment, lorry freight cost, and issue instant cash disbursement voucher for Helper Batta (ගෝලයාගේ බටා).
            </p>
        </div>
        <a href="index.php?page=consignments_list" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl border border-slate-700">
            Cancel & Return
        </a>
    </div>

    <!-- Form Container -->
    <form action="actions/consignments/save_inward.php" method="POST" class="bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Boat Owner / Supplier Picker -->
            <div>
                <label for="supplier_id" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Boat Owner / Supplier (මුදලාලි) <span class="text-red-400">*</span>
                </label>
                <select id="supplier_id" name="supplier_id" required
                    class="block w-full py-3 px-4 border border-slate-700 rounded-xl bg-slate-950 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 text-sm font-medium">
                    <option value="">-- Select Boat Owner / Supplier --</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['boat_name'] ?? 'No Boat') ?> - <?= htmlspecialchars($s['harbor']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Harbor of Origin -->
            <div>
                <label for="harbor_origin" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Harbor of Origin (තොටුපල) <span class="text-red-400">*</span>
                </label>
                <select id="harbor_origin" name="harbor_origin" required
                    class="block w-full py-3 px-4 border border-slate-700 rounded-xl bg-slate-950 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 text-sm font-medium">
                    <option value="">-- Select Sri Lankan Harbor --</option>
                    <?php foreach (SRI_LANKA_HARBORS as $h): ?>
                        <option value="<?= $h ?>"><?= $h ?> Harbor</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Lorry Registration Number -->
            <div>
                <label for="lorry_number" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Lorry Registration Number <span class="text-red-400">*</span>
                </label>
                <input type="text" id="lorry_number" name="lorry_number" required placeholder="e.g. WP LE-4592"
                    class="block w-full py-3 px-4 border border-slate-700 rounded-xl bg-slate-950 text-white font-mono uppercase focus:outline-none focus:ring-2 focus:ring-cyan-500 text-sm font-medium">
            </div>

            <!-- Crate Count -->
            <div>
                <label for="crate_count" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Offloaded Crate Count (පෙට්ටි ගණන)
                </label>
                <input type="number" id="crate_count" name="crate_count" min="0" value="20"
                    class="block w-full py-3 px-4 border border-slate-700 rounded-xl bg-slate-950 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 text-sm font-medium">
            </div>
        </div>

        <div class="border-t border-slate-800 pt-6">
            <h3 class="text-sm font-extrabold text-amber-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <span>Mudiyala Offloading & Cash Expenses</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Helper Batta (ගෝලයාගේ බටා) -->
                <div class="bg-amber-950/30 p-4 rounded-xl border border-amber-500/30 space-y-2">
                    <label for="helper_batta" class="block text-xs font-bold text-amber-300 uppercase tracking-wider">
                        Helper Batta / ගෝලයාගේ බටා (LKR)
                    </label>
                    <input type="number" step="100" id="helper_batta" name="helper_batta" value="<?= number_format($defaultBatta, 2, '.', '') ?>"
                        oninput="updateExpensePreview()"
                        class="block w-full py-2.5 px-3 border border-amber-500/50 rounded-lg bg-slate-950 text-amber-300 font-mono font-bold text-base focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <p class="text-[10px] text-amber-400/80">Paid in instant cash from Mudiyala counter upon lorry offload.</p>
                </div>

                <!-- Freight Cost (පටවන ලොරියේ කුලිය) -->
                <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-2">
                    <label for="freight_cost" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                        Lorry Freight / ලොරි කුලිය (LKR)
                    </label>
                    <input type="number" step="500" id="freight_cost" name="freight_cost" value="18000.00"
                        oninput="updateExpensePreview()"
                        class="block w-full py-2.5 px-3 border border-slate-700 rounded-lg bg-slate-900 text-white font-mono text-base focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    <p class="text-[10px] text-slate-400">Deducted from supplier end-of-day settlement.</p>
                </div>

                <!-- Coolie / Handling Charges -->
                <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-2">
                    <label for="coolie_charges" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                        Coolie Charges / නාට්ටාමි කුලිය (LKR)
                    </label>
                    <input type="number" step="100" id="coolie_charges" name="coolie_charges" value="<?= number_format($defaultCoolie, 2, '.', '') ?>"
                        oninput="updateExpensePreview()"
                        class="block w-full py-2.5 px-3 border border-slate-700 rounded-lg bg-slate-900 text-white font-mono text-base focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    <p class="text-[10px] text-slate-400">Market floor coolie handling fee.</p>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                Special Remarks / Notes
            </label>
            <textarea id="notes" name="notes" rows="2" placeholder="e.g. High grade Yellowfin consignment from Beruwala jetty..."
                class="block w-full py-3 px-4 border border-slate-700 rounded-xl bg-slate-950 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 text-sm"></textarea>
        </div>

        <!-- Expense Voucher Summary Box -->
        <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-receipt text-lg"></i>
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-300">Instant Cash Expense Voucher</div>
                    <div class="text-[11px] text-slate-400">Helper Batta + Coolie + Freight total to be logged</div>
                </div>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400 block">Total Offload Advance:</span>
                <span id="preview-total-advance" class="text-lg font-black text-amber-400 font-mono">Rs. 23,900.00</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4 pt-4 border-t border-slate-800">
            <a href="index.php?page=consignments_list" class="px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-sm">
                Cancel
            </a>
            <button type="submit"
                class="px-8 py-3 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-extrabold text-sm shadow-lg shadow-amber-900/30 transition-all flex items-center gap-2">
                <i class="fa-solid fa-check-double"></i>
                <span>SAVE CONSIGNMENT & PROCEED TO GRADING</span>
            </button>
        </div>
    </form>
</div>

<script>
    function updateExpensePreview() {
        const batta = parseFloat(document.getElementById('helper_batta').value) || 0;
        const freight = parseFloat(document.getElementById('freight_cost').value) || 0;
        const coolie = parseFloat(document.getElementById('coolie_charges').value) || 0;
        const total = batta + freight + coolie;
        document.getElementById('preview-total-advance').textContent = 'Rs. ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.addEventListener('DOMContentLoaded', updateExpensePreview);
</script>
