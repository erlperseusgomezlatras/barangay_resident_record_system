<?php
require_once("./db_connect.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Helper function to log actions
function add_audit_log($conn, $user_id, $action_type, $action_desc, $target_table, $target_id) {
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action_type, action_desc, target_table, target_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $user_id, $action_type, $action_desc, $target_table, $target_id);
    $stmt->execute();
    $stmt->close();
}

$current_user = $_SESSION['user_id'] ?? null;
$action = $_GET['action'] ?? null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle Delete
if ($action === 'delete' && $id > 0) {
    $conn->query("DELETE FROM residents WHERE resident_id = $id");
    add_audit_log($conn, $current_user, 'Delete', "Deleted resident ID $id", 'residents', $id);
    echo "<div class='alert alert-success'>Resident deleted successfully.</div>";
    $action = null;
}

// Fetch resident for view/edit
$resident = null;
if (($action === 'view' || $action === 'edit') && $id > 0) {
    $resQuery = $conn->query("SELECT * FROM residents WHERE resident_id = $id");
    if ($resQuery && $resQuery->num_rows > 0) {
        $resident = $resQuery->fetch_assoc();
    } else {
        echo "<div class='alert alert-warning'>Resident not found.</div>";
        $action = null;
    }
}

// Handle Add Resident Form Submission
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $middle_name = $conn->real_escape_string($_POST['middle_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $contact_no = $conn->real_escape_string($_POST['contact_no']);
    $address = $conn->real_escape_string($_POST['address']);
    $civil_status = $_POST['civil_status'];

    $insertResident = $conn->query("
        INSERT INTO residents (first_name, middle_name, last_name, birthdate, gender, contact_no, address, civil_status)
        VALUES ('$first_name','$middle_name','$last_name','$birthdate','$gender','$contact_no','$address','$civil_status')
    ");

    if ($insertResident) {
        $resident_id = $conn->insert_id;
        add_audit_log($conn, $current_user, 'Create', "Added new resident ID $resident_id", 'residents', $resident_id);
        echo "<div class='alert alert-success'>Resident created successfully.</div>";
        $action = null;
    } else {
        echo "<div class='alert alert-danger'>Error adding resident: " . $conn->error . "</div>";
    }
}

// Handle Edit Resident Form Submission
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $resident) {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $middle_name = $conn->real_escape_string($_POST['middle_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $contact_no = $conn->real_escape_string($_POST['contact_no']);
    $address = $conn->real_escape_string($_POST['address']);
    $civil_status = $_POST['civil_status'];

    $update = $conn->query("
        UPDATE residents SET
            first_name='$first_name',
            middle_name='$middle_name',
            last_name='$last_name',
            birthdate='$birthdate',
            gender='$gender',
            contact_no='$contact_no',
            address='$address',
            civil_status='$civil_status'
        WHERE resident_id=$id
    ");

    if ($update) {
        add_audit_log($conn, $current_user, 'Update', "Updated resident ID $id", 'residents', $id);
        echo "<div class='alert alert-success'>Resident updated successfully.</div>";
        $resident = $conn->query("SELECT * FROM residents WHERE resident_id = $id")->fetch_assoc();
    } else {
        echo "<div class='alert alert-danger'>Error updating resident: " . $conn->error . "</div>";
    }
}

// Build residents list
$where = "1";
if (!empty($_GET['q'])) {
    $q = $conn->real_escape_string($_GET['q']);
    $where = "(first_name LIKE '%$q%' OR middle_name LIKE '%$q%' OR last_name LIKE '%$q%' OR address LIKE '%$q%')";
}
$resList = $conn->query("SELECT * FROM residents WHERE $where ORDER BY last_name, first_name LIMIT 200");
?>

<div class="pagetitle">
    <h1>Residents</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_portal.php">Home</a></li>
            <li class="breadcrumb-item active">Residents</li>
        </ol>
    </nav>
</div>

<div class="card">
  <div class="card-body">

    <!-- Add/Edit/View Forms -->
    <?php if ($action === 'view' && $resident): ?>
        <h5 class="card-title">View Resident Details</h5>
        <table class="table table-bordered">
            <tr><th>Full Name</th><td><?= htmlspecialchars($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name']) ?></td></tr>
            <tr><th>Gender</th><td><?= htmlspecialchars($resident['gender']) ?></td></tr>
            <tr><th>Birthdate</th><td><?= htmlspecialchars($resident['birthdate']) ?></td></tr>
            <tr><th>Contact</th><td><?= htmlspecialchars($resident['contact_no']) ?></td></tr>
            <tr><th>Address</th><td><?= htmlspecialchars($resident['address']) ?></td></tr>
            <tr><th>Civil Status</th><td><?= htmlspecialchars($resident['civil_status']) ?></td></tr>
        </table>
        <a href="admin_portal.php?page=residents" class="btn btn-secondary">Back</a>

    <?php elseif ($action === 'edit' && $resident): ?>
        <h5 class="card-title">Edit Resident</h5>
        <form method="POST">
            <div class="mb-3"><label>First Name</label><input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($resident['first_name']) ?>"></div>
            <div class="mb-3"><label>Middle Name</label><input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($resident['middle_name']) ?>"></div>
            <div class="mb-3"><label>Last Name</label><input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($resident['last_name']) ?>"></div>
            <div class="mb-3"><label>Birthdate</label><input type="date" name="birthdate" class="form-control" value="<?= htmlspecialchars($resident['birthdate']) ?>"></div>
            <div class="mb-3"><label>Gender</label>
                <select name="gender" class="form-select">
                    <option value="Male" <?= $resident['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $resident['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= $resident['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="mb-3"><label>Contact</label><input type="text" name="contact_no" class="form-control" value="<?= htmlspecialchars($resident['contact_no']) ?>"></div>
            <div class="mb-3"><label>Address</label><input type="text" name="address" class="form-control" value="<?= htmlspecialchars($resident['address']) ?>"></div>
            <div class="mb-3"><label>Civil Status</label>
                <select name="civil_status" class="form-select">
                    <option value="Single" <?= $resident['civil_status'] === 'Single' ? 'selected' : '' ?>>Single</option>
                    <option value="Married" <?= $resident['civil_status'] === 'Married' ? 'selected' : '' ?>>Married</option>
                    <option value="Widowed" <?= $resident['civil_status'] === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                    <option value="Separated" <?= $resident['civil_status'] === 'Separated' ? 'selected' : '' ?>>Separated</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="admin_portal.php?page=residents" class="btn btn-secondary">Cancel</a>
        </form>

    <?php elseif ($action === 'add'): ?>
        <h5 class="card-title">Add New Resident</h5>
        <form method="POST">
            <div class="mb-3"><label>First Name</label><input type="text" name="first_name" class="form-control" required></div>
            <div class="mb-3"><label>Middle Name</label><input type="text" name="middle_name" class="form-control"></div>
            <div class="mb-3"><label>Last Name</label><input type="text" name="last_name" class="form-control" required></div>
            <div class="mb-3"><label>Birthdate</label><input type="date" name="birthdate" class="form-control"></div>
            <div class="mb-3"><label>Gender</label>
                <select name="gender" class="form-select">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="mb-3"><label>Contact</label><input type="text" name="contact_no" class="form-control"></div>
            <div class="mb-3"><label>Address</label><input type="text" name="address" class="form-control"></div>
            <div class="mb-3"><label>Civil Status</label>
                <select name="civil_status" class="form-select">
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Widowed">Widowed</option>
                    <option value="Separated">Separated</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Add Resident</button>
            <a href="admin_portal.php?page=residents" class="btn btn-secondary">Cancel</a>
        </form>
    <?php endif; ?>

    <!-- Residents list -->
    <?php if ($action === null): ?>
        <div class="mb-3 text-end">
            <a href="admin_portal.php?page=residents&action=add" class="btn btn-success">
                <i class="bi bi-person-plus"></i> Add Resident
            </a>
        </div>

        <form method="GET" class="row g-2 mb-3">
            <input type="hidden" name="page" value="residents">
            <div class="col-auto">
                <input type="text" name="q" class="form-control" placeholder="Search by name or address"
                       value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary">Search</button>
            </div>
            <div class="col-auto ms-auto">
                <a href="admin_portal.php?page=residents" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <?php
        if (!$resList) {
            echo "<div class='alert alert-danger'>Database error: " . $conn->error . "</div>";
        } elseif ($resList->num_rows == 0) {
            echo "<div class='alert alert-warning'>No residents found.</div>";
        } else {
            ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Birthdate</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 1;
                    while ($row = $resList->fetch_assoc()) {
                        $fullname = htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
                        echo "<tr>
                                <td>{$i}</td>
                                <td>{$fullname}</td>
                                <td>" . htmlspecialchars($row['gender']) . "</td>
                                <td>" . htmlspecialchars($row['birthdate']) . "</td>
                                <td>" . htmlspecialchars($row['address']) . "</td>
                                <td>" . htmlspecialchars($row['contact_no']) . "</td>
                                <td>" . htmlspecialchars($row['civil_status']) . "</td>
                                <td>
                                    <a class='btn btn-sm btn-outline-primary' href='admin_portal.php?page=residents&action=view&id={$row['resident_id']}'>View</a>
                                    <a class='btn btn-sm btn-outline-warning' href='admin_portal.php?page=residents&action=edit&id={$row['resident_id']}'>Edit</a>
                                    <a class='btn btn-sm btn-outline-danger' href='admin_portal.php?page=residents&action=delete&id={$row['resident_id']}' onclick=\"return confirm('Delete this resident?');\">Delete</a>
                                </td>
                              </tr>";
                        $i++;
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    <?php endif; ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
