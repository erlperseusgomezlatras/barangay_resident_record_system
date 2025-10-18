<?php
if(!isset($conn)) die("Database connection not found.");

if(session_status() === PHP_SESSION_NONE) session_start();
$current_user = $_SESSION['user_id'] ?? 1;

function e($str) { return htmlspecialchars($str); }

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

// -------------------- PROCESS REQUEST --------------------
if($action == 'process_request' && isset($_GET['request_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_GET['request_id']);
    $req = $conn->query("SELECT * FROM requests WHERE request_id=$request_id")->fetch_assoc();

    if($req) {
        $resident_id = $req['resident_id'];
        $amount = floatval($_POST['amount']); 
        $cedula_no = 'CED-'.str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
        $issue_date = date('Y-m-d');

        $conn->query("INSERT INTO cedula_records (resident_id, issued_by, cedula_no, issue_date, amount, request_id) 
                      VALUES ($resident_id, $current_user, '$cedula_no', '$issue_date', $amount, $request_id)");
        $cedula_id = $conn->insert_id;

        $conn->query("UPDATE requests SET status='Approved' WHERE request_id=$request_id");

        add_audit_log($conn, $current_user, 'Process', "Processed Cedula request ID $request_id → Cedula ID $cedula_id", 'cedula_records', $cedula_id);

        echo "<script>alert('Cedula request processed successfully!');window.location='admin_portal.php?page=cedulas';</script>";
        exit;
    }
}

// -------------------- ADD/EDIT --------------------
if($action == 'add' || $action == 'edit') {
    $cedula = ['resident_id'=>'','amount'=>'0.00','issue_date'=>date('Y-m-d')];
    if($action=='edit') {
        $id = intval($_GET['id']);
        $cedula = $conn->query("SELECT * FROM cedula_records WHERE cedula_id=$id")->fetch_assoc();
    }

    $residents = $conn->query("SELECT * FROM residents ORDER BY first_name");
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
                    <input type="number" name="amount" step="0.01" class="form-control" value="<?= number_format(floatval($cedula['amount']),2) ?>" required>
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

// -------------------- FETCH RECORDS --------------------
$cedula_q = $conn->query("
    SELECT c.*, r.first_name, r.last_name, u.username as issued_by_name
    FROM cedula_records c
    LEFT JOIN residents r ON c.resident_id=r.resident_id
    LEFT JOIN users u ON c.issued_by=u.user_id
    ORDER BY c.cedula_id DESC
");

$pending_requests = $conn->query("
    SELECT req.request_id, req.resident_id, req.request_date, r.first_name, r.last_name
    FROM requests req
    LEFT JOIN residents r ON req.resident_id=r.resident_id
    WHERE req.request_type='Cedula' AND req.status='Pending'
    ORDER BY req.request_date ASC
");
?>

<div class="pagetitle">
    <h1>Cedulas</h1>
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="admin_portal.php">Home</a></li>
        <li class="breadcrumb-item active">Cedulas</li>
    </ol></nav>
</div>

<!-- Pending Requests -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Pending Cedula Requests</h5>
        <?php if($pending_requests->num_rows==0): ?>
            <div class="alert alert-info">No pending requests.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-warning">
                    <tr>
                        <th>#</th>
                        <th>Resident</th>
                        <th>Request Date</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while($req=$pending_requests->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= e($req['first_name'].' '.$req['last_name']) ?></td>
                        <td><?= e($req['request_date']) ?></td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px;" action="admin_portal.php?page=cedulas&action=process_request&request_id=<?= $req['request_id'] ?>">
                                <input type="number" step="0.01" name="amount" value="0.00" class="form-control form-control-sm" required>
                                <button class="btn btn-sm btn-success" onclick="return confirm('Confirm payment and issue Cedula?')">Confirm</button>
                            </form>
                        </td>
                        <td></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Issued Cedulas -->
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Issued Cedulas</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Cedula No</th>
                        <th>Resident</th>
                        <th>Amount</th>
                        <th>Issue Date</th>
                        <th>Issued By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while($row=$cedula_q->fetch_assoc()): ?>
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
