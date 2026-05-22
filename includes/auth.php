<?php
// Authentication helpers - included on pages that need login/role checks
// Always call session_start() before including this file

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
        header("Location: index.php");
        exit();
    }
}

function isSeller() {
    return isLoggedIn() && in_array($_SESSION['role'], ['seller', 'admin']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function currentRole() {
    return $_SESSION['role'] ?? null;
}
?>