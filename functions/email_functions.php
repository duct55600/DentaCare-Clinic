<?php
require_once __DIR__ . '/../admin/vendors/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../admin/vendors/PHPMailer/SMTP.php';
require_once __DIR__ . '/../admin/vendors/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Cấu hình SMTP cho Brevo (Sendinblue)
 * 
 * QUAN TRỌNG: File config/email_config.local.php chứa SMTP key thực tế (không commit lên GitHub)
 * Nếu file local không tồn tại, các hằng số bên dưới sẽ dùng giá trị placeholder (sẽ không gửi được email)
 * 
 * HƯỚNG DẪN:
 * 1. Copy file config/email_config.local.php.example thành config/email_config.local.php
 * 2. Điền thông tin SMTP thực tế vào config/email_config.local.php
 * 3. File config/email_config.local.php sẽ tự động được .gitignore (không commit lên GitHub)
 */

// Load cấu hình từ file local nếu có (file này không commit lên GitHub)
$local_config = __DIR__ . '/../config/email_config.local.php';
if (file_exists($local_config)) {
    // File local có SMTP key thực tế (file này không commit lên GitHub)
    require_once $local_config;
}

// Nếu file local không tồn tại hoặc chưa định nghĩa các hằng số, dùng placeholder
if (!defined('BREVO_SMTP_HOST')) {
    // Placeholder - sẽ không gửi được email cho đến khi tạo file config local
    define('BREVO_SMTP_HOST', 'smtp-relay.brevo.com');
    define('BREVO_SMTP_PORT', 587);
    define('BREVO_SMTP_SECURE', 'tls');
    define('BREVO_SMTP_USERNAME', 'YOUR_BREVO_SMTP_USERNAME@smtp-brevo.com');
    define('BREVO_SMTP_PASSWORD', 'YOUR_BREVO_SMTP_KEY');
    define('BREVO_FROM_EMAIL', 'YOUR_BREVO_SMTP_USERNAME@smtp-brevo.com');
    define('BREVO_FROM_NAME', 'DentaCare - Nha Khoa');
    define('BREVO_CC_EMAIL', '');
}

function sendEmail($to, $subject, $body, $ccReceptionist = false)
{
    // Tăng thời gian thực thi tối đa cho hàm gửi email (30 giây)
    $original_timeout = ini_get('max_execution_time');
    @set_time_limit(30);
    
    try {
        $mail = new PHPMailer();
        // Class PHPMailer đã được custom để luôn gửi qua SMTP nên không cần gọi isSMTP()
        $mail->CharSet = 'UTF-8';
        
        // Cấu hình SMTP (Brevo)
        $mail->Host       = BREVO_SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = BREVO_SMTP_USERNAME;
        $mail->Password   = BREVO_SMTP_PASSWORD;
        $mail->SMTPSecure = BREVO_SMTP_SECURE;
        $mail->Port       = BREVO_SMTP_PORT;
        $mail->Timeout    = 10; // Timeout 10 giây cho SMTP
        
        // Người gửi và người nhận
        $fromEmail = BREVO_FROM_EMAIL ?: BREVO_SMTP_USERNAME;
        $fromName  = BREVO_FROM_NAME;
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        if ($ccReceptionist && !empty(BREVO_CC_EMAIL)) {
            $mail->addCC(BREVO_CC_EMAIL);
        }

        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $result = $mail->send();
        
        // Khôi phục timeout ban đầu
        if ($original_timeout !== false) {
            @set_time_limit($original_timeout);
        }
        
        // Ghi log kết quả để debug
        if ($result) {
            error_log("[EMAIL] (Brevo) Gửi đến $to thành công.");
        } else {
            error_log("[EMAIL] (Brevo) Gửi đến $to thất bại: " . ($mail->ErrorInfo ?? 'Unknown error'));
        }
        
        return $result;
    } catch (Exception $e) {
        if ($original_timeout !== false) {
            @set_time_limit($original_timeout);
        }
        error_log("[EMAIL] (Brevo) Exception (PHPMailer): " . $e->getMessage());
        return false;
    } catch (\Exception $e) {
        if ($original_timeout !== false) {
            @set_time_limit($original_timeout);
        }
        error_log("[EMAIL] (Brevo) Exception (Generic): " . $e->getMessage());
        return false;
    }
}
?>