<?php
// Kiểm tra session đã được start chưa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Dùng __DIR__ để đảm bảo đường dẫn đúng từ bất kỳ đâu include file này
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../functions/avatar_functions.php';

// Lấy thông tin user hiện tại để hiển thị avatar
$user_id = $_SESSION['user_id'] ?? null;
$current_user = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT id, full_name, role, avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();
}
$current_user = $current_user ?: ['full_name' => $_SESSION['full_name'] ?? 'User', 'role' => $_SESSION['role'] ?? 'admin', 'avatar' => null];
?>
<header class="header header-sticky p-0 mb-4">
  <div class="container-fluid border-bottom px-4 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
      <button class="header-toggler" type="button" onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
        <svg class="icon icon-lg"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-menu"></use></svg>
      </button>
    </div>
    <ul class="header-nav ms-auto d-flex align-items-center" style="list-style: none; margin: 0; padding: 0;">
      <li class="nav-item dropdown">
        <a class="nav-link py-0 pe-0 d-flex align-items-center" data-coreui-toggle="dropdown" href="#" role="button" style="text-decoration: none; color: inherit;">
          <?= getAvatarDisplay($current_user, 'md') ?>
          <span class="ms-2 text-dark" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500;"><?= htmlspecialchars($current_user['full_name']) ?></span>
        </a>
        <div class="dropdown-menu dropdown-menu-end pt-0">
          <a class="dropdown-item" href="profile.php">
            <svg class="icon me-2"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use></svg> Quản lý tài khoản
          </a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="logout.php">
            <svg class="icon me-2"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-account-logout"></use></svg> Đăng xuất
          </a>
        </div>
      </li>
    </ul>
  </div>
</header>