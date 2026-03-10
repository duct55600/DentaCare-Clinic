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

$stmt = $pdo->prepare("SELECT 
    SUBSTRING_INDEX(note, ' - ', 1) as service,
    COUNT(*) as count
    FROM appointments 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
    GROUP BY service
    ORDER BY count DESC");
$stmt->execute([$month]);
$hot_services = $stmt->fetchAll();

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Báo cáo dịch vụ hot - <?= $month ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Báo cáo dịch vụ hot tháng <?= $month ?></h1>
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
            <?php $i = 1; foreach ($hot_services as $service): 
                $price = isset($service_prices[$service['service']]) ? $service_prices[$service['service']] : 0;
                $revenue = $price * $service['count'];
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($service['service']) ?></td>
                <td><?= $service['count'] ?></td>
                <td><?= number_format($price) ?></td>
                <td><?= number_format($revenue) ?> đ</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <script>window.print();</script>
</body>
</html>

