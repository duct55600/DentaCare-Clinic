<?php
session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_role('admin'); // chỉ admin mới vào được

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if (empty($username) || empty($fullname) || empty($email) || empty($password) || empty($role)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } else {
        $avatar_path = null;
        
        // Xử lý upload avatar (chỉ cho bác sĩ)
        if ($role === 'doctor' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
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
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'doctor_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $avatar_path = 'uploads/avatars/' . $new_filename;
                } else {
                    $error = "Không thể upload file!";
                }
            }
        }
        
        if (empty($error)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                if ($avatar_path) {
                    $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password, role, avatar) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $fullname, $email, $hashed_password, $role, $avatar_path]);
                } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $fullname, $email, $hashed_password, $role]);
                }
        $success = "Tạo tài khoản thành công!";
                // Reset form
                $_POST = [];
            } catch (PDOException $e) {
                // Xóa file đã upload nếu insert thất bại
                if ($avatar_path && file_exists('../' . $avatar_path)) {
                    unlink('../' . $avatar_path);
                }
        $error = "Lỗi: Tài khoản đã tồn tại hoặc lỗi hệ thống!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Tạo tài khoản - DentaCare</title>
<link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3>Tạo tài khoản nhân viên</h3></div>
                <div class="card-body">
                    <?php if (isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                    <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Họ tên</label>
                            <input type="text" name="fullname" class="form-control" placeholder="Họ tên" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vai trò</label>
                            <select name="role" class="form-control" id="roleSelect" required>
                                <option value="receptionist" <?= (isset($_POST['role']) && $_POST['role'] === 'receptionist') ? 'selected' : '' ?>>Lễ tân</option>
                                <option value="doctor" <?= (isset($_POST['role']) && $_POST['role'] === 'doctor') ? 'selected' : '' ?>>Bác sĩ</option>
                        </select>
                        </div>
                        <div class="mb-3" id="avatarUpload" style="display: none;">
                            <label class="form-label">Ảnh đại diện (Bác sĩ)</label>
                            <input type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="text-muted">Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP), tối đa 5MB</small>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="admin_dashboard.php" class="btn btn-secondary">Quay lại</a>
                        <button type="submit" class="btn btn-primary">Tạo tài khoản</button>
                        </div>
                    </form>
                    <script>
                        document.getElementById('roleSelect').addEventListener('change', function() {
                            var avatarUpload = document.getElementById('avatarUpload');
                            if (this.value === 'doctor') {
                                avatarUpload.style.display = 'block';
                            } else {
                                avatarUpload.style.display = 'none';
                            }
                        });
                        // Kiểm tra giá trị ban đầu
                        if (document.getElementById('roleSelect').value === 'doctor') {
                            document.getElementById('avatarUpload').style.display = 'block';
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>