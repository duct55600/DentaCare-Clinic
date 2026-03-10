<?php
session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_once '../functions/email_functions.php';
require_role('admin');

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    // Prevent deleting admin users
    $stmt = $pdo->prepare("SELECT role, full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['role'] !== 'admin') {
        // Nếu là bác sĩ, kiểm tra và xử lý lịch hẹn trước khi xóa
        if ($user['role'] === 'doctor') {
            // Kiểm tra lịch hẹn đang chờ duyệt hoặc đã xác nhận chưa đến ngày khám
            // Kiểm tra xem cột deleted_at có tồn tại không
            try {
                $test_col = $pdo->query("SELECT deleted_at FROM appointments LIMIT 1");
                $deleted_filter = "AND deleted_at IS NULL";
            } catch (PDOException $e) {
                $deleted_filter = "AND status != 'deleted'";
            }
            
            $stmt = $pdo->prepare("SELECT id, patient_name, patient_email, appointment_date, status 
                FROM appointments 
                WHERE assigned_doctor_id = ? 
                AND status IN ('waiting_for_approval', 'confirmed') 
                AND appointment_date > NOW()
                $deleted_filter");
            $stmt->execute([$user_id]);
            $affected_appointments = $stmt->fetchAll();
            
            if (!empty($affected_appointments)) {
                // Chuyển các lịch hẹn về trạng thái pending để lễ tân xử lý lại
                $appointment_ids = array_column($affected_appointments, 'id');
                $placeholders = implode(',', array_fill(0, count($appointment_ids), '?'));
                
                $update_stmt = $pdo->prepare("UPDATE appointments 
                    SET status = 'pending', 
                        assigned_doctor_id = NULL, 
                        assigned_receptionist_id = NULL 
                    WHERE id IN ($placeholders)");
                $update_stmt->execute($appointment_ids);
                
                // Gửi email thông báo cho từng bệnh nhân
                foreach ($affected_appointments as $apt) {
                    $formatted_date = date('d/m/Y H:i', strtotime($apt['appointment_date']));
                    $subject = "Thông báo về lịch hẹn của bạn - DentaCare";
                    $message = "<h3>Xin chào {$apt['patient_name']}!</h3>
                               <p>Chúng tôi xin thông báo rằng lịch hẹn của bạn cần được xử lý lại.</p>
                               <p>Thông tin lịch hẹn:</p>
                               <ul>
                                 <li>Thời gian: <strong>$formatted_date</strong></li>
                                 <li>Bác sĩ: <strong>{$user['full_name']}</strong> (không còn làm việc)</li>
                               </ul>
                               <p>Lịch hẹn của bạn đã được chuyển về trạng thái chờ xử lý. Lễ tân sẽ liên hệ với bạn để sắp xếp lại lịch hẹn với bác sĩ khác.</p>
                               <p>Xin lỗi vì sự bất tiện này. Cảm ơn bạn đã tin tưởng DentaCare!</p>";
                    
                    sendEmail($apt['patient_email'], $subject, $message);
                }
                
                $_SESSION['success'] = 'Đã xóa bác sĩ thành công! ' . count($affected_appointments) . ' lịch hẹn đã được chuyển về trạng thái chờ xử lý và đã gửi email thông báo cho bệnh nhân.';
            } else {
                $_SESSION['success'] = 'Đã xóa người dùng thành công!';
            }
        } else {
            // Nếu là lễ tân, chỉ cần xóa (không có lịch hẹn nào được gán trực tiếp)
            $_SESSION['success'] = 'Đã xóa người dùng thành công!';
        }
        
        // Xóa user (foreign key constraint sẽ tự động set assigned_doctor_id = NULL cho các lịch còn lại)
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
    } else {
        $_SESSION['error'] = 'Không thể xóa tài khoản admin!';
    }
    header('Location: manage_users.php');
    exit;
}

// Handle bulk delete
if (isset($_POST['bulk_delete']) && isset($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Prevent deleting admin users
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE id IN ($placeholders) AND role = 'admin'");
    $stmt->execute($ids);
    $admin_count = $stmt->fetch()['count'];
    
    if ($admin_count > 0) {
        $_SESSION['error'] = 'Không thể xóa tài khoản admin!';
    } else {
        // Lấy danh sách bác sĩ trong danh sách xóa
        $doctor_stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id IN ($placeholders) AND role = 'doctor'");
        $doctor_stmt->execute($ids);
        $doctors_to_delete = $doctor_stmt->fetchAll();
        
        $total_affected_appointments = 0;
        
        // Kiểm tra xem cột deleted_at có tồn tại không (chỉ cần check 1 lần)
        try {
            $test_col = $pdo->query("SELECT deleted_at FROM appointments LIMIT 1");
            $deleted_filter = "AND deleted_at IS NULL";
        } catch (PDOException $e) {
            $deleted_filter = "AND status != 'deleted'";
        }
        
        // Xử lý lịch hẹn cho từng bác sĩ
        foreach ($doctors_to_delete as $doctor) {
            $doctor_id = $doctor['id'];
            
            // Kiểm tra lịch hẹn đang chờ duyệt hoặc đã xác nhận chưa đến ngày khám
            $stmt = $pdo->prepare("SELECT id, patient_name, patient_email, appointment_date, status 
                FROM appointments 
                WHERE assigned_doctor_id = ? 
                AND status IN ('waiting_for_approval', 'confirmed') 
                AND appointment_date > NOW()
                $deleted_filter");
            $stmt->execute([$doctor_id]);
            $affected_appointments = $stmt->fetchAll();
            
            if (!empty($affected_appointments)) {
                // Chuyển các lịch hẹn về trạng thái pending
                $appointment_ids = array_column($affected_appointments, 'id');
                $apt_placeholders = implode(',', array_fill(0, count($appointment_ids), '?'));
                
                $update_stmt = $pdo->prepare("UPDATE appointments 
                    SET status = 'pending', 
                        assigned_doctor_id = NULL, 
                        assigned_receptionist_id = NULL 
                    WHERE id IN ($apt_placeholders)");
                $update_stmt->execute($appointment_ids);
                
                // Gửi email thông báo cho từng bệnh nhân
                foreach ($affected_appointments as $apt) {
                    $formatted_date = date('d/m/Y H:i', strtotime($apt['appointment_date']));
                    $subject = "Thông báo về lịch hẹn của bạn - DentaCare";
                    $message = "<h3>Xin chào {$apt['patient_name']}!</h3>
                               <p>Chúng tôi xin thông báo rằng lịch hẹn của bạn cần được xử lý lại.</p>
                               <p>Thông tin lịch hẹn:</p>
                               <ul>
                                 <li>Thời gian: <strong>$formatted_date</strong></li>
                                 <li>Bác sĩ: <strong>{$doctor['full_name']}</strong> (không còn làm việc)</li>
                               </ul>
                               <p>Lịch hẹn của bạn đã được chuyển về trạng thái chờ xử lý. Lễ tân sẽ liên hệ với bạn để sắp xếp lại lịch hẹn với bác sĩ khác.</p>
                               <p>Xin lỗi vì sự bất tiện này. Cảm ơn bạn đã tin tưởng DentaCare!</p>";
                    
                    sendEmail($apt['patient_email'], $subject, $message);
                }
                
                $total_affected_appointments += count($affected_appointments);
            }
        }
        
        // Xóa users
        $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        if ($total_affected_appointments > 0) {
            $_SESSION['success'] = 'Đã xóa ' . count($ids) . ' người dùng thành công! ' . $total_affected_appointments . ' lịch hẹn đã được chuyển về trạng thái chờ xử lý và đã gửi email thông báo cho bệnh nhân.';
        } else {
            $_SESSION['success'] = 'Đã xóa ' . count($ids) . ' người dùng thành công!';
        }
    }
    header('Location: manage_users.php');
    exit;
}

// Tìm kiếm theo tên
$search_receptionist = $_GET['search_receptionist'] ?? '';
$search_doctor = $_GET['search_doctor'] ?? '';

// Phân trang riêng cho lễ tân và bác sĩ
$page_receptionist = isset($_GET['page_receptionist']) ? (int)$_GET['page_receptionist'] : 1;
$page_doctor = isset($_GET['page_doctor']) ? (int)$_GET['page_doctor'] : 1;
$per_page = 10;

// Xây dựng điều kiện WHERE cho lễ tân
$where_receptionist = ["role = 'receptionist'", "status = 'active'"];
$params_receptionist = [];
if (!empty($search_receptionist)) {
    $where_receptionist[] = "(full_name LIKE ? OR username LIKE ? OR email LIKE ?)";
    $search_term = '%' . $search_receptionist . '%';
    $params_receptionist[] = $search_term;
    $params_receptionist[] = $search_term;
    $params_receptionist[] = $search_term;
}
$where_clause_receptionist = "WHERE " . implode(" AND ", $where_receptionist);

// Xây dựng điều kiện WHERE cho bác sĩ
$where_doctor = ["role = 'doctor'", "status = 'active'"];
$params_doctor = [];
if (!empty($search_doctor)) {
    $where_doctor[] = "(full_name LIKE ? OR username LIKE ? OR email LIKE ?)";
    $search_term = '%' . $search_doctor . '%';
    $params_doctor[] = $search_term;
    $params_doctor[] = $search_term;
    $params_doctor[] = $search_term;
}
$where_clause_doctor = "WHERE " . implode(" AND ", $where_doctor);

// Lấy tổng số với điều kiện tìm kiếm
$total_receptionists_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $where_clause_receptionist");
$total_receptionists_stmt->execute($params_receptionist);
$total_receptionists = $total_receptionists_stmt->fetch()['total'];

$total_doctors_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $where_clause_doctor");
$total_doctors_stmt->execute($params_doctor);
$total_doctors = $total_doctors_stmt->fetch()['total'];

$total_pages_receptionists = ceil($total_receptionists / $per_page);
$total_pages_doctors = ceil($total_doctors / $per_page);

// Tính offset riêng cho từng loại
$offset_receptionist = ($page_receptionist - 1) * $per_page;
$offset_doctor = ($page_doctor - 1) * $per_page;

// Get receptionists and doctors with pagination and search
$receptionists_stmt = $pdo->prepare("SELECT * FROM users $where_clause_receptionist ORDER BY created_at DESC LIMIT ? OFFSET ?");
$param_index = 1;
foreach ($params_receptionist as $param) {
    $receptionists_stmt->bindValue($param_index++, $param);
}
$receptionists_stmt->bindValue($param_index++, $per_page, PDO::PARAM_INT);
$receptionists_stmt->bindValue($param_index++, $offset_receptionist, PDO::PARAM_INT);
$receptionists_stmt->execute();
$receptionists = $receptionists_stmt->fetchAll();

$doctors_stmt = $pdo->prepare("SELECT * FROM users $where_clause_doctor ORDER BY created_at DESC LIMIT ? OFFSET ?");
$param_index = 1;
foreach ($params_doctor as $param) {
    $doctors_stmt->bindValue($param_index++, $param);
}
$doctors_stmt->bindValue($param_index++, $per_page, PDO::PARAM_INT);
$doctors_stmt->bindValue($param_index++, $offset_doctor, PDO::PARAM_INT);
$doctors_stmt->execute();
$doctors = $doctors_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <base href="./">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Quản lý người dùng | DentaCare</title>
  <link rel="icon" href="assets/favicon/favicon-32x32.png">
  <link rel="stylesheet" href="vendors/simplebar/css/simplebar.css">
  <link href="css/style.css" rel="stylesheet">
</head>
<body>
  <?php 
  // Set active page for sidebar
  $active_page = 'manage_users';
  include 'includes/sidebar.php'; 
  ?>

  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include 'includes/header.php'; ?>

    <div class="body flex-grow-1 px-4">
      <div class="container-lg">
        <h2 class="mb-4">Quản lý người dùng</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Form tìm kiếm chung -->
        <div class="card mb-4">
          <div class="card-body">
            <form method="GET" action="" class="row g-3">
              <div class="col-md-5">
                <label class="form-label">Tìm kiếm lễ tân</label>
                <input type="text" name="search_receptionist" class="form-control" placeholder="Tên, username, email..." value="<?= htmlspecialchars($search_receptionist) ?>">
              </div>
              <div class="col-md-5">
                <label class="form-label">Tìm kiếm bác sĩ</label>
                <input type="text" name="search_doctor" class="form-control" placeholder="Tên, username, email..." value="<?= htmlspecialchars($search_doctor) ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                  <button type="submit" class="btn btn-primary w-100">
                    <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-magnifying-glass"></use></svg> Tìm kiếm
                  </button>
                </div>
              </div>
            </form>
            <?php if (!empty($search_receptionist) || !empty($search_doctor)): ?>
              <div class="mt-2">
                <a href="manage_users.php" class="btn btn-sm btn-secondary">Xóa tất cả bộ lọc</a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="row">
          <!-- Receptionists -->
          <div class="col-lg-6 mb-4">
            <div class="card">
              <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lễ tân (<?= $total_receptionists ?>)</h5>
                <button class="btn btn-danger btn-sm" onclick="bulkDelete('receptionist')" id="bulkDeleteBtnReceptionist" style="display:none;">
                  <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-trash"></use></svg> Xóa đã chọn
                </button>
              </div>
              <div class="card-body">
                <?php if (empty($receptionists)): ?>
                  <p class="text-muted">Chưa có lễ tân nào.</p>
                <?php else: ?>
                  <form id="bulkFormReceptionist" method="POST">
                    <input type="hidden" name="bulk_delete" value="1">
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th><input type="checkbox" id="selectAllReceptionist" onchange="toggleAll('receptionist')"></th>
                            <th>Tên đăng nhập</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Hành động</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($receptionists as $user): ?>
                          <tr>
                            <td>
                              <input type="checkbox" name="ids[]" value="<?= $user['id'] ?>" class="row-checkbox-receptionist" onchange="updateBulkDeleteBtn('receptionist')">
                            </td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                            <td>
                              <a href="manage_users.php?delete=1&id=<?= $user['id'] ?>" 
                                 class="btn btn-danger btn-sm" 
                                 onclick="return confirm('Bạn có chắc chắn muốn xóa <?= htmlspecialchars(addslashes($user['full_name'])) ?>? Hành động này sẽ xóa tất cả thông tin liên quan và không thể hoàn tác!');">
                                <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-trash"></use></svg>
                              </a>
                            </td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </form>
                  
                  <!-- Phân trang -->
                  <?php if ($total_pages_receptionists > 1): ?>
                  <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                      <?php if ($page_receptionist > 1): ?>
                        <li class="page-item">
                          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_receptionist' => $page_receptionist - 1])) ?>">« Trước</a>
                        </li>
                      <?php endif; ?>
                      
                      <?php
                      // Hiển thị tối đa 10 trang
                      $start = max(1, $page_receptionist - 4);
                      $end = min($total_pages_receptionists, $page_receptionist + 5);
                      
                      if ($start > 1): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_receptionist' => 1])) ?>">1</a></li>
                        <?php if ($start > 2): ?>
                          <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                      <?php endif; ?>
                      
                      <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i == $page_receptionist ? 'active' : '' ?>">
                          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_receptionist' => $i])) ?>"><?= $i ?></a>
                        </li>
                      <?php endfor; ?>
                      
                      <?php if ($end < $total_pages_receptionists): ?>
                        <?php if ($end < $total_pages_receptionists - 1): ?>
                          <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_receptionist' => $total_pages_receptionists])) ?>"><?= $total_pages_receptionists ?></a></li>
                      <?php endif; ?>
                      
                      <?php if ($page_receptionist < $total_pages_receptionists): ?>
                        <li class="page-item">
                          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_receptionist' => $page_receptionist + 1])) ?>">Sau »</a>
                        </li>
                      <?php endif; ?>
                    </ul>
                  </nav>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Doctors -->
          <div class="col-lg-6 mb-4">
            <div class="card">
              <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Bác sĩ (<?= $total_doctors ?>)</h5>
                <button class="btn btn-danger btn-sm" onclick="bulkDelete('doctor')" id="bulkDeleteBtnDoctor" style="display:none;">
                  <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-trash"></use></svg> Xóa đã chọn
                </button>
              </div>
              <div class="card-body">
                <?php if (empty($doctors)): ?>
                  <p class="text-muted">Chưa có bác sĩ nào.</p>
                <?php else: ?>
                  <form id="bulkFormDoctor" method="POST">
                    <input type="hidden" name="bulk_delete" value="1">
                    <div class="table-responsive">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th><input type="checkbox" id="selectAllDoctor" onchange="toggleAll('doctor')"></th>
                            <th>Tên đăng nhập</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Hành động</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($doctors as $user): ?>
                          <tr>
                            <td>
                              <input type="checkbox" name="ids[]" value="<?= $user['id'] ?>" class="row-checkbox-doctor" onchange="updateBulkDeleteBtn('doctor')">
                            </td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                            <td>
                              <a href="doctor_statistics_detail.php?doctor_id=<?= $user['id'] ?>" 
                                 class="btn btn-primary btn-sm" 
                                 title="Xem thống kê">
                                <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-chart"></use></svg>
                              </a>
                              <a href="manage_users.php?delete=1&id=<?= $user['id'] ?>" 
                                 class="btn btn-danger btn-sm" 
                                 onclick="return confirm('Bạn có chắc chắn muốn xóa <?= htmlspecialchars(addslashes($user['full_name'])) ?>? Hành động này sẽ xóa tất cả thông tin liên quan và không thể hoàn tác!');">
                                <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-trash"></use></svg>
                              </a>
                            </td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </form>
                  
                  <!-- Phân trang -->
                  <?php if ($total_pages_doctors > 1): ?>
                  <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                      <?php if ($page_doctor > 1): ?>
                        <li class="page-item">
                          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_doctor' => $page_doctor - 1])) ?>">« Trước</a>
                        </li>
                      <?php endif; ?>
                      
                      <?php
                      // Hiển thị tối đa 10 trang
                      $start = max(1, $page_doctor - 4);
                      $end = min($total_pages_doctors, $page_doctor + 5);
                      
                      if ($start > 1): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_doctor' => 1])) ?>">1</a></li>
                        <?php if ($start > 2): ?>
                          <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                      <?php endif; ?>
                      
                      <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i == $page_doctor ? 'active' : '' ?>">
                          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_doctor' => $i])) ?>"><?= $i ?></a>
                        </li>
                      <?php endfor; ?>
                      
                      <?php if ($end < $total_pages_doctors): ?>
                        <?php if ($end < $total_pages_doctors - 1): ?>
                          <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_doctor' => $total_pages_doctors])) ?>"><?= $total_pages_doctors ?></a></li>
                      <?php endif; ?>
                      
                      <?php if ($page_doctor < $total_pages_doctors): ?>
                        <li class="page-item">
                          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_doctor' => $page_doctor + 1])) ?>">Sau »</a>
                        </li>
                      <?php endif; ?>
                    </ul>
                  </nav>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php include 'includes/footer.php'; ?>
  </div>

  <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script>
    function toggleAll(type) {
      const selectAll = document.getElementById('selectAll' + type.charAt(0).toUpperCase() + type.slice(1));
      const checkboxes = document.querySelectorAll('.row-checkbox-' + type);
      checkboxes.forEach(cb => cb.checked = selectAll.checked);
      updateBulkDeleteBtn(type);
    }

    function updateBulkDeleteBtn(type) {
      const checked = document.querySelectorAll('.row-checkbox-' + type + ':checked');
      document.getElementById('bulkDeleteBtn' + type.charAt(0).toUpperCase() + type.slice(1)).style.display = checked.length > 0 ? 'inline-block' : 'none';
    }

    function bulkDelete(type) {
      const checked = document.querySelectorAll('.row-checkbox-' + type + ':checked');
      if (checked.length === 0) {
        alert('Vui lòng chọn ít nhất một người dùng để xóa!');
        return;
      }
      if (confirm(`Bạn có chắc chắn muốn xóa ${checked.length} người dùng đã chọn? Hành động này sẽ xóa tất cả thông tin liên quan và không thể hoàn tác!`)) {
        document.getElementById('bulkForm' + type.charAt(0).toUpperCase() + type.slice(1)).submit();
      }
    }
  </script>
</body>
</html>

