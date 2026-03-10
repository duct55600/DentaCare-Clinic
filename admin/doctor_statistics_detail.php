<?php
session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_role('admin');

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;

if ($doctor_id <= 0) {
    $_SESSION['error'] = 'Không tìm thấy bác sĩ!';
    header('Location: manage_users.php');
    exit;
}

// Lấy thông tin bác sĩ
$doctor_stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND role = 'doctor'");
$doctor_stmt->execute([$doctor_id]);
$doctor = $doctor_stmt->fetch();

if (!$doctor) {
    $_SESSION['error'] = 'Không tìm thấy bác sĩ!';
    header('Location: manage_users.php');
    exit;
}

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

// Tìm kiếm theo khoảng thời gian
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search_period = '';

// Xây dựng điều kiện WHERE
$where_conditions = ["h.doctor_id = ?", "h.action = 'accepted'"];
$params = [$doctor_id];

if (!empty($date_from)) {
    $where_conditions[] = "DATE(a.appointment_date) >= ?";
    $params[] = $date_from;
    $search_period .= "Từ " . date('d/m/Y', strtotime($date_from));
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(a.appointment_date) <= ?";
    $params[] = $date_to;
    if (!empty($search_period)) {
        $search_period .= " đến ";
    }
    $search_period .= date('d/m/Y', strtotime($date_to));
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Tính thống kê tổng quan
try {
    // Đếm lịch đã nhận
    $accepted_stmt = $pdo->prepare("SELECT COUNT(DISTINCT h.appointment_id) as total 
        FROM appointment_doctor_history h
        INNER JOIN appointments a ON h.appointment_id = a.id
        $where_clause");
    $accepted_stmt->execute($params);
    $accepted_count = $accepted_stmt->fetch()['total'];
    
    // Đếm lịch đã từ chối
    $rejected_where = str_replace("h.action = 'accepted'", "h.action = 'rejected'", $where_clause);
    $rejected_stmt = $pdo->prepare("SELECT COUNT(DISTINCT h.appointment_id) as total 
        FROM appointment_doctor_history h
        INNER JOIN appointments a ON h.appointment_id = a.id
        $rejected_where");
    $rejected_stmt->execute($params);
    $rejected_count = $rejected_stmt->fetch()['total'];
    
    // Tính doanh thu
    $revenue_stmt = $pdo->prepare("SELECT DISTINCT a.note, a.appointment_date, a.patient_name
        FROM appointment_doctor_history h
        INNER JOIN appointments a ON h.appointment_id = a.id
        $where_clause
        ORDER BY a.appointment_date DESC");
    $revenue_stmt->execute($params);
    $revenue_appts = $revenue_stmt->fetchAll();
    
    $revenue = 0;
    foreach ($revenue_appts as $apt) {
        $service = explode(' - ', $apt['note'] ?? '')[0] ?? '';
        if (isset($service_prices[$service])) {
            $revenue += $service_prices[$service];
        }
    }
    
    // Thống kê theo dịch vụ
    $service_stats_stmt = $pdo->prepare("SELECT 
        SUBSTRING_INDEX(a.note, ' - ', 1) as service,
        COUNT(DISTINCT h.appointment_id) as count
        FROM appointment_doctor_history h
        INNER JOIN appointments a ON h.appointment_id = a.id
        $where_clause
        GROUP BY service
        ORDER BY count DESC");
    $service_stats_stmt->execute($params);
    $service_stats = $service_stats_stmt->fetchAll();
    
} catch (PDOException $e) {
    // Bảng history chưa có, dùng appointments table
    $where_conditions = ["assigned_doctor_id = ?", "status = 'confirmed'"];
    $params = [$doctor_id];
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(appointment_date) >= ?";
        $params[] = $date_from;
    }
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(appointment_date) <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    $accepted_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments $where_clause");
    $accepted_stmt->execute($params);
    $accepted_count = $accepted_stmt->fetch()['total'];
    
    $rejected_where = str_replace("status = 'confirmed'", "status = 'rejected'", $where_clause);
    $rejected_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments $rejected_where");
    $rejected_stmt->execute($params);
    $rejected_count = $rejected_stmt->fetch()['total'];
    
    $revenue_stmt = $pdo->prepare("SELECT note, appointment_date, patient_name FROM appointments 
        $where_clause ORDER BY appointment_date DESC");
    $revenue_stmt->execute($params);
    $revenue_appts = $revenue_stmt->fetchAll();
    
    $revenue = 0;
    foreach ($revenue_appts as $apt) {
        $service = explode(' - ', $apt['note'] ?? '')[0] ?? '';
        if (isset($service_prices[$service])) {
            $revenue += $service_prices[$service];
        }
    }
    
    $service_stats_stmt = $pdo->prepare("SELECT 
        SUBSTRING_INDEX(note, ' - ', 1) as service,
        COUNT(*) as count
        FROM appointments 
        $where_clause
        GROUP BY service
        ORDER BY count DESC");
    $service_stats_stmt->execute($params);
    $service_stats = $service_stats_stmt->fetchAll();
}

$total_processed = $accepted_count + $rejected_count;
$accept_rate = $total_processed > 0 ? round(($accepted_count / $total_processed) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <base href="./">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Thống kê chi tiết - <?= htmlspecialchars($doctor['full_name']) ?> | DentaCare</title>
  <link rel="icon" href="assets/favicon/favicon-32x32.png">
  <link rel="stylesheet" href="vendors/simplebar/css/simplebar.css">
  <link href="css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <?php include 'includes/sidebar.php'; ?>

  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include 'includes/header.php'; ?>

    <div class="body flex-grow-1 px-4">
      <div class="container-lg">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Thống kê chi tiết - <?= htmlspecialchars($doctor['full_name']) ?></h2>
          <a href="manage_users.php" class="btn btn-secondary">← Quay lại</a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Form tìm kiếm -->
        <div class="card mb-4">
          <div class="card-body">
            <form method="GET" action="" class="row g-3">
              <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
              <div class="col-md-4">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div>
                  <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                  <a href="doctor_statistics_detail.php?doctor_id=<?= $doctor_id ?>" class="btn btn-secondary">Xóa bộ lọc</a>
                  <button type="button" class="btn btn-success" onclick="exportStatistics()">Export</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Cards thống kê -->
        <div class="row mb-4">
          <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card text-white bg-success">
              <div class="card-body">
                <div class="text-value-lg"><?= number_format($revenue) ?> đ</div>
                <div>Doanh thu<?= $search_period ? " ($search_period)" : "" ?></div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card text-white bg-primary">
              <div class="card-body">
                <div class="text-value-lg"><?= $accepted_count ?></div>
                <div>Đã nhận</div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card text-white bg-danger">
              <div class="card-body">
                <div class="text-value-lg"><?= $rejected_count ?></div>
                <div>Đã từ chối</div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card text-white bg-info">
              <div class="card-body">
                <div class="text-value-lg"><?= $accept_rate ?>%</div>
                <div>Tỷ lệ nhận</div>
                <small>(<?= $accepted_count ?> / <?= $total_processed ?>)</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Biểu đồ thống kê -->
        <div class="row mb-4">
          <div class="col-lg-6 mb-4">
            <div class="card">
              <div class="card-header">
                <strong>Thống kê xử lý</strong>
              </div>
              <div class="card-body">
                <canvas id="processingChart" height="200"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-6 mb-4">
            <div class="card">
              <div class="card-header">
                <strong>Thống kê dịch vụ</strong>
              </div>
              <div class="card-body">
                <canvas id="serviceChart" height="200"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Bảng chi tiết dịch vụ -->
        <div class="card mb-4">
          <div class="card-header">
            <strong>Chi tiết dịch vụ</strong>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Dịch vụ</th>
                    <th>Số lượng</th>
                    <th>Giá (VNĐ)</th>
                    <th>Doanh thu</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($service_stats)): ?>
                    <tr>
                      <td colspan="4" class="text-center">Chưa có dữ liệu</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($service_stats as $service): 
                      $price = isset($service_prices[$service['service']]) ? $service_prices[$service['service']] : 0;
                      $service_revenue = $price * $service['count'];
                    ?>
                    <tr>
                      <td><?= htmlspecialchars($service['service']) ?></td>
                      <td><?= $service['count'] ?></td>
                      <td><?= number_format($price) ?></td>
                      <td><?= number_format($service_revenue) ?> đ</td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Bảng chi tiết lịch hẹn -->
        <div class="card">
          <div class="card-header">
            <strong>Chi tiết lịch hẹn đã nhận</strong>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Ngày giờ</th>
                    <th>Bệnh nhân</th>
                    <th>Dịch vụ</th>
                    <th>Giá (VNĐ)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($revenue_appts)): ?>
                    <tr>
                      <td colspan="4" class="text-center">Chưa có lịch hẹn nào</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($revenue_appts as $apt): 
                      $service = explode(' - ', $apt['note'] ?? '')[0] ?? '';
                      $price = isset($service_prices[$service]) ? $service_prices[$service] : 0;
                    ?>
                    <tr>
                      <td><?= date('d/m/Y H:i', strtotime($apt['appointment_date'])) ?></td>
                      <td><?= htmlspecialchars($apt['patient_name']) ?></td>
                      <td><?= htmlspecialchars($service) ?></td>
                      <td><?= number_format($price) ?> đ</td>
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
  <script>
    // Biểu đồ thống kê xử lý
    const processingData = {
      labels: ['Đã nhận', 'Đã từ chối'],
      datasets: [{
        label: 'Số lượng',
        data: [<?= $accepted_count ?>, <?= $rejected_count ?>],
        backgroundColor: [
          'rgba(40, 167, 69, 0.6)',
          'rgba(220, 53, 69, 0.6)'
        ]
      }]
    };

    new Chart(document.getElementById('processingChart'), {
      type: 'doughnut',
      data: processingData,
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });

    // Biểu đồ thống kê dịch vụ
    const serviceData = {
      labels: [<?= implode(',', array_map(function($s) { return "'" . htmlspecialchars($s['service']) . "'"; }, $service_stats)) ?>],
      datasets: [{
        label: 'Số lượng',
        data: [<?= implode(',', array_column($service_stats, 'count')) ?>],
        backgroundColor: [
          'rgba(54, 162, 235, 0.6)',
          'rgba(255, 99, 132, 0.6)',
          'rgba(255, 206, 86, 0.6)',
          'rgba(75, 192, 192, 0.6)',
          'rgba(153, 102, 255, 0.6)'
        ]
      }]
    };

    new Chart(document.getElementById('serviceChart'), {
      type: 'bar',
      data: serviceData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    function exportStatistics() {
      const dateFrom = '<?= urlencode($date_from) ?>';
      const dateTo = '<?= urlencode($date_to) ?>';
      const doctorId = <?= $doctor_id ?>;
      let url = 'export_doctor_statistics.php?doctor_id=' + doctorId;
      if (dateFrom) url += '&date_from=' + dateFrom;
      if (dateTo) url += '&date_to=' + dateTo;
      window.open(url, '_blank');
    }
  </script>
</body>
</html>
