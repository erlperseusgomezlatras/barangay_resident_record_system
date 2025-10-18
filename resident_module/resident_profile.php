<?php
// resident_module/resident_profile.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Ensure DB connection is available. resident_portal.php usually requires db_connect.php
if (!isset($conn)) {
    // module is in resident_module/, db_connect.php is one level up
    require_once __DIR__ . '/../db_connect.php';
}

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">You must be logged in to view this page.</div>';
    return;
}

$user_id = intval($_SESSION['user_id']);
$messages = [];
$errors = [];

/* ---------------------------
   Handle Photo Upload
   --------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "No file selected.";
    } else {
        $f = $_FILES['profile_image'];
        // Basic validations
        $allowed = ['image/jpeg','image/png','image/webp'];
        if (!in_array($f['type'], $allowed)) {
            $errors[] = "Only JPG, PNG or WEBP images are allowed.";
        }
        if ($f['size'] > 2 * 1024 * 1024) { // 2MB limit
            $errors[] = "File size must be <= 2MB.";
        }

        if (empty($errors)) {
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $safe_name = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $upload_dir = __DIR__ . '/../uploads/profile_images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $target_path = $upload_dir . $safe_name;
            if (move_uploaded_file($f['tmp_name'], $target_path)) {
                // Save relative path to DB (matching other records in your DB)
                $relative_path = 'uploads/profile_images/' . $safe_name;
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("si", $relative_path, $user_id);
                    if ($stmt->execute()) {
                        $messages[] = "Profile photo updated.";
                        // add log
                        $l = $conn->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
                        if ($l) {
                            $action = "Updated profile photo";
                            $l->bind_param("is", $user_id, $action);
                            $l->execute();
                            $l->close();
                        }
                    } else {
                        $errors[] = "Failed to update profile image in database.";
                        // cleanup
                        @unlink($target_path);
                    }
                    $stmt->close();
                } else {
                    $errors[] = "DB error (prepare failed).";
                    @unlink($target_path);
                }
            } else {
                $errors[] = "Failed to move uploaded file. Check folder permissions.";
            }
        }
    }
}

/* ---------------------------
   Handle Profile Update
   --------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // sanitize input
    $first_name  = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $birthdate   = trim($_POST['birthdate'] ?? '') ?: null;
    $gender      = in_array($_POST['gender'] ?? '', ['Male','Female','Other']) ? $_POST['gender'] : null;
    $contact_no  = trim($_POST['contact_no'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $civil_status= in_array($_POST['civil_status'] ?? '', ['Single','Married','Widowed','Separated']) ? $_POST['civil_status'] : null;

    // Find resident_id from users table
    $resident_id = null;
    $q = $conn->prepare("SELECT resident_id FROM users WHERE user_id = ?");
    if ($q) {
        $q->bind_param("i", $user_id);
        $q->execute();
        $q->bind_result($resident_id);
        $q->fetch();
        $q->close();
    }

    if (!$resident_id) {
        $errors[] = "Resident profile not linked to your user account.";
    } else {
        $stmt = $conn->prepare("
            UPDATE residents 
            SET first_name = ?, middle_name = ?, last_name = ?, birthdate = ?, gender = ?, contact_no = ?, address = ?, civil_status = ?
            WHERE resident_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("ssssssssi",
                $first_name,
                $middle_name,
                $last_name,
                $birthdate,
                $gender,
                $contact_no,
                $address,
                $civil_status,
                $resident_id
            );
            if ($stmt->execute()) {
                $messages[] = "Profile updated successfully.";
                // add log
                $l = $conn->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
                if ($l) {
                    $action = "Updated personal details";
                    $l->bind_param("is", $user_id, $action);
                    $l->execute();
                    $l->close();
                }
            } else {
                $errors[] = "Failed to update profile.";
            }
            $stmt->close();
        } else {
            $errors[] = "DB error (prepare failed).";
        }
    }
}

/* ---------------------------
   Fetch current profile data
   --------------------------- */
$profile = [
    'username'=>'',
    'email'=>'',
    'profile_image'=>null,
    'resident_id'=>null,
    'first_name'=>'',
    'middle_name'=>'',
    'last_name'=>'',
    'birthdate'=>'',
    'gender'=>'',
    'contact_no'=>'',
    'address'=>'',
    'civil_status'=>''
];

