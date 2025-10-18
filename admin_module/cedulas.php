<?php
// admin_module/cedulas.php
if(!isset($conn)) {
    die("Database connection not found.");
}

if(session_status() === PHP_SESSION_NONE) session_start();
$current_user = $_SESSION['user_id'] ?? 1; // fallback if not logged in

// Escape output
function e($str) { return htmlspecialchars($str); }

// Audit logging function
function add_audit_log($conn, $user_id, $action_type, $action_desc, $target_table, $target_id) {
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action_type, action_desc, target_table, target_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $user_id, $action_type, $action_desc, $target_table, $target_id);
    $stmt->execute();
    $stmt->close();
}

$action = $_GET['action'] ?? '';
$search_query = $_GET['search'] ?? '';

// -------------------- DELETE --------------------
if($action == 'delete') {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM cedula_records WHERE cedula_id=$id");
    add_audit_log($conn, $current_user, 'Delete', "Deleted cedula ID $id", 'cedula_records', $id);
    echo "<script>window.location='admin_portal.php?page=cedulas';</script>";
    exit;
}

// -------------------- ADD --------------------
if($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $resident_id = intval($_POST['resident_id']);
    $amount = floatval($_POST['amount']);
    $issued_by = $current_user;
    $cedula_no = 'CED-'.str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
    $issue_date = $_POST['issue_date'] ?? date('Y-m-d');

    $conn->query("INSERT INTO cedula_records (resident_id, issued_by, cedula_no, issue_date, amount) 
                  VALUES ($resident_id, $issued_by, '$cedula_no', '$issue_date', $amount)");
    $cedula_id = $conn->insert_id;

    add_audit_log($conn, $current_user, 'Create', "Added cedula ID $cedula_id for resident ID $resident_id", 'cedula_records', $cedula_id);

    echo "<script>window.location='admin_portal.php?page=cedulas';</script>";
    exit;
}

// -------------------- EDIT --------------------
if($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_GET['id']);
    $resident_id = intval($_POST['resident_id']);
    $amount = floatval($_POST['amount']);
    $issue_date = $_POST['issue_date'];

    $conn->query("UPDATE cedula_records SET resident_id=$resident_id, amount=$amount, issue_date='$issue_date' WHERE cedula_id=$id");
    add_audit_log($conn, $current_user, 'Update', "Updated cedula ID $id", 'cedula_records', $id);

    echo "<script>window.location='admin_portal.php?page=cedulas';</script>";
    exit;
}

// -------------------- FETCH AVAILABLE RESIDENTS --------------------
$residents = $conn->query("SELECT * FROM residents ORDER BY first_name");

// -------------------- ADD/EDIT FORM --------------------
if($action=='add' || $action=='edit') {
    $cedula = ['resident_id'=>'','amount'=>'','issue_date'=>date('Y-m-d')];
    if($action=='edit') {
        $id = intval($_GET['id']);
        $cedula = $conn->query("SELECT * FROM cedula_records WHERE cedula_id=$id")->fetch_assoc();
    }
    ?>
    <div class="pagetitle">
        <h1><?= $action=='add'?'Add':'Edit' ?> Cedula</h1>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_portal.php">Home</a></li>
            <li class="breadcrumb-item"><a href="admin_portal.php?page=cedulas">Cedulas</a></li>
            <li class="breadcrumb-item active"><?= ucfirst($action) ?></li>
        </ol></nav>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label>Resident</label>
                    <select name="resident_id" class="form-control" required>
                        <option value="">Select Resident</option>
                        <?php while($r = $residents->fetch_assoc()): ?>
                            <option value="<?= $r['resident_id'] ?>" <?= ($r['resident_id']==$cedula['resident_id']?'selected':'') ?>>
                                <?= e($r['first_name'].' '.$r['last_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Amount</label>
                    <input type="number" name="amount" step="0.01" class="form-control" value="<?= e($cedula['amount']) ?>" required>
                </div>

                <div class="mb-3">
                    <label>Issue Date</label>
                    <input type="date" name="issue_date" class="form-control" value="<?= e($cedula['issue_date']) ?>" required>
                </div>

                <button class="btn btn-primary"><?= $action=='add'?'Add':'Update' ?> Cedula</button>
            </form>
        </div>
    </div>
    <?php
    exit;
}

// -------------------- SEARCH --------------------
$search_result = [];
if($search_query != '') {
    $search_query_safe = $conn->real_escape_string($search_query);
    $res = $conn->query("
        SELECT c.*, r.first_name, r.last_name
        FROM cedula_records c
        LEFT JOIN residents r ON c.resident_id=r.resident_id
        WHERE CONCAT(r.first_name,' ',r.last_name) LIKE '%$search_query_safe%'
    ");
    while($row=$res->fetch_assoc()) $search_result[] = $row;
}

// -------------------- LIST --------------------
?>
<div class="pagetitle">
    <h1>Cedulas</h1>
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="admin_portal.php">Home</a></li>
        <li class="breadcrumb-item active">Cedulas</li>
    </ol></nav>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Cedula List</h5>

        <!-- Search -->
        <form method="GET" class="mb-3">
            <input type="hidden" name="page" value="cedulas">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search resident by name" value="<?= e($search_query) ?>">
                <button class="btn btn-primary">Search</button>
            </div>
        </form>

        <?php if($search_query != '' && count($search_result)==0): ?>
            <div class="alert alert-warning">No resident found.</div>
        <?php endif; ?>

        <?php
        $q = $conn->query("
            SELECT c.*, r.first_name, r.last_name, u.username as issued_by_name
            FROM cedula_records c
            LEFT JOIN residents r ON c.resident_id=r.resident_id
            LEFT JOIN users u ON c.issued_by=u.user_id
            ORDER BY c.cedula_id DESC
        ");
        ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>#</th><th>Cedula No</th><th>Resident</th><th>Amount</th><th>Issue Date</th><th>Issued By</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while($row=$q->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= e($row['cedula_no']) ?></td>
                        <td><?= e($row['first_name'].' '.$row['last_name']) ?></td>
                        <td><?= number_format($row['amount'],2) ?></td>
                        <td><?= e($row['issue_date']) ?></td>
                        <td><?= e($row['issued_by_name']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-success" href="admin_portal.php?page=cedulas&action=edit&id=<?= $row['cedula_id'] ?>">Edit</a>
                            <a class="btn btn-sm btn-outline-danger" href="admin_portal.php?page=cedulas&action=delete&id=<?= $row['cedula_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                            <a class="btn btn-sm btn-outline-primary" target="_blank" href="generate_cedula.php?id=<?= $row['cedula_id'] ?>">Print PDF</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <a class="btn btn-primary mt-3" href="admin_portal.php?page=cedulas&action=add">Add New Cedula</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
