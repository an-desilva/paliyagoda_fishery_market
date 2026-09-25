<?php
// index.php
// Master Application Router Entry Point with RBAC Route Guarding

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/auth_check.php';

$page = $_GET['page'] ?? 'dashboard';

// Public pages
if ($page === 'login') {
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/views/auth/login.php';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Protected pages guard
$user = currentUser();
if (!$user) {
    header('Location: index.php?page=login');
    exit;
}

// Include Header
include __DIR__ . '/includes/header.php';

// Route Handler
switch ($page) {
    case 'dashboard':
        include __DIR__ . '/views/dashboard.php';
        break;

    // Executive Admin Governance Routes
    case 'admin_dashboard':
        requireRole(['admin']);
        include __DIR__ . '/views/admin/dashboard.php';
        break;

    case 'admin_buyers_credit':
        requireRole(['admin']);
        include __DIR__ . '/views/admin/buyers/credit_limits.php';
        break;

    case 'admin_suppliers_loans':
        requireRole(['admin']);
        include __DIR__ . '/views/admin/suppliers/manage_loans.php';
        break;

    case 'admin_settings_commission':
        requireRole(['admin']);
        include __DIR__ . '/views/admin/settings/commission.php';
        break;

    case 'admin_users':
        requireRole(['admin']);
        include __DIR__ . '/views/admin/users/index.php';
        break;

    case 'admin_reports_settlements':
        requireRole(['admin']);
        include __DIR__ . '/views/admin/reports/settlements_audit.php';
        break;

    case 'admin_reports_expenses':
        requireRole(['admin']);
        include __DIR__ . '/views/admin/reports/cash_expenses.php';
        break;

    // Operational Modules
    case 'consignments_list':
        include __DIR__ . '/views/consignments/list.php';
        break;

    case 'consignments_new':
        include __DIR__ . '/views/consignments/new.php';
        break;

    case 'grading_entry':
        include __DIR__ . '/views/grading/entry.php';
        break;

    case 'billing_pos':
        include __DIR__ . '/views/billing/pos.php';
        break;

    case 'invoice_print':
        include __DIR__ . '/views/billing/invoice_print.php';
        break;

    case 'credit_ledger':
        include __DIR__ . '/views/billing/credit_ledger.php';
        break;

    case 'settlement_calculate':
        include __DIR__ . '/views/settlements/calculate.php';
        break;

    case 'settlement_voucher':
        include __DIR__ . '/views/settlements/voucher_print.php';
        break;

    case 'report_daily':
        include __DIR__ . '/views/reports/daily_summary.php';
        break;

    case 'report_lorry':
        include __DIR__ . '/views/reports/lorry_expenses.php';
        break;

    default:
        echo "<div class='p-8 bg-slate-900 rounded-2xl border border-slate-800 text-center text-slate-300 font-bold'>404 - Page Not Found</div>";
        break;
}

// Include Footer
include __DIR__ . '/includes/footer.php';
