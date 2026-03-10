<!-- Modal Đặt lịch -->
<div class="modal fade" id="modalRequest" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Đặt lịch khám</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="modalAppointmentForm" class="appointment-form">
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <select name="department" class="form-control" required>
                  <option value="">Chọn dịch vụ</option>
                  <option value="Tẩy trắng răng">Tẩy trắng răng</option>
                  <option value="Cạo vôi răng">Cạo vôi răng</option>
                  <option value="Niềng răng">Niềng răng</option>
                  <option value="Cấy ghép Implant">Cấy ghép Implant</option>
                  <option value="Nhổ răng">Nhổ răng</option>
                  <option value="Trám răng">Trám răng</option>
                  <option value="Điều trị tủy">Điều trị tủy</option>
                  <option value="Bọc răng sứ">Bọc răng sứ</option>
                  <option value="Tẩy trắng răng tại nhà">Tẩy trắng răng tại nhà</option>
                  <option value="Khám tổng quát">Khám tổng quát</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <input type="text" name="name" class="form-control" placeholder="Họ tên" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <input type="text" name="phone" class="form-control" placeholder="SĐT" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <input type="text" name="date" class="form-control appointment_date" placeholder="Ngày" required>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <input type="text" name="time" class="form-control appointment_time" placeholder="Giờ" required>
              </div>
            </div>
          </div>
          <div class="form-group">
            <textarea name="reason" class="form-control" rows="3" placeholder="Ghi chú"></textarea>
          </div>
          <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Đặt lịch</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- SweetAlert2 - Chỉ load một lần, kiểm tra xem đã load chưa -->
<?php if (!isset($sweetalert_loaded)): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php $sweetalert_loaded = true; endif; ?>

<!-- Script xử lý form đặt lịch - ĐƠN GIẢN HÓA -->
<script>
// Đảm bảo script chạy được dù có lỗi khác
try {
  console.log('[Appointment Form] ===== SCRIPT ĐÃ ĐƯỢC LOAD =====');
} catch(e) {
  // Nếu console.log cũng lỗi thì bỏ qua
}

