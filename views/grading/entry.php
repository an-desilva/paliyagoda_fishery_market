<?php
// views/grading/entry.php
// Touch & Counter-Friendly Fish Weighing & Grading Board

requireAuth();
$db = getDB();

$selectedConsignmentId = intval($_GET['consignment_id'] ?? 0);

// Fetch active consignments (not settled)
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

// Fetch graded items for selected consignment
$gradedItems = [];
$selectedConsignment = null;
if ($selectedConsignmentId > 0) {
    foreach ($consignments as $c) {
        if ($c['id'] == $selectedConsignmentId) {
            $selectedConsignment = $c;
            break;
        }
    }

    $stmtItems = $db->prepare("
        SELECT * FROM fish_items 
        WHERE consignment_id = :cid 
        ORDER BY id DESC
    ");
    $stmtItems->execute([':cid' => $selectedConsignmentId]);
    $gradedItems = $stmtItems->fetchAll();
}
?>

<div class="space-y-6">
    <!-- Header & Consignment Selector -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <div class="text-xs font-semibold text-cyan-400 uppercase tracking-wider">Pelagic Fish Ingestion</div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-weight-scale text-emerald-400"></i>
                <span>Weighing, Grading & Tagging Board</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Individual pelagic fish tracking (Yellowfin Tuna, Sailfish, Marlin, Swordfish). Net Weight = Gross Weight - Tare (Ice/box).
            </p>
        </div>

        <!-- Consignment Dropdown Switcher -->
        <div class="flex items-center gap-3">
            <label for="consignment_select" class="text-xs font-bold text-slate-300 uppercase whitespace-nowrap">Target Lorry:</label>
            <select id="consignment_select" onchange="location.href='index.php?page=grading_entry&consignment_id=' + this.value"
                class="py-2.5 px-4 border border-cyan-500/40 rounded-xl bg-slate-950 text-cyan-300 font-semibold text-xs focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-inner">
                <?php if (empty($consignments)): ?>
                    <option value="">No Active Lorries Found</option>
                <?php else: ?>
                    <?php foreach ($consignments as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedConsignmentId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['lorry_number']) ?> (<?= htmlspecialchars($c['supplier_name']) ?> - <?= htmlspecialchars($c['harbor_origin']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <?php if (!$selectedConsignment): ?>
        <div class="bg-slate-900 p-8 rounded-2xl border border-slate-800 text-center text-slate-400 space-y-4">
            <i class="fa-solid fa-truck-ramp-box text-4xl text-slate-600"></i>
            <p>No active lorry consignment selected. Please register a lorry first.</p>
            <a href="index.php?page=consignments_new" class="inline-block px-5 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl">
                Register New Lorry Offload
            </a>
        </div>
    <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Weighing & Grading Input Form (5 cols) -->
            <div class="lg:col-span-5 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                        <i class="fa-solid fa-plus-circle text-emerald-400"></i>
                        <span>Ingest Graded Fish Item</span>
                    </h3>
                    <span class="text-[11px] font-mono text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded border border-cyan-500/20">
                        <?= $selectedConsignment['consignment_no'] ?>
                    </span>
                </div>

                <form action="actions/grading/save_fish_item.php" method="POST" class="space-y-4" id="gradingForm">
                    <input type="hidden" name="consignment_id" value="<?= $selectedConsignmentId ?>">

                    <!-- Fish Species Picker -->
                    <div>
                        <label for="species" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Fish Species / මත්ස්‍ය වර්ගය <span class="text-red-400">*</span>
                        </label>
                        <select id="species" name="species" required
                            class="block w-full py-3 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <?php foreach (FISH_SPECIES as $label => $val): ?>
                                <option value="<?= $label ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Gross & Tare Weight Inputs -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="gross_weight" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                                Gross Weight (kg) <span class="text-red-400">*</span>
                            </label>
                            <input type="number" step="0.1" id="gross_weight" name="gross_weight" required value="48.5" autofocus
                                oninput="calculateNetWeight()"
                                class="block w-full py-3 px-3 border border-slate-700 rounded-xl bg-slate-950 text-emerald-300 font-mono font-bold text-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="tare_weight" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                                Tare / Ice Deduction (kg)
                            </label>
                            <input type="number" step="0.1" id="tare_weight" name="tare_weight" value="2.5"
                                oninput="calculateNetWeight()"
                                class="block w-full py-3 px-3 border border-slate-700 rounded-xl bg-slate-950 text-rose-300 font-mono text-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>

                    <!-- Computed Net Weight Banner -->
                    <div class="bg-slate-950 p-4 rounded-xl border border-emerald-500/40 flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Net Billable Weight</div>
                            <div class="text-[10px] text-slate-500">Gross Weight - Tare Deduction</div>
                        </div>
                        <div class="text-right">
                            <span id="net-weight-display" class="text-2xl font-black text-emerald-400 font-mono">46.00 kg</span>
                        </div>
                    </div>

                    <!-- Meat Grade Selection -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                            Meat Quality Grade / මාළු ශ්‍රේණිය <span class="text-red-400">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="cursor-pointer border border-slate-700 rounded-xl p-3 bg-slate-950 text-center hover:border-emerald-500 transition-all has-[:checked]:bg-emerald-950/60 has-[:checked]:border-emerald-500">
                                <input type="radio" name="grade" value="A_export" checked class="sr-only">
                                <span class="block text-xs font-bold text-emerald-300">Grade A</span>
                                <span class="block text-[9px] text-slate-400">Export / Sashimi</span>
                            </label>
                            <label class="cursor-pointer border border-slate-700 rounded-xl p-3 bg-slate-950 text-center hover:border-cyan-500 transition-all has-[:checked]:bg-cyan-950/60 has-[:checked]:border-cyan-500">
                                <input type="radio" name="grade" value="B_local" class="sr-only">
                                <span class="block text-xs font-bold text-cyan-300">Grade B</span>
                                <span class="block text-[9px] text-slate-400">Local Market</span>
                            </label>
                            <label class="cursor-pointer border border-slate-700 rounded-xl p-3 bg-slate-950 text-center hover:border-amber-500 transition-all has-[:checked]:bg-amber-950/60 has-[:checked]:border-amber-500">
                                <input type="radio" name="grade" value="C_canning" class="sr-only">
                                <span class="block text-xs font-bold text-amber-300">Grade C</span>
                                <span class="block text-[9px] text-slate-400">Canning / Dried</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Tag Button -->
                    <button type="submit"
                        class="w-full py-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-sm shadow-xl shadow-emerald-900/40 border border-emerald-400/30 transition-all hover:scale-[1.01] flex items-center justify-center gap-2">
                        <i class="fa-solid fa-barcode text-lg"></i>
                        <span>GENERATE TAG & SAVE FISH ITEM</span>
                    </button>
                </form>
            </div>

            <!-- Right Column: Graded Fish Items Table (7 cols) -->
            <div class="lg:col-span-7 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                        <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                            <i class="fa-solid fa-tags text-cyan-400"></i>
                            <span>Consignment Graded Inventory</span>
                        </h3>
                        <span class="text-xs font-bold text-slate-300">
                            Total: <span class="text-cyan-400 font-mono"><?= count($gradedItems) ?></span> Items Graded
                        </span>
                    </div>

                    <div class="overflow-x-auto max-h-[480px]">
                        <table class="w-full text-left text-xs">
                            <thead class="sticky top-0 bg-slate-950 text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                <tr>
                                    <th class="p-3">Tag Code</th>
                                    <th class="p-3">Species</th>
                                    <th class="p-3 text-right">Gross</th>
                                    <th class="p-3 text-right">Net Weight</th>
                                    <th class="p-3 text-center">Grade</th>
                                    <th class="p-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <?php if (empty($gradedItems)): ?>
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-slate-500 font-medium">
                                            No fish items graded for this consignment yet. Use the form on the left to weigh fish.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($gradedItems as $item): ?>
                                        <tr class="hover:bg-slate-800/50 transition-colors">
                                            <td class="p-3 font-mono font-bold text-cyan-300 flex items-center gap-1.5">
                                                <i class="fa-solid fa-barcode text-slate-500 text-sm"></i>
                                                <span><?= $item['tag_code'] ?></span>
                                            </td>
                                            <td class="p-3 text-slate-200 font-medium"><?= htmlspecialchars($item['species']) ?></td>
                                            <td class="p-3 text-right font-mono text-slate-400"><?= formatKg($item['gross_weight']) ?></td>
                                            <td class="p-3 text-right font-mono font-bold text-emerald-400"><?= formatKg($item['net_weight']) ?></td>
                                            <td class="p-3 text-center">
                                                <?php
                                                    $gBadge = match($item['grade']) {
                                                        'A_export'  => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                                        'B_local'   => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
                                                        'C_canning' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                                        default     => 'bg-slate-800 text-slate-300'
                                                    };
                                                ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border <?= $gBadge ?>">
                                                    <?= str_replace('_', ' ', $item['grade']) ?>
                                                </span>
                                            </td>
                                            <td class="p-3 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $item['status'] === 'available' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-400' ?>">
                                                    <?= $item['status'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <div>Lorry: <span class="font-bold text-white"><?= htmlspecialchars($selectedConsignment['lorry_number']) ?></span></div>
                    <a href="index.php?page=billing_pos" class="font-bold text-cyan-400 hover:text-cyan-300 flex items-center gap-1">
                        <span>Proceed to POS Auction Billing</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
    function calculateNetWeight() {
        const gross = parseFloat(document.getElementById('gross_weight').value) || 0;
        const tare = parseFloat(document.getElementById('tare_weight').value) || 0;
        const net = Math.max(0.1, gross - tare);
        document.getElementById('net-weight-display').textContent = net.toFixed(2) + ' kg';
    }
</script>
