<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once './db_connect.php';

// User session check
if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$userQuery = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
if ($userQuery && $userQuery->num_rows > 0) {
    $user = $userQuery->fetch_assoc();
} else {
    $user = [
        'username' => 'Unknown',
        'profile_image' => './assets/default-avatar.png'
    ];
}

// Fetch the 5 most recently added residents
$residentQuery = $conn->query("SELECT resident_id, first_name, last_name, created_at FROM residents ORDER BY created_at DESC LIMIT 5");
$notifications = [];
if ($residentQuery && $residentQuery->num_rows > 0) {
    while ($row = $residentQuery->fetch_assoc()) {
        $notifications[] = [
            'message' => $row['first_name'] . ' ' . $row['last_name'] . ' was added',
            'time' => date('M d, H:i', strtotime($row['created_at'])),
            'link' => "./admin_portal.php?page=residents&resident_id=" . $row['resident_id']
        ];
    }
}

// Only show badge if notifications have not been marked read in this session
$notificationCount = (!isset($_SESSION['notifications_read'])) ? count($notifications) : 0;
?>

<!-- Bootstrap CSS & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
.navbar-custom {
    background: linear-gradient(90deg, #198754, #28a745);
    border-radius: 10px;
}
.navbar-custom .nav-link,
.navbar-custom .navbar-brand,
.navbar-custom .dropdown-toggle {
    color: #fff;
}
.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    font-size: 0.65rem;
    background: red;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    transition: 0.3s;
}
.profile-img {
    object-fit: cover;
    border: 2px solid #fff;
}
</style>

<nav class="navbar navbar-expand-lg navbar-custom mb-4 px-3">
  <a class="navbar-brand fw-bold" href="./admin_portal.php">Admin Portal</a>

  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
          aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto align-items-center">

          <!-- Notification Bell Dropdown -->
          <li class="nav-item dropdown me-3">
              <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button"
                 data-bs-toggle="dropdown" aria-expanded="false" onclick="markNotificationsRead()">
                  <i class="bi bi-bell-fill fs-5"></i>
                  <?php if ($notificationCount > 0): ?>
                      <span id="notificationBadge" class="notification-badge"><?= $notificationCount ?></span>
                  <?php endif; ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow" style="width:300px;" aria-labelledby="notificationDropdown">
                  <li class="dropdown-header fw-bold">Recently Added Residents (<?= count($notifications) ?>)</li>
                  <li><hr class="dropdown-divider"></li>
                  <?php if (!empty($notifications)): ?>
                      <?php foreach ($notifications as $notif): ?>
                          <li>
                              <a class="dropdown-item d-flex flex-column small" href="<?= htmlspecialchars($notif['link']) ?>">
                                  <span><?= htmlspecialchars($notif['message']) ?></span>
                                  <small class="text-muted"><?= htmlspecialchars($notif['time']) ?></small>
                              </a>
                          </li>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <li class="dropdown-item small text-center text-muted">No recent residents</li>
                  <?php endif; ?>
              </ul>
          </li>

          <!-- Profile Dropdown -->
          <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button"
                 data-bs-toggle="dropdown" aria-expanded="false">
                  <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : './assets/default-avatar.png' ?>" 
                       alt="Profile" class="rounded-circle me-2 profile-img" width="36" height="36">
                  <?= htmlspecialchars($user['username']) ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow">
                  <li>
                      <a class="dropdown-item" href="./admin_portal.php?page=profile">
                          <i class="bi bi-pencil-square me-2"></i>Edit Profile
                      </a>
                  </li>
                  <li>
                      <a class="dropdown-item" href="./admin_portal.php?page=settings">
                          <i class="bi bi-gear me-2"></i>Settings
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

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Function to mark notifications as "read" (removes red badge and updates session via AJAX)
function markNotificationsRead() {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        badge.style.display = 'none';
    }

    // AJAX request to PHP to set session variable
    // AJAX request to PHP to set session variable
        fetch('./admin_module/mark_notifications_read.php')
        .then(response => response.text())
        .then(data => {
            console.log('Notifications marked as read');
        })
        .catch(err => console.error(err));
}
</script>
