<?php
// Hàm đa ngôn ngữ đơn giản
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi'; // Mặc định tiếng Việt
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = in_array($_GET['lang'], ['vi', 'en']) ? $_GET['lang'] : 'vi';
}

$lang = $_SESSION['lang'];

$translations = [
    'vi' => [
        'home' => 'Trang chủ',
        'about' => 'Giới thiệu',
        'services' => 'Dịch vụ',
        'doctors' => 'Bác sĩ',
        'blog' => 'Blog',
        'contact' => 'Liên hệ',
        'my_appointments' => 'Lịch hẹn của tôi',
        'book_appointment' => 'Đặt lịch khám',
        'welcome' => 'Xin chào',
        'appointment_success' => 'Đặt lịch thành công!',
        'select_service' => 'Chọn dịch vụ',
        'name' => 'Họ tên',
        'email' => 'Email',
        'phone' => 'Số điện thoại',
        'date' => 'Ngày',
        'time' => 'Giờ',
        'note' => 'Ghi chú',
        'submit' => 'Đặt lịch',
    ],
    'en' => [
        'home' => 'Home',
        'about' => 'About',
        'services' => 'Services',
        'doctors' => 'Doctors',
        'blog' => 'Blog',
        'contact' => 'Contact',
        'my_appointments' => 'My Appointments',
        'book_appointment' => 'Book Appointment',
        'welcome' => 'Welcome',
        'appointment_success' => 'Appointment booked successfully!',
        'select_service' => 'Select Service',
        'name' => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'date' => 'Date',
        'time' => 'Time',
        'note' => 'Note',
        'submit' => 'Book',
    ]
];

function t($key) {
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}
?>

