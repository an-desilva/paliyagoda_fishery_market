<?php
// views/auth/login.php
// Counter-Friendly Login Screen
?>
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-slate-900/90 p-8 rounded-2xl border border-slate-800 shadow-2xl backdrop-blur-md">
        <div class="text-center">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-tr from-cyan-600 to-blue-600 flex items-center justify-center shadow-xl shadow-cyan-500/20 mb-4">
                <i class="fa-solid fa-fish-fins text-white text-3xl"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">
                PELIYAGODA FISH TRADE
            </h2>
            <p class="text-xs text-cyan-400 uppercase tracking-widest mt-1 font-semibold">
                පෑලියගොඩ එක්සත් මත්ස්‍ය වෙළඳ සංකීර්ණය
            </p>
            <p class="text-slate-400 text-xs mt-2">
                Authorized Cashier & Mudiyala Staff Sign In
            </p>
        </div>

        <form class="mt-8 space-y-6" action="actions/auth_login.php" method="POST">
            <div class="rounded-md shadow-sm space-y-4">
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Username / පරිශීලක නාමය
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <input id="username" name="username" type="text" required value="admin"
                            class="block w-full pl-10 pr-3 py-3 border border-slate-700 rounded-xl bg-slate-950 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm font-medium transition-all"
                            placeholder="e.g. admin or cashier1">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Password / මුරපදය
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <input id="password" name="password" type="password" required value="admin123"
                            class="block w-full pl-10 pr-3 py-3 border border-slate-700 rounded-xl bg-slate-950 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm font-medium transition-all"
                            placeholder="••••••••">
                    </div>
                </div>
            </div>

            <div>
                <button type="submit"
                    class="group relative w-full flex justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 shadow-lg shadow-cyan-900/30 transition-all hover:scale-[1.01]">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fa-solid fa-right-to-bracket text-cyan-200 group-hover:text-white"></i>
                    </span>
                    AUTHENTICATE & LOG IN
                </button>
            </div>

            <!-- Pre-populated credentials hint -->
            <div class="bg-slate-950 p-3 rounded-lg border border-slate-800 text-[11px] text-slate-400 space-y-1">
                <div class="font-bold text-slate-300">Quick Test Credentials:</div>
                <div class="flex justify-between"><span>Admin (Sunil Mudiyala):</span> <span class="font-mono text-cyan-400">admin / admin123</span></div>
                <div class="flex justify-between"><span>Cashier (Kusal):</span> <span class="font-mono text-cyan-400">cashier1 / admin123</span></div>
                <div class="flex justify-between"><span>Offloader (Nimal):</span> <span class="font-mono text-cyan-400">offloader1 / admin123</span></div>
            </div>
        </form>
    </div>
</div>