// Đợi jQuery và DOM ready - Sử dụng window.onload để đảm bảo tất cả đã load
(function() {
  'use strict';
  
  function initForm() {
    try {
      console.log('[Appointment Form] Bắt đầu khởi tạo...');
      
      // Kiểm tra jQuery
      if (typeof jQuery === 'undefined' || typeof window.$ === 'undefined') {
        console.log('[Appointment Form] Đợi jQuery load...');
        if (!window._jQueryWaitCount) {
          window._jQueryWaitCount = 0;
        }
        if (window._jQueryWaitCount < 100) { // Retry tối đa 10 giây
          window._jQueryWaitCount++;
          setTimeout(initForm, 100);
          return;
        } else {
          console.error('[Appointment Form] jQuery không load được sau 10 giây!');
          alert('Lỗi: jQuery chưa được load. Vui lòng refresh trang.');
          return;
        }
      }
      
      console.log('[Appointment Form] jQuery đã sẵn sàng, version:', jQuery.fn.jquery);
      var $ = jQuery;
      
      // Đợi DOM ready
      $(document).ready(function() {
        console.log('[Appointment Form] DOM đã sẵn sàng, khởi tạo form...');
        
        // Kiểm tra form có tồn tại không
        var $form = $('#modalAppointmentForm');
        if ($form.length === 0) {
          console.error('[Appointment Form] Không tìm thấy form #modalAppointmentForm!');
          console.log('[Appointment Form] Đang tìm tất cả form trong page...');
          $('form').each(function(i) {
            console.log('[Appointment Form] Form ' + i + ':', this.id || '(no id)', this.className);
          });
          return;
        }
        
        console.log('[Appointment Form] Đã tìm thấy form #modalAppointmentForm');
      
      // Datepicker + Timepicker
      if ($.fn.datepicker) {
        $('.appointment_date').datepicker({
          format: 'dd/mm/yyyy',
          autoclose: true,
          todayHighlight: true,
          startDate: new Date()
        });
        console.log('[Appointment Form] Datepicker đã khởi tạo');
      } else {
        console.warn('[Appointment Form] Datepicker chưa được load!');
      }
      
      if ($.fn.timepicker) {
        $('.appointment_time').timepicker({
          timeFormat: 'H:i',
          interval: 30,
          minTime: '08:00',
          maxTime: '19:00',
          defaultTime: '08:00',
          dynamic: false,
          dropdown: true,
          scrollbar: true
        });
        console.log('[Appointment Form] Timepicker đã khởi tạo');
      } else {
        console.warn('[Appointment Form] Timepicker chưa được load!');
      }
      
      // Xử lý form submit
      let isSubmitting = false;
      
      $form.off('submit').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('[Appointment Form] ===== FORM SUBMIT ĐƯỢC GỌI =====');
        
        // Chặn submit nhiều lần
        if (isSubmitting) {
          console.log('[Appointment Form] Đang xử lý, bỏ qua submit');
          return false;
        }
        
        const $submitBtn = $form.find('button[type="submit"]');
        const formData = $form.serialize();
        
        console.log('[Appointment Form] Dữ liệu form:', formData);
        
        // Validate form
        if (!$form[0].checkValidity()) {
          console.log('[Appointment Form] Form không hợp lệ');
          $form[0].reportValidity();
          return false;
        }
        
        // Disable button và hiển thị loading
        isSubmitting = true;
        const originalText = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...');
        
        // Xác định URL đúng
        const currentPath = window.location.pathname;
        let ajaxUrl = '../handle/appointment_process.php';
        if (currentPath.includes('/public_site/')) {
          ajaxUrl = '../handle/appointment_process.php';
        } else {
          ajaxUrl = 'handle/appointment_process.php';
        }
        
        console.log('[Appointment Form] Gửi AJAX request đến:', ajaxUrl);
        
        $.ajax({
          url: ajaxUrl,
          method: 'POST',
          data: formData,
          dataType: 'json',
          timeout: 30000,
          success: function(res) {
            console.log('[Appointment Form] ===== AJAX SUCCESS =====', res);
            isSubmitting = false;
            $submitBtn.prop('disabled', false).html(originalText);
            
            if (res && res.status === 'success') {
              if (typeof Swal !== 'undefined') {
                Swal.fire({
                  icon: 'success',
                  title: 'Đặt lịch thành công!',
                  html: '<strong>' + (res.message || 'Đặt lịch thành công!') + '</strong><br><small>Vui lòng kiểm tra email để xác nhận.</small>',
                  timer: 5000,
                  timerProgressBar: true,
                  showConfirmButton: true,
                  confirmButtonText: 'Đóng',
                  allowOutsideClick: false,
                  allowEscapeKey: false
                }).then((result) => {
                  $form[0].reset();
                  $('#modalRequest').modal('hide');
                });
              } else {
                alert('Đặt lịch thành công! ' + (res.message || ''));
                $form[0].reset();
                $('#modalRequest').modal('hide');
              }
            } else {
              const errorMsg = (res && res.message) ? res.message : 'Có lỗi xảy ra!';
              console.error('[Appointment Form] Lỗi từ server:', errorMsg);
              if (typeof Swal !== 'undefined') {
                Swal.fire({
                  icon: 'error',
                  title: 'Lỗi!',
                  text: errorMsg,
                  showConfirmButton: true,
                  confirmButtonText: 'Đóng'
                });
              } else {
                alert('Lỗi: ' + errorMsg);
              }
            }
          },
          error: function(xhr, status, error) {
            console.error('[Appointment Form] ===== AJAX ERROR =====', {
              status: status,
              error: error,
              statusCode: xhr.status,
              responseText: xhr.responseText ? xhr.responseText.substring(0, 500) : 'No response',
              readyState: xhr.readyState
            });
            
            isSubmitting = false;
            $submitBtn.prop('disabled', false).html(originalText);
            
            let errorMsg = 'Không thể kết nối server. Vui lòng thử lại sau.';
            if (xhr.status === 0) {
              errorMsg = 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.';
            } else if (xhr.status === 404) {
              errorMsg = 'Không tìm thấy file xử lý. Vui lòng liên hệ quản trị viên.';
            } else if (xhr.status === 500) {
              errorMsg = 'Lỗi server. Vui lòng thử lại sau.';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMsg = xhr.responseJSON.message;
            } else if (xhr.responseText) {
              try {
                const json = JSON.parse(xhr.responseText);
                if (json.message) errorMsg = json.message;
              } catch(e) {
                if (xhr.responseText.length < 500) {
                  errorMsg = 'Lỗi: ' + xhr.responseText.substring(0, 200);
                }
              }
            }
            
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Lỗi kết nối!',
                text: errorMsg,
                showConfirmButton: true,
                confirmButtonText: 'Đóng'
              });
            } else {
              alert('Lỗi: ' + errorMsg);
            }
          }
        });
        
        return false;
      });
      
        console.log('[Appointment Form] ===== FORM ĐÃ ĐƯỢC KHỞI TẠO THÀNH CÔNG =====');
        window._formInitSuccess = true; // Đánh dấu đã khởi tạo thành công
      });
    } catch (error) {
      console.error('[Appointment Form] Lỗi khi khởi tạo form:', error);
      // Vẫn cố gắng khởi tạo lại sau 1 giây
      setTimeout(function() {
        if (window._retryCount === undefined) {
          window._retryCount = 0;
        }
        if (window._retryCount < 3) {
          window._retryCount++;
          console.log('[Appointment Form] Retry khởi tạo lần', window._retryCount);
          initForm();
        }
      }, 1000);
    }
  }
  
  // Khởi tạo - thử nhiều cách để đảm bảo chạy được
  function startInit() {
    try {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initForm);
      } else {
        initForm();
      }
    } catch(e) {
      console.error('[Appointment Form] Lỗi khi start init:', e);
      // Fallback: đợi window load
      if (window.addEventListener) {
        window.addEventListener('load', initForm);
      } else {
        window.attachEvent('onload', initForm);
      }
    }
  }
  
  // Khởi tạo ngay
  startInit();
  
  // Backup: khởi tạo lại sau 2 giây nếu chưa thành công
  setTimeout(function() {
    if (!window._formInitSuccess) {
      console.log('[Appointment Form] Backup: Khởi tạo lại sau 2 giây...');
      initForm();
    }
  }, 2000);
})();
</script>
