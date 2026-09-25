<?php
// views/consignments/list.php
// Consignment List & Offloading Register

requireAuth();
$db = getDB();

$stmt = $db->query("
    SELECT 
        c.*, 
        s.name AS supplier_name, 
        s.boat_name,
        (SELECT COUNT(id) FROM fish_items WHERE consignment_id = c.id) AS total_graded_items,
        (SELECT COUNT(id) FROM fish_items WHERE consignment_id = c.id AND status IN ('sold', 'settled')) AS sold_items
    FROM consignments c
    JOIN suppliers s ON c.supplier_id = s.id
    ORDER BY c.id DESC
");
$consignments = $stmt->fetchAll();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-truck-ramp-box text-amber-400"></i>
                <span>Lorry Consignments & Offloading Register</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Inward harbor shipments, Lorry registration, Helper Batta disbursements, and consignment status tracking.
            </p>
        </div>
        <a href="index.php?page=consignments_new" class="px-5 py-3 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-extrabold text-xs shadow-lg shadow-amber-900/30 border border-amber-400/30 flex items-center gap-2 transition-all">
            <i class="fa-solid fa-plus text-sm"></i>
            <span>NEW LORRY OFFLOAD</span>
        </a>
    </div>

    <!-- Consignments Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-950 text-slate-400 border-b border-slate-800 uppercase tracking-wider">
                        <th class="p-4">Consignment #</th>
                        <th class="p-4">Lorry #</th>
                        <th class="p-4">Harbor & Boat</th>
                        <th class="p-4">Supplier (මුදලාලි)</th>
                        <th class="p-4 text-center">Crates</th>
                        <th class="p-4 text-right">Helper Batta</th>
                        <th class="p-4 text-right">Freight</th>
                        <th class="p-4 text-center">Graded Fish</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <?php if (empty($consignments)): ?>
                        <tr>
                            <td colspan="10" class="p-8 text-center text-slate-500 font-medium">No consignments registered yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($consignments as $c): ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="p-4 font-mono font-bold text-cyan-400"><?= $c['consignment_no'] ?></td>
                                <td class="p-4 font-mono font-bold text-amber-300"><?= htmlspecialchars($c['lorry_number']) ?></td>
                                <td class="p-4 text-slate-200 font-semibold">
                                    <?= htmlspecialchars($c['harbor_origin']) ?>
                                    <span class="block text-[10px] text-slate-400 font-normal"><?= htmlspecialchars($c['boat_name'] ?? 'N/A') ?></span>
                                </td>
                                <td class="p-4 text-slate-300"><?= htmlspecialchars($c['supplier_name']) ?></td>
                                <td class="p-4 text-center font-bold text-slate-200"><?= $c['crate_count'] ?></td>
                                <td class="p-4 text-right font-mono font-bold text-amber-400"><?= formatLKR($c['helper_batta']) ?></td>
                                <td class="p-4 text-right font-mono text-slate-300"><?= formatLKR($c['freight_cost']) ?></td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 rounded bg-slate-800 border border-slate-700 text-cyan-300 font-bold text-[11px]">
                                        <?= $c['total_graded_items'] ?> Fish (<?= $c['sold_items'] ?> Sold)
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <?php
                                        $statusClass = match($c['status']) {
                                            'unloaded'   => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                                            'graded'     => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
                                            'auctioning' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                            'settled'    => 'bg-slate-800 text-slate-400 border-slate-700',
                                            default      => 'bg-slate-800 text-slate-300'
                                        };
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border <?= $statusClass ?>">
                                        <?= $c['status'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <a href="index.php?page=grading_entry&consignment_id=<?= $c['id'] ?>" class="px-3 py-1.5 bg-cyan-600/20 hover:bg-cyan-600/40 text-cyan-300 rounded-lg text-[11px] font-bold border border-cyan-500/30">
                                        Weigh / Grade
                                    </a>
                                    <?php if ($c['status'] !== 'settled'): ?>
                                        <a href="index.php?page=settlement_calculate&consignment_id=<?= $c['id'] ?>" class="px-3 py-1.5 bg-emerald-600/20 hover:bg-emerald-600/40 text-emerald-300 rounded-lg text-[11px] font-bold border border-emerald-500/30">
                                            Settle EOD
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
