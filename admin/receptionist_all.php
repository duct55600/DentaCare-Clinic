<?php
session_start();
require_once '../functions/auth_functions.php';
require_role('receptionist');
require_once '../config/db.php';

$receptionist_id = $_SESSION['user_id'];

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Tìm kiếm theo khoảng thời gian
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$where_conditions = [];
$params = [];

// Bỏ qua các appointment đã bị soft delete khi hiển thị
// Kiểm tra xem cột deleted_at có tồn tại không
try {
    $test_col = $pdo->query("SELECT deleted_at FROM appointments LIMIT 1");
    $where_conditions[] = "a.deleted_at IS NULL";
} catch (PDOException $e) {
    // Nếu cột deleted_at chưa có, dùng status != 'deleted'
    $where_conditions[] = "a.status != 'deleted'";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(a.appointment_date) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $where_conditions[] = "DATE(a.appointment_date) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Lấy tổng số lịch hẹn
$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments a $where_clause");
$total_stmt->execute($params);
$total = $total_stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Lấy tất cả lịch hẹn với phân trang
$stmt = $pdo->prepare("SELECT a.*, 
    u1.full_name as doctor_name, 
    u2.full_name as receptionist_name
    FROM appointments a
    LEFT JOIN users u1 ON a.assigned_doctor_id = u1.id
    LEFT JOIN users u2 ON a.assigned_receptionist_id = u2.id
    $where_clause
    ORDER BY a.created_at DESC 
    LIMIT ? OFFSET ?");
// Bind parameters
$param_index = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_index++, $param);
}
$stmt->bindValue($param_index++, $per_page, PDO::PARAM_INT);
$stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll();

