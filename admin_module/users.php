<?php
// admin_module/users.php
if (!isset($conn)) {
    die("Database connection not found.");
}

if (session_status() === PHP_SESSION_NONE) session_start();

// -------------------- HELPER FUNCTIONS --------------------
function e($str) { return htmlspecialchars($str); }

function add_audit_log($conn, $user_id, $action_type, $action_desc, $target_table, $target_id) {
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action_type, action_desc, target_table, target_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $user_id, $action_type, $action_desc, $target_table, $target_id);
    $stmt->execute();
    $stmt->close();
}

$current_user = $_SESSION['user_id'] ?? 1; // Fallback to 1 if session not set

$action = $_GET['action'] ?? '';
$search_query = $_GET['search'] ?? '';
$show_no_user = isset($_GET['no_user']);
$show_has_user = isset($_GET['has_user']);

// -------------------- FETCH RESIDENTS WITH USERS --------------------
$residents = $conn->query("
    SELECT r.*, u.user_id, u.username, u.email
    FROM residents r
    LEFT JOIN users u ON r.resident_id=u.resident_id
    ORDER BY r.first_name, r.last_name
");

$filtered = [];
while ($row = $residents->fetch_assoc()) {
    $has_user = $row['user_id'] ? true : false;
    $fullname = $row['first_name'].' '.$row['last_name'];

    if ($search_query && stripos($fullname, $search_query) === false) continue;
    if ($show_no_user && $has_user) continue;
    if ($show_has_user && !$has_user) continue;

    $filtered[] = $row;
}

// -------------------- HANDLE FORM SUBMISSION --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resident_id'])) {
    $resident_id = intval($_POST['resident_id']);
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $check = $conn->query("SELECT * FROM users WHERE resident_id = $resident_id");

    if ($check && $check->num_rows > 0) {
        // Update existing user
        $conn->query("UPDATE users SET username='$username', email='$email', password='$password' WHERE resident_id=$resident_id");

        // Fetch user_id for audit
        $user = $conn->query("SELECT user_id FROM users WHERE resident_id=$resident_id")->fetch_assoc();
        add_audit_log($conn, $current_user, 'Update', "Updated user for resident ID $resident_id", 'users', $user['user_id']);
    } else {
        // Create new user
        $conn->query("INSERT INTO users (resident_id, username, email, password, role) VALUES ($resident_id,'$username','$email','$password','Resident')");
        $user_id = $conn->insert_id;
        add_audit_log($conn, $current_user, 'Create', "Created user for resident ID $resident_id", 'users', $user_id);
    }

    echo "<script>window.location='admin_portal.php?page=users';</script>";
    exit;
}

?>

<div class="pagetitle">
    <h1>Users</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_portal.php">Home</a></li>
            <li class="breadcrumb-item active">Users</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">User List</h5>

        <!-- Search and Checkboxes -->
        <form method="GET" class="mb-3 d-flex align-items-center">
            <input type="hidden" name="page" value="users">
            <input type="text" name="search" class="form-control form-control-sm me-3" placeholder="Search resident" value="<?= e($search_query) ?>">

            <div class="form-check me-3">
                <input class="form-check-input" type="checkbox" name="no_user" id="no_user" <?= $show_no_user?'checked':'' ?> onchange="this.form.submit()">
                <label class="form-check-label small" for="no_user">Show No Users</label>
            </div>
            <div class="form-check me-3">
                <input class="form-check-input" type="checkbox" name="has_user" id="has_user" <?= $show_has_user?'checked':'' ?> onchange="this.form.submit()">
                <label class="form-check-label small" for="has_user">Show Edit Users</label>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Resident</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filtered)): ?>
                        <tr><td colspan="5" class="text-center">No users found.</td></tr>
                    <?php else: $i=1; foreach ($filtered as $row):
                        $fullname = e($row['first_name'].' '.$row['last_name']);
                        $username = e($row['username']);
                        $email = e($row['email']);
                        $has_user = $row['user_id'] ? true : false;
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= $fullname ?></td>
                            <td><?= $username ?: '-' ?></td>
                            <td><?= $email ?: '-' ?></td>
                            <td>
                                <button class="btn btn-sm <?= $has_user?'btn-success':'btn-danger' ?>" 
                                        data-bs-toggle="modal" data-bs-target="#userModal"
                                        data-resident-id="<?= $row['resident_id'] ?>"
                                        data-username="<?= $username ?>"
                                        data-email="<?= $email ?>">
                                    <?= $has_user?'Edit':'Create' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">Create/Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" name="resident_id" id="resident_id">
          <div class="mb-2">
              <label class="form-label small">Username</label>
              <input type="text" name="username" id="username" class="form-control form-control-sm" required>
          </div>
          <div class="mb-2">
              <label class="form-label small">Email</label>
              <input type="email" name="email" id="email" class="form-control form-control-sm">
          </div>
          <div class="mb-2">
              <label class="form-label small">Password</label>
              <input type="text" name="password" id="password" class="form-control form-control-sm" value="password123" required>
              <small class="text-muted">Default password is 'password123'</small>
          </div>
      </div>
      <div class="modal-footer py-2">
        <button type="submit" class="btn btn-success btn-sm">Save</button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
var userModal = document.getElementById('userModal');
userModal.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var residentId = button.getAttribute('data-resident-id');
  var username = button.getAttribute('data-username') || '';
  var email = button.getAttribute('data-email') || '';

  userModal.querySelector('#resident_id').value = residentId;
  userModal.querySelector('#username').value = username;
  userModal.querySelector('#email').value = email;
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
