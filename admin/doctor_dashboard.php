<?php
session_start();
require_once '../config/db.php';
require_once '../functions/auth_functions.php';
require_role('doctor');

$doctor_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'overview';

// Thống kê tổng quan
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

$week_appts = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
    WHERE assigned_doctor_id = ? 
    AND DATE(appointment_date) BETWEEN ? AND ?");
$week_appts->execute([$doctor_id, $week_start, $week_end]);
$week_total = $week_appts->fetch()['total'];

// Đếm lịch đã nhận (confirmed) - tính từ lịch sử để giữ lại thống kê ngay cả khi đã xóa hoặc chuyển bác sĩ
// Ưu tiên dùng appointment_doctor_history nếu có, nếu không thì dùng appointments
try {
    $accepted = $pdo->prepare("SELECT COUNT(DISTINCT appointment_id) as total FROM appointment_doctor_history 
        WHERE doctor_id = ? AND action = 'accepted'");
    $accepted->execute([$doctor_id]);
    $accepted_count = $accepted->fetch()['total'];
    
    $rejected = $pdo->prepare("SELECT COUNT(DISTINCT appointment_id) as total FROM appointment_doctor_history 
        WHERE doctor_id = ? AND action = 'rejected'");
    $rejected->execute([$doctor_id]);
    $rejected_count = $rejected->fetch()['total'];
} catch (PDOException $e) {
    // Bảng history chưa có, dùng appointments table
    $accepted = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
        WHERE assigned_doctor_id = ? AND status = 'confirmed'");
    $accepted->execute([$doctor_id]);
    $accepted_count = $accepted->fetch()['total'];
    
    $rejected = $pdo->prepare("SELECT COUNT(*) as total FROM appointments 
        WHERE assigned_doctor_id = ? AND status = 'rejected'");
    $rejected->execute([$doctor_id]);
    $rejected_count = $rejected->fetch()['total'];
}

$total_processed = $accepted_count + $rejected_count;
$accept_rate = $total_processed > 0 ? round(($accepted_count / $total_processed) * 100, 1) : 0;
$reject_rate = $total_processed > 0 ? round(($rejected_count / $total_processed) * 100, 1) : 0;

// Lấy lịch chờ bác sĩ (status = 'waiting_for_approval')
// Bỏ qua các appointment đã bị soft delete
try {
    $test_col = $pdo->query("SELECT deleted_at FROM appointments LIMIT 1");
    $deleted_filter = "AND deleted_at IS NULL";
} catch (PDOException $e) {
    $deleted_filter = "AND status != 'deleted'";
}
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE status = 'waiting_for_approval' AND assigned_doctor_id = ? $deleted_filter ORDER BY appointment_date");
$stmt->execute([$doctor_id]);
$pending_appts = $stmt->fetchAll();

// Phân trang cho tab appointments
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Tìm kiếm theo khoảng thời gian
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$where_conditions = ["assigned_doctor_id = ?", "status IN ('confirmed', 'rejected')"];
$params = [$doctor_id];

// Bỏ qua các appointment đã bị soft delete khi hiển thị danh sách
// LƯU Ý: Thống kê ở trên vẫn tính cả đã xóa (không filter deleted_at)
try {
    $test_col = $pdo->query("SELECT deleted_at FROM appointments LIMIT 1");
    $where_conditions[] = "deleted_at IS NULL";
} catch (PDOException $e) {
    $where_conditions[] = "status != 'deleted'";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(appointment_date) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $where_conditions[] = "DATE(appointment_date) <= ?";
    $params[] = $date_to;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Lấy tổng số lịch đã xử lý (confirmed + rejected)
$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments $where_clause");
$total_stmt->execute($params);
$total_appointments = $total_stmt->fetch()['total'];
$total_pages = ceil($total_appointments / $per_page);

// Lấy lịch đã xử lý (confirmed + rejected) - lịch sử với phân trang
$stmt = $pdo->prepare("SELECT *, 
    CASE 
        WHEN doctor_note IS NOT NULL AND doctor_note != '' THEN doctor_note
        WHEN note LIKE '%[Ghi chú BS]:%' THEN SUBSTRING(note, LOCATE('[Ghi chú BS]:', note) + 13)
        ELSE NULL
    END as extracted_doctor_note
    FROM appointments 
    $where_clause
    ORDER BY appointment_date DESC 
    LIMIT ? OFFSET ?");
// Bind parameters
$param_index = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_index++, $param);
}
$stmt->bindValue($param_index++, $per_page, PDO::PARAM_INT);
$stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
$stmt->execute();
$my_appointments = $stmt->fetchAll();

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
    $search_result_text .= " - Tổng: $total_appointments lịch hẹn)";
}

// Check for duplicate appointments on same day for each pending appointment
$duplicate_warnings = [];
foreach ($pending_appts as $apt) {
    $appointment_date = date('Y-m-d', strtotime($apt['appointment_date']));
    $stmt = $pdo->prepare("SELECT id FROM appointments 
        WHERE assigned_doctor_id = ? 
        AND DATE(appointment_date) = ? 
        AND status = 'confirmed' 
        AND id != ?");
    $stmt->execute([$doctor_id, $appointment_date, $apt['id']]);
    if ($stmt->fetch()) {
        $duplicate_warnings[$apt['id']] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bác sĩ | DentaCare</title>
  <link href="css/style.css" rel="stylesheet">
</head>
<body>
  <?php include 'includes/doctor_sidebar.php'; ?>

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
      
      <?php if (isset($_SESSION['duplicate_warning_id'])): ?>
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center justify-content-between">
          <div>
            <strong>⚠ Cảnh báo trùng lịch!</strong> Bạn đã có lịch hẹn khác trong ngày 
            <?= date('d/m/Y', strtotime($_SESSION['duplicate_warning_date'])) ?>.
            Bạn có chắc chắn muốn nhận lịch hẹn của bệnh nhân <strong><?= htmlspecialchars($_SESSION['duplicate_warning_name']) ?></strong>?
          </div>
          <div class="ms-3">
            <form method="POST" action="../handle/doctor_process.php" class="d-inline">
              <input type="hidden" name="action" value="accept">
              <input type="hidden" name="appt_id" value="<?= $_SESSION['duplicate_warning_id'] ?>">
              <input type="hidden" name="confirm_duplicate" value="1">
              <button type="submit" class="btn btn-warning btn-sm">Chấp nhận</button>
            </form>
            <a href="doctor_dashboard.php" class="btn btn-secondary btn-sm">Hủy</a>
          </div>
        </div>
        <?php 
        unset($_SESSION['duplicate_warning_id']);
        unset($_SESSION['duplicate_warning_date']);
        unset($_SESSION['duplicate_warning_name']);
        ?>
      <?php endif; ?>
      
      <h2 class="mb-4">Xin chào, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong></h2>

      <!-- Tab Navigation -->
      <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
          <a class="nav-link <?= $tab === 'overview' ? 'active' : '' ?>" href="?tab=overview">Tổng quan</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $tab === 'appointments' ? 'active' : '' ?>" href="?tab=appointments">Lịch hẹn</a>
        </li>
      </ul>

      <?php if ($tab === 'overview'): ?>
      <!-- Tab Tổng quan -->
      <div class="row">
        <div class="col-md-4 mb-3">
          <div class="card text-white bg-primary">
            <div class="card-body">
              <h5 class="card-title">Lịch hẹn trong tuần</h5>
              <h2 class="mb-0"><?= $week_total ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card text-white bg-success">
            <div class="card-body">
              <h5 class="card-title">Tỷ lệ nhận</h5>
              <h2 class="mb-0"><?= $accept_rate ?>%</h2>
              <small>(<?= $accepted_count ?> lịch)</small>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card text-white bg-danger">
            <div class="card-body">
              <h5 class="card-title">Tỷ lệ từ chối</h5>
              <h2 class="mb-0"><?= $reject_rate ?>%</h2>
              <small>(<?= $rejected_count ?> lịch)</small>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header bg-primary text-white">Lịch mới cần nhận (<?= count($pending_appts) ?>)</div>
            <div class="card-body">
              <?php if (empty($pending_appts)): ?>
                <p class="text-muted">Không có lịch hẹn mới.</p>
              <?php else: ?>
              <?php foreach ($pending_appts as $a): ?>
              <div class="border-bottom pb-3 mb-3">
                  <?php if (isset($duplicate_warnings[$a['id']])): ?>
                    <div class="alert alert-warning alert-sm mb-2">
                      <strong>⚠ Cảnh báo:</strong> Bạn đã có lịch hẹn khác trong ngày này!
                    </div>
                  <?php endif; ?>
                  <strong><?= htmlspecialchars($a['patient_name']) ?></strong> - <?= htmlspecialchars($a['patient_phone']) ?><br>
                  <small><?= htmlspecialchars($a['note'] ?? 'N/A') ?></small><br>
                Thời gian: <strong><?= date('d/m/Y H:i', strtotime($a['appointment_date'])) ?></strong>
                <div class="mt-2">
                    <button class="btn btn-<?= isset($duplicate_warnings[$a['id']]) ? 'warning' : 'success' ?> btn-sm" onclick="acceptAppointment(<?= $a['id'] ?>, <?= isset($duplicate_warnings[$a['id']]) ? 'true' : 'false' ?>)">
                      Nhận lịch<?= isset($duplicate_warnings[$a['id']]) ? ' (Trùng ngày)' : '' ?>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="rejectAppointment(<?= $a['id'] ?>)">Từ chối</button>
                </div>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
            </div>
          </div>
        </div>

      <?php elseif ($tab === 'appointments'): ?>
      <!-- Tab Lịch hẹn -->
          <div class="card">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
          <span>Lịch hẹn đã xử lý<?= $search_result_text ?><?= empty($search_result_text) ? " ($total_appointments)" : "" ?></span>
          <button class="btn btn-danger btn-sm" onclick="bulkDelete()" id="bulkDeleteBtn" style="display:none;">
            <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-trash"></use></svg> Xóa đã chọn
          </button>
        </div>
        <div class="card-body">
          <!-- Form tìm kiếm -->
          <div class="card mb-3">
            <div class="card-body">
              <form method="GET" action="" class="row g-3">
                <input type="hidden" name="tab" value="appointments">
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
                    <a href="?tab=appointments" class="btn btn-secondary">Xóa bộ lọc</a>
                  </div>
              </div>
              </form>
            </div>
          </div>
          <form id="bulkForm" method="POST" action="../handle/doctor_process.php">
            <input type="hidden" name="action" value="bulk_delete">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th><input type="checkbox" id="selectAll" onchange="toggleAll()"></th>
                    <th>Bệnh nhân</th>
                    <th>Thời gian</th>
                    <th>Dịch vụ</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú đề xuất</th>
                    <th>Hành động</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($my_appointments as $a): ?>
                  <tr>
                    <td>
                      <input type="checkbox" name="ids[]" value="<?= $a['id'] ?>" class="row-checkbox" onchange="updateBulkDeleteBtn()">
                    </td>
                    <td>
                      <strong><?= htmlspecialchars($a['patient_name']) ?></strong><br>
                      <small><?= htmlspecialchars($a['patient_phone']) ?></small>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($a['appointment_date'])) ?></td>
                    <td><?= htmlspecialchars($a['note'] ?? 'N/A') ?></td>
                    <td>
                      <?php
                      $status_class = $a['status'] === 'confirmed' ? 'success' : 'danger';
                      $status_text = $a['status'] === 'confirmed' ? 'Đã nhận' : 'Đã từ chối';
                      ?>
                      <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                    </td>
                    <td><?= htmlspecialchars($a['extracted_doctor_note'] ?? $a['doctor_note'] ?? 'Chưa có') ?></td>
                    <td>
                      <button type="button" class="btn btn-sm btn-info" onclick="addNote(<?= $a['id'] ?>)" title="Thêm ghi chú">
                        <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-note-add"></use></svg>
                      </button>
                      <button type="button" class="btn btn-sm btn-primary" onclick="exportPDF(<?= $a['id'] ?>)" title="In phiếu khám">
                        <svg class="icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-print"></use></svg>
                      </button>
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
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal Thêm ghi chú -->
  <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="POST" action="../handle/doctor_process.php" id="noteForm">
          <input type="hidden" name="action" value="add_note">
          <input type="hidden" name="appt_id" id="note_appt_id">
          <div class="modal-header">
            <h5 class="modal-title" id="noteModalLabel">Thêm ghi chú đề xuất</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" onclick="if(noteModal) noteModal.hide();">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Ghi chú đề xuất</label>
              <textarea name="doctor_note" class="form-control" rows="4" placeholder="Nhập ghi chú đề xuất cho bệnh nhân..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="if(noteModal) noteModal.hide();">Hủy</button>
            <button type="submit" class="btn btn-primary">Lưu</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script>
    // Khởi tạo Bootstrap modal
    let noteModal;
    document.addEventListener('DOMContentLoaded', function() {
      noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
    });
    
    function acceptAppointment(id, hasDuplicate) {
      if (hasDuplicate) {
        if (!confirm('⚠ Cảnh báo: Bạn đã có lịch hẹn khác trong ngày này. Bạn có chắc chắn muốn nhận lịch này?')) {
          return;
        }
      }
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '../handle/doctor_process.php';
      const input1 = document.createElement('input');
      input1.type = 'hidden';
      input1.name = 'action';
      input1.value = 'accept';
      const input2 = document.createElement('input');
      input2.type = 'hidden';
      input2.name = 'appt_id';
      input2.value = id;
      form.appendChild(input1);
      form.appendChild(input2);
      document.body.appendChild(form);
      form.submit();
    }

    function rejectAppointment(id) {
      if (confirm('Bạn có chắc chắn muốn từ chối lịch hẹn này? Lịch hẹn sẽ được chuyển lại cho lễ tân.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../handle/doctor_process.php';
        const input1 = document.createElement('input');
        input1.type = 'hidden';
        input1.name = 'action';
        input1.value = 'reject';
        const input2 = document.createElement('input');
        input2.type = 'hidden';
        input2.name = 'appt_id';
        input2.value = id;
        form.appendChild(input1);
        form.appendChild(input2);
        document.body.appendChild(form);
        form.submit();
      }
    }

    function addNote(id) {
      document.getElementById('note_appt_id').value = id;
      const textarea = document.querySelector('#noteForm textarea[name="doctor_note"]');
      if (textarea) textarea.value = '';
      if (noteModal) {
        noteModal.show();
      } else {
        setTimeout(() => {
          noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
          noteModal.show();
        }, 100);
      }
    }

    function exportPDF(id) {
      window.open(`../handle/doctor_process.php?action=export_pdf&id=${id}`, '_blank');
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
      if (confirm(`Bạn có chắc chắn muốn xóa ${checked.length} lịch hẹn đã chọn?`)) {
        document.getElementById('bulkForm').submit();
      }
    }
  </script>
</body>
</html>
