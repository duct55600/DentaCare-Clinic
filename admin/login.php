<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    $redirect = match ($_SESSION['role']) {
        'admin' => 'admin_dashboard.php',
        'receptionist' => 'receptionist_dashboard.php',
        'doctor' => 'doctor_dashboard.php',
        default => 'login.php'
    };
    header("Location: $redirect"); exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Đăng nhập | DentaCare</title>
  <link rel="icon" href="../assets/favicon/favicon-32x32.png">
  <link rel="stylesheet" href="vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="css/vendors/simplebar.css">
  <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-md-8 col-lg-5">
        <div class="card shadow-lg border-0">
          <div class="card-body p-5">
            <div class="text-center mb-5">
              <h1 class="fw-bold text-primary">DentaCare</h1>
              <p class="text-muted">Hệ thống quản lý phòng khám nha khoa</p>
            </div>

            <?php if ($error): ?>
              <div class="alert alert-danger alert-dismissible fade show">
                <strong>Lỗi:</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <form action="../handle/auth_process.php" method="POST" class="needs-validation" novalidate>
              <div class="mb-3">
                <label class="form-label fw-semibold">Tên đăng nhập</label>
                <input type="text" name="username" class="form-control form-control-lg" 
                       placeholder="Tên đăng nhập" required autofocus>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Mật khẩu</label>
                <input type="password" name="password" class="form-control form-control-lg" 
                       placeholder="Mật khẩu" required>
              </div>

              <div class="mb-4">
                <label class="form-label fw-semibold">Đăng nhập với vai trò</label>
                <select name="role" class="form-select form-select-lg" required>
                  <option value="">-- Chọn vai trò --</option>
                  <option value="admin">Quản trị viên (Admin)</option>
                  <option value="receptionist">Lễ tân</option>
                  <option value="doctor">Bác sĩ</option>
                </select>
              </div>

              <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                ĐĂNG NHẬP
              </button>
            </form>

            <div class="text-center mt-4">
              <p class="text-muted">
                Chưa có tài khoản? 
                <a href="register_public.php" class="text-primary fw-bold">Đăng ký ngay</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php if ($error): ?>
  <script>Swal.fire('Lỗi đăng nhập!', '<?= addslashes($error) ?>', 'error')</script>
  <?php endif; ?>
</body>
</html>