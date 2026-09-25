<?php
// includes/auth_check.php
// Session & Role-Based Access Control (RBAC) Middleware

require_once __DIR__ . '/../config/helpers.php';

/**
 * Enforce logged-in user session
 */
function checkAuth(): array {
    return requireAuth();
}

/**
 * Enforce RBAC roles (e.g. requireRole(['admin']))
 */
function requireRole(array $allowedRoles): array {
    $user = requireAuth();
    if (!in_array($user['role'], $allowedRoles)) {
        setFlash('danger', 'Access Denied: You do not have permission to access this module.');
        header('Location: index.php?page=dashboard');
        exit;
    }
    return $user;
}

/**
 * Helper to check if current user has a role
 */
function hasRole(string|array $roles): bool {
    $user = currentUser();
    if (!$user) return false;
    if (is_string($roles)) {
        return $user['role'] === $roles;
    }
    return in_array($user['role'], $roles);
}
