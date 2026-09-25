<?php
// config/helpers.php
// Utility helper functions (Currency, Weight formatting, Sanitization, Flash Messages, Settings)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format currency in Sri Lankan Rupees (LKR)
 */
function formatLKR(float|int $amount): string {
    return 'Rs. ' . number_format($amount, 2);
}

/**
 * Format weight in kilograms
 */
function formatKg(float|int $weight): string {
    return number_format($weight, 2) . ' kg';
}

/**
 * Sanitize string input
 */
function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Fetch dynamic setting from DB
 */
function getSetting(string $key, mixed $default = null): mixed {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Generate unique tag code (e.g. TAG-2026-X8F2)
 */
function generateTagCode(): string {
    $datePart = date('Ymd');
    $randPart = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    return "TAG-{$datePart}-{$randPart}";
}

/**
 * Generate invoice number (e.g. INV-2026-0089)
 */
function generateInvoiceNo(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) AS max_id FROM invoices");
    $row = $stmt->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;
    return 'INV-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate consignment number (e.g. CNS-2026-0042)
 */
function generateConsignmentNo(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) AS max_id FROM consignments");
    $row = $stmt->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;
    return 'CNS-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate settlement number (e.g. STL-2026-0012)
 */
function generateSettlementNo(PDO $db): string {
    $stmt = $db->query("SELECT MAX(id) AS max_id FROM settlements");
    $row = $stmt->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;
    return 'STL-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate Security Hash for Gate Pass verification
 */
function generateSecurityHash(string $invoiceNo, float $amount, int $buyerId): string {
    return strtoupper(substr(hash('sha256', $invoiceNo . '|' . $amount . '|' . $buyerId . '|PELIYAGODA_SALT'), 0, 16));
}

/**
 * Flash messaging
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Auth Guard
 */
function requireAuth(): array {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }
    return $_SESSION['user'];
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}
