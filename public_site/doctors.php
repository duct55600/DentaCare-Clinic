<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/open-iconic-bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    
    <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="assets/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css">

    <link rel="stylesheet" href="assets/css/aos.css">
    <link rel="stylesheet" href="assets/css/ionicons.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-datepicker.css">
    <link rel="stylesheet" href="assets/css/jquery.timepicker.css">
    <link rel="stylesheet" href="assets/css/flaticon.css">
    <link rel="stylesheet" href="assets/css/icomoon.css">
    <link rel="stylesheet" href="assets/css/style.css">
  </head>
  <body>
    
    <!-- NAVIGATION -->
    <?php 
    $current_page = 'doctors';
    include 'includes/nav.php'; 
    ?>
    <!-- END nav -->

    <section class="home-slider owl-carousel">
      <div class="slider-item bread-item" style="background-image: url('assets/images/bg_1.jpg');" data-stellar-background-ratio="0.5">
        <div class="overlay"></div>
        <div class="container" data-scrollax-parent="true">
          <div class="row slider-text align-items-end">
            <div class="col-md-7 col-sm-12 ftco-animate mb-5">
              <p class="breadcrumbs" data-scrollax=" properties: { translateY: '70%', opacity: 1.6}">
                <span class="mr-2"><a href="index.php">Home</a></span> 
                <span>Doctors</span>
              </p>
              <h1 class="mb-3" data-scrollax=" properties: { translateY: '70%', opacity: .9}">Well Experienced Doctors</h1>
            </div>
          </div>
        </div>
      </div>
    </section>
		
    <?php
    // Lấy danh sách bác sĩ từ database
    require_once '../config/db.php';
    require_once '../functions/avatar_functions.php';
    
    // Phân trang: 6 bác sĩ/trang
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 6;
    $offset = ($page - 1) * $per_page;
    
    // Lấy tổng số bác sĩ
    $total_stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'doctor' AND status = 'active'");
    $total = $total_stmt->fetch()['total'];
    $total_pages = ceil($total / $per_page);
    
    // Lấy danh sách bác sĩ với phân trang
    $stmt = $pdo->prepare("SELECT id, full_name, email, avatar FROM users WHERE role = 'doctor' AND status = 'active' ORDER BY full_name LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $doctors = $stmt->fetchAll();
    ?>
    <section class="ftco-section">
      <div class="container">
        <div class="row justify-content-center mb-3">
          <div class="col-md-12">
            <a href="index.php" class="btn btn-secondary">
              <span>←</span> Quay về trang chủ
            </a>
          </div>
        </div>
        <div class="row justify-content-center mb-5 pb-5">
          <div class="col-md-7 text-center heading-section ftco-animate">
            <h2 class="mb-3">Đội ngũ bác sĩ chuyên nghiệp</h2>
            <p>Đội ngũ bác sĩ giàu kinh nghiệm, tận tâm với nghề, luôn đặt sức khỏe và sự hài lòng của bệnh nhân lên hàng đầu.</p>
          </div>
        </div>
        <div class="row">
          <?php if (empty($doctors)): ?>
            <div class="col-12 text-center">
              <p class="text-muted">Chưa có bác sĩ nào trong hệ thống.</p>
            </div>
          <?php else: ?>
            <?php foreach ($doctors as $doctor): ?>
            <div class="col-lg-3 col-md-6 d-flex mb-sm-4 ftco-animate">
              <div class="staff">
                <?php 
                  $avatar_path = null;
                  if ($doctor['avatar']) {
                    $full_path = '../' . $doctor['avatar'];
                    if (file_exists($full_path)) {
                      $avatar_path = '../' . $doctor['avatar'];
                    }
                  }
                  
                  // Tạo style string
                  $bg_style = '';
                  if ($avatar_path) {
                    $bg_style = "background-image: url('" . htmlspecialchars($avatar_path) . "');";
                  } else {
                    $bg_style = "background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);";
                  }
                  $bg_style .= " background-size: cover; background-position: center; min-height: 300px; display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem; font-weight: bold;";
                ?>
                <div class="img mb-4" style="<?= $bg_style ?>">
                  <?php if (!$avatar_path): ?>
                    <!-- Hiển thị initial nếu không có ảnh -->
                    <?= htmlspecialchars(getInitials($doctor['full_name'])) ?>
                  <?php endif; ?>
                </div>
                <div class="info text-center">
                  <h3><a href="#"><?= htmlspecialchars($doctor['full_name']) ?></a></h3>
                  <span class="position">Bác sĩ nha khoa</span>
                  <div class="text">
                    <p>Bác sĩ chuyên nghiệp với nhiều năm kinh nghiệm trong lĩnh vực nha khoa</p>
                    <?php if ($doctor['email']): ?>
                      <p class="small text-muted mb-2"><?= htmlspecialchars($doctor['email']) ?></p>
                    <?php endif; ?>
                    <a href="index.php?doctor=<?= urlencode($doctor['full_name']) ?>" class="btn btn-primary btn-sm mt-2">Đặt lịch với bác sĩ này</a>
                    <ul class="ftco-social mt-3">
                      <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
                      <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
                      <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
                      <li class="ftco-animate"><a href="#"><span class="icon-google-plus"></span></a></li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        
        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
        <div class="row mt-5">
          <div class="col-md-12 text-center">
            <nav aria-label="Phân trang">
              <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                  <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Trước</a>
                  </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                  <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Sau</a>
                  </li>
                <?php endif; ?>
              </ul>
            </nav>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </section>


    <section class="ftco-section ftco-counter img" id="section-counter" style="background-image: url('assets/images/bg_1.jpg');" data-stellar-background-ratio="0.5">
      <div class="container">
        <div class="row d-flex align-items-center">
          <div class="col-md-3 aside-stretch py-5">
            <div class="heading-section heading-section-white ftco-animate pr-md-4">
              <h2 class="mb-3">Achievements</h2>
              <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
            </div>
          </div>
          <div class="col-md-9 py-5 pl-md-5">
            <div class="row">
              <div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
                <div class="block-18">
                  <div class="text">
                    <strong class="number" data-number="14">0</strong>
                    <span>Years of Experience</span>
                  </div>
                </div>
              </div>
              <div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
                <div class="block-18">
                  <div class="text">
                    <strong class="number" data-number="4500">0</strong>
                    <span>Qualified Dentist</span>
                  </div>
                </div>
              </div>
              <div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
                <div class="block-18">
                  <div class="text">
                    <strong class="number" data-number="4200">0</strong>
                    <span>Happy Smiling Customer</span>
                  </div>
                </div>
              </div>
              <div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
                <div class="block-18">
                  <div class="text">
                    <strong class="number" data-number="320">0</strong>
                    <span>Patients Per Year</span>
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
            <h2 class="mb-3">Our Best Pricing</h2>
            <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 ftco-animate">
            <div class="pricing-entry pb-5 text-center">
              <div>
                <h3 class="mb-4">Basic</h3>
                <p><span class="price">$24.50</span> <span class="per">/ session</span></p>
              </div>
              <ul>
                <li>Diagnostic Services</li>
                <li>Professional Consultation</li>
                <li>Tooth Implants</li>
                <li>Surgical Extractions</li>
                <li>Teeth Whitening</li>
              </ul>
              <p class="button text-center"><a href="#" class="btn btn-primary btn-outline-primary px-4 py-3">Order now</a></p>
            </div>
          </div>
          <div class="col-md-3 ftco-animate">
            <div class="pricing-entry pb-5 text-center">
              <div>
                <h3 class="mb-4">Standard</h3>
                <p><span class="price">$34.50</span> <span class="per">/ session</span></p>
              </div>
              <ul>
                <li>Diagnostic Services</li>
                <li>Professional Consultation</li>
                <li>Tooth Implants</li>
                <li>Surgical Extractions</li>
                <li>Teeth Whitening</li>
              </ul>
              <p class="button text-center"><a href="#" class="btn btn-primary btn-outline-primary px-4 py-3">Order now</a></p>
            </div>
          </div>
          <div class="col-md-3 ftco-animate">
            <div class="pricing-entry active pb-5 text-center">
              <div>
                <h3 class="mb-4">Premium</h3>
                <p><span class="price">$54.50</span> <span class="per">/ session</span></p>
              </div>
              <ul>
                <li>Diagnostic Services</li>
                <li>Professional Consultation</li>
                <li>Tooth Implants</li>
                <li>Surgical Extractions</li>
                <li>Teeth Whitening</li>
              </ul>
              <p class="button text-center"><a href="#" class="btn btn-primary btn-outline-primary px-4 py-3">Order now</a></p>
            </div>
          </div>
          <div class="col-md-3 ftco-animate">
            <div class="pricing-entry pb-5 text-center">
              <div>
                <h3 class="mb-4">Platinum</h3>
                <p><span class="price">$89.50</span> <span class="per">/ session</span></p>
              </div>
              <ul>
                <li>Diagnostic Services</li>
                <li>Professional Consultation</li>
                <li>Tooth Implants</li>
                <li>Surgical Extractions</li>
                <li>Teeth Whitening</li>
              </ul>
              <p class="button text-center"><a href="#" class="btn btn-primary btn-outline-primary px-4 py-3">Order now</a></p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <footer class="ftco-footer ftco-bg-dark ftco-section">
      <div class="container">
        <div class="row mb-5">
          <div class="col-md-3">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">DentaCare.</h2>
              <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
            </div>
            <ul class="ftco-footer-social list-unstyled float-md-left float-lft ">
              <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
              <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
              <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
            </ul>
          </div>
          <div class="col-md-2">
            <div class="ftco-footer-widget mb-4 ml-md-5">
              <h2 class="ftco-heading-2">Quick Links</h2>
              <ul class="list-unstyled">
                <li><a href="#" class="py-2 d-block">About</a></li>
                <li><a href="#" class="py-2 d-block">Features</a></li>
                <li><a href="#" class="py-2 d-block">Projects</a></li>
                <li><a href="#" class="py-2 d-block">Blog</a></li>
                <li><a href="#" class="py-2 d-block">Contact</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md-4 pr-md-4">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">Recent Blog</h2>
              <div class="block-21 mb-4 d-flex">
                <a class="blog-img mr-4" style="background-image: url('assets/images/image_1.jpg');"></a>
                <div class="text">
                  <h3 class="heading"><a href="#">Even the all-powerful Pointing has no control about</a></h3>
                  <div class="meta">
                    <div><a href="#"><span class="icon-calendar"></span> Sept 15, 2018</a></div>
                    <div><a href="#"><span class="icon-person"></span> Admin</a></div>
                    <div><a href="#"><span class="icon-chat"></span> 19</a></div>
                  </div>
                </div>
              </div>
              <div class="block-21 mb-4 d-flex">
                <a class="blog-img mr-4" style="background-image: url('assets/images/image_2.jpg');"></a>
                <div class="text">
                  <h3 class="heading"><a href="#">Even the all-powerful Pointing has no control about</a></h3>
                  <div class="meta">
                    <div><a href="#"><span class="icon-calendar"></span> Sept 15, 2018</a></div>
                    <div><a href="#"><span class="icon-person"></span> Admin</a></div>
                    <div><a href="#"><span class="icon-chat"></span> 19</a></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">Office</h2>
              <div class="block-23 mb-3">
                <ul>
                  <li><span class="icon icon-map-marker"></span><span class="text">Hà Nội, Việt Nam</span></li>
                  <li><a href="#"><span class="icon icon-phone"></span><span class="text">+84 345 277 764</span></a></li>
                  <li><a href="#"><span class="icon icon-envelope"></span><span class="text">phongsir205@gmail.com</span></a></li>
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

    <!-- Modal -->
    <div class="modal fade" id="modalRequest" tabindex="-1" role="dialog" aria-labelledby="modalRequestLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalRequestLabel">Make an Appointment</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form action="#">
              <div class="form-group">
                <input type="text" class="form-control" id="appointment_name" placeholder="Full Name">
              </div>
              <div class="form-group">
                <input type="text" class="form-control" id="appointment_email" placeholder="Email">
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <input type="text" class="form-control appointment_date" placeholder="Date">
                  </div>    
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <input type="text" class="form-control appointment_time" placeholder="Time">
                  </div>
                </div>
              </div>
              <div class="form-group">
                <textarea name="" id="appointment_message" class="form-control" cols="30" rows="10" placeholder="Message"></textarea>
              </div>
              <div class="form-group">
                <input type="submit" value="Make an Appointment" class="btn btn-primary">
              </div>
            </form>
          </div>
          
        </div>
      </div>
    </div>


    <!-- JS -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/jquery-migrate-3.0.1.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.easing.1.3.js"></script>
    <script src="assets/js/jquery.waypoints.min.js"></script>
    <script src="assets/js/jquery.stellar.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/jquery.magnific-popup.min.js"></script>
    <script src="assets/js/aos.js"></script>
    <script src="assets/js/jquery.animateNumber.min.js"></script>
    <script src="assets/js/bootstrap-datepicker.js"></script>
    <script src="assets/js/jquery.timepicker.min.js"></script>
    <script src="assets/js/scrollax.min.js"></script>
    <!-- trang này không có map nên bỏ script map để khỏi lỗi -->
    <!-- <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY&sensor=false"></script>
    <script src="assets/js/google-map.js"></script> -->
    <script src="assets/js/main.js"></script>
  
  <!-- Modal đặt lịch -->
  <?php include 'includes/appointment_modal.php'; ?>
    
  </body>
</html>
