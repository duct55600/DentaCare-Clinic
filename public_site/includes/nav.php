<?php
// language.php sẽ tự động khởi tạo session nếu chưa có
require_once __DIR__ . '/../../functions/language.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
  <div class="container">
    <a class="navbar-brand" href="index.php">Denta<span>Care</span></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav">
      <span class="oi oi-menu"></span> Menu
    </button>
    <div class="collapse navbar-collapse" id="ftco-nav">
      <ul class="navbar-nav ml-auto">
        <?php 
        $current_lang = $_SESSION['lang'] ?? 'vi';
        ?>
        <li class="nav-item <?= ($current_page ?? '') === 'index' ? 'active' : '' ?>">
          <a href="index.php" class="nav-link"><?= t('home') ?></a>
        </li>
        <li class="nav-item <?= ($current_page ?? '') === 'about' ? 'active' : '' ?>">
          <a href="about.php" class="nav-link"><?= t('about') ?></a>
        </li>
        <li class="nav-item <?= ($current_page ?? '') === 'services' ? 'active' : '' ?>">
          <a href="services.php" class="nav-link"><?= t('services') ?></a>
        </li>
        <li class="nav-item <?= ($current_page ?? '') === 'doctors' ? 'active' : '' ?>">
          <a href="doctors_list.php" class="nav-link"><?= t('doctors') ?></a>
        </li>
        <li class="nav-item <?= ($current_page ?? '') === 'blog' ? 'active' : '' ?>">
          <a href="blog.php" class="nav-link"><?= t('blog') ?></a>
        </li>
        <li class="nav-item <?= ($current_page ?? '') === 'contact' ? 'active' : '' ?>">
          <a href="contact.php" class="nav-link"><?= t('contact') ?></a>
        </li>
        <li class="nav-item <?= ($current_page ?? '') === 'my_appointments' ? 'active' : '' ?>">
          <a href="my_appointments.php" class="nav-link"><?= t('my_appointments') ?></a>
        </li>
        <li class="nav-item cta">
          <a href="#" class="nav-link" data-toggle="modal" data-target="#modalRequest">
            <span><?= t('book_appointment') ?></span>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-toggle="dropdown">
            <?php if ($current_lang === 'vi'): ?>
              🇻🇳
            <?php else: ?>
              🇬🇧
            <?php endif; ?>
          </a>
          <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item" href="?lang=vi">🇻🇳 Tiếng Việt</a>
            <a class="dropdown-item" href="?lang=en">🇬🇧 English</a>
          </div>
        </li>
        <li class="nav-item">
          <a href="https://www.facebook.com/kieu.phong.466439" target="_blank" class="nav-link" title="Chat Facebook">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

