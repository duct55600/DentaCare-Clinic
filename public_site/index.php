<?php
session_start();
require_once '../config/db.php';

$success = $error = '';
if (isset($_SESSION['appointment_success'])) {
    $success = $_SESSION['appointment_success'];
    unset($_SESSION['appointment_success']);
}
if (isset($_SESSION['appointment_error'])) {
    $error = $_SESSION['appointment_error'];
    unset($_SESSION['appointment_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>DentaCare - Free Bootstrap 4 Template by Colorlib</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700" rel="stylesheet">
    <!-- CSS: dùng relative path thay vì C:\... -->
    <!-- SweetAlert2 sẽ được load trong appointment_modal.php để tránh duplicate -->
    <link rel="stylesheet" href="assets/css/open-iconic-bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    
    <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="assets/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css">

    <link rel="stylesheet" href="assets/css/aos.css">

    <link rel="stylesheet" href="assets/css/ionicons.min.css">

    <link rel="stylesheet" href="assets/css/bootstrap-datepicker.css">
    <link rel="stylesheet" href="assets/css/flaticon.css">
    <link rel="stylesheet" href="assets/css/icomoon.css">
    <link rel="stylesheet" href="assets/css/style.css">
  </head>
  <body>
    
	  <!-- NAVIGATION -->
    <?php 
    $current_page = 'index';
    include 'includes/nav.php'; 
    ?>
    <!-- END nav -->

    <section class="home-slider owl-carousel">
    <div class="slider-item" style="background-image: url('assets/images/bg_1.jpg');">
      <div class="overlay"></div>
      <div class="container">
        <div class="row slider-text align-items-center">
          <div class="col-md-6 col-sm-12 ftco-animate">
            <h1 class="mb-4">Nha khoa hiện đại &amp; thân thiện</h1>
            <p class="mb-4">Chăm sóc nụ cười của bạn với đội ngũ bác sĩ chuyên nghiệp và trang thiết bị hiện đại.</p>
            <p><a href="#" class="btn btn-primary px-4 py-3" data-toggle="modal" data-target="#modalRequest">Đặt lịch ngay</a></p>
          </div>
        </div>
      </div>
    </div>
    <div class="slider-item" style="background-image: url('assets/images/bg_2.jpg');">
      <div class="overlay"></div>
      <div class="container">
        <div class="row slider-text align-items-center">
          <div class="col-md-6 col-sm-12 ftco-animate">
            <h1 class="mb-4">Nụ cười hoàn hảo – Bắt đầu từ đây</h1>
            <p><a href="#" class="btn btn-primary px-4 py-3" data-toggle="modal" data-target="#modalRequest">Đặt lịch khám</a></p>
          </div>
        </div>
      </div>
    </div>
  </section>

    <!-- FORM ĐẶT LỊCH -->
    <section class="ftco-intro">
      <div class="container">
        <div class="row no-gutters">
          <div class="col-md-3 color-1 p-4">
            <h3 class="mb-4">Khẩn cấp</h3>
            <p>Liên hệ ngay khi cần hỗ trợ</p>
            <span class="phone-number">+84 345 277 764</span>
          </div>
          <div class="col-md-3 color-2 p-4">
            <h3 class="mb-4">Giờ mở cửa</h3>
            <p class="openinghours d-flex"><span>Thứ 2 - Thứ 6</span><span>8:00 - 19:00</span></p>
            <p class="openinghours d-flex"><span>Thứ 7</span><span>10:00 - 17:00</span></p>
            <p class="openinghours d-flex"><span>Chủ nhật</span><span>10:00 - 16:00</span></p>
          </div>
          <div class="col-md-6 color-3 p-4">
            <h3 class="mb-2">Đặt lịch khám</h3>

            <!-- FORM ĐÃ ĐƯỢC THỐNG NHẤT -->
            <form id="appointmentForm" class="appointment-form">
              <div class="row">
                <div class="col-sm-4">
                  <div class="form-group">
                    <div class="select-wrap">
                      <div class="icon"><span class="ion-ios-arrow-down"></span></div>
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
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <div class="icon"><span class="icon-user"></span></div>
                    <input type="text" name="name" class="form-control" placeholder="Họ tên" required>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <div class="icon"><span class="icon-paper-plane"></span></div>
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-sm-4">
                  <div class="form-group">
                    <div class="icon"><span class="ion-ios-calendar"></span></div>
                    <input type="text" name="date" class="form-control appointment_date" placeholder="Ngày" required>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <div class="icon"><span class="ion-ios-clock"></span></div>
                    <input type="text" name="time" class="form-control appointment_time" placeholder="Giờ" required>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <div class="icon"><span class="icon-phone2"></span></div>
                    <input type="text" name="phone" class="form-control" placeholder="SĐT" required>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <textarea name="reason" class="form-control" rows="2" placeholder="Ghi chú (tùy chọn)"></textarea>
              </div>
              <div class="form-group">
                <button type="submit" class="btn btn-primary">Đặt lịch ngay</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  
    <section class="ftco-section ftco-services">
      <div class="container">
      	<div class="row justify-content-center mb-5 pb-5">
          <div class="col-md-7 text-center heading-section ftco-animate">
            <h2 class="mb-2">Dịch vụ của chúng tôi giữ nụ cười của bạn</h2>
            <p>DentaCare cung cấp đầy đủ các dịch vụ nha khoa từ cơ bản đến chuyên sâu, đảm bảo chăm sóc toàn diện cho sức khỏe răng miệng của bạn.</p>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 d-flex align-self-stretch ftco-animate">
            <div class="media block-6 services d-block text-center">
              <div class="icon d-flex justify-content-center align-items-center">
            		<span class="flaticon-tooth-1"></span>
              </div>
              <div class="media-body p-2 mt-3">
                <h3 class="heading">Tẩy trắng răng</h3>
                <p>Dịch vụ tẩy trắng răng chuyên nghiệp, an toàn, giúp bạn có nụ cười tự tin và rạng rỡ hơn.</p>
              </div>
            </div>      
          </div>
          <div class="col-md-3 d-flex align-self-stretch ftco-animate">
            <div class="media block-6 services d-block text-center">
              <div class="icon d-flex justify-content-center align-items-center">
            		<span class="flaticon-dental-care"></span>
              </div>
              <div class="media-body p-2 mt-3">
                <h3 class="heading">Cạo vôi răng</h3>
                <p>Làm sạch vôi răng định kỳ giúp bảo vệ răng khỏi các bệnh lý về nướu và duy trì sức khỏe răng miệng tốt.</p>
              </div>
            </div>    
          </div>
          <div class="col-md-3 d-flex align-self-stretch ftco-animate">
            <div class="media block-6 services d-block text-center">
              <div class="icon d-flex justify-content-center align-items-center">
            		<span class="flaticon-tooth-with-braces"></span>
              </div>
              <div class="media-body p-2 mt-3">
                <h3 class="heading">Niềng răng</h3>
                <p>Chỉnh nha niềng răng với công nghệ hiện đại, giúp bạn có hàm răng đều đẹp và nụ cười hoàn hảo.</p>
              </div>
            </div>      
          </div>
          <div class="col-md-3 d-flex align-self-stretch ftco-animate">
            <div class="media block-6 services d-block text-center">
              <div class="icon d-flex justify-content-center align-items-center">
            		<span class="flaticon-anesthesia"></span>
              </div>
              <div class="media-body p-2 mt-3">
                <h3 class="heading">Cấy ghép Implant</h3>
                <p>Phục hồi răng mất bằng công nghệ cấy ghép Implant hiện đại, mang lại hàm răng tự nhiên và bền vững.</p>
              </div>
            </div>      
          </div>
        </div>
      </div>
      <div class="container-wrap mt-5">
      	<div class="row d-flex no-gutters">
      		<div class="col-md-6 img" style="background-image: url('assets/images/about-2.jpg');">
      		</div>
      		<div class="col-md-6 d-flex">
      			<div class="about-wrap">
      				<div class="heading-section heading-section-white mb-5 ftco-animate">
		            <h2 class="mb-2">DentaCare với sự chăm sóc tận tâm</h2>
		            <p>Chúng tôi cam kết mang đến dịch vụ nha khoa chất lượng cao với sự quan tâm đặc biệt đến từng bệnh nhân.</p>
		          </div>
      				<div class="list-services d-flex ftco-animate">
      					<div class="icon d-flex justify-content-center align-items-center">
      						<span class="icon-check2"></span>
      					</div>
      					<div class="text">
	      					<h3>Bác sĩ giàu kinh nghiệm</h3>
	      					<p>Đội ngũ bác sĩ được đào tạo chuyên sâu, có nhiều năm kinh nghiệm trong lĩnh vực nha khoa.</p>
      					</div>
      				</div>
      				<div class="list-services d-flex ftco-animate">
      					<div class="icon d-flex justify-content-center align-items-center">
      						<span class="icon-check2"></span>
      					</div>
      					<div class="text">
	      					<h3>Trang thiết bị hiện đại</h3>
	      					<p>Hệ thống máy móc và thiết bị nha khoa được nhập khẩu từ các nước tiên tiến, đảm bảo chất lượng điều trị tốt nhất.</p>
      					</div>
      				</div>
      				<div class="list-services d-flex ftco-animate">
      					<div class="icon d-flex justify-content-center align-items-center">
      						<span class="icon-check2"></span>
      					</div>
      					<div class="text">
	      					<h3>Phòng khám tiện nghi</h3>
	      					<p>Không gian phòng khám rộng rãi, sạch sẽ, tạo cảm giác thoải mái và an tâm cho mọi bệnh nhân.</p>
      					</div>
      				</div>
      			</div>
      		</div>
      	</div>
      </div>
    </section>


    <section class="ftco-section">
      <div class="container">
      	<div class="row justify-content-center mb-5 pb-5">
          <div class="col-md-7 text-center heading-section ftco-animate">
            <h2 class="mb-3">Gặp gỡ đội ngũ bác sĩ của chúng tôi</h2>
            <p>Đội ngũ bác sĩ nha khoa giàu kinh nghiệm, tận tâm và chuyên nghiệp, luôn sẵn sàng chăm sóc sức khỏe răng miệng cho bạn.</p>
          </div>
        </div>
        <div class="row">
        	<div class="col-lg-3 col-md-6 d-flex mb-sm-4 ftco-animate">
        		<div class="staff">
      				<div class="img mb-4" style="background-image: url('assets/images/person_5.jpg');"></div>
      				<div class="info text-center">
      					<h3><a href="#">Tom Smith</a></h3>
      					<span class="position">Bác sĩ Nha khoa</span>
      					<div class="text">
	        				<p>Chuyên khoa: Chỉnh nha và Phục hình răng. Kinh nghiệm 15 năm.</p>
	        				<ul class="ftco-social">
			              <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-google-plus"></span></a></li>
			            </ul>
	        			</div>
      				</div>
        		</div>
        	</div>
        	<div class="col-lg-3 col-md-6 d-flex mb-sm-4 ftco-animate">
        		<div class="staff">
      				<div class="img mb-4" style="background-image: url('assets/images/person_6.jpg');"></div>
      				<div class="info text-center">
      					<h3><a href="#">Mark Wilson</a></h3>
      					<span class="position">Bác sĩ Nha khoa</span>
      					<div class="text">
	        				<p>Chuyên khoa: Điều trị tủy và Phẫu thuật răng. Kinh nghiệm 12 năm.</p>
	        				<ul class="ftco-social">
			              <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-google-plus"></span></a></li>
			            </ul>
	        			</div>
      				</div>
        		</div>
        	</div>
        	<div class="col-lg-3 col-md-6 d-flex mb-sm-4 ftco-animate">
        		<div class="staff">
      				<div class="img mb-4" style="background-image: url('assets/images/person_7.jpg');"></div>
      				<div class="info text-center">
      					<h3><a href="#">Patrick Jacobson</a></h3>
      					<span class="position">Bác sĩ Nha khoa</span>
      					<div class="text">
	        				<p>Chuyên khoa: Cấy ghép Implant và Phục hình thẩm mỹ. Kinh nghiệm 10 năm.</p>
	        				<ul class="ftco-social">
			              <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-google-plus"></span></a></li>
			            </ul>
	        			</div>
      				</div>
        		</div>
        	</div>
        	<div class="col-lg-3 col-md-6 d-flex mb-sm-4 ftco-animate">
        		<div class="staff">
      				<div class="img mb-4" style="background-image: url('assets/images/person_8.jpg');"></div>
      				<div class="info text-center">
      					<h3><a href="#">Ivan Dorchsner</a></h3>
      					<span class="position">Bác sĩ Nha khoa</span>
      					<div class="text">
	        				<p>Chuyên khoa: Nha khoa tổng quát và Nha khoa trẻ em. Kinh nghiệm 8 năm.</p>
	        				<ul class="ftco-social">
			              <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
			              <li class="ftco-animate"><a href="#"><span class="icon-google-plus"></span></a></li>
			            </ul>
	        			</div>
      				</div>
        		</div>
        	</div>
        </div>
        <div class="row  mt-5 justify-conten-center">
        	<div class="col-md-8 ftco-animate">
        		<p>Với phương châm "Nụ cười của bạn là niềm vui của chúng tôi", DentaCare luôn nỗ lực mang đến dịch vụ nha khoa chất lượng cao nhất, giúp bạn tự tin với nụ cười rạng rỡ.</p>
        	</div>
        </div>
      </div>
    </section>

    <section class="ftco-section ftco-counter img" id="section-counter" style="background-image: url('assets/images/bg_1.jpg');" data-stellar-background-ratio="0.5">
    	<div class="container">
    		<div class="row d-flex align-items-center">
    			<div class="col-md-3 aside-stretch py-5">
    				<div class=" heading-section heading-section-white ftco-animate pr-md-4">
	            <h2 class="mb-3">Thành tựu</h2>
	            <p>Những con số ấn tượng thể hiện sự tin tưởng và hài lòng của khách hàng dành cho DentaCare.</p>
	          </div>
    			</div>
    			<div class="col-md-9 py-5 pl-md-5">
		    		<div class="row">
		          <div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
		            <div class="block-18">
		              <div class="text">
		                <strong class="number" data-number="15">0</strong>
		                <span>Năm kinh nghiệm</span>
		              </div>
		            </div>
		          </div>
		          <div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
		            <div class="block-18">
		              <div class="text">
		                <strong class="number" data-number="50">0</strong>
		                <span>Bác sĩ chuyên nghiệp</span>
		              </div>
		            </div>
		          </div>
		          <div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
		            <div class="block-18">
		              <div class="text">
		                <strong class="number" data-number="5000">0</strong>
		                <span>Khách hàng hài lòng</span>
		              </div>
		            </div>
		          </div>
		          <div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
		            <div class="block-18">
		              <div class="text">
		                <strong class="number" data-number="2000">0</strong>
		                <span>Bệnh nhân mỗi năm</span>
		              </div>
		            </div>
		          </div>
		        </div>
		      </div>
	      </div>
    	</div>
    </section>

    <section class="ftco-section">
    	<div class="container">
    		<div class="row justify-content-center mb-5 pb-5">
          <div class="col-md-7 text-center heading-section ftco-animate">
            <h2 class="mb-3">Bảng giá dịch vụ</h2>
            <p>Bảng giá minh bạch, hợp lý cho tất cả các dịch vụ nha khoa tại DentaCare.</p>
          </div>
        </div>
    		<div class="row">
        	<div class="col-md-3 ftco-animate">
        		<div class="pricing-entry pb-5 text-center">
        			<div>
	        			<h3 class="mb-4">Cơ bản</h3>
	        			<p><span class="price">300.000đ</span> <span class="per">/ lần</span></p>
	        		</div>
        			<ul>
        				<li>Khám tổng quát</li>
								<li>Tư vấn chuyên nghiệp</li>
								<li>Cạo vôi răng</li>
								<li>Chụp X-quang</li>
								<li>Điều trị cơ bản</li>
        			</ul>
        			<p class="button text-center"><a href="#" class="btn btn-primary btn-outline-primary px-4 py-3" data-toggle="modal" data-target="#modalRequest">Đặt lịch ngay</a></p>
        		</div>
        	</div>
        	<div class="col-md-3 ftco-animate">
        		<div class="pricing-entry pb-5 text-center">
        			<div>
	        			<h3 class="mb-4">Tiêu chuẩn</h3>
	        			<p><span class="price">500.000đ</span> <span class="per">/ lần</span></p>
	        		</div>
        			<ul>
        				<li>Khám tổng quát</li>
								<li>Tư vấn chuyên sâu</li>
								<li>Trám răng</li>
								<li>Nhổ răng</li>
								<li>Tẩy trắng răng</li>
        			</ul>
        			<p class="button text-center"><a href="#" class="btn btn-primary btn-outline-primary px-4 py-3" data-toggle="modal" data-target="#modalRequest">Đặt lịch ngay</a></p>
        		</div>
        	</div>
        	<div class="col-md-3 ftco-animate">
        		<div class="pricing-entry active pb-5 text-center">
        			<div>
	        			<h3 class="mb-4">Cao cấp</h3>
	        			<p><span class="price">2.000.000đ</span> <span class="per">/ lần</span></p>
	        		</div>
        			<ul>
        				<li>Khám tổng quát</li>
								<li>Tư vấn miễn phí</li>
								<li>Điều trị tủy</li>
								<li>Bọc răng sứ</li>
								<li>Tẩy trắng răng</li>
        			</ul>
        			<p class="button text-center"><a href="#" class="btn btn-primary btn-outline-primary px-4 py-3" data-toggle="modal" data-target="#modalRequest">Đặt lịch ngay</a></p>
        		</div>
        	</div>
        	<div class="col-md-3 ftco-animate">
        		<div class="pricing-entry pb-5 text-center">
        			<div>
	        			<h3 class="mb-4">VIP</h3>
	        			<p><span class="price">30.000.000đ</span> <span class="per">/ ca</span></p>
	        		</div>
        			<ul>
        				<li>Khám tổng quát</li>
								<li>Tư vấn 1-1</li>
								<li>Cấy ghép Implant</li>
								<li>Niềng răng</li>
								<li>Phục hình thẩm mỹ</li>
        			</ul>
        			<p class="button text-center"><a href="#" class="btn btn-primary btn-outline-primary px-4 py-3" data-toggle="modal" data-target="#modalRequest">Đặt lịch ngay</a></p>
        		</div>
        	</div>
        </div>
    	</div>
    </section>

    <section class="ftco-section-parallax">
      <div class="parallax-img d-flex align-items-center">
        <div class="container">
          <div class="row d-flex justify-content-center">
            <div class="col-md-7 text-center heading-section heading-section-white ftco-animate">
              <h2>Đăng ký nhận tin tức</h2>
              <p>Nhận thông tin về các chương trình ưu đãi, mẹo chăm sóc răng miệng và cập nhật dịch vụ mới từ DentaCare.</p>
              <div class="row d-flex justify-content-center mt-5">
                <div class="col-md-8">
                  <form action="#" class="subscribe-form">
                    <div class="form-group d-flex">
                      <input type="text" class="form-control" placeholder="Nhập địa chỉ email">
                      <input type="submit" value="Đăng ký" class="submit px-3">
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
		
		<section class="ftco-section testimony-section bg-light">
      <div class="container">
        <div class="row justify-content-center mb-5 pb-3">
          <div class="col-md-7 text-center heading-section ftco-animate">
            <h2 class="mb-2">Cảm nhận khách hàng</h2>
            <span class="subheading">Những chia sẻ từ khách hàng của chúng tôi</span>
          </div>
        </div>
        <div class="row justify-content-center ftco-animate">
          <div class="col-md-8">
            <div class="carousel-testimony owl-carousel ftco-owl">
              <div class="item">
                <div class="testimony-wrap p-4 pb-5">
                  <div class="user-img mb-5" style="background-image: url('assets/images/person_1.jpg')">
                    <span class="quote d-flex align-items-center justify-content-center">
                      <i class="icon-quote-left"></i>
                    </span>
                  </div>
                  <div class="text text-center">
                    <p class="mb-5">Dịch vụ tại DentaCare thật sự tuyệt vời! Bác sĩ rất tận tâm, trang thiết bị hiện đại và quy trình chăm sóc rất chuyên nghiệp. Tôi rất hài lòng với kết quả điều trị.</p>
                    <p class="name">Nguyễn Văn An</p>
                    <span class="position">Khách hàng</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap p-4 pb-5">
                  <div class="user-img mb-5" style="background-image: url('assets/images/person_2.jpg')">
                    <span class="quote d-flex align-items-center justify-content-center">
                      <i class="icon-quote-left"></i>
                    </span>
                  </div>
                  <div class="text text-center">
                    <p class="mb-5">Sau khi điều trị tại DentaCare, tôi cảm thấy tự tin hơn rất nhiều với nụ cười của mình. Cảm ơn đội ngũ bác sĩ đã giúp tôi có được hàm răng đẹp như mong muốn.</p>
                    <p class="name">Trần Thị Bình</p>
                    <span class="position">Khách hàng</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap p-4 pb-5">
                  <div class="user-img mb-5" style="background-image: url('assets/images/person_3.jpg')">
                    <span class="quote d-flex align-items-center justify-content-center">
                      <i class="icon-quote-left"></i>
                    </span>
                  </div>
                  <div class="text text-center">
                    <p class="mb-5">Phòng khám rất sạch sẽ, nhân viên thân thiện và bác sĩ giải thích rất rõ ràng về quy trình điều trị. Tôi hoàn toàn yên tâm khi đến đây.</p>
                    <p class="name">Lê Minh Cường</p>
                    <span class="position">Khách hàng</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap p-4 pb-5">
                  <div class="user-img mb-5" style="background-image: url('assets/images/person_1.jpg')">
                    <span class="quote d-flex align-items-center justify-content-center">
                      <i class="icon-quote-left"></i>
                    </span>
                  </div>
                  <div class="text text-center">
                    <p class="mb-5">Giá cả hợp lý, chất lượng dịch vụ tốt. Tôi đã giới thiệu nhiều người thân đến DentaCare và họ đều rất hài lòng.</p>
                    <p class="name">Phạm Thị Dung</p>
                    <span class="position">Khách hàng</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap p-4 pb-5">
                  <div class="user-img mb-5" style="background-image: url('assets/images/person_1.jpg')">
                    <span class="quote d-flex align-items-center justify-content-center">
                      <i class="icon-quote-left"></i>
                    </span>
                  </div>
                  <div class="text text-center">
                    <p class="mb-5">Quy trình đặt lịch rất tiện lợi, không phải chờ đợi lâu. Bác sĩ chuyên nghiệp và tận tâm với từng bệnh nhân.</p>
                    <p class="name">Hoàng Văn Đức</p>
                    <span class="position">Khách hàng</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
		
		<section class="ftco-gallery">
    	<div class="container-wrap">
    		<div class="row no-gutters">
					<div class="col-md-3 ftco-animate">
						<a href="#" class="gallery img d-flex align-items-center" style="background-image: url('assets/images/gallery-1.jpg');">
							<div class="icon mb-4 d-flex align-items-center justify-content-center">
    						<span class="icon-search"></span>
    					</div>
						</a>
					</div>
					<div class="col-md-3 ftco-animate">
						<a href="#" class="gallery img d-flex align-items-center" style="background-image: url('assets/images/gallery-2.jpg');">
							<div class="icon mb-4 d-flex align-items-center justify-content-center">
    						<span class="icon-search"></span>
    					</div>
						</a>
					</div>
					<div class="col-md-3 ftco-animate">
						<a href="#" class="gallery img d-flex align-items-center" style="background-image: url('assets/images/gallery-3.jpg');">
							<div class="icon mb-4 d-flex align-items-center justify-content-center">
    						<span class="icon-search"></span>
    					</div>
						</a>
					</div>
					<div class="col-md-3 ftco-animate">
						<a href="#" class="gallery img d-flex align-items-center" style="background-image: url('assets/images/gallery-4.jpg');">
							<div class="icon mb-4 d-flex align-items-center justify-content-center">
    						<span class="icon-search"></span>
    					</div>
						</a>
					</div>
        </div>
    	</div>
    </section>

    <section class="ftco-section">
      <div class="container">
        <div class="row justify-content-center mb-5 pb-3">
          <div class="col-md-7 text-center heading-section ftco-animate">
            <h2 class="mb-2">Tin tức mới nhất</h2>
            <p>Cập nhật những thông tin hữu ích về chăm sóc răng miệng và các dịch vụ nha khoa tại DentaCare.</p>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 ftco-animate">
            <div class="blog-entry">
              <a href="blog-single.php" class="block-20" style="background-image: url('assets/images/image_1.jpg');">
              </a>
              <div class="text d-flex py-4">
                <div class="meta mb-3">
                  <div><a href="#">Sep. 20, 2018</a></div>
                  <div><a href="#">Admin</a></div>
                  <div><a href="#" class="meta-chat"><span class="icon-chat"></span> 3</a></div>
                </div>
                <div class="desc pl-3">
	                <h3 class="heading"><a href="#">Cách chăm sóc răng miệng đúng cách tại nhà</a></h3>
	              </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 ftco-animate">
            <div class="blog-entry" data-aos-delay="100">
              <a href="blog-single.php" class="block-20" style="background-image: url('assets/images/image_2.jpg');">
              </a>
              <div class="text d-flex py-4">
                <div class="meta mb-3">
                  <div><a href="#">Sep. 20, 2018</a></div>
                  <div><a href="#">Admin</a></div>
                  <div><a href="#" class="meta-chat"><span class="icon-chat"></span> 3</a></div>
                </div>
                <div class="desc pl-3">
	                <h3 class="heading"><a href="#">Cách chăm sóc răng miệng đúng cách tại nhà</a></h3>
	              </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 ftco-animate">
            <div class="blog-entry" data-aos-delay="200">
              <a href="blog-single.php" class="block-20" style="background-image: url('assets/images/image_3.jpg');">
              </a>
              <div class="text d-flex py-4">
                <div class="meta mb-3">
                  <div><a href="#">Sep. 20, 2018</a></div>
                  <div><a href="#">Admin</a></div>
                  <div><a href="#" class="meta-chat"><span class="icon-chat"></span> 3</a></div>
                </div>
                <div class="desc pl-3">
	                <h3 class="heading"><a href="#">Cách chăm sóc răng miệng đúng cách tại nhà</a></h3>
	              </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
		
		<section class="ftco-quote">
    	<div class="container">
    		<div class="row">
    			<div class="col-md-6 pr-md-5 aside-stretch py-5 choose">
    				<div class="heading-section heading-section-white mb-5 ftco-animate">
	            <h2 class="mb-2">Quy trình DentaCare &amp; Dịch vụ chất lượng cao</h2>
	          </div>
	          <div class="ftco-animate">
	          	<p>DentaCare áp dụng quy trình điều trị chuyên nghiệp, đảm bảo an toàn và hiệu quả cho mọi bệnh nhân. Chúng tôi luôn cập nhật công nghệ mới nhất và tuân thủ nghiêm ngặt các tiêu chuẩn vệ sinh y tế.</p>
	          	<ul class="un-styled my-5">
	          		<li><span class="icon-check"></span>Khám và tư vấn miễn phí</li>
	          		<li><span class="icon-check"></span>Điều trị theo phác đồ chuẩn</li>
	          		<li><span class="icon-check"></span>Theo dõi và chăm sóc sau điều trị</li>
	          	</ul>
	          </div>
    			</div>
    			<div class="col-md-6 py-5 pl-md-5">
    				<div class="heading-section mb-5 ftco-animate">
	            <h2 class="mb-2">Nhận báo giá miễn phí</h2>
	          </div>
	          <form action="#" class="ftco-animate">
	          	<div class="row">
	          		<div class="col-md-6">
		              <div class="form-group">
		                <input type="text" class="form-control" placeholder="Họ tên">
		              </div>
	              </div>
	              <div class="col-md-6">
		              <div class="form-group">
		                <input type="text" class="form-control" placeholder="Email">
		              </div>
	              </div>
	              <div class="col-md-6">
	              	<div class="form-group">
		                <input type="text" class="form-control" placeholder="Số điện thoại">
		              </div>
		            </div>
	              <div class="col-md-6">
	              	<div class="form-group">
		                <input type="text" class="form-control" placeholder="Dịch vụ quan tâm">
		              </div>
		            </div>
		            <div class="col-md-12">
		              <div class="form-group">
		                <textarea name="" id="" cols="30" rows="7" class="form-control" placeholder="Tin nhắn"></textarea>
		              </div>
		            </div>
		            <div class="col-md-12">
		              <div class="form-group">
		                <input type="submit" value="Gửi yêu cầu" class="btn btn-primary py-3 px-5">
		              </div>
	              </div>
              </div>
            </form>
    			</div>
    		</div>
    	</div>
    </section>
		
		<div id="map"></div>

    <footer class="ftco-footer ftco-bg-dark ftco-section">
      <div class="container">
        <div class="row mb-5">
          <div class="col-md-3">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">DentaCare.</h2>
              <p>Phòng khám nha khoa DentaCare - Nơi chăm sóc nụ cười của bạn với đội ngũ bác sĩ chuyên nghiệp và trang thiết bị hiện đại nhất.</p>
            </div>
            <ul class="ftco-footer-social list-unstyled float-md-left float-lft ">
              <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
              <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
              <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
            </ul>
          </div>
          <div class="col-md-2">
            <div class="ftco-footer-widget mb-4 ml-md-5">
              <h2 class="ftco-heading-2">Liên kết nhanh</h2>
              <ul class="list-unstyled">
                <li><a href="about.php" class="py-2 d-block">Giới thiệu</a></li>
                <li><a href="services.php" class="py-2 d-block">Dịch vụ</a></li>
                <li><a href="doctors_list.php" class="py-2 d-block">Bác sĩ</a></li>
                <li><a href="blog.php" class="py-2 d-block">Tin tức</a></li>
                <li><a href="contact.php" class="py-2 d-block">Liên hệ</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md-4 pr-md-4">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">Tin tức gần đây</h2>
              <div class="block-21 mb-4 d-flex">
                <a class="blog-img mr-4" style="background-image: url('assets/images/image_1.jpg');"></a>
                <div class="text">
                  <h3 class="heading"><a href="#">Even the all-powerful Pointing has no control about</a></h3>
                  <div class="meta">
                  <div><a href="#"><span class="icon-calendar"></span> 15/11/2025</a></div>
                  <div><a href="#"><span class="icon-person"></span> DentaCare</a></div>
                  <div><a href="#"><span class="icon-chat"></span> 12</a></div>
                  </div>
                </div>
              </div>
              <div class="block-21 mb-4 d-flex">
                <a class="blog-img mr-4" style="background-image: url('assets/images/image_2.jpg');"></a>
                <div class="text">
                  <h3 class="heading"><a href="#">Even the all-powerful Pointing has no control about</a></h3>
                  <div class="meta">
                  <div><a href="#"><span class="icon-calendar"></span> 15/11/2025</a></div>
                  <div><a href="#"><span class="icon-person"></span> DentaCare</a></div>
                  <div><a href="#"><span class="icon-chat"></span> 12</a></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="ftco-footer-widget mb-4">
            	<h2 class="ftco-heading-2">Văn phòng</h2>
            	<div class="block-23 mb-3">
	              <ul>
	                <li><span class="icon icon-map-marker"></span><span class="text">Hà Nội, Việt Nam</span></li>
	                <li><a href="tel:+84345277764"><span class="icon icon-phone"></span><span class="text">+84 345 277 764</span></a></li>
	                <li><a href="mailto:info@dentacare.vn"><span class="icon icon-envelope"></span><span class="text">phongsir205@gmail.com</span></a></li>
	              </ul>
	            </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
    
  <!-- loader -->
  <div id="ftco-loader" class="show fullscreen">
    <svg class="circular" width="48px" height="48px">
      <circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee"/>
      <circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#F96D00"/>
    </svg>
  </div>

  <!-- Modal đặt lịch - Sử dụng file chung -->


  <!-- JS - Load jQuery và Bootstrap trước, các script khác có thể defer -->
  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/jquery-migrate-3.0.1.min.js"></script>
  <script src="assets/js/popper.min.js"></script>
  <script src="assets/js/bootstrap.min.js"></script>
  <script src="assets/js/jquery.easing.1.3.js" defer></script>
  <script src="assets/js/jquery.waypoints.min.js" defer></script>
  <script src="assets/js/jquery.stellar.min.js" defer></script>
  <script src="assets/js/owl.carousel.min.js" defer></script>
  <script src="assets/js/jquery.magnific-popup.min.js" defer></script>
  <script src="assets/js/aos.js" defer></script>
  <script src="assets/js/jquery.animateNumber.min.js" defer></script>
  <script src="assets/js/bootstrap-datepicker.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.14.1/jquery.timepicker.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.14.1/jquery.timepicker.min.js" defer></script>
  <script src="assets/js/scrollax.min.js" defer></script>

  <!-- Google Map: Tạm thời bỏ để tránh ảnh hưởng đến form đặt lịch -->
  <!-- <script src="assets/js/google-map.js"></script> -->
  <!-- <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBVWaKrjvy3MaE7SQ74_uJiULgl1JY0H2s&loading=async&callback=initGoogleMap" async defer></script> -->

  <script src="assets/js/main.js" defer></script>
  <!-- AJAX XỬ LÝ FORM -->
 <script>
$(document).ready(function() {
  // Datepicker + Timepicker
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
    defaultTime: '08:00',
    dynamic: false,
    dropdown: true,
    scrollbar: true
});
  // Xử lý form inline (nếu có) - form modal được xử lý trong appointment_modal.php
  // Chỉ xử lý form #appointmentForm nếu tồn tại (không phải modal)
  let isSubmitting = false;
  $('#appointmentForm').on('submit', function(e) {
    e.preventDefault();
    
    // Chặn submit nhiều lần
    if (isSubmitting) {
      return false;
    }
    
    const $form = $(this);
    const $submitBtn = $form.find('button[type="submit"], input[type="submit"]');
    const formData = $form.serialize();
    
    // Disable button và hiển thị loading
    isSubmitting = true;
    const originalText = $submitBtn.html() || $submitBtn.val();
    $submitBtn.prop('disabled', true);
    if ($submitBtn.is('button')) {
      $submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...');
    } else {
      $submitBtn.val('Đang xử lý...');
    }

    $.ajax({
      url: '../handle/appointment_process.php',
      method: 'POST',
      data: formData,
      dataType: 'json',
      success: function(res) {
        isSubmitting = false;
        $submitBtn.prop('disabled', false);
        if ($submitBtn.is('button')) {
          $submitBtn.html(originalText);
        } else {
          $submitBtn.val(originalText);
        }
        
        if (res.status === 'success') {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Đặt lịch thành công!',
              html: '<strong>' + res.message + '</strong><br><small>Vui lòng kiểm tra email để xác nhận.</small>',
              timer: 5000,
              timerProgressBar: true,
              showConfirmButton: true,
              confirmButtonText: 'Đóng',
              allowOutsideClick: false,
              allowEscapeKey: false
            }).then(() => {
              $form[0].reset();
            });
          } else {
            alert('Đặt lịch thành công! ' + res.message);
            $form[0].reset();
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Lỗi!',
              text: res.message,
              showConfirmButton: true,
              confirmButtonText: 'Đóng'
            });
          } else {
            alert('Lỗi: ' + res.message);
          }
        }
      },
      error: function(xhr, status, error) {
        isSubmitting = false;
        $submitBtn.prop('disabled', false);
        if ($submitBtn.is('button')) {
          $submitBtn.html(originalText);
        } else {
          $submitBtn.val(originalText);
        }
        
        console.error('AJAX Error:', status, error);
        let errorMsg = 'Không thể kết nối server. Vui lòng thử lại sau.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        } else if (xhr.responseText) {
          try {
            const json = JSON.parse(xhr.responseText);
            if (json.message) errorMsg = json.message;
          } catch(e) {}
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
  });

  // Hiển thị thông báo từ session (nếu redirect)
  <?php if ($success): ?>
    Swal.fire('Thành công', '<?= addslashes($success) ?>', 'success');
  <?php elseif ($error): ?>
    Swal.fire('Lỗi', '<?= addslashes($error) ?>', 'error');
  <?php endif; ?>
});
</script>

  <!-- Modal đặt lịch -->
  <?php include 'includes/appointment_modal.php'; ?>
  
  <script>
    // Tự động mở modal đặt lịch và điền tên bác sĩ khi có parameter doctor trong URL
    $(document).ready(function() {
      const urlParams = new URLSearchParams(window.location.search);
      const doctorName = urlParams.get('doctor');
      
      if (doctorName) {
        // Mở modal đặt lịch
        $('#modalRequest').modal('show');
        
        // Điền tên bác sĩ vào trường ghi chú (reason)
        setTimeout(function() {
          const reasonTextarea = $('#modalAppointmentForm textarea[name="reason"]');
          if (reasonTextarea.length) {
            const currentValue = reasonTextarea.val().trim();
            const doctorNote = 'Đặt lịch với bác sĩ ' + decodeURIComponent(doctorName);
            if (currentValue) {
              reasonTextarea.val(doctorNote + ' - ' + currentValue);
            } else {
              reasonTextarea.val(doctorNote);
            }
          }
        }, 500); // Đợi modal hiển thị hoàn toàn
        
        // Xóa parameter khỏi URL để tránh mở lại khi refresh
        window.history.replaceState({}, document.title, window.location.pathname);
      }
    });
  </script>
    
</body>
</html>