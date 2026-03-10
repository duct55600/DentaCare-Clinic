<?php
session_start();
require_once '../config/db.php';
require_once '../functions/avatar_functions.php';

// Phân trang: 6 bác sĩ/trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 6;
$offset = ($page - 1) * $per_page;

// Lấy tổng số bác sĩ
$total_stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'doctor' AND status = 'active'");
$total = $total_stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Lấy danh sách bác sĩ với phân trang
$stmt = $pdo->prepare("SELECT id, full_name, email, avatar FROM users WHERE role = 'doctor' AND status = 'active' ORDER BY full_name LIMIT ? OFFSET ?");
$stmt->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$doctors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Danh sách bác sĩ | DentaCare</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php 
  // Include navigation từ index.php hoặc tạo riêng
  $current_page = 'doctors_list';
  include 'includes/nav.php'; 
  ?>

  <section class="ftco-section">
    <div class="container">
      <div class="row justify-content-center mb-3">
        <div class="col-md-12">
          <a href="index.php" class="btn btn-secondary">
            <span>←</span> Quay về trang chủ
          </a>
        </div>
      </div>
      <div class="row justify-content-center mb-5">
        <div class="col-md-7 text-center heading-section ftco-animate">
          <h2 class="mb-2">Đội ngũ bác sĩ</h2>
          <p>Chọn bác sĩ phù hợp với nhu cầu của bạn</p>
        </div>
      </div>
      
      <div class="row">
        <?php if (empty($doctors)): ?>
          <div class="col-12 text-center">
            <p class="text-muted">Chưa có bác sĩ nào trong hệ thống.</p>
          </div>
        <?php else: ?>
          <?php foreach ($doctors as $doctor): ?>
          <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
              <div class="card-body text-center">
                <div class="mb-3 d-flex justify-content-center">
                  <?php
                  // Hiển thị avatar từ database hoặc initial
                  if ($doctor['avatar'] && file_exists('../' . $doctor['avatar'])) {
                    echo '<img src="../' . htmlspecialchars($doctor['avatar']) . '" alt="' . htmlspecialchars($doctor['full_name']) . '" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #007bff;">';
                  } else {
                    // Hiển thị initial avatar
                    $initial = getInitials($doctor['full_name']);
                    echo '<div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; font-size: 2rem; font-weight: bold; margin: 0 auto;">' . htmlspecialchars($initial) . '</div>';
                  }
                  ?>
                </div>
                <h4 class="card-title"><?= htmlspecialchars($doctor['full_name']) ?></h4>
                <p class="text-muted">Bác sĩ</p>
                <?php if ($doctor['email']): ?>
                  <p class="small text-muted"><?= htmlspecialchars($doctor['email']) ?></p>
                <?php endif; ?>
                <a href="index.php?doctor=<?= urlencode($doctor['full_name']) ?>" class="btn btn-primary mt-3">Đặt lịch với bác sĩ này</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      
      <!-- Phân trang -->
      <?php if ($total_pages > 1): ?>
      <div class="row mt-5">
        <div class="col-md-12 text-center">
          <nav aria-label="Phân trang">
            <ul class="pagination justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?= $page - 1 ?>">Trước</a>
                </li>
              <?php endif; ?>
              
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              
              <?php if ($page < $total_pages): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?= $page + 1 ?>">Sau</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/bootstrap.min.js"></script>
  
  <!-- Modal đặt lịch -->
  <?php include 'includes/appointment_modal.php'; ?>
</body>
</html>

