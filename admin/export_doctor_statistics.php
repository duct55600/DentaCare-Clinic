<?php
session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_role('admin');

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

if ($doctor_id <= 0) {
    die('Không tìm thấy bác sĩ!');
}

// Lấy thông tin bác sĩ
$doctor_stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND role = 'doctor'");
$doctor_stmt->execute([$doctor_id]);
$doctor = $doctor_stmt->fetch();

if (!$doctor) {
    die('Không tìm thấy bác sĩ!');
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

// Xây dựng điều kiện WHERE
$where_conditions = ["h.doctor_id = ?", "h.action = 'accepted'"];
$params = [$doctor_id];

if (!empty($date_from)) {
    $where_conditions[] = "DATE(a.appointment_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(a.appointment_date) <= ?";
    $params[] = $date_to;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Tính thống kê
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
    
    // Lấy chi tiết lịch hẹn
    $appts_stmt = $pdo->prepare("SELECT DISTINCT a.*
        FROM appointment_doctor_history h
        INNER JOIN appointments a ON h.appointment_id = a.id
        $where_clause
        ORDER BY a.appointment_date DESC");
    $appts_stmt->execute($params);
    $appointments = $appts_stmt->fetchAll();
    
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
    
    $appts_stmt = $pdo->prepare("SELECT * FROM appointments 
        $where_clause ORDER BY appointment_date DESC");
    $appts_stmt->execute($params);
    $appointments = $appts_stmt->fetchAll();
    
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

// Tính doanh thu
$total_revenue = 0;
foreach ($appointments as $apt) {
    $service = explode(' - ', $apt['note'] ?? '')[0] ?? '';
    if (isset($service_prices[$service])) {
        $total_revenue += $service_prices[$service];
    }
}

$total_processed = $accepted_count + $rejected_count;
$accept_rate = $total_processed > 0 ? round(($accepted_count / $total_processed) * 100, 1) : 0;

// Tạo chuỗi thời gian cho tiêu đề
$period_str = '';
if (!empty($date_from) && !empty($date_to)) {
    $period_str = " (Từ " . date('d/m/Y', strtotime($date_from)) . " đến " . date('d/m/Y', strtotime($date_to)) . ")";
} elseif (!empty($date_from)) {
    $period_str = " (Từ " . date('d/m/Y', strtotime($date_from)) . ")";
} elseif (!empty($date_to)) {
    $period_str = " (Đến " . date('d/m/Y', strtotime($date_to)) . ")";
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Báo cáo thống kê - <?= htmlspecialchars($doctor['full_name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .summary { background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .summary-item { margin: 10px 0; }
        .total { font-weight: bold; font-size: 1.1em; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Báo cáo thống kê bác sĩ</h1>
    
    <div class="summary">
        <h2>Thông tin bác sĩ</h2>
        <div class="summary-item"><strong>Tên:</strong> <?= htmlspecialchars($doctor['full_name']) ?></div>
        <div class="summary-item"><strong>Email:</strong> <?= htmlspecialchars($doctor['email']) ?></div>
    </div>
    
    <div class="summary">
        <h2>Tổng quan thống kê<?= $period_str ?></h2>
        <div class="summary-item"><strong>Đã nhận:</strong> <?= $accepted_count ?> lịch hẹn</div>
        <div class="summary-item"><strong>Đã từ chối:</strong> <?= $rejected_count ?> lịch hẹn</div>
        <div class="summary-item"><strong>Tổng xử lý:</strong> <?= $total_processed ?> lịch hẹn</div>
        <div class="summary-item"><strong>Tỷ lệ nhận:</strong> <?= $accept_rate ?>%</div>
        <div class="summary-item"><strong>Doanh thu:</strong> <?= number_format($total_revenue) ?> đ</div>
    </div>
    
    <h2>Thống kê theo dịch vụ</h2>
    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Dịch vụ</th>
                <th>Số lượng</th>
                <th>Giá (VNĐ)</th>
                <th>Doanh thu</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($service_stats)): ?>
                <tr>
                    <td colspan="5" class="text-center">Chưa có dữ liệu</td>
                </tr>
            <?php else: ?>
                <?php $i = 1; foreach ($service_stats as $service): 
                    $price = isset($service_prices[$service['service']]) ? $service_prices[$service['service']] : 0;
                    $service_revenue = $price * $service['count'];
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($service['service']) ?></td>
                    <td><?= $service['count'] ?></td>
                    <td class="text-right"><?= number_format($price) ?></td>
                    <td class="text-right"><?= number_format($service_revenue) ?> đ</td>
                </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td colspan="4" class="text-right">Tổng doanh thu</td>
                    <td class="text-right"><?= number_format($total_revenue) ?> đ</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <h2>Chi tiết lịch hẹn đã nhận</h2>
    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Ngày giờ</th>
                <th>Bệnh nhân</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Dịch vụ</th>
                <th>Giá (VNĐ)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($appointments)): ?>
                <tr>
                    <td colspan="7" class="text-center">Chưa có lịch hẹn nào</td>
                </tr>
            <?php else: ?>
                <?php $i = 1; foreach ($appointments as $apt): 
                    $service = explode(' - ', $apt['note'] ?? '')[0] ?? '';
                    $price = isset($service_prices[$service]) ? $service_prices[$service] : 0;
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($apt['appointment_date'])) ?></td>
                    <td><?= htmlspecialchars($apt['patient_name']) ?></td>
                    <td><?= htmlspecialchars($apt['patient_email']) ?></td>
                    <td><?= htmlspecialchars($apt['patient_phone']) ?></td>
                    <td><?= htmlspecialchars($service) ?></td>
                    <td class="text-right"><?= number_format($price) ?> đ</td>
                </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td colspan="6" class="text-right">Tổng doanh thu</td>
                    <td class="text-right"><?= number_format($total_revenue) ?> đ</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 30px; text-align: center; color: #888; font-size: 0.9em;">
        <p>Báo cáo được tạo vào: <?= date('d/m/Y H:i:s') ?></p>
    </div>
    
    <script>window.print();</script>
</body>
</html>
