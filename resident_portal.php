<?php
session_start();
require_once 'db_connect.php';

// Check login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// NOTE: page must match the filename (without .php) inside resident_module/
// e.g. resident_dashboard, resident_profile, resident_request, resident_view_profile
$page = isset($_GET['page']) ? $_GET['page'] : 'resident_dashboard';
$module_path = "resident_module/{$page}.php";

// Basic whitelist to avoid arbitrary file include
$allowed_pages = [
  'resident_dashboard',
  'resident_profile',
  'resident_request',
  'resident_view_profile'
];
if (!in_array($page, $allowed_pages)) {
  $page = 'resident_dashboard';
  $module_path = "resident_module/{$page}.php";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Resident Portal | Barangay CARMEN</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0; left: 0;
      height: 100vh; width: 260px;
      background: #196f3d; color: #fff;
      display: flex; flex-direction: column; align-items: center;
      padding-top: 20px; z-index: 100;
      box-shadow: 2px 0 8px rgba(0,0,0,0.15);
    }
    .sidebar img.logo { width:100px; height:auto; margin-bottom:15px; border-radius:50%; border:2px solid #fff; }
    .sidebar h2 { font-size:20px; text-align:center; margin-bottom:25px; font-weight:600; color:#fff; }
    .sidebar a { display:flex; align-items:center; width:100%; color:#fff; text-decoration:none; padding:12px 25px; font-size:15px; border-radius:0 25px 25px 0; margin-bottom:5px; transition:all .3s ease; }
    .sidebar a:hover, .sidebar a.active { background:#27ae60; box-shadow:0 2px 8px rgba(0,0,0,0.2); border-left:4px solid #fff; }
    .sidebar i { margin-right:12px; font-size:18px; }

    /* Main */
    .main { margin-left:260px; padding:20px 30px; min-height:100vh; background:#e8f5e9; }

    /* Footer */
    .footer { text-align:center; padding:15px 0; color:#fff; font-size:14px; background:#196f3d; margin-top:40px; border-top:1px solid rgba(255,255,255,0.2); box-shadow:0 -2px 6px rgba(0,0,0,0.1); }
  </style>
</head>

<body>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <img src="uploads/profile_images/images.jpg" alt="Barangay Logo" class="logo">
    <h2>Barangay CARMEN</h2>

    <!-- Sidebar links (Removed Pending & Completed Requests) -->
    <a href="resident_portal.php?page=resident_dashboard" class="<?= $page=='resident_dashboard'?'active':'' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <a href="resident_portal.php?page=resident_profile" class="<?= $page=='resident_profile'?'active':'' ?>">
      <i class="bi bi-person-circle"></i> My Profile
    </a>

    <a href="resident_portal.php?page=resident_request" class="<?= $page=='resident_request'?'active':'' ?>">
      <i class="bi bi-file-earmark-text"></i> Request Certificate
    </a>

    <a href="resident_portal.php?page=resident_view_profile" class="<?= $page=='resident_view_profile'?'active':'' ?>">
      <i class="bi bi-eye"></i> View Profile
    </a>

    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main">
    <!-- Resident Header -->
    <?php include "resident_module/resident_header.php"; ?>

    <!-- Page Content -->
    <div class="content">
      <?php
        if (file_exists($module_path)) {
          include $module_path;
        } else {
          echo "<div class='alert alert-danger'>Module not found: <b>{$page}.php</b></div>";
        }
      ?>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>© <?= date('Y') ?> Barangay CARMEN | Resident Portal</p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