$stmt = $conn->prepare("
    SELECT u.username, u.email, u.profile_image, r.resident_id, r.first_name, r.middle_name, r.last_name, r.birthdate, r.gender, r.contact_no, r.address, r.civil_status
    FROM users u
    LEFT JOIN residents r ON u.resident_id = r.resident_id
    WHERE u.user_id = ?
    LIMIT 1
");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $profile = array_merge($profile, $res->fetch_assoc());
    }
    $stmt->close();
}

// Helper for escaping
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

/* ---------------------------
   Output HTML
   --------------------------- */
?>

<div class="container">
  <h3 class="fw-bold mb-4 text-primary"><i class="bi bi-person-badge me-2"></i>My Profile</h3>

  <?php if (!empty($messages)): ?>
    <?php foreach($messages as $m): ?>
      <div class="alert alert-success"><?php echo e($m); ?></div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach($errors as $err): ?>
      <div class="alert alert-danger"><?php echo e($err); ?></div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <form id="profileForm" method="POST" enctype="multipart/form-data">
        <div class="row mb-3">
          <div class="col-md-4 text-center">
            <?php
              $img = $profile['profile_image'] ?: 'uploads/profile_images/default-avatar.png';
              // If file doesn't exist, fallback (only check if path is local)
              if (strpos($img, 'uploads/') === 0 && !file_exists(__DIR__ . '/../' . $img)) {
                  $img = 'uploads/profile_images/default-avatar.png';
              }
            ?>
            <img id="profilePreview" src="<?php echo e($img); ?>" alt="Profile" class="rounded-circle mb-3" width="120" height="120" style="object-fit:cover;">

            <!-- Photo upload -->
            <div class="mt-2">
              <label class="btn btn-outline-primary btn-sm mb-0" for="profile_image_input">
                <i class="bi bi-camera"></i> Change Photo
              </label>
              <input id="profile_image_input" name="profile_image" type="file" accept="image/*" class="d-none">
            </div>

            <div class="mt-2">
              <button id="uploadPhotoBtn" type="submit" name="upload_photo" class="btn btn-primary btn-sm" style="display:none;">
                Upload
              </button>
              <button id="cancelPhotoBtn" type="button" class="btn btn-secondary btn-sm" style="display:none;">Cancel</button>
            </div>
          </div>

          <div class="col-md-8">
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <div class="input-group">
                <input id="first_name" name="first_name" type="text" class="form-control" value="<?php echo e($profile['first_name'] . ($profile['middle_name'] ? ' ' . $profile['middle_name'] : '') . ' ' . $profile['last_name']); ?>" readonly>
              </div>
            </div>

            <!-- We'll provide separate editable fields when toggled -->
            <div id="editFields" style="display:none;">
              <div class="row">
                <div class="col-md-4 mb-2">
                  <label class="form-label">First Name</label>
                  <input id="first_name_edit" name="first_name" type="text" class="form-control" value="<?php echo e($profile['first_name']); ?>" >
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label">Middle Name</label>
                  <input id="middle_name_edit" name="middle_name" type="text" class="form-control" value="<?php echo e($profile['middle_name']); ?>" >
                </div>
                <div class="col-md-4 mb-2">
                  <label class="form-label">Last Name</label>
                  <input id="last_name_edit" name="last_name" type="text" class="form-control" value="<?php echo e($profile['last_name']); ?>" >
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Birthdate</label>
                  <input id="birthdate" name="birthdate" type="date" class="form-control" value="<?php echo e($profile['birthdate']); ?>">
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label">Gender</label>
                  <select id="gender" name="gender" class="form-select">
                    <option value="">-- Select --</option>
                    <option value="Male" <?php echo ($profile['gender']=='Male') ? 'selected':''; ?>>Male</option>
                    <option value="Female" <?php echo ($profile['gender']=='Female') ? 'selected':''; ?>>Female</option>
                    <option value="Other" <?php echo ($profile['gender']=='Other') ? 'selected':''; ?>>Other</option>
                  </select>
                </div>

                <div class="col-md-6 mb-2">
                  <label class="form-label">Contact No</label>
                  <input id="contact_no" name="contact_no" type="text" class="form-control" value="<?php echo e($profile['contact_no']); ?>">
                </div>
                <div class="col-md-6 mb-2">
                  <label class="form-label">Civil Status</label>
                  <select id="civil_status" name="civil_status" class="form-select">
                    <option value="">-- Select --</option>
                    <?php
                      $statuses = ['Single','Married','Widowed','Separated'];
                      foreach($statuses as $s) {
                        $sel = ($profile['civil_status']==$s) ? 'selected':'';
                        echo "<option value=\"".e($s)."\" $sel>".e($s)."</option>";
                      }
                    ?>
                  </select>
                </div>

                <div class="col-12 mb-2">
                  <label class="form-label">Address</label>
                  <input id="address" name="address" type="text" class="form-control" value="<?php echo e($profile['address']); ?>">
                </div>
              </div>
            </div>

            <div id="viewFields">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?php echo e($profile['email']); ?>" readonly>
              </div>

              <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" class="form-control" value="<?php echo e($profile['address']); ?>" readonly>
              </div>

              <div class="mb-3">
                <label class="form-label">Contact</label>
                <input type="text" class="form-control" value="<?php echo e($profile['contact_no']); ?>" readonly>
              </div>

              <div class="d-flex gap-2">
                <button id="editBtn" type="button" class="btn btn-primary">Edit Information</button>
                <a href="resident_portal.php?page=resident_view_profile" class="btn btn-outline-secondary">View Full Profile</a>
              </div>
            </div>

            <!-- Save / Cancel (visible in edit mode) -->
            <div id="editActions" style="display:none;">
              <button type="submit" name="update_profile" class="btn btn-success">Save changes</button>
              <button id="cancelEditBtn" type="button" class="btn btn-secondary">Cancel</button>
            </div>

          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JS: toggles and preview -->
