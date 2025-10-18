<?php
require_once './db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$userQuery = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
if (!$userQuery || $userQuery->num_rows === 0) {
    die("<div class='alert alert-danger'>User not found.</div>");
}
$user = $userQuery->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = !empty($_POST['password']) 
        ? password_hash($_POST['password'], PASSWORD_BCRYPT) 
        : $user['password'];

    // Handle image upload
    $profile_image = $user['profile_image'] ?? null;
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "uploads/profile_images/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
                $profile_image = $targetFilePath;
            } else {
                echo "<div class='alert alert-danger'>Error uploading image.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Only JPG, PNG, and GIF files allowed.</div>";
        }
    }

    // Update user info (without email)
    $update = $conn->query("
        UPDATE users 
        SET username='$username', 
            password='$password',
            profile_image='$profile_image'
        WHERE user_id=$user_id
    ");

    if ($update) {
        $_SESSION['username'] = $username;
        $_SESSION['profile_image'] = $profile_image;
        echo "<div class='alert alert-success'>Profile updated successfully!</div>";

        // Reload latest user data
        $user = $conn->query("SELECT * FROM users WHERE user_id = $user_id")->fetch_assoc();
    } else {
        echo "<div class='alert alert-danger'>Error updating profile: " . $conn->error . "</div>";
    }
}
?>

<div class="card shadow-sm">
  <div class="card-header bg-success text-white">
    <h5><i class="bi bi-person-gear"></i> Edit Profile</h5>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <div class="text-center mb-3">
        <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : './assets/default-avatar.png' ?>" 
             alt="Profile Image" 
             class="rounded-circle" width="120" height="120" 
             style="object-fit: cover; border:3px solid #198754;">
      </div>

      <div class="mb-3">
        <label class="form-label">Profile Image</label>
        <input type="file" name="profile_image" class="form-control" accept="image/*">
      </div>

      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" 
               value="<?= htmlspecialchars($user['username']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" 
               placeholder="Leave blank to keep current password">
      </div>

      <button type="submit" class="btn btn-success w-100">
        <i class="bi bi-save"></i> Save Changes
      </button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
