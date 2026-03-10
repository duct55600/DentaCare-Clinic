<?php
session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_role('admin');

// Lấy danh sách tất cả bác sĩ
$doctors_stmt = $pdo->query("SELECT id, full_name, email FROM users WHERE role = 'doctor' ORDER BY full_name");
$doctors = $doctors_stmt->fetchAll();

// Giá dịch vụ
$service_prices = [
    'Tẩy trắng răng' => 2000000,
    'Cạo vôi răng' => 500000,
    'Niềng răng' => 50000000,
    'Cấy ghép Implant' => 30000000,
    'Nhổ răng' => 500000,
    'Trám răng' => 500000,
    'Điều trị tủy' => 2000000,
    'Bọc răng sứ' => 5000000,
    'Tẩy trắng răng tại nhà' => 1500000,
    'Khám tổng quát' => 300000,
];

// Tính thống kê cho từng bác sĩ
$doctor_stats = [];
foreach ($doctors as $doctor) {
    $doctor_id = $doctor['id'];
    
    // Đếm lịch đã nhận (accepted) - dùng history
    try {
        $accepted_stmt = $pdo->prepare("SELECT COUNT(DISTINCT appointment_id) as total FROM appointment_doctor_history 
            WHERE doctor_id = ? AND action = 'accepted'");
        $accepted_stmt->execute([$doctor_id]);
        $accepted_count = $accepted_stmt->fetch()['total'];
        
        // Đếm lịch đã từ chối (rejected) - dùng history
        $rejected_stmt = $pdo->prepare("SELECT COUNT(DISTINCT appointment_id) as total FROM appointment_doctor_history 
            WHERE doctor_id = ? AND action = 'rejected'");
        $rejected_stmt->execute([$doctor_id]);
        $rejected_count = $rejected_stmt->fetch()['total'];
        
        // Tính doanh thu từ các lịch đã accept
        $revenue_stmt = $pdo->prepare("SELECT DISTINCT a.note 
            FROM appointment_doctor_history h
            INNER JOIN appointments a ON h.appointment_id = a.id
            WHERE h.doctor_id = ? AND h.action = 'accepted'");
        $revenue_stmt->execute([$doctor_id]);
        $revenue_appts = $revenue_stmt->fetchAll();
        
        $revenue = 0;
        foreach ($revenue_appts as $apt) {
            $service = explode(' - ', $apt['note'] ?? '')[0] ?? '';
            if (isset($service_prices[$service])) {
                $revenue += $service_prices[$service];
            }
        }
    } catch (PDOException $e) {
        // Bảng history chưa có, dùng appointments table
        $accepted_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
            WHERE assigned_doctor_id = ? AND status = 'confirmed'");
        $accepted_stmt->execute([$doctor_id]);
        $accepted_count = $accepted_stmt->fetch()['total'];
        
        $rejected_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
            WHERE assigned_doctor_id = ? AND status = 'rejected'");
        $rejected_stmt->execute([$doctor_id]);
        $rejected_count = $rejected_stmt->fetch()['total'];
        
        // Tính doanh thu
        $revenue_stmt = $pdo->prepare("SELECT note FROM appointments 
            WHERE assigned_doctor_id = ? AND status = 'confirmed'");
        $revenue_stmt->execute([$doctor_id]);
        $revenue_appts = $revenue_stmt->fetchAll();
        
        $revenue = 0;
        foreach ($revenue_appts as $apt) {
            $service = explode(' - ', $apt['note'] ?? '')[0] ?? '';
            if (isset($service_prices[$service])) {
                $revenue += $service_prices[$service];
            }
        }
    }
    
    $total_processed = $accepted_count + $rejected_count;
    $accept_rate = $total_processed > 0 ? round(($accepted_count / $total_processed) * 100, 1) : 0;
    
    $doctor_stats[$doctor_id] = [
        'accepted' => $accepted_count,
        'rejected' => $rejected_count,
        'total_processed' => $total_processed,
        'accept_rate' => $accept_rate,
        'revenue' => $revenue
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <base href="./">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Thống kê bác sĩ | DentaCare</title>
  <link rel="icon" href="assets/favicon/favicon-32x32.png">
  <link rel="stylesheet" href="vendors/simplebar/css/simplebar.css">
  <link href="css/style.css" rel="stylesheet">
</head>
<body>
  <?php include 'includes/sidebar.php'; ?>

  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include 'includes/header.php'; ?>

    <div class="body flex-grow-1 px-4">
      <div class="container-lg">
        <h2 class="mb-4">Thống kê bác sĩ</h2>
        
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

        <!-- Bảng danh sách bác sĩ với thống kê -->
        <div class="card">
          <div class="card-header">
            <strong>Danh sách bác sĩ và thống kê tổng quan</strong>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>STT</th>
                    <th>Tên bác sĩ</th>
                    <th>Email</th>
                    <th>Đã nhận</th>
                    <th>Đã từ chối</th>
                    <th>Tổng xử lý</th>
                    <th>Tỷ lệ nhận</th>
                    <th>Doanh thu</th>
                    <th>Thao tác</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($doctors)): ?>
                    <tr>
                      <td colspan="9" class="text-center">Chưa có bác sĩ nào trong hệ thống</td>
                    </tr>
                  <?php else: ?>
                    <?php $i = 1; foreach ($doctors as $doctor): 
                      $stats = $doctor_stats[$doctor['id']] ?? ['accepted' => 0, 'rejected' => 0, 'total_processed' => 0, 'accept_rate' => 0, 'revenue' => 0];
                    ?>
                    <tr>
                      <td><?= $i++ ?></td>
                      <td><?= htmlspecialchars($doctor['full_name']) ?></td>
                      <td><?= htmlspecialchars($doctor['email']) ?></td>
                      <td><span class="badge bg-success"><?= $stats['accepted'] ?></span></td>
                      <td><span class="badge bg-danger"><?= $stats['rejected'] ?></span></td>
                      <td><strong><?= $stats['total_processed'] ?></strong></td>
                      <td><?= $stats['accept_rate'] ?>%</td>
                      <td><strong><?= number_format($stats['revenue']) ?> đ</strong></td>
                      <td>
                        <a href="doctor_statistics_detail.php?doctor_id=<?= $doctor['id'] ?>" class="btn btn-sm btn-primary">
                          Xem chi tiết
                        </a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
</body>
</html>

