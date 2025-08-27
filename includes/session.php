<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function getUserName() {
    return isset($_SESSION['name']) ? $_SESSION['name'] : null;
}

function getUserEmail() {
    return isset($_SESSION['email']) ? $_SESSION['email'] : null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (getUserRole() !== $role) {
        header("Location: index.php");
        exit();
    }
}
?>
