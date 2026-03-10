<?php
session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_role('admin');

$month = $_GET['month'] ?? date('Y-m');

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

$stmt = $pdo->prepare("SELECT a.*, u.full_name as doctor_name 
    FROM appointments a
    LEFT JOIN users u ON a.assigned_doctor_id = u.id
    WHERE a.status = 'confirmed' 
    AND DATE_FORMAT(a.appointment_date, '%Y-%m') = ?
    ORDER BY a.appointment_date");
$stmt->execute([$month]);
$appointments = $stmt->fetchAll();

$total_revenue = 0;
foreach ($appointments as $apt) {
    $service = explode(' - ', $apt['note'] ?? '')[0] ?? '';
    if (isset($service_prices[$service])) {
        $total_revenue += $service_prices[$service];
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Báo cáo doanh thu - <?= $month ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; font-size: 1.2em; }
    </style>
</head>
<body>
    <h1>Báo cáo doanh thu tháng <?= $month ?></h1>
    <table>
        <thead>
            <tr>
                <th>Ngày</th>
                <th>Bệnh nhân</th>
                <th>Bác sĩ</th>
                <th>Dịch vụ</th>
                <th>Giá (VNĐ)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $apt): 
                $service = explode(' - ', $apt['note'] ?? '')[0] ?? '';
                $price = isset($service_prices[$service]) ? $service_prices[$service] : 0;
            ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($apt['appointment_date'])) ?></td>
                <td><?= htmlspecialchars($apt['patient_name']) ?></td>
                <td><?= htmlspecialchars($apt['doctor_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($service) ?></td>
                <td><?= number_format($price) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="4">Tổng doanh thu</td>
                <td><?= number_format($total_revenue) ?> đ</td>
            </tr>
        </tbody>
    </table>
    <script>window.print();</script>
</body>
</html>

