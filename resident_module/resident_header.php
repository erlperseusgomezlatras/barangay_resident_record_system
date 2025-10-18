<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once './db_connect.php';

// Session check
if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userQuery = $conn->query("SELECT username, profile_image FROM users WHERE user_id = $user_id");
if ($userQuery && $userQuery->num_rows > 0) {
    $user = $userQuery->fetch_assoc();
} else {
    $user = [
        'username' => 'Resident',
        'profile_image' => './assets/default-avatar.png'
    ];
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
.navbar-resident {
  background-color: #196f3d;
  border-radius: 10px;
}
.navbar-resident .nav-link,
.navbar-resident .navbar-brand {
  color: #fff;
}
.profile-img {
  object-fit: cover;
  border: 2px solid #fff;
}
</style>

<nav class="navbar navbar-expand-lg navbar-resident mb-4 px-3">
  <a class="navbar-brand fw-bold" href="./resident_portal.php">Resident Portal</a>

  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#residentNavbar"
          aria-controls="residentNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="residentNavbar">
      <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown"
                 role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : './assets/default-avatar.png' ?>"
                       alt="Profile" class="rounded-circle me-2 profile-img" width="36" height="36">
                  <?= htmlspecialchars($user['username']) ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow">
                  <li>
                      <a class="dropdown-item" href="./resident_portal.php?page=resident_view_profile">
                          <i class="bi bi-pencil-square me-2"></i>View Profile
                      </a>
                  </li>
                  <li>
                      <a class="dropdown-item" href="./resident_portal.php?page=resident_request">
                          <i class="bi bi-file-earmark-plus me-2"></i>Request Certificate
                      </a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                      <a class="dropdown-item text-danger" href="./logout.php">
                          <i class="bi bi-box-arrow-right me-2"></i>Logout
                      </a>
                  </li>
              </ul>
          </li>
      </ul>
  </div>
</nav>
