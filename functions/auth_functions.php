<?php
// Kiểm tra đăng nhập
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Kiểm tra role
function require_role($role) {
    if (!is_logged_in() || $_SESSION['role'] !== $role) {
        header('Location: ../admin/login.php');
        exit;
    }
}

// Đăng xuất
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>