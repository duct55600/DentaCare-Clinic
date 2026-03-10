<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? '';

if (empty($username) || empty($password) || empty($role)) {
    $_SESSION['login_error'] = 'Vui lòng điền đầy đủ thông tin!';
    header('Location: ../admin/login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ? AND status = 'active'");
$stmt->execute([$username, $role]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    // Đăng nhập thành công
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];

    unset($_SESSION['login_error']);

    // Chuyển hướng đúng dashboard
    $redirect = match ($user['role']) {
        'admin'        => '../admin/admin_dashboard.php',
        'receptionist' => '../admin/receptionist_dashboard.php',
        'doctor'       => '../admin/doctor_dashboard.php',
    };
    header("Location: $redirect");
    exit;
} else {
    $_SESSION['login_error'] = 'Sai tên đăng nhập, mật khẩu hoặc vai trò!';
    header('Location: ../admin/login.php');
    exit;
}