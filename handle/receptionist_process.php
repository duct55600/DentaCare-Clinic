<?php
session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_once '../functions/email_functions.php';
require_role('receptionist');

$receptionist_id = $_SESSION['user_id'];

// Xử lý GET request (view, get_edit)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'view') {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT a.*, 
            u1.full_name as doctor_name, 
            u2.full_name as receptionist_name
            FROM appointments a
            LEFT JOIN users u1 ON a.assigned_doctor_id = u1.id
            LEFT JOIN users u2 ON a.assigned_receptionist_id = u2.id
            WHERE a.id = ?");
        $stmt->execute([$id]);
        $apt = $stmt->fetch();
        
        if (!$apt) {
            echo json_encode(['error' => 'Không tìm thấy lịch hẹn']);
            exit;
        }
        
        $status_text = match($apt['status']) {
            'pending' => 'Chờ duyệt',
            'waiting_for_approval' => 'Chờ BS xác nhận',
            'confirmed' => 'Đã xác nhận',
            'rejected' => 'Đã từ chối',
            default => $apt['status']
        };
        
        $html = "
        <div class='row'>
            <div class='col-md-6'><strong>Bệnh nhân:</strong></div>
            <div class='col-md-6'>{$apt['patient_name']}</div>
        </div>
        <div class='row mt-2'>
            <div class='col-md-6'><strong>Email:</strong></div>
            <div class='col-md-6'>{$apt['patient_email']}</div>
        </div>
        <div class='row mt-2'>
            <div class='col-md-6'><strong>Số điện thoại:</strong></div>
            <div class='col-md-6'>{$apt['patient_phone']}</div>
        </div>
        <div class='row mt-2'>
            <div class='col-md-6'><strong>Thời gian:</strong></div>
            <div class='col-md-6'>" . date('d/m/Y H:i', strtotime($apt['appointment_date'])) . "</div>
        </div>
        <div class='row mt-2'>
            <div class='col-md-6'><strong>Dịch vụ/Ghi chú:</strong></div>
            <div class='col-md-6'>" . htmlspecialchars($apt['note'] ?? 'N/A') . "</div>
        </div>
        <div class='row mt-2'>
            <div class='col-md-6'><strong>Bác sĩ:</strong></div>
            <div class='col-md-6'>" . htmlspecialchars($apt['doctor_name'] ?? 'Chưa gán') . "</div>
        </div>
        <div class='row mt-2'>
            <div class='col-md-6'><strong>Lễ tân xử lý:</strong></div>
            <div class='col-md-6'>" . htmlspecialchars($apt['receptionist_name'] ?? 'N/A') . "</div>
        </div>
        <div class='row mt-2'>
            <div class='col-md-6'><strong>Trạng thái:</strong></div>
            <div class='col-md-6'><span class='badge bg-info'>{$status_text}</span></div>
        </div>
        <div class='row mt-2'>
            <div class='col-md-6'><strong>Ngày tạo:</strong></div>
            <div class='col-md-6'>" . date('d/m/Y H:i', strtotime($apt['created_at'])) . "</div>
        </div>
        ";
        
        echo json_encode(['html' => $html]);
        exit;
    }
    
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
        
        $html = "
        <div class='mb-3'>
            <label class='form-label'>Tên bệnh nhân</label>
            <input type='text' name='patient_name' class='form-control' value='" . htmlspecialchars($apt['patient_name']) . "' required>
        </div>
        <div class='mb-3'>
            <label class='form-label'>Email</label>
            <input type='email' name='patient_email' class='form-control' value='" . htmlspecialchars($apt['patient_email']) . "' required>
        </div>
        <div class='mb-3'>
            <label class='form-label'>Số điện thoại</label>
            <input type='text' name='patient_phone' class='form-control' value='" . htmlspecialchars($apt['patient_phone']) . "' required>
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
            <textarea name='note' class='form-control' rows='3'>" . htmlspecialchars($apt['note'] ?? '') . "</textarea>
        </div>
        ";
        
        echo json_encode(['html' => $html]);
        exit;
    }
}

