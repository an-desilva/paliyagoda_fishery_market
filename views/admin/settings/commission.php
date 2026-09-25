<?php
// views/admin/settings/commission.php
// System Dynamic Commission & Fee Rates Settings Panel

require_once __DIR__ . '/../../../includes/auth_check.php';
requireRole(['admin']);

$commRate     = (float)getSetting('default_commission_rate', 6.00);
$handlingFee  = (float)getSetting('default_handling_fee', 250.00);
$helperBatta  = (float)getSetting('default_helper_batta', 3500.00);
$coolieCharge = (float)getSetting('default_coolie_charge', 2400.00);
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-teal-400 uppercase tracking-wider">
                <span>Executive Governance</span>
                <span>/</span>
                <span>System Configuration</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-teal-400"></i>
                <span>Commission & Tariff Rates Configuration</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Configure standard Mudiyala commission rate %, floor handling fees, and baseline helper batta rates.
            </p>
        </div>
    </div>

    <!-- Configuration Form -->
    <form action="actions/admin/save_rates.php" method="POST" class="bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Default Commission Rate % -->
            <div class="bg-slate-950 p-5 rounded-xl border border-slate-800 space-y-2">
                <label for="default_commission_rate" class="block text-xs font-extrabold text-teal-300 uppercase">
                    Mudiyala Commission Rate (%) <span class="text-red-400">*</span>
                </label>
                <input type="number" step="0.25" id="default_commission_rate" name="default_commission_rate" value="<?= number_format($commRate, 2, '.', '') ?>" required
                    class="block w-full py-3 px-3 border border-slate-700 rounded-xl bg-slate-900 text-teal-300 font-mono font-bold text-xl focus:outline-none focus:ring-2 focus:ring-teal-500">
                <p class="text-[10px] text-slate-400">Standard percentage deducted from gross auction sales during boat owner settlements.</p>
            </div>

            <!-- Floor Handling Fee -->
            <div class="bg-slate-950 p-5 rounded-xl border border-slate-800 space-y-2">
                <label for="default_handling_fee" class="block text-xs font-extrabold text-cyan-300 uppercase">
                    POS Bill Handling Fee (LKR) <span class="text-red-400">*</span>
                </label>
                <input type="number" step="10" id="default_handling_fee" name="default_handling_fee" value="<?= number_format($handlingFee, 2, '.', '') ?>" required
                    class="block w-full py-3 px-3 border border-slate-700 rounded-xl bg-slate-900 text-cyan-300 font-mono font-bold text-xl focus:outline-none focus:ring-2 focus:ring-cyan-500">
                <p class="text-[10px] text-slate-400">Default handling fee charged on POS invoices issued to buyers.</p>
            </div>

            <!-- Helper Batta Baseline -->
            <div class="bg-slate-950 p-5 rounded-xl border border-slate-800 space-y-2">
                <label for="default_helper_batta" class="block text-xs font-extrabold text-amber-300 uppercase">
                    Baseline Helper Batta / ගෝල බටා (LKR) <span class="text-red-400">*</span>
                </label>
                <input type="number" step="100" id="default_helper_batta" name="default_helper_batta" value="<?= number_format($helperBatta, 2, '.', '') ?>" required
                    class="block w-full py-3 px-3 border border-slate-700 rounded-xl bg-slate-900 text-amber-300 font-mono font-bold text-xl focus:outline-none focus:ring-2 focus:ring-amber-500">
                <p class="text-[10px] text-slate-400">Default petty cash allowance paid to lorry helper upon offloading.</p>
            </div>

            <!-- Coolie Charge Baseline -->
            <div class="bg-slate-950 p-5 rounded-xl border border-slate-800 space-y-2">
                <label for="default_coolie_charge" class="block text-xs font-extrabold text-slate-300 uppercase">
                    Baseline Coolie Charge (LKR) <span class="text-red-400">*</span>
                </label>
                <input type="number" step="100" id="default_coolie_charge" name="default_coolie_charge" value="<?= number_format($coolieCharge, 2, '.', '') ?>" required
                    class="block w-full py-3 px-3 border border-slate-700 rounded-xl bg-slate-900 text-white font-mono font-bold text-xl focus:outline-none focus:ring-2 focus:ring-cyan-500">
                <p class="text-[10px] text-slate-400">Standard market floor coolie unloading charge.</p>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex justify-end">
            <button type="submit"
                class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white font-extrabold text-sm shadow-xl transition-all flex items-center gap-2">
                <i class="fa-solid fa-save text-lg"></i>
                <span>SAVE SYSTEM SETTINGS</span>
            </button>
        </div>
    </form>
</div>
