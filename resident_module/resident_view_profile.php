<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once './db_connect.php';

// Get current user info
$user_id = $_SESSION['user_id'] ?? 0;
$user = $conn->query("SELECT r.*, u.email, u.profile_image 
                      FROM residents r 
                      LEFT JOIN users u ON r.resident_id = u.resident_id 
                      WHERE u.user_id = $user_id")->fetch_assoc();
?>

<div class="container">
  <!-- Breadcrumb -->
  <div class="pagetitle mb-4">
    <h3 class="fw-bold text-success"><i class="bi bi-eye me-2"></i>View Profile Details</h3>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="resident_portal.php?page=resident_dashboard">Home</a></li>
        <li class="breadcrumb-item active">View Profile</li>
      </ol>
    </nav>
  </div>

  <!-- Profile Card -->
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="row">
        <!-- Profile Image -->
        <div class="col-md-3 text-center mb-3">
          <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : './uploads/profile_images/default-avatar.png' ?>" 
               alt="Profile" class="rounded-circle border border-success" width="130" height="130">
        </div>

        <!-- Profile Details -->
        <div class="col-md-9">
          <table class="table table-hover align-middle">
            <tr>
              <th style="width:150px;">Full Name:</th>
              <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
            </tr>
            <tr>
              <th>Email:</th>
              <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
            </tr>
            <tr>
              <th>Address:</th>
              <td><?= htmlspecialchars($user['address'] ?? '-') ?></td>
            </tr>
            <tr>
              <th>Contact:</th>
              <td><?= htmlspecialchars($user['contact_no'] ?? '-') ?></td>
            </tr>
            <tr>
              <th>Registered On:</th>
              <td><?= !empty($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : '-' ?></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* Green theme uniform styling */
.pagetitle h3 { color: #27ae60; }
.breadcrumb .breadcrumb-item a { color: #196f3d; text-decoration: none; }
.breadcrumb .breadcrumb-item.active { color: #27ae60; font-weight: 600; }
.table th { color: #196f3d; }
.table td { font-weight: 500; }
.card { border-left: 4px solid #27ae60; }
.card .table-hover tr:hover { background-color: #eafaf1; }
</style>