<script>
  (function(){
    const editBtn = document.getElementById('editBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const viewFields = document.getElementById('viewFields');
    const editFields = document.getElementById('editFields');
    const editActions = document.getElementById('editActions');

    const profileImageInput = document.getElementById('profile_image_input');
    const profilePreview = document.getElementById('profilePreview');
    const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
    const cancelPhotoBtn = document.getElementById('cancelPhotoBtn');

    editBtn && editBtn.addEventListener('click', function(){
      viewFields.style.display = 'none';
      editFields.style.display = 'block';
      editActions.style.display = 'block';
      editBtn.style.display = 'none';
      // copy values into edit inputs (ensure data present)
      // (not necessary because we filled edit inputs server-side)
    });

    cancelEditBtn && cancelEditBtn.addEventListener('click', function(){
      viewFields.style.display = 'block';
      editFields.style.display = 'none';
      editActions.style.display = 'none';
      editBtn.style.display = 'inline-block';
    });

    // Photo change flow
    profileImageInput && profileImageInput.addEventListener('change', function(e){
      const file = this.files[0];
      if (!file) return;
      // Preview
      const reader = new FileReader();
      reader.onload = function(ev){
        profilePreview.src = ev.target.result;
      };
      reader.readAsDataURL(file);
      // Show upload / cancel
      uploadPhotoBtn.style.display = 'inline-block';
      cancelPhotoBtn.style.display = 'inline-block';
    });

    cancelPhotoBtn && cancelPhotoBtn.addEventListener('click', function(){
      // reset input
      profileImageInput.value = '';
      // revert preview to original (reload page to be safe)
      window.location.reload();
    });

  })();
</script>

<style>
/* Page heading */
h3.fw-bold.text-primary {
    color: #27ae60 !important; /* green like your resident pages */
}

/* Breadcrumbs */
.breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 1rem;
}

.breadcrumb .breadcrumb-item a {
    color: #196f3d;
    text-decoration: none;
}

.breadcrumb .breadcrumb-item.active {
    color: #27ae60;
    font-weight: 600;
}

/* Card border highlight */
.card.shadow-sm {
    border-left: 4px solid #27ae60;
}

/* Buttons */
.btn-primary {
    background-color: #27ae60;
    border-color: #27ae60;
}

.btn-outline-secondary {
    border-color: #27ae60;
    color: #27ae60;
}

.btn-outline-secondary:hover {
    background-color: #27ae60;
    color: #fff;
}

/* Input highlights on focus */
.form-control:focus {
    border-color: #27ae60;
    box-shadow: 0 0 0 0.2rem rgba(39, 174, 96, 0.25);
}

/* File upload label */
label.btn-outline-primary {
    border-color: #27ae60;
    color: #27ae60;
}

label.btn-outline-primary:hover {
    background-color: #27ae60;
    color: #fff;
}

.container img { 
  margin-top: 20%;
  height: 200px;
  width: 200px;
}
</style>

