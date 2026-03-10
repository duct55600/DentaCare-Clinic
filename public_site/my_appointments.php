<?php
session_start();
require_once '../config/db.php';

$phone = '';
$appointments = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($phone)) {
        $error = 'Vui lòng nhập số điện thoại!';
    } else {
        // Bỏ qua các appointment đã bị soft delete
        try {
            $test_col = $pdo->query("SELECT deleted_at FROM appointments LIMIT 1");
            $deleted_filter = "AND deleted_at IS NULL";
        } catch (PDOException $e) {
            $deleted_filter = "AND status != 'deleted'";
        }
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE patient_phone = ? $deleted_filter ORDER BY appointment_date DESC");
        $stmt->execute([$phone]);
        $appointments = $stmt->fetchAll();
        
        if (empty($appointments)) {
            $error = 'Bạn chưa đặt lịch nào với số điện thoại này.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lịch hẹn của tôi | DentaCare</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
  <?php 
  $current_page = 'my_appointments';
  include 'includes/nav.php'; 
  ?>

  <section class="ftco-section">
    <div class="container">
      <div class="row justify-content-center mb-3">
        <div class="col-md-8">
          <a href="index.php" class="btn btn-secondary">
            <span>←</span> Quay về trang chủ
          </a>
        </div>
      </div>
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card shadow">
            <div class="card-body p-5">
              <h2 class="text-center mb-4">Lịch hẹn của tôi</h2>
              
              <form method="POST" class="mb-4">
                <div class="form-group mb-3">
                  <label class="form-label">Nhập số điện thoại</label>
                  <input type="text" name="phone" class="form-control form-control-lg" 
                         value="<?= htmlspecialchars($phone) ?>" 
                         placeholder="0345277764" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">Kiểm tra</button>
              </form>

              <?php if ($error): ?>
                <div class="alert alert-warning">
                  <?= htmlspecialchars($error) ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($appointments)): ?>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Thời gian</th>
                        <th>Dịch vụ</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($appointments as $apt): ?>
                      <tr>
                        <td><?= date('d/m/Y H:i', strtotime($apt['appointment_date'])) ?></td>
                        <td><?= htmlspecialchars($apt['note'] ?? 'N/A') ?></td>
                        <td>
                          <?php
                          $status_class = match($apt['status']) {
                              'pending' => 'warning',
                              'waiting_for_approval' => 'info',
                              'confirmed' => 'success',
                              'rejected' => 'danger',
                              default => 'secondary'
                          };
                          $status_text = match($apt['status']) {
                              'pending' => 'Chờ duyệt',
                              'waiting_for_approval' => 'Chờ BS xác nhận',
                              'confirmed' => 'Đã xác nhận',
                              'rejected' => 'Đã từ chối',
                              default => $apt['status']
                          };
                          ?>
                          <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                        </td>
                        <td>
                          <?php if ($apt['status'] == 'pending' || $apt['status'] == 'waiting_for_approval'): ?>
                            <button class="btn btn-sm btn-warning" onclick="editAppointment(<?= $apt['id'] ?>)" title="Sửa">
                              ✏️
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="cancelAppointment(<?= $apt['id'] ?>)" title="Hủy">
                              ❌
                            </button>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Modal Sửa -->
  <div class="modal fade" id="editModal">
    <div class="modal-dialog">
      <form id="editForm">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Sửa lịch hẹn</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="editModalBody"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary">Lưu</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/bootstrap.min.js"></script>
  <script src="assets/js/bootstrap-datepicker.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.14.1/jquery.timepicker.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.14.1/jquery.timepicker.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    let editModalInstance = null;

    function editAppointment(id) {
      fetch(`../handle/appointment_process.php?action=get_edit&id=${id}`)
        .then(r => r.json())
        .then(data => {
          document.getElementById('edit_id').value = id;
          document.getElementById('editModalBody').innerHTML = data.html;
          
          // Khởi tạo modal Bootstrap
          const modalElement = document.getElementById('editModal');
          if (!editModalInstance) {
            editModalInstance = new bootstrap.Modal(modalElement);
          }
          editModalInstance.show();
          
          // Khởi tạo datepicker và timepicker
          $('.appointment_date').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true,
            startDate: new Date()
          });
          $('.appointment_time').timepicker({
            timeFormat: 'H:i',
            interval: 30,
            minTime: '08:00',
            maxTime: '19:00'
          });
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: 'Không thể tải thông tin lịch hẹn. Vui lòng thử lại.'
          });
        });
    }

    // Xử lý submit form edit bằng AJAX
    $('#editForm').on('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const submitBtn = $(this).find('button[type="submit"]');
      const originalText = submitBtn.html();
      
      submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Đang xử lý...');
      
      fetch('../handle/appointment_process.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Thành công!',
            text: data.message,
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            editModalInstance.hide();
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: data.message || 'Có lỗi xảy ra, vui lòng thử lại.'
          });
          submitBtn.prop('disabled', false).html(originalText);
        }
      })
      .catch(error => {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi!',
          text: 'Không thể kết nối đến server. Vui lòng thử lại.'
        });
        submitBtn.prop('disabled', false).html(originalText);
      });
    });

    function cancelAppointment(id) {
      Swal.fire({
        title: 'Xác nhận hủy lịch hẹn',
        text: 'Bạn có chắc chắn muốn hủy lịch hẹn này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Có, hủy lịch',
        cancelButtonText: 'Không'
      }).then((result) => {
        if (result.isConfirmed) {
          const formData = new FormData();
          formData.append('action', 'cancel');
          formData.append('id', id);
          
          fetch('../handle/appointment_process.php', {
            method: 'POST',
            body: formData
          })
          .then(r => r.json())
          .then(data => {
            if (data.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: data.message || 'Không thể hủy lịch hẹn. Vui lòng thử lại.'
              });
            }
          })
          .catch(error => {
            Swal.fire({
              icon: 'error',
              title: 'Lỗi!',
              text: 'Không thể kết nối đến server. Vui lòng thử lại.'
            });
          });
        }
      });
    }
  </script>
</body>
</html>

