<?php
session_start();
require_once 'db_connect.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$module_path = "admin_module/{$page}.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal | Barangay CARMEN</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Poppins', sans-serif;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 260px;
      background: #145a32;
      color: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding-top: 20px;
      z-index: 100;
      box-shadow: 2px 0 8px rgba(0,0,0,0.15);
      transition: 0.3s;
    }

    .sidebar img.logo {
      width: 100px;
      height: auto;
      margin-bottom: 15px;
      border-radius: 50%;
      border: 2px solid #fff;
    }

    .sidebar h2 {
      font-size: 20px;
      text-align: center;
      margin-bottom: 25px;
      font-weight: 600;
      color: #fff;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      width: 100%;
      color: #fff;
      text-decoration: none;
      padding: 12px 25px;
      font-size: 15px;
      border-radius: 0 25px 25px 0;
      margin-bottom: 5px;
      transition: all 0.3s ease;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background: #198754;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      border-left: 4px solid #fff;
    }

    .sidebar i {
      margin-right: 12px;
      font-size: 18px;
    }

    /* Main content */
    .main {
      margin-left: 260px;
      padding: 20px 30px;
      min-height: 100vh;
      background: #f6fdf7;
    }

    /* Footer */
    .footer {
      text-align: center;
      padding: 15px 0;
      color: #fff;
      font-size: 14px;
      background: #145a32;
      margin-top: 40px;
      border-top: 1px solid rgba(255,255,255,0.2);
      box-shadow: 0 -2px 6px rgba(0,0,0,0.1);
    }


  </style>
</head>

<body>

  <!-- SIDEBAR -->
  <div class="sidebar">
<img src="uploads/profile_images/images.jpg" alt="Barangay Logo" class="logo">

    <h2>Barangay CARMEN</h2>
    <a href="admin_portal.php?page=dashboard" class="<?= $page=='dashboard'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="admin_portal.php?page=residents" class="<?= $page=='residents'?'active':'' ?>"><i class="bi bi-people"></i> Residents</a>
    <a href="admin_portal.php?page=households" class="<?= $page=='households'?'active':'' ?>"><i class="bi bi-house-door"></i> Households</a>
    <a href="admin_portal.php?page=cedulas" class="<?= $page=='cedulas'?'active':'' ?>"><i class="bi bi-file-earmark-text"></i> Cedulas</a>
    <a href="admin_portal.php?page=users" class="<?= $page=='users'?'active':'' ?>"><i class="bi bi-person-gear"></i> Users</a>
    <a href="admin_portal.php?page=audit" class="<?= $page=='audit'?'active':'' ?>"><i class="bi bi-clock-history"></i> Audit Logs</a>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main">

    <!-- Admin Header -->
    <?php include "admin_module/admin_header.php"; ?>

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
      <p>© 2025 Barangay CARMEN | Admin Portal</p>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
