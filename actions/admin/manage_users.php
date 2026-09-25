<?php
// actions/admin/manage_users.php
// User Account Creation & Staff Role Permission Management

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php?page=admin_users');
    exit;
}

$action = sanitizeInput($_POST['action'] ?? 'create');

if ($action === 'create') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $role     = sanitizeInput($_POST['role'] ?? 'cashier');

    if (empty($username) || empty($password) || empty($fullName)) {
        setFlash('danger', 'Username, password, and full name are required.');
        header('Location: ../../index.php?page=admin_users');
        exit;
    }

    try {
        $db = getDB();
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $stmtCheck->execute([':u' => $username]);
        if ($stmtCheck->fetch()) {
            throw new Exception("Username '{$username}' is already taken.");
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (username, password_hash, full_name, role, status, created_at)
            VALUES (:u, :p, :fn, :role, 'active', NOW())
        ");
        $stmt->execute([
            ':u'    => $username,
            ':p'    => $passwordHash,
            ':fn'   => $fullName,
            ':role' => $role
        ]);

        setFlash('success', "Staff Account '{$username}' ({$fullName}) created successfully as " . strtoupper($role) . ".");
        header('Location: ../../index.php?page=admin_users');
        exit;

    } catch (Exception $e) {
        setFlash('danger', 'Failed to create user: ' . $e->getMessage());
        header('Location: ../../index.php?page=admin_users');
        exit;
    }
} elseif ($action === 'toggle_status') {
    $userId = intval($_POST['user_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'active');

    if ($userId <= 0) {
        setFlash('danger', 'Invalid user ID.');
        header('Location: ../../index.php?page=admin_users');
        exit;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET status = :s WHERE id = :uid");
        $stmt->execute([':s' => $status, ':uid' => $userId]);

        setFlash('success', "User account status updated to " . strtoupper($status) . ".");
        header('Location: ../../index.php?page=admin_users');
        exit;
    } catch (Exception $e) {
        setFlash('danger', 'Failed to update user status: ' . $e->getMessage());
        header('Location: ../../index.php?page=admin_users');
        exit;
    }
}
