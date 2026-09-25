<?php
// views/admin/users/index.php
// User Accounts & Staff Permissions Management Panel

require_once __DIR__ . '/../../../includes/auth_check.php';
requireRole(['admin']);

$db = getDB();

// Fetch staff users
$stmtUsers = $db->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmtUsers->fetchAll();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-lg">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-cyan-400 uppercase tracking-wider">
                <span>Executive Governance</span>
                <span>/</span>
                <span>User Accounts</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight mt-1 flex items-center gap-2">
                <i class="fa-solid fa-users-gear text-cyan-400"></i>
                <span>Staff Accounts & Permission Roles</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Manage user credentials for Admins, Cashiers, and Scales Inspectors (Weighers).
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Create User Form (5 cols) -->
        <div class="lg:col-span-5 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-sm font-extrabold text-white pb-3 border-b border-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-cyan-400"></i>
                <span>Create Staff User Account</span>
            </h3>

            <form action="actions/admin/manage_users.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">

                <div>
                    <label for="full_name" class="block text-xs font-bold text-slate-300 uppercase mb-1">
                        Full Name <span class="text-red-400">*</span>
                    </label>
                    <input type="text" id="full_name" name="full_name" required placeholder="e.g. Kasun Fernando"
                        class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white font-medium text-xs focus:outline-none focus:ring-2 focus:ring-cyan-500">
                </div>

                <div>
                    <label for="username" class="block text-xs font-bold text-slate-300 uppercase mb-1">
                        Username <span class="text-red-400">*</span>
                    </label>
                    <input type="text" id="username" name="username" required placeholder="e.g. cashier2"
                        class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white font-mono text-xs focus:outline-none focus:ring-2 focus:ring-cyan-500">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-300 uppercase mb-1">
                        Password <span class="text-red-400">*</span>
                    </label>
                    <input type="password" id="password" name="password" required placeholder="••••••••"
                        class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white font-mono text-xs focus:outline-none focus:ring-2 focus:ring-cyan-500">
                </div>

                <div>
                    <label for="role" class="block text-xs font-bold text-slate-300 uppercase mb-1">
                        Access Role <span class="text-red-400">*</span>
                    </label>
                    <select id="role" name="role" required
                        class="block w-full py-2.5 px-3 border border-slate-700 rounded-xl bg-slate-950 text-white font-bold text-xs">
                        <option value="cashier">Cashier (Auction POS Billing & Gate Pass)</option>
                        <option value="offloader">Weigher / Offloader (Lorry Offloading & Fish Grading)</option>
                        <option value="admin">Admin (Main Mudiyala Full Access)</option>
                    </select>
                </div>

                <button type="submit"
                    class="w-full py-3.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-user-check"></i>
                    <span>CREATE USER ACCOUNT</span>
                </button>
            </form>
        </div>

        <!-- Right: Active Users Table (7 cols) -->
        <div class="lg:col-span-7 bg-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-white pb-3 border-b border-slate-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-users text-cyan-400"></i>
                    <span>Registered Staff Accounts</span>
                </h3>

                <div class="overflow-x-auto max-h-[420px]">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 bg-slate-950 text-slate-400 uppercase border-b border-slate-800">
                            <tr>
                                <th class="p-3">User ID</th>
                                <th class="p-3">Full Name</th>
                                <th class="p-3">Username</th>
                                <th class="p-3 text-center">Role</th>
                                <th class="p-3 text-center">Status</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-slate-800/40">
                                    <td class="p-3 font-mono text-slate-400">#<?= $u['id'] ?></td>
                                    <td class="p-3 font-bold text-white"><?= htmlspecialchars($u['full_name']) ?></td>
                                    <td class="p-3 font-mono font-bold text-cyan-300"><?= htmlspecialchars($u['username']) ?></td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $u['role'] === 'admin' ? 'bg-cyan-500/20 text-cyan-300' : ($u['role'] === 'cashier' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300') ?>">
                                            <?= $u['role'] ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $u['status'] === 'active' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
                                            <?= $u['status'] ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-right">
                                        <?php if ($u['username'] !== 'admin'): ?>
                                            <form action="actions/admin/manage_users.php" method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                <button type="submit" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-xs font-bold rounded text-slate-300">
                                                    <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
