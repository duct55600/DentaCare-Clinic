<?php
session_start();
require_once '../config/db.php';
require_once '../functions/email_functions.php';

// Xử lý GET request (get_edit)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_edit') {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        $apt = $stmt->fetch();
        
        if (!$apt) {
            echo json_encode(['error' => 'Không tìm thấy lịch hẹn']);
            exit;
        }
        
        $date = date('d/m/Y', strtotime($apt['appointment_date']));
        $time = date('H:i', strtotime($apt['appointment_date']));
        $note_parts = explode(' - ', $apt['note'] ?? '');
        $department = $note_parts[0] ?? '';
        $reason = isset($note_parts[1]) ? implode(' - ', array_slice($note_parts, 1)) : '';
        
        $html = "
        <div class='mb-3'>
            <label class='form-label'>Tên</label>
            <input type='text' name='name' class='form-control' value='" . htmlspecialchars($apt['patient_name']) . "' required>
        </div>
        <div class='mb-3'>
            <label class='form-label'>Email</label>
            <input type='email' name='email' class='form-control' value='" . htmlspecialchars($apt['patient_email']) . "' required>
        </div>
        <div class='mb-3'>
            <label class='form-label'>Số điện thoại</label>
            <input type='text' name='phone' class='form-control' value='" . htmlspecialchars($apt['patient_phone']) . "' required>
        </div>
        <div class='mb-3'>
            <label class='form-label'>Dịch vụ</label>
            <select name='department' class='form-control' required>
                <option value=''>Chọn dịch vụ</option>
                <option value='Tẩy trắng răng'" . ($department == 'Tẩy trắng răng' ? ' selected' : '') . ">Tẩy trắng răng</option>
                <option value='Cạo vôi răng'" . ($department == 'Cạo vôi răng' ? ' selected' : '') . ">Cạo vôi răng</option>
                <option value='Niềng răng'" . ($department == 'Niềng răng' ? ' selected' : '') . ">Niềng răng</option>
                <option value='Cấy ghép Implant'" . ($department == 'Cấy ghép Implant' ? ' selected' : '') . ">Cấy ghép Implant</option>
                <option value='Nhổ răng'" . ($department == 'Nhổ răng' ? ' selected' : '') . ">Nhổ răng</option>
                <option value='Trám răng'" . ($department == 'Trám răng' ? ' selected' : '') . ">Trám răng</option>
                <option value='Điều trị tủy'" . ($department == 'Điều trị tủy' ? ' selected' : '') . ">Điều trị tủy</option>
                <option value='Bọc răng sứ'" . ($department == 'Bọc răng sứ' ? ' selected' : '') . ">Bọc răng sứ</option>
                <option value='Tẩy trắng răng tại nhà'" . ($department == 'Tẩy trắng răng tại nhà' ? ' selected' : '') . ">Tẩy trắng răng tại nhà</option>
                <option value='Khám tổng quát'" . ($department == 'Khám tổng quát' ? ' selected' : '') . ">Khám tổng quát</option>
            </select>
        </div>
        <div class='mb-3'>
            <label class='form-label'>Ngày</label>
            <input type='text' name='date' class='form-control appointment_date' value='{$date}' required>
        </div>
        <div class='mb-3'>
            <label class='form-label'>Giờ</label>
            <input type='text' name='time' class='form-control appointment_time' value='{$time}' required>
        </div>
        <div class='mb-3'>
            <label class='form-label'>Ghi chú</label>
            <textarea name='reason' class='form-control' rows='3'>" . htmlspecialchars($reason) . "</textarea>
        </div>
        ";
        
        echo json_encode(['html' => $html]);
        exit;
    }
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Xử lý edit và cancel
    if ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        $department = $_POST['department'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        
        if (empty($name) || empty($email) || empty($phone) || empty($date) || empty($time)) {
            echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin']);
            exit;
        }
        
        if (preg_match('#^(\d{2})\/(\d{2})\/(\d{4})$#', $date, $m)) {
            $appointment_datetime = $m[3] . '-' . sprintf('%02d', $m[2]) . '-' . sprintf('%02d', $m[1]) . ' ' . $time . ':00';
            $note = trim($department . ($reason ? ' - ' . $reason : ''));
            
            $stmt = $pdo->prepare("UPDATE appointments SET 
                patient_name = ?, patient_email = ?, patient_phone = ?, 
                appointment_date = ?, note = ? 
                WHERE id = ? AND status IN ('pending', 'waiting_for_approval')");
            $stmt->execute([$name, $email, $phone, $appointment_datetime, $note, $id]);
            
            echo json_encode(['status' => 'success', 'message' => 'Đã cập nhật lịch hẹn thành công!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Định dạng ngày không hợp lệ']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'cancel') {
        $id = (int)$_POST['id'];
        
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        $apt = $stmt->fetch();
        
        if ($apt && ($apt['status'] == 'pending' || $apt['status'] == 'waiting_for_approval')) {
            // Gửi email thông báo hủy
            $subject = "Thông báo hủy lịch hẹn - DentaCare";
            $message = "<h3>Xin chào {$apt['patient_name']}!</h3>
                       <p>Lịch hẹn của bạn đã được hủy.</p>
                       <p>Thông tin lịch hẹn đã hủy:</p>
                       <ul>
                         <li>Thời gian: " . date('d/m/Y H:i', strtotime($apt['appointment_date'])) . "</li>
                         <li>Dịch vụ: " . htmlspecialchars($apt['note'] ?? 'N/A') . "</li>
                       </ul>
                       <p>Vui lòng liên hệ với chúng tôi nếu bạn muốn đặt lịch mới.</p>";
            
            sendEmail($apt['patient_email'], $subject, $message);
            
            // Soft delete appointment (giữ lại để tính thống kê)
            try {
                $pdo->prepare("UPDATE appointments SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
            } catch (PDOException $e) {
                $pdo->prepare("UPDATE appointments SET status = 'deleted' WHERE id = ?")->execute([$id]);
            }
            echo json_encode(['status' => 'success', 'message' => 'Đã hủy lịch hẹn thành công!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Không thể hủy lịch hẹn này']);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

$name       = trim($_POST['name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$date       = $_POST['date'] ?? '';
$time       = $_POST['time'] ?? '';
$department = $_POST['department'] ?? ''; // Keep for form compatibility
$reason     = trim($_POST['reason'] ?? '');

if (empty($name) || empty($email) || empty($phone) || empty($date) || empty($time)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Email không hợp lệ']);
    exit;
}

// Trong handle/appointment_process.php
if (!preg_match('#^(\d{2})\/(\d{2})\/(\d{4})$#', $date, $m)) {
    echo json_encode(['status' => 'error', 'message' => 'Ngày không đúng định dạng']);
    exit;
}
// $m[1] = ngày, $m[2] = tháng, $m[3] = năm
$appointment_datetime = $m[3] . '-' . sprintf('%02d', $m[2]) . '-' . sprintf('%02d', $m[1]) . ' ' . $time . ':00';
$appointment_timestamp = strtotime($appointment_datetime);

if ($appointment_timestamp === false) {
    echo json_encode(['status' => 'error', 'message' => 'Giờ không hợp lệ']);
    exit;
}

if ($appointment_timestamp < time()) {
    echo json_encode(['status' => 'error', 'message' => 'Không thể đặt lịch quá khứ']);
    exit;
}
try {
    // Combine department and reason into note field
    $note = trim($department . ($reason ? ' - ' . $reason : ''));
    
    $stmt = $pdo->prepare("INSERT INTO appointments 
        (patient_name, patient_email, patient_phone, appointment_date, note, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$name, $email, $phone, $appointment_datetime, $note]);

    $formatted = date('d/m/Y lúc H:i', strtotime($appointment_datetime));
    $subject = "Xác nhận đặt lịch khám - DentaCare";
    $message = "<h3>Xin chào " . htmlspecialchars($name) . "!</h3><p>Lịch hẹn của bạn đã được tiếp nhận:</p>
                <ul>" . ($department ? "<li>Dịch vụ: <strong>" . htmlspecialchars($department) . "</strong></li>" : "") . "
                <li>Thời gian: <strong>" . htmlspecialchars($formatted) . "</strong></li></ul>
                <p>Cảm ơn bạn đã tin tưởng DentaCare!</p>";

    $mailResult = sendEmail($email, $subject, $message);

    if ($mailResult) {
        echo json_encode(['status' => 'success', 'message' => 'Đặt lịch thành công! Vui lòng kiểm tra email.']);
    } else {
        // Đặt lịch thành công nhưng gửi email thất bại
        echo json_encode(['status' => 'success', 'message' => 'Đặt lịch thành công nhưng gửi email thất bại. Vui lòng liên hệ phòng khám để xác nhận.']);
    }

} catch (Exception $e) {
    error_log("Lỗi đặt lịch: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra, vui lòng thử lại!']);
}
?>