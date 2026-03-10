<?php
session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_once '../functions/avatar_functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Lấy thông tin user hiện tại
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, full_name, email, role, avatar, status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

// Đảm bảo các key luôn tồn tại
if (!$current_user || !is_array($current_user)) {
    header('Location: login.php');
    exit;
}

// Đảm bảo tất cả các key cần thiết đều tồn tại với giá trị mặc định
$current_user = array_merge([
    'id' => 0,
    'username' => '',
    'full_name' => '',
    'email' => '',
    'role' => '',
    'avatar' => null,
    'status' => 'active'
], $current_user);

$error = '';
$success = '';

// Xử lý cập nhật profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name) || empty($email)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ!";
    } else {
        // Xử lý upload avatar mới (chỉ cho bác sĩ và lễ tân)
        $avatar_path = $current_user['avatar'] ?? null;
        
        if (($current_user['role'] ?? '') !== 'admin' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file = $_FILES['avatar'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = "Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)!";
            } elseif ($file['size'] > $max_size) {
                $error = "Kích thước file không được vượt quá 5MB!";
            } else {
                // Xóa avatar cũ nếu có
                if ($avatar_path && file_exists('../' . $avatar_path)) {
                    unlink('../' . $avatar_path);
                }
                
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = ($current_user['role'] ?? 'user') . '_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $avatar_path = 'uploads/avatars/' . $new_filename;
                } else {
                    $error = "Không thể upload file!";
                }
            }
        }
        
        if (empty($error)) {
            try {
                // Kiểm tra password nếu có thay đổi
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        $error = "Mật khẩu phải có ít nhất 6 ký tự!";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "Mật khẩu xác nhận không khớp!";
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, avatar = ? WHERE id = ?");
                        $stmt->execute([$full_name, $email, $hashed_password, $avatar_path, $user_id]);
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, avatar = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $avatar_path, $user_id]);
                }
                
                if (empty($error)) {
                    // Cập nhật session
                    $_SESSION['full_name'] = $full_name;
                    $success = "Cập nhật thông tin thành công!";
                    
                    // Reload user data với đầy đủ các cột
                    $stmt = $pdo->prepare("SELECT id, username, full_name, email, role, avatar, status FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $reloaded_user = $stmt->fetch();
                    
                    if ($reloaded_user) {
                        $current_user = array_merge([
                            'username' => '',
                            'full_name' => '',
                            'email' => '',
                            'role' => '',
                            'avatar' => null,
                            'status' => 'active'
                        ], $reloaded_user);
                    }
                }
            } catch (PDOException $e) {
                $error = "Lỗi: Không thể cập nhật thông tin!";
            }
        }
    }
}

// Determine which sidebar to include based on role
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <base href="./">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Quản lý tài khoản | DentaCare</title>
  <link rel="icon" href="assets/favicon/favicon-32x32.png">
  <link rel="stylesheet" href="vendors/simplebar/css/simplebar.css">
  <link href="css/style.css" rel="stylesheet">
</head>
<body>
  <?php
  // Include sidebar theo vai trò
  if ($role === 'doctor') {
    include 'includes/doctor_sidebar.php';
  } elseif ($role === 'receptionist') {
    include 'includes/receptionist_sidebar.php';
  } else {
    // admin hoặc vai trò khác
    include 'includes/sidebar.php';
  }
  ?>

  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include 'includes/header.php'; ?>
  <div class="body flex-grow-1 px-3">
    <div class="container-lg">
      <div class="row">
        <div class="col-12">
          <div class="card mb-4">
            <div class="card-header">
              <h4 class="mb-0">Quản lý tài khoản</h4>
            </div>
            <div class="card-body">
              <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <strong>Lỗi:</strong> <?= htmlspecialchars($error) ?>
                  <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
              <?php endif; ?>
              
              <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <strong>Thành công:</strong> <?= htmlspecialchars($success) ?>
                  <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
              <?php endif; ?>
              
              <form method="POST" enctype="multipart/form-data">
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Ảnh đại diện</label>
                    <div class="mb-3">
                      <?= getAvatarDisplay($current_user, 'xl') ?>
                    </div>
                    <?php if (($current_user['role'] ?? '') !== 'admin'): ?>
                      <div class="mb-3">
                        <label class="form-label">Thay đổi ảnh đại diện</label>
                        <input type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="text-muted">Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP), tối đa 5MB</small>
                        <?php if (!empty($current_user['avatar'])): ?>
                          <div class="mt-2">
                            <a href="../<?= htmlspecialchars($current_user['avatar'] ?? '') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Xem ảnh hiện tại</a>
                          </div>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <p class="text-muted small">Admin sử dụng avatar cơ bản (chữ cái đầu tên)</p>
                    <?php endif; ?>
                  </div>
                  
                  <div class="col-md-8">
                    <div class="mb-3">
                      <label class="form-label fw-bold">Tên đăng nhập</label>
                      <input type="text" class="form-control" value="<?= htmlspecialchars($current_user['username'] ?? '') ?>" disabled>
                      <small class="text-muted">Tên đăng nhập không thể thay đổi</small>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                      <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($current_user['full_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($current_user['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label fw-bold">Vai trò</label>
                      <input type="text" class="form-control" value="<?= ucfirst($current_user['role'] ?? '') ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label fw-bold">Mật khẩu mới</label>
                      <input type="password" name="new_password" class="form-control" placeholder="Để trống nếu không muốn đổi mật khẩu">
                      <small class="text-muted">Để trống nếu không muốn thay đổi mật khẩu</small>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label fw-bold">Xác nhận mật khẩu mới</label>
                      <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới">
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                      <a href="<?= $role === 'admin' ? 'admin_dashboard.php' : ($role === 'doctor' ? 'doctor_dashboard.php' : 'receptionist_dashboard.php') ?>" class="btn btn-secondary">Hủy</a>
                      <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
  </div>

  <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="vendors/simplebar/js/simplebar.min.js"></script>
</body>
</html>

