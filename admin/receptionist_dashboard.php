<?php
session_start();
require_once '../functions/auth_functions.php';
require_role('receptionist');
require_once '../config/db.php';

// Lấy danh sách bác sĩ
$doctors = $pdo->query("SELECT id, full_name FROM users WHERE role = 'doctor'")->fetchAll();

$receptionist_id = $_SESSION['user_id'];

// Lấy lịch chờ duyệt: chỉ những lịch chưa được gán (pending) 
// HOẶC những lịch mà bác sĩ từ chối và do chính lễ tân này gán (rejected)
// Bỏ qua các appointment đã bị soft delete
try {
    $test_col = $pdo->query("SELECT deleted_at FROM appointments LIMIT 1");
    $deleted_filter = "AND deleted_at IS NULL";
} catch (PDOException $e) {
    $deleted_filter = "AND status != 'deleted'";
}
$stmt = $pdo->prepare("SELECT * FROM appointments 
    WHERE ((status = 'pending' AND assigned_receptionist_id IS NULL) 
    OR (status = 'rejected' AND assigned_receptionist_id = ?))
    $deleted_filter
    ORDER BY created_at DESC");
$stmt->execute([$receptionist_id]);
$pending = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lễ tân | DentaCare</title>
  <link href="css/style.css" rel="stylesheet">
  <link href="vendors/simplebar/css/simplebar.css" rel="stylesheet">
</head>
<body>
  <!-- SIDEBAR -->
  <?php include 'includes/receptionist_sidebar.php'; ?>

  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include 'includes/header.php'; ?>

    <div class="body flex-grow-1 px-4">
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?= htmlspecialchars($_SESSION['success']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?= htmlspecialchars($_SESSION['error']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>
      <h3>Lịch hẹn chờ duyệt (<?= count($pending) ?>)</h3>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Bệnh nhân</th>
              <th>Dịch vụ</th>
              <th>Thời gian</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending as $apt): ?>
            <tr>
              <td><?= htmlspecialchars($apt['patient_name']) ?><br><small><?= $apt['patient_phone'] ?></small></td>
              <td><?= htmlspecialchars($apt['note'] ?? 'N/A') ?></td>
              <td><?= date('d/m/Y H:i', strtotime($apt['appointment_date'])) ?></td>
              <td>
                <button class="btn btn-sm btn-primary" onclick="assignDoc(<?= $apt['id'] ?>, event); return false;" title="Chuyển bác sĩ">
                  <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-transfer"></use></svg>
                </button>
                <button class="btn btn-sm btn-info" onclick="viewAppointment(<?= $apt['id'] ?>, event); return false;" title="Xem chi tiết">
                  <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-info"></use></svg>
                </button>
                <button class="btn btn-sm btn-warning" onclick="editAppointment(<?= $apt['id'] ?>, event); return false;" title="Sửa">
                  <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-pencil"></use></svg>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteAppointment(<?= $apt['id'] ?>, event); return false;" title="Xóa">
                  <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-trash"></use></svg>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal Chuyển BS -->
  <div class="modal fade" id="assignModal">
    <div class="modal-dialog">
      <form method="POST" action="../handle/receptionist_process.php">
        <input type="hidden" name="action" value="assign">
        <input type="hidden" name="id" id="assign_id">
        <input type="hidden" name="redirect" value="receptionist_dashboard.php">
        <div class="modal-content">
          <div class="modal-header">
            <h5>Chuyển cho bác sĩ</h5>
          </div>
          <div class="modal-body">
            <select name="doctor_id" class="form-select" required>
              <option value="">-- Chọn bác sĩ --</option>
              <?php foreach ($doctors as $doc): ?>
                <option value="<?= $doc['id'] ?>"><?= $doc['full_name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Chuyển</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Xem chi tiết -->
  <div class="modal fade" id="viewModal">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Chi tiết lịch hẹn</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="viewModalBody"></div>
      </div>
    </div>
  </div>

  <!-- Modal Sửa -->
  <div class="modal fade" id="editModal">
    <div class="modal-dialog">
      <form method="POST" action="../handle/receptionist_process.php" id="editForm">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <input type="hidden" name="redirect" value="../admin/receptionist_dashboard.php">
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

  <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.14.1/jquery.timepicker.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.14.1/jquery.timepicker.min.js"></script>
  <script>
    function assignDoc(id, e) {
      // Ngăn chặn event bubbling
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      document.getElementById('assign_id').value = id;
      new coreui.Modal(document.getElementById('assignModal')).show();
      return false;
    }

    function viewAppointment(id, e) {
      // Ngăn chặn event bubbling
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      
      fetch(`../handle/receptionist_process.php?action=view&id=${id}`)
        .then(r => r.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
            return;
          }
          document.getElementById('viewModalBody').innerHTML = data.html;
          new coreui.Modal(document.getElementById('viewModal')).show();
        })
        .catch(err => {
          console.error('Error:', err);
          alert('Có lỗi xảy ra khi tải dữ liệu');
        });
      return false;
    }

    function editAppointment(id, e) {
      // Ngăn chặn event bubbling
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      
      fetch(`../handle/receptionist_process.php?action=get_edit&id=${id}`)
        .then(r => r.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
            return;
          }
          document.getElementById('edit_id').value = id;
          document.getElementById('editModalBody').innerHTML = data.html;
          new coreui.Modal(document.getElementById('editModal')).show();
          
          // Khởi tạo datepicker và timepicker sau khi load form
          setTimeout(() => {
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
              maxTime: '19:00',
              dynamic: false,
              dropdown: true,
              scrollbar: true
            });
          }, 200);
        })
        .catch(err => {
          console.error('Error:', err);
          alert('Có lỗi xảy ra khi tải dữ liệu');
        });
      return false;
    }

    function deleteAppointment(id, e) {
      // Ngăn chặn event bubbling
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      
      if (confirm('Bạn có chắc chắn muốn xóa lịch hẹn này? Bệnh nhân sẽ nhận được email thông báo hủy lịch hẹn.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../handle/receptionist_process.php';
        
        const input1 = document.createElement('input');
        input1.type = 'hidden';
        input1.name = 'action';
        input1.value = 'delete';
        form.appendChild(input1);
        
        const input2 = document.createElement('input');
        input2.type = 'hidden';
        input2.name = 'id';
        input2.value = id;
        form.appendChild(input2);
        
        // Thêm redirect về trang dashboard
        const redirect = document.createElement('input');
        redirect.type = 'hidden';
        redirect.name = 'redirect';
        redirect.value = 'receptionist_dashboard.php';
        form.appendChild(redirect);
        
        document.body.appendChild(form);
        form.submit();
      }
      return false;
    }
  </script>
</body>
</html>