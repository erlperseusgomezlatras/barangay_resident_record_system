<?php
require_once './db_connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = $_SESSION['user_id'];

// Fetch user + resident info
$userQuery = $conn->query("
    SELECT u.*, r.*
    FROM users u
    LEFT JOIN residents r ON u.resident_id = r.resident_id
    WHERE u.user_id = $user_id
");
if (!$userQuery || $userQuery->num_rows === 0) {
    die("<div class='alert alert-danger'>User not found.</div>");
}
$user = $userQuery->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = !empty($_POST['password']) 
        ? password_hash($_POST['password'], PASSWORD_BCRYPT) 
        : $user['password'];

    // Resident info
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $middle_name = $conn->real_escape_string($_POST['middle_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $contact_no = $conn->real_escape_string($_POST['contact_no']);
    $address = $conn->real_escape_string($_POST['address']);
    $civil_status = $_POST['civil_status'];

    // Handle image upload
    $profile_image = $user['profile_image'] ?? null;
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "../uploads/profile_images/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg','jpeg','png','gif'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
                $profile_image = $targetFilePath;
            } else echo "<div class='alert alert-danger'>Error uploading image.</div>";
        } else {
            echo "<div class='alert alert-danger'>Only JPG, PNG, and GIF files allowed.</div>";
        }
    }

    // Update residents table
    $updateRes = $conn->query("
        UPDATE residents SET 
        first_name='$first_name', middle_name='$middle_name', last_name='$last_name', 
        birthdate='$birthdate', gender='$gender', contact_no='$contact_no', 
        address='$address', civil_status='$civil_status'
        WHERE resident_id=".$user['resident_id']."
    ");

    // Update users table
    $updateUser = $conn->query("
        UPDATE users SET 
        username='$username', password='$password', profile_image='$profile_image'
        WHERE user_id=$user_id
    ");

    if ($updateRes && $updateUser) {
        $_SESSION['username'] = $username;
        $_SESSION['profile_image'] = $profile_image;
        echo "<div class='alert alert-success'>Profile updated successfully!</div>";
        $user = $conn->query("SELECT u.*, r.* FROM users u LEFT JOIN residents r ON u.resident_id = r.resident_id WHERE u.user_id = $user_id")->fetch_assoc();
    } else {
        echo "<div class='alert alert-danger'>Error updating profile: ".$conn->error."</div>";
    }
}
?>

<div class="card shadow-sm mx-auto my-4" style="max-width: 700px;">
  <div class="card-header bg-success text-white">
    <h5><i class="bi bi-person-gear"></i> Edit Profile</h5>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <div class="text-center mb-3">
        <img src="<?= !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '../assets/default-avatar.png' ?>" 
             alt="Profile Image" 
             class="rounded-circle" width="120" height="120" style="object-fit: cover; border:3px solid #198754;">
      </div>

      <div class="mb-3">
        <label class="form-label">Profile Image</label>
        <input type="file" name="profile_image" class="form-control" accept="image/*">
      </div>

      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
      </div>

      <hr>
      <h6 class="text-success">Resident Info</h6>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">First Name</label>
          <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Middle Name</label>
          <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($user['middle_name']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Last Name</label>
          <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Birthdate</label>
          <input type="date" name="birthdate" class="form-control" value="<?= htmlspecialchars($user['birthdate']) ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-select">
            <option value="Male" <?= $user['gender']=='Male'?'selected':'' ?>>Male</option>
            <option value="Female" <?= $user['gender']=='Female'?'selected':'' ?>>Female</option>
            <option value="Other" <?= $user['gender']=='Other'?'selected':'' ?>>Other</option>
          </select>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Civil Status</label>
          <select name="civil_status" class="form-select">
            <option value="Single" <?= $user['civil_status']=='Single'?'selected':'' ?>>Single</option>
            <option value="Married" <?= $user['civil_status']=='Married'?'selected':'' ?>>Married</option>
            <option value="Widowed" <?= $user['civil_status']=='Widowed'?'selected':'' ?>>Widowed</option>
            <option value="Separated" <?= $user['civil_status']=='Separated'?'selected':'' ?>>Separated</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Contact No</label>
        <input type="text" name="contact_no" class="form-control" value="<?= htmlspecialchars($user['contact_no']) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address']) ?>">
      </div>

      <button type="submit" class="btn btn-success w-100">
        <i class="bi bi-save"></i> Save Changes
      </button>
    </form>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
