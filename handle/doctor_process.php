<?php
// Tắt warning cho undefined array key (PHP 8+)
error_reporting(E_ALL & ~E_WARNING);

session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_once '../functions/email_functions.php';
require_role('doctor');

$doctor_id = $_SESSION['user_id'];

// Xử lý GET request trước (export PDF)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export_pdf') {
    $id = (int)$_GET['id'];
    
    $stmt = $pdo->prepare("SELECT a.*, u.full_name as doctor_name 
        FROM appointments a
        LEFT JOIN users u ON a.assigned_doctor_id = u.id
        WHERE a.id = ? AND a.assigned_doctor_id = ?");
    $stmt->execute([$id, $doctor_id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        die('Không tìm thấy lịch hẹn');
    }
    
    // Lấy giá dịch vụ
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
    
    // Lấy tên dịch vụ từ note (phần trước dấu " - ")
    $service_name = explode(' - ', $appointment['note'] ?? '')[0] ?? '';
    $service_price = isset($service_prices[$service_name]) ? $service_prices[$service_name] : 0;
    
    // Tạo HTML cho PDF
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Phiếu khám - {$appointment['patient_name']}</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .info { margin: 15px 0; }
            .info strong { display: inline-block; width: 150px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            table td { padding: 8px; border: 1px solid #ddd; }
            table th { padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5; text-align: left; }
            .price-row { font-weight: bold; background-color: #f9f9f9; }
            .footer { margin-top: 30px; text-align: right; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>PHIẾU KHÁM BỆNH</h1>
            <h2>DentaCare - Nha Khoa</h2>
        </div>
        <div class='info'>
            <strong>Bệnh nhân:</strong> {$appointment['patient_name']}
        </div>
        <div class='info'>
            <strong>Email:</strong> {$appointment['patient_email']}
        </div>
        <div class='info'>
            <strong>Số điện thoại:</strong> {$appointment['patient_phone']}
        </div>
        <div class='info'>
            <strong>Thời gian khám:</strong> " . date('d/m/Y H:i', strtotime($appointment['appointment_date'])) . "
        </div>
        <div class='info'>
            <strong>Bác sĩ:</strong> {$appointment['doctor_name']}
        </div>
        <table>
            <thead>
                <tr>
                    <th>Dịch vụ</th>
                    <th style='text-align: right;'>Giá (VNĐ)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>" . htmlspecialchars($service_name ?: 'N/A') . "</td>
                    <td style='text-align: right;'>" . number_format($service_price) . "</td>
                </tr>
                <tr class='price-row'>
                    <td><strong>Tổng cộng:</strong></td>
                    <td style='text-align: right;'><strong>" . number_format($service_price) . " đ</strong></td>
                </tr>
            </tbody>
        </table>";
    
    if (!empty($appointment['doctor_note'])) {
        $html .= "
        <div class='info'>
            <strong>Ghi chú đề xuất:</strong><br>
            " . nl2br(htmlspecialchars($appointment['doctor_note'])) . "
        </div>";
    }
    
    $html .= "
        <div class='footer'>
            <p>Ngày in: " . date('d/m/Y H:i') . "</p>
        </div>
    </body>
    </html>";
    
    // Export PDF sử dụng TCPDF hoặc mở cửa sổ in
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    echo "<script>window.print();</script>";
    exit;
}

// Xử lý POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept') {
    $id = (int)$_POST['appt_id'];
    
    // Get appointment details
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND status = 'waiting_for_approval'");
    $stmt->execute([$id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        $_SESSION['error'] = 'Lịch hẹn không tồn tại hoặc đã được xử lý!';
        header('Location: ../admin/doctor_dashboard.php');
        exit;
    }

    // Server-side duplicate check
    $appointment_date = date('Y-m-d', strtotime($appointment['appointment_date']));
    $stmt = $pdo->prepare("SELECT id FROM appointments 
        WHERE assigned_doctor_id = ? 
        AND DATE(appointment_date) = ? 
        AND status = 'confirmed' 
        AND id != ?");
    $stmt->execute([$doctor_id, $appointment_date, $id]);
    $hasDuplicate = $stmt->fetch();

    if ($hasDuplicate && empty($_POST['confirm_duplicate'])) {
        $_SESSION['duplicate_warning_id']   = $id;
        $_SESSION['duplicate_warning_date'] = $appointment['appointment_date'];
        $_SESSION['duplicate_warning_name'] = $appointment['patient_name'];
        header('Location: ../admin/doctor_dashboard.php');
        exit;
    }
    
    // Accept appointment
    // Lưu vào lịch sử trước khi update để giữ lại thống kê
    try {
        $history_stmt = $pdo->prepare("INSERT INTO appointment_doctor_history (appointment_id, doctor_id, action, status_before, status_after) 
            SELECT id, assigned_doctor_id, 'accepted', status, 'confirmed' 
            FROM appointments WHERE id = ?");
        $history_stmt->execute([$id]);
    } catch (PDOException $e) {
        // Bảng history chưa có, bỏ qua
    }
    
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'confirmed', assigned_doctor_id = ? WHERE id = ?");
    $stmt->execute([$doctor_id, $id]);
    
    // Send email to patient
    $formatted_date = date('d/m/Y H:i', strtotime($appointment['appointment_date']));
    $subject = "Xác nhận đặt lịch khám - DentaCare";
    $message = "<h3>Xin chào {$appointment['patient_name']}!</h3>
               <p>Bạn đã đặt lịch hẹn thành công!</p>
               <p>Thông tin lịch hẹn:</p>
               <ul>
                 <li>Thời gian: <strong>$formatted_date</strong></li>
                 <li>Bác sĩ: <strong>{$_SESSION['full_name']}</strong></li>
                 <li>Ghi chú: " . htmlspecialchars($appointment['note'] ?? 'N/A') . "</li>
               </ul>
               <p>Vui lòng đến đúng giờ hẹn. Cảm ơn bạn đã tin tưởng DentaCare!</p>";
    
    sendEmail($appointment['patient_email'], $subject, $message);
    
    $_SESSION['success'] = 'Đã chấp nhận lịch hẹn và gửi email cho bệnh nhân!';
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    $id = (int)$_POST['appt_id'];
    
    // Get appointment details
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND status = 'waiting_for_approval'");
    $stmt->execute([$id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        $_SESSION['error'] = 'Lịch hẹn không tồn tại hoặc đã được xử lý!';
        header('Location: ../admin/doctor_dashboard.php');
        exit;
    }
    
    // Reject appointment - set status to 'rejected' nhưng giữ assigned_doctor_id để đếm tỷ lệ
    // Lễ tân có thể xem các lịch rejected với assigned_receptionist_id của họ
    // Lưu vào lịch sử trước khi update để giữ lại thống kê
    try {
        $history_stmt = $pdo->prepare("INSERT INTO appointment_doctor_history (appointment_id, doctor_id, action, status_before, status_after) 
            SELECT id, assigned_doctor_id, 'rejected', status, 'rejected' 
            FROM appointments WHERE id = ?");
        $history_stmt->execute([$id]);
    } catch (PDOException $e) {
        // Bảng history chưa có, bỏ qua
    }
    
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = 'Đã từ chối lịch hẹn. Lịch hẹn sẽ được chuyển lại cho lễ tân.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_note') {
    $id = (int)$_POST['appt_id'];
    $doctor_note = trim($_POST['doctor_note'] ?? '');
    
    // Kiểm tra xem cột doctor_note có tồn tại không, nếu không thì dùng note
    try {
        $stmt = $pdo->prepare("UPDATE appointments SET doctor_note = ? WHERE id = ? AND assigned_doctor_id = ?");
        $stmt->execute([$doctor_note, $id, $doctor_id]);
        $_SESSION['success'] = 'Đã thêm ghi chú đề xuất!';
    } catch (PDOException $e) {
        // Nếu cột không tồn tại, có thể cần ALTER TABLE
        // Tạm thời lưu vào note field
        $stmt = $pdo->prepare("UPDATE appointments SET note = CONCAT(COALESCE(note, ''), '\n[Ghi chú BS]: ', ?) WHERE id = ? AND assigned_doctor_id = ?");
        $stmt->execute([$doctor_note, $id, $doctor_id]);
        $_SESSION['success'] = 'Đã thêm ghi chú đề xuất!';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) {
        $_SESSION['error'] = 'Vui lòng chọn ít nhất một lịch hẹn để xóa!';
    } else {
        $ids = array_map('intval', $ids);
        $count = count($ids);
        $placeholders = implode(',', array_fill(0, $count, '?'));
        
        // Soft delete các lịch hẹn (giữ lại để tính thống kê) - chỉ của bác sĩ này
        try {
            $stmt = $pdo->prepare("UPDATE appointments SET deleted_at = NOW() WHERE id IN ($placeholders) AND assigned_doctor_id = ?");
            $params = array_merge($ids, [$doctor_id]);
            $stmt->execute($params);
        } catch (PDOException $e) {
            // Nếu cột deleted_at chưa có, dùng status = 'deleted'
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'deleted' WHERE id IN ($placeholders) AND assigned_doctor_id = ?");
            $params = array_merge($ids, [$doctor_id]);
            $stmt->execute($params);
        }
        
        $_SESSION['success'] = "Đã xóa $count lịch hẹn thành công!";
    }
}

$redirect_tab = $_GET['tab'] ?? $_POST['tab'] ?? 'appointments';
$redirect = '../admin/doctor_dashboard.php?tab=' . $redirect_tab;
header("Location: $redirect");
exit;

