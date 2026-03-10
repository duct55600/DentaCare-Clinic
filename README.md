<h2 align="center">
    <a href="https://dainam.edu.vn/vi/khoa-cong-nghe-thong-tin">
    🎓 Faculty of Information Technology (DaiNam University)
    </a>
</h2>
<h2 align="center">
    🦷 DentaCare - Hệ thống quản lý phòng khám nha khoa
</h2>
<div align="center">
    <p align="center">
        <img src="docs/logo/aiotlab_logo.png" alt="AIoTLab Logo" width="170"/>
        <img src="docs/logo/fitdnu_logo.png" alt="FIT Logo" width="180"/>
        <img src="docs/logo/dnu_logo.png" alt="DaiNam University Logo" width="200"/>
    </p>

[![AIoTLab](https://img.shields.io/badge/AIoTLab-green?style=for-the-badge)](https://www.facebook.com/DNUAIoTLab)
[![Faculty of Information Technology](https://img.shields.io/badge/Faculty%20of%20Information%20Technology-blue?style=for-the-badge)](https://dainam.edu.vn/vi/khoa-cong-nghe-thong-tin)
[![DaiNam University](https://img.shields.io/badge/DaiNam%20University-orange?style=for-the-badge)](https://dainam.edu.vn)

</div>

## 📖 1. Giới thiệu

Hệ thống Quản lý Phòng khám Nha khoa (DentaCare) được xây dựng nhằm hỗ trợ các phòng khám nha khoa trong việc quản lý toàn diện các quy trình hoạt động. Hệ thống cung cấp một nền tảng web hiện đại, dễ sử dụng để quản lý lịch hẹn, phân công bác sĩ, xử lý đặt lịch và thống kê doanh thu.

### Các chức năng chính:
- **Đặt lịch khám trực tuyến**: Bệnh nhân có thể đặt lịch khám qua website với thông báo email tự động
- **Quản lý lịch hẹn**: Lễ tân và Admin có thể xem, sửa, hủy và phân công bác sĩ cho từng lịch hẹn
- **Xử lý lịch hẹn**: Bác sĩ có thể chấp nhận, từ chối lịch hẹn với ghi chú và thông báo email tự động
- **Quản lý người dùng**: Admin quản lý tài khoản Bác sĩ, Lễ tân với phân quyền rõ ràng
- **Thống kê và báo cáo**: Dashboard thống kê doanh thu, số lịch hẹn, dịch vụ hot, tỷ lệ nhận/từ chối
- **Quản lý tài khoản cá nhân**: Người dùng có thể cập nhật thông tin, avatar, đổi mật khẩu
- **Email thông báo tự động**: Tích hợp Brevo SMTP để gửi email xác nhận đặt lịch, hủy lịch, chấp nhận/từ chối

## 🔧 2. Các công nghệ được sử dụng

<div align="center">

### Hệ điều hành
![Windows](https://img.shields.io/badge/Windows-0078D6?style=for-the-badge&logo=windows&logoColor=white)
![Linux](https://img.shields.io/badge/Linux-FCC624?style=for-the-badge&logo=linux&logoColor=black)

### Backend
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![PDO](https://img.shields.io/badge/PDO-MySQL-blue?style=for-the-badge)](#)
[![PHPMailer](https://img.shields.io/badge/PHPMailer-0078D4?style=for-the-badge)](#)

### Frontend
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](#)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](#)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](#)
[![jQuery](https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white)](https://jquery.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![CoreUI](https://img.shields.io/badge/CoreUI-FF6F00?style=for-the-badge)](#)
[![SweetAlert2](https://img.shields.io/badge/SweetAlert2-6C757D?style=for-the-badge)](#)
[![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)](https://www.chartjs.org/)

### Web Server & Database
[![Apache](https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)](https://www.apachefriends.org/)

### Email Service
[![Brevo](https://img.shields.io/badge/Brevo-0092FF?style=for-the-badge&logo=sendinblue&logoColor=white)](https://www.brevo.com/)

### Database Management Tools
[![MySQL Workbench](https://img.shields.io/badge/MySQL_Workbench-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://dev.mysql.com/downloads/workbench/)
[![phpMyAdmin](https://img.shields.io/badge/phpMyAdmin-6C78AF?style=for-the-badge&logo=phpmyadmin&logoColor=white)](https://www.phpmyadmin.net/)

</div>

## 🚀 3. Hình ảnh các chức năng

### Trang chủ website (Bệnh nhân)
<img src="docs/images/trang-chu.png" alt="Trang chủ DentaCare" width="900"/>
*Trang chủ với form đặt lịch khám trực tuyến, danh sách bác sĩ, thông tin phòng khám*

### Dashboard Admin
<img src="docs/images/dashboard-admin.png" alt="Dashboard Admin" width="900"/>
*Trang tổng quan với thống kê doanh thu, số lịch hẹn, biểu đồ dịch vụ hot, tỷ lệ no-show*

### Quản lý người dùng (Admin)
<img src="docs/images/ql-nguoi-dung.png" alt="Quản lý người dùng" width="900"/>
*Trang quản lý tài khoản Bác sĩ và Lễ tân: thêm, sửa, xóa, tìm kiếm*

### Dashboard Lễ tân
<img src="docs/images/dashboard-letan-1.png" alt="Dashboard Lễ tân - Lịch chờ duyệt" width="900"/>
*Trang lịch chờ duyệt - Xem danh sách lịch hẹn cần xử lý: phân công bác sĩ, sửa/xóa*

<img src="docs/images/dashboard-letan-2.png" alt="Dashboard Lễ tân - Tất cả lịch hẹn" width="900"/>
*Trang tất cả lịch hẹn - Quản lý lịch hẹn: xem, tìm kiếm theo ngày, phân công bác sĩ, sửa/xóa*

### Dashboard Bác sĩ
<img src="docs/images/dashboard-bacsi.png" alt="Dashboard Bác sĩ" width="900"/>
*Trang xử lý lịch hẹn: xem lịch chờ duyệt, chấp nhận/từ chối, thêm ghi chú, thống kê cá nhân*

### Trang lịch hẹn của tôi (Bệnh nhân)
<img src="docs/images/my-appoinment.png" alt="Lịch hẹn của tôi" width="900"/>
*Trang tra cứu lịch hẹn theo số điện thoại, xem trạng thái, sửa/hủy lịch hẹn*

## ⚙️ 4. Cài đặt

### 4.1. Cài đặt công cụ và môi trường phát triển

#### Bước 1: Cài đặt XAMPP
- Tải và cài đặt **XAMPP** (khuyến nghị PHP 8.x)  
  👉 https://www.apachefriends.org/download.html

#### Bước 2: Cài đặt Visual Studio Code
- Tải và cài đặt **VS Code**: https://code.visualstudio.com/
- Cài đặt các extension hữu ích:
  - **PHP Intelephense** - Hỗ trợ PHP
  - **MySQL** - Quản lý MySQL
  - **Prettier** - Code Formatter

#### Bước 3: Cài đặt MySQL Workbench (Tùy chọn)
- Download tại: https://dev.mysql.com/downloads/workbench/

### 4.2. Tải project

Clone project về thư mục `htdocs` của XAMPP:

```bash
cd C:\xampp\htdocs
git clone https://github.com/MrPhong19/DentaCare-Clinic.git
cd "DentaCare-Clinic"
```

### 4.3. Setup database

1. Mở **XAMPP Control Panel** → Start **Apache** và **MySQL**

2. Truy cập **phpMyAdmin** hoặc **MySQL Workbench** và tạo database:

```sql
CREATE DATABASE IF NOT EXISTS dental_clinic
   CHARACTER SET utf8mb4
   COLLATE utf8mb4_unicode_ci;
```

3. Import database schema (nếu có file SQL):
   - Mở phpMyAdmin: http://localhost/phpmyadmin
   - Chọn database `dental_clinic`
   - Import file SQL hoặc tạo các bảng theo schema trong báo cáo

### 4.4. Setup cấu hình kết nối database

Mở file `config/db.php` và chỉnh thông tin kết nối phù hợp:

```php
<?php
function getDbConnection() {
    $host = 'localhost';
    $dbname = 'dental_clinic';
    $username = 'root';  // Thay đổi nếu cần
    $password = '';      // Thay đổi nếu MySQL có password
    $port = 3306;

    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Kết nối CSDL thất bại: " . $e->getMessage());
    }
}

$pdo = getDbConnection();
?>
```

### 4.5. Setup cấu hình email (Brevo SMTP)

**QUAN TRỌNG**: File cấu hình email chứa SMTP key, không được commit lên GitHub.

1. Copy file mẫu:
```bash
copy config\email_config.local.php.example config\email_config.local.php
```

2. Mở file `config/email_config.local.php` và điền thông tin SMTP Brevo của bạn:

```php
<?php
define('BREVO_SMTP_HOST', 'smtp-relay.brevo.com');
define('BREVO_SMTP_PORT', 587);
define('BREVO_SMTP_SECURE', 'tls');
define('BREVO_SMTP_USERNAME', 'YOUR_BREVO_SMTP_USERNAME@smtp-brevo.com');
define('BREVO_SMTP_PASSWORD', 'YOUR_BREVO_SMTP_KEY');
define('BREVO_FROM_EMAIL', 'YOUR_BREVO_SMTP_USERNAME@smtp-brevo.com');
define('BREVO_FROM_NAME', 'DentaCare - Nha Khoa');
define('BREVO_CC_EMAIL', '');
?>
```

**Lưu ý**: 
- File `config/email_config.local.php` sẽ tự động được `.gitignore` (không commit lên GitHub)
- Để có SMTP key, bạn cần đăng ký tài khoản Brevo (https://www.brevo.com/) và tạo SMTP key trong mục **SMTP & API**

### 4.6. Tạo thư mục uploads (nếu chưa có)

```bash
mkdir uploads\avatars
```

File `.htaccess` trong `uploads/avatars/` sẽ tự động được tạo để bảo vệ thư mục.

### 4.7. Chạy hệ thống

1. Mở **XAMPP Control Panel** → Start **Apache** và **MySQL**

2. Truy cập hệ thống:
   - **Trang chủ (Public)**: 👉 http://localhost/DentaCare-Clinic/public_site/index.php
   - **Trang đăng nhập Admin**: 👉 http://localhost/DentaCare-Clinic/admin/login.php

### 4.8. Tạo tài khoản đầu tiên

Sau khi setup xong, bạn cần tạo tài khoản Admin đầu tiên:

1. Truy cập trang đăng ký Admin: `admin/register.php` (hoặc tạo trực tiếp trong database)
2. Đăng nhập với tài khoản Admin
3. Từ trang Admin, bạn có thể:
   - Thêm tài khoản Bác sĩ và Lễ tân
   - Quản lý lịch hẹn
   - Xem thống kê và báo cáo

## 📋 5. Cấu trúc thư mục

```
DentaCare-Clinic/
├── admin/              # Khu vực quản trị (Admin, Lễ tân, Bác sĩ)
│   ├── includes/       # Header, sidebar, navigation
│   ├── login.php       # Trang đăng nhập
│   ├── register.php    # Đăng ký Admin/Bác sĩ/Lễ tân
│   └── ...
├── public_site/        # Website công khai cho bệnh nhân
│   ├── includes/       # Navigation, appointment modal
│   ├── index.php       # Trang chủ
│   ├── doctors.php     # Danh sách bác sĩ
│   ├── my_appointments.php  # Lịch hẹn của bệnh nhân
│   └── ...
├── config/             # Cấu hình
│   ├── db.php          # Cấu hình kết nối database
│   └── email_config.local.php.example  # Mẫu cấu hình email
├── functions/          # Các hàm dùng chung
│   ├── auth_functions.php   # Xác thực và phân quyền
│   ├── email_functions.php  # Gửi email qua Brevo SMTP
│   └── avatar_functions.php # Xử lý avatar người dùng
├── handle/             # Xử lý các request từ form
│   ├── appointment_process.php  # Xử lý đặt lịch
│   ├── doctor_process.php       # Xử lý lịch hẹn của bác sĩ
│   └── ...
├── uploads/            # Thư mục lưu file upload (avatars)
└── .gitignore          # Các file không commit lên GitHub
```

## 🔐 6. Bảo mật

- **SMTP Key**: Không được commit lên GitHub, nằm trong `config/email_config.local.php` (đã được `.gitignore`)
- **Database password**: Cập nhật trong `config/db.php` và không commit file có thông tin nhạy cảm
- **File upload**: Thư mục `uploads/` được bảo vệ bằng `.htaccess`

## 👥 7. Các vai trò trong hệ thống

- **Admin**: Quản lý toàn bộ hệ thống, quản lý người dùng, xem thống kê tổng quan
- **Lễ tân**: Quản lý lịch hẹn, phân công bác sĩ, xem tất cả lịch hẹn
- **Bác sĩ**: Xử lý lịch hẹn (chấp nhận/từ chối), xem thống kê cá nhân, in phiếu khám
- **Bệnh nhân**: Đặt lịch khám, xem lịch hẹn của mình, sửa/hủy lịch hẹn

## 📧 8. Email thông báo

Hệ thống tự động gửi email thông báo qua Brevo SMTP khi:
- Bệnh nhân đặt lịch thành công
- Lịch hẹn bị hủy
- Bác sĩ chấp nhận/từ chối lịch hẹn

## 🐛 9. Xử lý lỗi thường gặp

### Lỗi kết nối database
- Kiểm tra MySQL đã chạy trong XAMPP
- Kiểm tra thông tin kết nối trong `config/db.php`

### Email không gửi được
- Kiểm tra file `config/email_config.local.php` đã được tạo và điền đúng thông tin SMTP
- Kiểm tra SMTP key Brevo còn hiệu lực
- Xem log trong Apache error log để debug

### Lỗi upload avatar
- Kiểm tra thư mục `uploads/avatars/` có quyền ghi
- Kiểm tra file `.htaccess` trong thư mục uploads

## 📝 10. License

Dự án này được phát triển cho mục đích học tập và nghiên cứu.


---

## 📞 Liên hệ

Nếu có thắc mắc hoặc cần hỗ trợ, vui lòng tạo issue trên GitHub repository.

**Repository**: https://github.com/MrPhong19/DentaCare-Clinic

---

*Cảm ơn bạn đã quan tâm đến dự án DentaCare! 🦷✨*