// Hiển thị kết quả tìm kiếm
$search_result_text = "";
if (!empty($date_from) || !empty($date_to)) {
    $search_result_text = " (Tìm kiếm: ";
    if (!empty($date_from) && !empty($date_to)) {
        $search_result_text .= "Từ " . date('d/m/Y', strtotime($date_from)) . " đến " . date('d/m/Y', strtotime($date_to));
    } elseif (!empty($date_from)) {
        $search_result_text .= "Từ " . date('d/m/Y', strtotime($date_from));
    } elseif (!empty($date_to)) {
        $search_result_text .= "Đến " . date('d/m/Y', strtotime($date_to));
    }
    $search_result_text .= " - Tổng: $total lịch hẹn)";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tất cả lịch hẹn | DentaCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
      
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Tất cả lịch hẹn<?= $search_result_text ?><?= empty($search_result_text) ? " ($total)" : "" ?></h3>
        <div>
          <button class="btn btn-danger btn-sm" onclick="bulkDelete()" id="bulkDeleteBtn" style="display:none;">
            <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-trash"></use></svg> Xóa đã chọn
          </button>
        </div>
      </div>
      
      <!-- Form tìm kiếm -->
      <div class="card mb-3">
        <div class="card-body">
          <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Từ ngày</label>
              <input type="date" name="date_from" class="form-control" value="<?= $_GET['date_from'] ?? '' ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Đến ngày</label>
              <input type="date" name="date_to" class="form-control" value="<?= $_GET['date_to'] ?? '' ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">&nbsp;</label>
              <div>
                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                <a href="receptionist_all.php" class="btn btn-secondary">Xóa bộ lọc</a>
              </div>
            </div>
          </form>
        </div>
      </div>
      
      <form id="bulkForm" method="POST" action="../handle/receptionist_process.php">
        <input type="hidden" name="action" value="bulk_delete">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th><input type="checkbox" id="selectAll" onchange="toggleAll()"></th>
                <th>Bệnh nhân</th>
                <th>Dịch vụ</th>
                <th>Thời gian</th>
                <th>Bác sĩ</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($appointments as $apt): ?>
              <tr>
                <td>
                  <?php if ($apt['status'] == 'pending' || ($apt['status'] == 'rejected' && $apt['assigned_receptionist_id'] == $receptionist_id)): ?>
                    <input type="checkbox" name="ids[]" value="<?= $apt['id'] ?>" class="row-checkbox" onchange="updateBulkDeleteBtn()">
                  <?php endif; ?>
                </td>
                <td>
                <strong><?= htmlspecialchars($apt['patient_name']) ?></strong><br>
                <small><?= htmlspecialchars($apt['patient_email']) ?></small><br>
                <small><?= htmlspecialchars($apt['patient_phone']) ?></small>
              </td>
              <td><?= htmlspecialchars($apt['note'] ?? 'N/A') ?></td>
              <td><?= date('d/m/Y H:i', strtotime($apt['appointment_date'])) ?></td>
              <td><?= htmlspecialchars($apt['doctor_name'] ?? 'Chưa gán') ?></td>
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
                <button class="btn btn-sm btn-info" onclick="viewAppointment(<?= $apt['id'] ?>, event); return false;" title="Xem chi tiết">
                  <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-info"></use></svg>
                </button>
                <?php if ($apt['status'] == 'pending' || ($apt['status'] == 'rejected' && $apt['assigned_receptionist_id'] == $receptionist_id)): ?>
                <button class="btn btn-sm btn-warning" onclick="editAppointment(<?= $apt['id'] ?>, event); return false;" title="Sửa">
                  <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-pencil"></use></svg>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteAppointment(<?= $apt['id'] ?>, event); return false;" title="Xóa">
                  <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-trash"></use></svg>
                </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      </form>

      <!-- Phân trang -->
      <?php if ($total_pages > 1): ?>
      <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
          <?php if ($page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">« Trước</a>
            </li>
          <?php endif; ?>
          
          <?php
          // Hiển thị tối đa 10 trang
          $start = max(1, $page - 4);
          $end = min($total_pages, $page + 5);
          
          if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a></li>
            <?php if ($start > 2): ?>
              <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
          <?php endif; ?>
          
          <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          
          <?php if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?>
              <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a></li>
          <?php endif; ?>
          
          <?php if ($page < $total_pages): ?>
            <li class="page-item">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Sau »</a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal Xem chi tiết -->
  <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewModalLabel">Chi tiết lịch hẹn</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="viewModalBody">
          <!-- Nội dung sẽ được load bằng AJAX -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Sửa -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="POST" action="../handle/receptionist_process.php" id="editForm">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit_id">
          <input type="hidden" name="redirect" value="../admin/receptionist_all.php">
          <div class="modal-header">
            <h5 class="modal-title" id="editModalLabel">Sửa lịch hẹn</h5>
            <button type="button" class="btn-close" aria-label="Close" onclick="if(editModal) editModal.hide();"></button>
          </div>
          <div class="modal-body" id="editModalBody">
            <!-- Nội dung sẽ được load bằng AJAX -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="if(editModal) editModal.hide();">Hủy</button>
            <button type="submit" class="btn btn-primary">Lưu</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.14.1/jquery.timepicker.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.14.1/jquery.timepicker.min.js"></script>
  <script>
    // Khởi tạo Bootstrap modals - đảm bảo chỉ khởi tạo một lần
    let viewModal, editModal;
    document.addEventListener('DOMContentLoaded', function() {
      const viewModalEl = document.getElementById('viewModal');
      const editModalEl = document.getElementById('editModal');
      if (viewModalEl && !viewModalEl._modal) {
        viewModal = new bootstrap.Modal(viewModalEl, {
          backdrop: 'static',
          keyboard: false
        });
        viewModalEl._modal = viewModal;
      }
      if (editModalEl && !editModalEl._modal) {
        editModal = new bootstrap.Modal(editModalEl, {
          backdrop: 'static',
          keyboard: false
        });
        editModalEl._modal = editModal;
      }
    });
    
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
          const modalBody = document.getElementById('viewModalBody');
          if (modalBody) {
            modalBody.innerHTML = data.html;
          }
          
          // Đảm bảo modal được khởi tạo và hiển thị
          const viewModalEl = document.getElementById('viewModal');
          if (viewModalEl) {
            if (!viewModal) {
              viewModal = new bootstrap.Modal(viewModalEl, {
                backdrop: 'static',
                keyboard: false
              });
            }
            viewModal.show();
          }
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
          const modalBody = document.getElementById('editModalBody');
          if (modalBody) {
            modalBody.innerHTML = data.html;
          }
          
          // Khởi tạo datepicker và timepicker sau khi load form
          setTimeout(() => {
            $('#editModal .appointment_date').datepicker({
              format: 'dd/mm/yyyy',
              autoclose: true,
              todayHighlight: true,
              startDate: new Date()
            });
            $('#editModal .appointment_time').timepicker({
              timeFormat: 'H:i',
              interval: 30,
              minTime: '08:00',
              maxTime: '19:00',
              dynamic: false,
              dropdown: true,
              scrollbar: true
            });
          }, 200);
          
          // Đảm bảo modal được khởi tạo và hiển thị
          const editModalEl = document.getElementById('editModal');
          if (editModalEl) {
            if (!editModal) {
              editModal = new bootstrap.Modal(editModalEl, {
                backdrop: 'static',
                keyboard: false
              });
            }
            editModal.show();
          }
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
        
        // Thêm redirect với các tham số tìm kiếm hiện tại
        const redirect = document.createElement('input');
        redirect.type = 'hidden';
        redirect.name = 'redirect';
        const urlParams = new URLSearchParams(window.location.search);
        let redirectUrl = 'receptionist_all.php';
        const queryString = urlParams.toString();
        if (queryString) {
          redirectUrl += '?' + queryString;
        }
        redirect.value = redirectUrl;
        form.appendChild(redirect);
        
        document.body.appendChild(form);
        form.submit();
      }
      return false;
    }

    function toggleAll() {
      const selectAll = document.getElementById('selectAll');
      const checkboxes = document.querySelectorAll('.row-checkbox');
      checkboxes.forEach(cb => cb.checked = selectAll.checked);
      updateBulkDeleteBtn();
    }

    function updateBulkDeleteBtn() {
      const checked = document.querySelectorAll('.row-checkbox:checked');
      document.getElementById('bulkDeleteBtn').style.display = checked.length > 0 ? 'inline-block' : 'none';
    }

    function bulkDelete() {
      const checked = document.querySelectorAll('.row-checkbox:checked');
      if (checked.length === 0) {
        alert('Vui lòng chọn ít nhất một lịch hẹn để xóa!');
        return;
      }
      if (confirm(`Bạn có chắc chắn muốn xóa ${checked.length} lịch hẹn đã chọn? Bệnh nhân sẽ nhận được email thông báo hủy lịch hẹn.`)) {
        // Thêm redirect với các tham số tìm kiếm hiện tại
        const urlParams = new URLSearchParams(window.location.search);
        let redirectUrl = 'receptionist_all.php';
        const queryString = urlParams.toString();
        if (queryString) {
          redirectUrl += '?' + queryString;
        }
        
        // Kiểm tra xem đã có redirect input chưa
        let redirectInput = document.querySelector('#bulkForm input[name="redirect"]');
        if (!redirectInput) {
          redirectInput = document.createElement('input');
          redirectInput.type = 'hidden';
          redirectInput.name = 'redirect';
          document.getElementById('bulkForm').appendChild(redirectInput);
        }
        redirectInput.value = redirectUrl;
        document.getElementById('bulkForm').submit();
      }
    }
  </script>
</body>
</html>

