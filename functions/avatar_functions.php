<?php
/**
 * Hàm helper để hiển thị avatar theo role
 * - Admin: Hiển thị initial avatar (chữ cái đầu)
 * - Doctor/Receptionist: Hiển thị ảnh upload hoặc initial nếu chưa có
 */

function getAvatarDisplay($user, $size = 'md') {
    $role = $user['role'] ?? 'admin';
    $full_name = $user['full_name'] ?? 'User';
    $avatar_path = $user['avatar'] ?? null;
    
    // Admin: Luôn hiển thị initial avatar
    if ($role === 'admin') {
        $initial = strtoupper(mb_substr($full_name, 0, 1));
        return '<div class="avatar avatar-' . $size . ' bg-primary text-white d-flex align-items-center justify-content-center" style="font-weight: bold; font-size: 1rem;">' . htmlspecialchars($initial) . '</div>';
    }
    
    // Doctor/Receptionist: Hiển thị ảnh nếu có, không thì initial
    // Avatar path trong DB là: uploads/avatars/filename.jpg
    // Khi hiển thị trong admin (từ admin/includes/header.php): cần ../uploads/avatars/filename.jpg
    if ($avatar_path) {
        // Đảm bảo đường dẫn đúng format
        $display_path = $avatar_path;
        if (strpos($display_path, '../') !== 0 && strpos($display_path, 'uploads/') === 0) {
            $display_path = '../' . $display_path;
        }
        
        // Kiểm tra file tồn tại (từ vị trí gọi hàm - thường là admin/)
        $check_path = '../' . $avatar_path;
        if (file_exists($check_path)) {
            return '<div class="avatar avatar-' . $size . '"><img class="avatar-img" src="' . htmlspecialchars($display_path) . '" alt="' . htmlspecialchars($full_name) . '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"></div>';
        }
    }
    
    // Fallback: hiển thị initial
    $initial = strtoupper(mb_substr($full_name, 0, 1));
    // Màu khác nhau cho doctor và receptionist
    $bg_color = $role === 'doctor' ? 'bg-success' : 'bg-info';
    return '<div class="avatar avatar-' . $size . ' ' . $bg_color . ' text-white d-flex align-items-center justify-content-center" style="font-weight: bold; font-size: 1rem;">' . htmlspecialchars($initial) . '</div>';
}

/**
 * Lấy đường dẫn avatar hoặc initial cho public site
 */
function getAvatarPath($user) {
    $avatar_path = $user['avatar'] ?? null;
    
    if ($avatar_path && file_exists('../uploads/avatars/' . basename($avatar_path))) {
        return '../uploads/avatars/' . basename($avatar_path);
    }
    
    return null; // Trả về null để hiển thị initial
}

/**
 * Lấy initial từ tên
 */
function getInitials($full_name) {
    $words = explode(' ', trim($full_name));
    if (count($words) >= 2) {
        return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[count($words) - 1], 0, 1));
    }
    return strtoupper(mb_substr($full_name, 0, 1));
}
?>

