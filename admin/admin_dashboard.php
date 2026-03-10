<?php
session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_role('admin');

// Giá dịch vụ (có thể lưu vào database sau)
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
$current_month = date('Y-m'); // Luôn định nghĩa biến này (mặc định là tháng hiện tại)

if (!empty($date_from) || !empty($date_to)) {
    // Tìm kiếm theo khoảng thời gian
    // LƯU Ý: Thống kê doanh thu vẫn tính cả appointment đã bị soft delete
    $where_conditions = ["status = 'confirmed'"];
    $params = [];
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(appointment_date) >= ?";
        $params[] = $date_from;
    }
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(appointment_date) <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    $appts_stmt = $pdo->prepare("SELECT note FROM appointments $where_clause");
    $appts_stmt->execute($params);
    $appts = $appts_stmt->fetchAll();
    
    $search_period = " (Tìm kiếm: ";
    if (!empty($date_from) && !empty($date_to)) {
        $search_period .= "Từ " . date('d/m/Y', strtotime($date_from)) . " đến " . date('d/m/Y', strtotime($date_to));
    } elseif (!empty($date_from)) {
        $search_period .= "Từ " . date('d/m/Y', strtotime($date_from));
    } elseif (!empty($date_to)) {
        $search_period .= "Đến " . date('d/m/Y', strtotime($date_to));
    }
    $search_period .= ")";
} else {
    // Doanh thu tháng hiện tại (từ các lịch đã được bác sĩ nhận - confirmed)
    $appts_stmt = $pdo->prepare("SELECT note FROM appointments 
        WHERE status = 'confirmed' 
        AND DATE_FORMAT(appointment_date, '%Y-%m') = ?");
    $appts_stmt->execute([$current_month]);
    $appts = $appts_stmt->fetchAll();
}

// Tính doanh thu từ các dịch vụ đã confirmed - dùng history để tính chính xác hơn
$calculated_revenue = 0;
try {
    // Lấy các lịch đã được accept từ history
    if (!empty($date_from) || !empty($date_to)) {
        // Tìm kiếm theo khoảng thời gian
        $where_conditions = ["h.action = 'accepted'"];
        $params = [];
        
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(a.appointment_date) >= ?";
            $params[] = $date_from;
        }
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(a.appointment_date) <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        $revenue_stmt = $pdo->prepare("SELECT DISTINCT a.note 
            FROM appointment_doctor_history h
            INNER JOIN appointments a ON h.appointment_id = a.id
            $where_clause");
        $revenue_stmt->execute($params);
    } else {
        // Doanh thu tháng hiện tại (từ các lịch đã được bác sĩ nhận - accepted)
        $revenue_stmt = $pdo->prepare("SELECT DISTINCT a.note 
            FROM appointment_doctor_history h
            INNER JOIN appointments a ON h.appointment_id = a.id
            WHERE h.action = 'accepted' 
            AND DATE_FORMAT(a.appointment_date, '%Y-%m') = ?");
        $revenue_stmt->execute([$current_month]);
    }
    $revenue_appts = $revenue_stmt->fetchAll();
    
    // Tính doanh thu từ các lịch đã accept
    foreach ($revenue_appts as $apt) {
        $service = explode(' - ', $apt['note'] ?? '')[0] ?? '';
        if (isset($service_prices[$service])) {
            $calculated_revenue += $service_prices[$service];
        }
    }
} catch (PDOException $e) {
    // Bảng history chưa có, dùng appointments table (code cũ)
    foreach ($appts as $apt) {
        $service = explode(' - ', $apt['note'])[0] ?? '';
        if (isset($service_prices[$service])) {
            $calculated_revenue += $service_prices[$service];
        }
    }
}

// Số lịch hẹn trong ngày của tất cả bác sĩ - dùng history để tính chính xác hơn
$today = date('Y-m-d');
try {
    // Đếm từ history (lịch đã được bác sĩ accept) có appointment_date hôm nay
    $today_appts = $pdo->prepare("SELECT COUNT(DISTINCT h.appointment_id) as total 
        FROM appointment_doctor_history h
        INNER JOIN appointments a ON h.appointment_id = a.id
        WHERE h.action = 'accepted' AND DATE(a.appointment_date) = ?");
    $today_appts->execute([$today]);
    $today_count = $today_appts->fetch()['total'];
} catch (PDOException $e) {
    // Bảng history chưa có, dùng appointments table
    $today_appts = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
        WHERE DATE(appointment_date) = ? AND status = 'confirmed'");
    $today_appts->execute([$today]);
    $today_count = $today_appts->fetch()['total'];
}

// Tổng quan lịch hẹn trong tháng - dùng appointment_doctor_history để giữ lại thống kê ngay cả khi đã xóa hoặc chuyển bác sĩ
// Ưu tiên dùng appointment_doctor_history nếu có, nếu không thì dùng appointments
try {
    // Đếm lịch đã nhận (accepted) trong tháng từ history
    $month_accepted = $pdo->prepare("SELECT COUNT(DISTINCT appointment_id) as total FROM appointment_doctor_history 
        WHERE action = 'accepted' 
        AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $month_accepted->execute([$current_month]);
    $accepted_count = $month_accepted->fetch()['total'];
    
    // Đếm lịch đã từ chối (rejected) trong tháng từ history
    $month_rejected = $pdo->prepare("SELECT COUNT(DISTINCT appointment_id) as total FROM appointment_doctor_history 
        WHERE action = 'rejected' 
        AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $month_rejected->execute([$current_month]);
    $rejected_count = $month_rejected->fetch()['total'];
    
    // Đếm lịch chờ xử lý (pending) trong tháng từ appointments (chưa có trong history)
    $month_pending = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
        WHERE status IN ('pending', 'waiting_for_approval') 
        AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $month_pending->execute([$current_month]);
    $pending_count = $month_pending->fetch()['total'];
} catch (PDOException $e) {
    // Bảng history chưa có, dùng appointments table
    $month_accepted = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
        WHERE status = 'confirmed' AND DATE_FORMAT(appointment_date, '%Y-%m') = ?");
    $month_accepted->execute([$current_month]);
    $accepted_count = $month_accepted->fetch()['total'];
    
    $month_rejected = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
        WHERE status = 'rejected' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $month_rejected->execute([$current_month]);
    $rejected_count = $month_rejected->fetch()['total'];
    
    $month_pending = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
        WHERE status IN ('pending', 'waiting_for_approval') AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $month_pending->execute([$current_month]);
    $pending_count = $month_pending->fetch()['total'];
}

// Tỷ lệ no-show (giả sử no-show là các lịch đã confirmed nhưng quá ngày hẹn 1 ngày mà chưa có ghi chú hoàn thành)
$no_show_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
    WHERE status = 'confirmed' 
    AND DATE(appointment_date) < DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    AND (doctor_note IS NULL OR doctor_note = '')");
$no_show_stmt->execute();
$no_show_count = $no_show_stmt->fetch()['total'];
$total_confirmed = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE status = 'confirmed'")->fetch()['total'];
$no_show_rate = $total_confirmed > 0 ? round(($no_show_count / $total_confirmed) * 100, 1) : 0;

// Dịch vụ hot (top 5 dịch vụ được đặt nhiều nhất trong tháng)
$hot_services_stmt = $pdo->prepare("SELECT 
    SUBSTRING_INDEX(note, ' - ', 1) as service,
    COUNT(*) as count
    FROM appointments 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    GROUP BY service
    ORDER BY count DESC
    LIMIT 5");
$hot_services_stmt->execute([$current_month]);
$hot_services = $hot_services_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <base href="./">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Admin Dashboard | DentaCare</title>
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
        <h2 class="mb-4">Tổng quan</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Form tìm kiếm doanh thu -->
        <div class="card mb-4">
          <div class="card-body">
            <form method="GET" action="" class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from" class="form-control" value="<?= $_GET['date_from'] ?? '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to" class="form-control" value="<?= $_GET['date_to'] ?? '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div>
                  <button type="submit" class="btn btn-primary">Tìm kiếm doanh thu</button>
                  <a href="admin_dashboard.php" class="btn btn-secondary">Xóa bộ lọc</a>
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
                <div class="text-value-lg"><?= number_format($calculated_revenue) ?> đ</div>
                <div>Doanh thu<?= $search_period ?><?= empty($search_period) ? " tháng " . date('m/Y') : "" ?></div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card text-white bg-info">
              <div class="card-body">
                <div class="text-value-lg"><?= $today_count ?></div>
                <div>Lịch hẹn hôm nay</div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card text-white bg-primary">
              <div class="card-body">
                <div class="text-value-lg"><?= $accepted_count ?></div>
                <div>Đã nhận (tháng)</div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card text-white bg-danger">
              <div class="card-body">
                <div class="text-value-lg"><?= $rejected_count ?></div>
                <div>Đã từ chối (tháng)</div>
              </div>
            </div>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card text-white bg-warning">
              <div class="card-body">
                <div class="text-value-lg"><?= $no_show_rate ?>%</div>
                <div>Tỷ lệ no-show</div>
                <small>(<?= $no_show_count ?> / <?= $total_confirmed ?>)</small>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card text-white bg-secondary">
              <div class="card-body">
                <div class="text-value-lg"><?= $pending_count ?></div>
                <div>Chờ xử lý (tháng)</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Biểu đồ dịch vụ hot -->
        <div class="row">
          <div class="col-lg-6 mb-4">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Dịch vụ hot (tháng <?= date('m/Y') ?>)</strong>
                <button class="btn btn-sm btn-primary" onclick="exportHotServices()">Export</button>
              </div>
              <div class="card-body">
                <canvas id="hotServicesChart" height="200"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-6 mb-4">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Tổng quan lịch hẹn (tháng <?= date('m/Y') ?>)</strong>
                <button class="btn btn-sm btn-primary" onclick="exportRevenue()">Export</button>
              </div>
              <div class="card-body">
                <canvas id="appointmentsChart" height="200"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Bảng chi tiết dịch vụ hot -->
        <div class="card">
          <div class="card-header">
            <strong>Chi tiết dịch vụ hot</strong>
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
                  <?php foreach ($hot_services as $service): ?>
                  <tr>
                    <td><?= htmlspecialchars($service['service']) ?></td>
                    <td><?= $service['count'] ?></td>
                    <td><?= isset($service_prices[$service['service']]) ? number_format($service_prices[$service['service']]) : 'N/A' ?></td>
                    <td><?= isset($service_prices[$service['service']]) ? number_format($service_prices[$service['service']] * $service['count']) : 'N/A' ?> đ</td>
                  </tr>
                  <?php endforeach; ?>
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
    // Biểu đồ dịch vụ hot
    const hotServicesData = {
      labels: [<?= implode(',', array_map(function($s) { return "'" . htmlspecialchars($s['service']) . "'"; }, $hot_services)) ?>],
      datasets: [{
        label: 'Số lượng',
        data: [<?= implode(',', array_column($hot_services, 'count')) ?>],
        backgroundColor: [
          'rgba(54, 162, 235, 0.6)',
          'rgba(255, 99, 132, 0.6)',
          'rgba(255, 206, 86, 0.6)',
          'rgba(75, 192, 192, 0.6)',
          'rgba(153, 102, 255, 0.6)'
        ]
      }]
    };

    new Chart(document.getElementById('hotServicesChart'), {
      type: 'bar',
      data: hotServicesData,
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

    // Biểu đồ tổng quan lịch hẹn
    const appointmentsData = {
      labels: ['Đã nhận', 'Đã từ chối', 'Chờ xử lý'],
      datasets: [{
        label: 'Số lượng',
        data: [<?= $accepted_count ?>, <?= $rejected_count ?>, <?= $pending_count ?>],
        backgroundColor: [
          'rgba(40, 167, 69, 0.6)',
          'rgba(220, 53, 69, 0.6)',
          'rgba(255, 193, 7, 0.6)'
        ]
      }]
    };

    new Chart(document.getElementById('appointmentsChart'), {
      type: 'doughnut',
      data: appointmentsData,
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });

    function exportRevenue() {
      window.open('export_revenue.php?month=<?= $current_month ?>', '_blank');
    }

    function exportHotServices() {
      window.open('export_hot_services.php?month=<?= $current_month ?>', '_blank');
    }
  </script>
</body>
</html>

</html>
