<?php
// Sidebar dùng chung cho vai trò Lễ tân
?>
<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
  <div class="sidebar-header border-bottom p-3">
    <h5 class="mb-0 text-white">Lễ tân</h5>
  </div>
  <ul class="sidebar-nav" data-coreui="navigation" data-simplebar="">
    <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'receptionist_dashboard.php' ? ' active' : '' ?>" href="receptionist_dashboard.php">
      <svg class="nav-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-calendar-check"></use></svg> Lịch chờ duyệt</a></li>
    <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'receptionist_all.php' ? ' active' : '' ?>" href="receptionist_all.php">
      <svg class="nav-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-list"></use></svg> Tất cả lịch hẹn</a></li>
    <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? ' active' : '' ?>" href="profile.php">
      <svg class="nav-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use></svg> Quản lý tài khoản</a></li>
    <li class="nav-item"><a class="nav-link" href="logout.php">
      <svg class="nav-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-account-logout"></use></svg> Đăng xuất</a></li>
  </ul>
</div>


