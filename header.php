<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start(); // เริ่ม output buffering

require __DIR__ . '/../includes/db.php';

// Check if database connection is successful
if (!isset($pdo)) {
    die('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ຖານຂໍ້ມູນ');
}

?>

<!doctype html>
<html lang="lo">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#667eea">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title>Parking System</title>
  <?php
  // Check for custom favicon
  $faviconPath = __DIR__ . '/../assets/uploads/favicon.*';
  $favicons = glob($faviconPath);
  if (!empty($favicons)) {
      $favicon = basename($favicons[0]);
      echo '<link rel="icon" href="/Parking%20car/assets/uploads/' . $favicon . '?v=' . filemtime($favicons[0]) . '">';
  }
  ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/Parking%20car/assets/css/app.css">
  <style>
    /* Theme Variables */
    :root {
      --bg-primary: #ffffff;
      --bg-secondary: #f8f9fa;
      --text-primary: #212529;
      --text-secondary: #6c757d;
      --navbar-bg: #ffffff;
      --card-bg: #ffffff;
      --border-color: #dee2e6;
      --shadow: rgba(0, 0, 0, 0.1);
      --input-bg: #ffffff;
      --table-stripe: rgba(0, 0, 0, 0.05);
    }

    [data-theme="dark"] {
      --bg-primary: #1a1a2e;
      --bg-secondary: #16213e;
      --text-primary: #eaeaea;
      --text-secondary: #a0a0a0;
      --navbar-bg: #0f3460;
      --card-bg: #16213e;
      --border-color: #2a2a3e;
      --shadow: rgba(0, 0, 0, 0.5);
      --input-bg: #1a1a2e;
      --table-stripe: rgba(255, 255, 255, 0.05);
    }

    /* Apply theme colors */
    body {
      background-color: var(--bg-primary);
      color: var(--text-primary);
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .bg-blue {
      background-color: var(--bg-secondary) !important;
    }

    .navbar {
      background-color: var(--navbar-bg) !important;
      box-shadow: 0 2px 4px var(--shadow);
      transition: all 0.3s ease;
      min-height: 60px;
      padding: 0.5rem 1rem;
      position: fixed;
      top: 0;
      right: 0;
      left: 0;
      z-index: 1030;
    }

    @media (max-width: 768px) {
      .navbar {
        padding: 0.25rem 0.5rem;
      }

      .navbar-brand {
        font-size: 1rem;
        padding: 0.5rem;
      }

      .navbar-toggler {
        padding: 0.25rem 0.5rem;
        margin-right: 0.5rem;
      }
    }

    .navbar-brand, .nav-link {
      color: var(--text-primary) !important;
      transition: color 0.3s ease;
    }

    .navbar-nav {
      margin: 0;
      padding: 0;
      list-style: none;
      display: flex;
      flex-direction: row;
      align-items: center;
    }

    @media (max-width: 991.98px) {
      .navbar-nav {
        flex-direction: column;
        width: 100%;
        padding: 0.5rem 0;
      }

      .navbar-nav .nav-item {
        width: 100%;
        text-align: center;
      }

      .navbar-collapse {
        background-color: var(--navbar-bg);
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1000;
        padding: 0.5rem;
        box-shadow: 0 4px 6px var(--shadow);
      }
    }

    .nav-link {
      padding: 0.5rem 1rem;
      display: block;
      text-decoration: none;
      white-space: nowrap;
    }

    .nav-link:hover {
      color: #667eea !important;
      transform: translateY(-1px);
    }

    @media (max-width: 991.98px) {
      .nav-link {
        padding: 0.75rem 1rem;
        border-radius: 4px;
      }

      .nav-link:hover {
        background-color: var(--bg-secondary);
        transform: none;
      }
    }

    .card, .form-card, .table-container, .form-container > div {
      background-color: var(--card-bg) !important;
      color: var(--text-primary) !important;
      border-color: var(--border-color) !important;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .form-control, .form-select, .form-input {
      background-color: var(--input-bg) !important;
      color: var(--text-primary) !important;
      border-color: var(--border-color) !important;
      transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

    .form-control::placeholder,
    .form-input::placeholder {
      color: var(--text-secondary) !important;
    }

    .table {
      color: var(--text-primary) !important;
    }

    .table-striped > tbody > tr:nth-of-type(odd) {
      background-color: var(--table-stripe);
    }

    .modern-table tbody tr {
      border-bottom-color: var(--border-color) !important;
    }

    [data-theme="dark"] .modern-table tbody tr:hover {
      background: rgba(102, 126, 234, 0.1) !important;
    }

    .vehicle-type-card {
      background-color: var(--card-bg) !important;
      border-color: var(--border-color) !important;
    }

    [data-theme="dark"] .page-header::before {
      background: rgba(255, 255, 255, 0.05);
    }

    [data-theme="dark"] .error-alert {
      background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%);
    }

    /* Theme Toggle Button */
    .theme-toggle {
      position: relative;
      width: 60px;
      height: 30px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 30px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      padding: 0;
      overflow: hidden;
    }

    .theme-toggle:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .theme-toggle-slider {
      position: absolute;
      top: 3px;
      left: 3px;
      width: 24px;
      height: 24px;
      background: white;
      border-radius: 50%;
      transition: transform 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }

    [data-theme="dark"] .theme-toggle-slider {
      transform: translateX(30px);
    }

    .theme-toggle-icon {
      transition: opacity 0.3s ease;
    }

    /* Font Family Settings */
    html {
      font-size: 16px;
    }

    @media (max-width: 768px) {
      html {
        font-size: 14px;
      }
    }

    @media (max-width: 480px) {
      html {
        font-size: 13px;
      }
    }

    body {
      font-family: 'Phetsarath OT', 'Times New Roman', Times, serif;
      padding-top: 80px;
      -webkit-text-size-adjust: 100%;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      overscroll-behavior-y: none;
      touch-action: manipulation;
    }

    body, .nav-link, .card-title, .card-text, 
    .form-label, .btn, .table, h1, h2, h3, h4, h5, h6,
    .alert, label, input::placeholder, textarea::placeholder {
      font-family: 'Phetsarath OT', sans-serif;
    }

    input[type="text"], input[type="number"], 
    input[type="tel"], textarea, select,
    .form-control, .form-select {
      font-family: 'Times New Roman', 'Phetsarath OT', serif;
    }

    .table td, .table th {
      font-family: 'Times New Roman', 'Phetsarath OT', serif;
    }

    .navbar-brand, .nav-link {
      font-family: 'Phetsarath OT', sans-serif;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-size: 1.1rem;
    }

    .btn {
      font-family: 'Phetsarath OT', 'Times New Roman', serif;
      font-weight: 500;
    }

    /* Responsive container classes */
    .container {
      width: 100%;
      padding-right: 15px;
      padding-left: 15px;
      margin-right: auto;
      margin-left: auto;
    }

    @media (min-width: 576px) {
      .container {
        max-width: 540px;
      }
    }

    @media (min-width: 768px) {
      .container {
        max-width: 720px;
      }
    }

    @media (min-width: 992px) {
      .container {
        max-width: 960px;
      }
    }

    @media (min-width: 1200px) {
      .container {
        max-width: 1140px;
      }
    }

    /* Touch device optimizations */
    @media (hover: none) {
      .btn:hover, .nav-link:hover {
        transform: none !important;
      }
      
      * {
        touch-action: manipulation;
      }
    }

    /* Responsive table improvements */
    @media (max-width: 768px) {
      .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      .table td, .table th {
        min-width: 100px;
      }
    }

    /* Form control responsive improvements */
    @media (max-width: 576px) {
      .form-control, .form-select {
        font-size: 16px; /* Prevents iOS zoom on focus */
        padding: 0.375rem 0.75rem;
      }

      .input-group {
        flex-wrap: wrap;
      }

      .input-group > .form-control {
        flex: 1 1 auto;
        width: auto;
      }
    }

    /* Smooth theme transition for all elements */
    * {
      transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

    /* Keep gradients bright in dark mode */
    [data-theme="dark"] .page-header,
    [data-theme="dark"] .btn-primary,
    [data-theme="dark"] .btn-primary-modern,
    [data-theme="dark"] .modern-table thead {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
      color: white !important;
    }

    /* Dark mode specific adjustments */
    [data-theme="dark"] .form-label,
    [data-theme="dark"] .card-title {
      color: #eaeaea !important;
    }

    [data-theme="dark"] .text-muted,
    [data-theme="dark"] .opacity-75 {
      color: #a0a0a0 !important;
    }

    [data-theme="dark"] .badge {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }

    [data-theme="dark"] .disabled-badge {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }
  </style>
</head>
<body class="bg-blue">
<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/Parking%20car/index.php">ລະບົບບັນທຶກການຈອດລົດ</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link text-center" href="/Parking car/index.php">ໜ້າຫຼັກ</a></li>
        <li class="nav-item"><a class="nav-link text-center" href="/Parking car/pages/add_vehicle.php">ເພີ່ມລົດໃໝ່</a></li>
        <li class="nav-item"><a class="nav-link text-center" href="/Parking car/pages/view_vehicle.php">ເບີ່ງຂໍ້ມູນລົດ</a></li>
        <li class="nav-item"><a class="nav-link text-center" href="/Parking car/pages/report.php">ລາຍງານລາຍຮັບ</a></li>
        <li class="nav-item"><a class="nav-link text-center" href="/Parking car/pages/setting.php">ການຕັ້ງຄ່າ</a></li>
      </ul>

      <!-- Theme Toggle -->              
      <div class="me-3">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <div class="theme-toggle-slider">
            <span class="theme-toggle-icon" id="themeIcon">☀️</span>
          </div>
        </button>
      </div>

      <!-- right side: user / logout -->
      <ul class="navbar-nav">
        <?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
        <?php if (!empty($_SESSION['user'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle btn btn-danger text-black" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= htmlspecialchars($_SESSION['user']) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <li><a class="dropdown-item" href="/Parking%20car/pages/logout.php">ອອກຈາກລະບົບ</a></li>
            </ul>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<script>
  // Theme Toggle Functionality
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon = document.getElementById('themeIcon');
  const html = document.documentElement;

  // Load saved theme
  const savedTheme = localStorage.getItem('theme') || 'light';
  html.setAttribute('data-theme', savedTheme);
  updateThemeIcon(savedTheme);

  themeToggle.addEventListener('click', () => {
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
  });

  function updateThemeIcon(theme) {
    themeIcon.textContent = theme === 'light' ? '☀️' : '🌙';
  }

  // Navbar scroll effect
  let lastScrollTop = 0;
  const navbar = document.querySelector('.navbar');
  window.addEventListener('scroll', () => {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    if (scrollTop > lastScrollTop && scrollTop > 100) {
      navbar.style.top = '-70px';
    } else {
      navbar.style.top = '0';
    }
    lastScrollTop = scrollTop;
  });
</script>
<div class="container">