if ($_POST['action'] === 'assign') {
    $id = (int)$_POST['id'];
    $doctor_id = (int)$_POST['doctor_id'];
    
    // Lấy thông tin lịch hẹn
    $stmt = $pdo->prepare("SELECT appointment_date, patient_name FROM appointments WHERE id = ?");
    $stmt->execute([$id]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        $_SESSION['error'] = 'Không tìm thấy lịch hẹn để chuyển!';
        $redirect = $_POST['redirect'] ?? 'receptionist_dashboard.php';
        if (!preg_match('/^(https?:\/\/|\/)/', $redirect)) {
            $redirect = '../admin/' . ltrim($redirect, '/');
        }
        header("Location: $redirect");
        exit;
    }

    // Kiểm tra bác sĩ đã bận slot này chưa
    $check = $pdo->prepare("SELECT id FROM appointments 
        WHERE assigned_doctor_id = ? 
          AND appointment_date = ?
          AND status IN ('waiting_for_approval','confirmed')");
    $check->execute([$doctor_id, $appointment['appointment_date']]);

    if ($check->fetch()) {
        $_SESSION['error'] = 'Bác sĩ đã có lịch vào khung giờ này. Vui lòng chọn bác sĩ khác.';
        $redirect = $_POST['redirect'] ?? 'receptionist_dashboard.php';
        if (!preg_match('/^(https?:\/\/|\/)/', $redirect)) {
            $redirect = '../admin/' . ltrim($redirect, '/');
        }
        header("Location: $redirect");
        exit;
    }
    
    // Update appointment: assign to doctor, set status to 'waiting_for_approval', track which receptionist assigned it
    // LƯU Ý: Nếu appointment đã có assigned_doctor_id (bác sĩ từ chối), giữ lại để tính thống kê
    // Nhưng khi chuyển bác sĩ mới, vẫn cập nhật assigned_doctor_id mới
    // Thống kê của bác sĩ cũ vẫn được giữ vì đã có status = 'rejected' với assigned_doctor_id cũ
    $stmt = $pdo->prepare("UPDATE appointments SET assigned_doctor_id = ?, assigned_receptionist_id = ?, status = 'waiting_for_approval' WHERE id = ? AND status IN ('pending','rejected')");
    $stmt->execute([$doctor_id, $receptionist_id, $id]);
    
    $_SESSION['success'] = 'Đã chuyển lịch hẹn cho bác sĩ!';
    
    // Redirect về trang dashboard
    $redirect = $_POST['redirect'] ?? 'receptionist_dashboard.php';
    if (!preg_match('/^(https?:\/\/|\/)/', $redirect)) {
        $redirect = '../admin/' . ltrim($redirect, '/');
    }
    header("Location: $redirect");
    exit;
} elseif ($_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    
    // Get appointment details before deleting
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
    $stmt->execute([$id]);
    $appointment = $stmt->fetch();
    
    if ($appointment) {
        // Lưu thông tin email trước khi xóa
        $email_to = $appointment['patient_email'];
        $email_subject = "Thông báo hủy lịch hẹn - DentaCare";
        $email_message = "<h3>Xin chào {$appointment['patient_name']}!</h3>
                   <p>Rất tiếc, lịch hẹn của bạn không thể được thực hiện.</p>
                   <p>Thông tin lịch hẹn:</p>
                   <ul>
                     <li>Thời gian: " . date('d/m/Y H:i', strtotime($appointment['appointment_date'])) . "</li>
                     <li>Dịch vụ: " . htmlspecialchars($appointment['note'] ?? 'N/A') . "</li>
                   </ul>
                   <p>Vui lòng liên hệ với chúng tôi để đặt lịch hẹn mới.</p>
                   <p>Cảm ơn bạn đã tin tưởng DentaCare!</p>";
        
        // Soft delete appointment (giữ lại để tính thống kê) - set deleted_at thay vì xóa thực sự
        // Kiểm tra xem cột deleted_at có tồn tại không
        try {
            $pdo->prepare("UPDATE appointments SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
        } catch (PDOException $e) {
            // Nếu cột deleted_at chưa có, dùng status = 'deleted'
            $pdo->prepare("UPDATE appointments SET status = 'deleted' WHERE id = ?")->execute([$id]);
}

        $_SESSION['success'] = 'Đã xóa lịch hẹn và gửi thông báo cho bệnh nhân!';
        
        // Gửi email sau khi đã set redirect (không block)
        // Sử dụng @ để suppress errors nếu email gửi chậm
        if (function_exists('fastcgi_finish_request')) {
            // Nếu dùng FastCGI, gửi email sau khi redirect
            register_shutdown_function(function() use ($email_to, $email_subject, $email_message) {
                @sendEmail($email_to, $email_subject, $email_message);
            });
        } else {
            // Nếu không dùng FastCGI, gửi email với error suppression
            // Redirect sẽ không bị block nếu email timeout
            @sendEmail($email_to, $email_subject, $email_message);
        }
    }
    
    // Redirect về trang hiện tại với các tham số tìm kiếm
    $redirect = $_POST['redirect'] ?? 'receptionist_all.php';
    // Nếu redirect không bắt đầu bằng http hoặc /, thêm ../admin/
    if (!preg_match('/^(https?:\/\/|\/)/', $redirect)) {
        $redirect = '../admin/' . ltrim($redirect, '/');
    }
    header("Location: $redirect");
    exit;
} elseif ($_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_email = trim($_POST['patient_email'] ?? '');
    $patient_phone = trim($_POST['patient_phone'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $note = trim($_POST['note'] ?? '');
    
    if (empty($patient_name) || empty($patient_email) || empty($patient_phone) || empty($date) || empty($time)) {
        $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin!';
        $redirect = $_POST['redirect'] ?? 'receptionist_all.php';
        if (!preg_match('/^(https?:\/\/|\/)/', $redirect)) {
            $redirect = '../admin/' . ltrim($redirect, '/');
        }
        header("Location: $redirect");
        exit;
    }
    
    // Validate email
    if (!filter_var($patient_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Email không hợp lệ!';
        $redirect = $_POST['redirect'] ?? 'receptionist_all.php';
        // Nếu redirect không bắt đầu bằng http hoặc /, thêm ../admin/
        if (!preg_match('/^(https?:\/\/|\/)/', $redirect)) {
            $redirect = '../admin/' . ltrim($redirect, '/');
        }
        header("Location: $redirect");
        exit;
    }
    
    // Parse date và time
    if (preg_match('#^(\d{2})\/(\d{2})\/(\d{4})$#', $date, $m)) {
        $appointment_datetime = $m[3] . '-' . sprintf('%02d', $m[2]) . '-' . sprintf('%02d', $m[1]) . ' ' . $time . ':00';
        
        $stmt = $pdo->prepare("UPDATE appointments SET 
            patient_name = ?, 
            patient_email = ?, 
            patient_phone = ?, 
            appointment_date = ?, 
            note = ? 
            WHERE id = ?");
        $stmt->execute([$patient_name, $patient_email, $patient_phone, $appointment_datetime, $note, $id]);
        
        $_SESSION['success'] = 'Đã cập nhật lịch hẹn thành công!';
    } else {
        $_SESSION['error'] = 'Định dạng ngày không hợp lệ!';
    }
    
    // Redirect về trang hiện tại với các tham số tìm kiếm
    $redirect = $_POST['redirect'] ?? 'receptionist_all.php';
    // Nếu redirect không bắt đầu bằng http hoặc /, thêm ../admin/
    if (!preg_match('/^(https?:\/\/|\/)/', $redirect)) {
        $redirect = '../admin/' . ltrim($redirect, '/');
    }
    if (isset($_POST['date_from']) || isset($_POST['date_to'])) {
        $query_params = [];
        if (!empty($_POST['date_from'])) $query_params['date_from'] = $_POST['date_from'];
        if (!empty($_POST['date_to'])) $query_params['date_to'] = $_POST['date_to'];
        if (!empty($query_params)) {
            $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . http_build_query($query_params);
        }
    }
    header("Location: $redirect");
    exit;
} elseif ($_POST['action'] === 'bulk_delete') {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) {
        $_SESSION['error'] = 'Vui lòng chọn ít nhất một lịch hẹn để xóa!';
    } else {
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Lấy thông tin các lịch hẹn để gửi email (trước khi xóa)
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $appointments = $stmt->fetchAll();
        
        // Lưu thông tin email
        $emails_to_send = [];
        foreach ($appointments as $appointment) {
            $emails_to_send[] = [
                'to' => $appointment['patient_email'],
                'subject' => "Thông báo hủy lịch hẹn - DentaCare",
                'message' => "<h3>Xin chào {$appointment['patient_name']}!</h3>
                           <p>Rất tiếc, lịch hẹn của bạn không thể được thực hiện.</p>
                           <p>Thông tin lịch hẹn:</p>
                           <ul>
                             <li>Thời gian: " . date('d/m/Y H:i', strtotime($appointment['appointment_date'])) . "</li>
                             <li>Dịch vụ: " . htmlspecialchars($appointment['note'] ?? 'N/A') . "</li>
                           </ul>
                           <p>Vui lòng liên hệ với chúng tôi để đặt lịch hẹn mới.</p>
                           <p>Cảm ơn bạn đã tin tưởng DentaCare!</p>"
            ];
        }
        
        // Soft delete các lịch hẹn (giữ lại để tính thống kê)
        try {
            $stmt = $pdo->prepare("UPDATE appointments SET deleted_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($ids);
        } catch (PDOException $e) {
            // Nếu cột deleted_at chưa có, dùng status = 'deleted'
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'deleted' WHERE id IN ($placeholders)");
            $stmt->execute($ids);
        }
        
        $_SESSION['success'] = "Đã xóa " . count($ids) . " lịch hẹn và gửi thông báo cho bệnh nhân!";
        
        // Gửi email sau khi đã set redirect (không block)
        if (function_exists('fastcgi_finish_request')) {
            // Nếu dùng FastCGI, gửi email sau khi redirect
            register_shutdown_function(function() use ($emails_to_send) {
                foreach ($emails_to_send as $email_data) {
                    @sendEmail($email_data['to'], $email_data['subject'], $email_data['message']);
                }
            });
        } else {
            // Nếu không dùng FastCGI, gửi email với error suppression
            foreach ($emails_to_send as $email_data) {
                @sendEmail($email_data['to'], $email_data['subject'], $email_data['message']);
            }
        }
    }
    
    // Redirect về trang hiện tại với các tham số tìm kiếm
    $redirect = $_POST['redirect'] ?? 'receptionist_all.php';
    // Nếu redirect không bắt đầu bằng http hoặc /, thêm ../admin/
    if (!preg_match('/^(https?:\/\/|\/)/', $redirect)) {
        $redirect = '../admin/' . ltrim($redirect, '/');
    }
    header("Location: $redirect");
    exit;
}

// Xác định redirect cho các action khác
$redirect = $_POST['redirect'] ?? 'receptionist_all.php';
// Nếu redirect không bắt đầu bằng http hoặc /, thêm ../admin/
if (!preg_match('/^(https?:\/\/|\/)/', $redirect)) {
    $redirect = '../admin/' . ltrim($redirect, '/');
}
if (isset($_POST['date_from']) || isset($_POST['date_to'])) {
    $query_params = [];
    if (!empty($_POST['date_from'])) $query_params['date_from'] = $_POST['date_from'];
    if (!empty($_POST['date_to'])) $query_params['date_to'] = $_POST['date_to'];
    if (!empty($query_params)) {
        $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . http_build_query($query_params);
    }
}
header("Location: $redirect");
exit;