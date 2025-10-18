<?php
// admin_module/households.php
if(!isset($conn)) {
    die("Database connection not found.");
}

if(session_status() === PHP_SESSION_NONE) session_start();

// -------------------- HELPER FUNCTIONS --------------------
function e($str) { return htmlspecialchars($str); }

function add_audit_log($conn, $user_id, $action_type, $action_desc, $target_table, $target_id) {
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action_type, action_desc, target_table, target_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $user_id, $action_type, $action_desc, $target_table, $target_id);
    $stmt->execute();
    $stmt->close();
}

$current_user = $_SESSION['user_id'] ?? 1; // fallback if not in session

$action = $_GET['action'] ?? '';
$search_query = $_GET['search'] ?? '';

// -------------------- DELETE --------------------
if($action == 'delete') {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM households WHERE household_id = $id");
    add_audit_log($conn, $current_user, 'Delete', "Deleted household ID $id", 'households', $id);
    echo "<script>window.location='admin_portal.php?page=households';</script>";
    exit;
}

// -------------------- SEARCH RESIDENT --------------------
$search_result = [];
if($search_query != '') {
    $search_query_safe = $conn->real_escape_string($search_query);
    $res = $conn->query("
        SELECT r.first_name, r.last_name, h.household_no, hm.relationship
        FROM residents r
        LEFT JOIN household_members hm ON r.resident_id = hm.resident_id
        LEFT JOIN households h ON hm.household_id = h.household_id
        WHERE CONCAT(r.first_name,' ',r.last_name) LIKE '%$search_query_safe%'
    ");
    while($row = $res->fetch_assoc()) $search_result[] = $row;
}

// -------------------- VIEW --------------------
if($action == 'view') {
    $id = intval($_GET['id']);
    $household = $conn->query("SELECT h.*, r.first_name, r.last_name 
                               FROM households h 
                               LEFT JOIN residents r ON h.head_id = r.resident_id 
                               WHERE h.household_id=$id")->fetch_assoc();
    $members = $conn->query("SELECT hm.*, r.first_name, r.last_name, r.gender, r.birthdate
                             FROM household_members hm
                             LEFT JOIN residents r ON hm.resident_id = r.resident_id
                             WHERE hm.household_id=$id");
    ?>
    <div class="pagetitle">
        <h1>Household Details</h1>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_portal.php">Home</a></li>
            <li class="breadcrumb-item"><a href="admin_portal.php?page=households">Households</a></li>
            <li class="breadcrumb-item active">View</li>
        </ol></nav>
    </div>
    <div class="card">
        <div class="card-body">
            <p><strong>Household No:</strong> <?= e($household['household_no']) ?></p>
            <p><strong>Address:</strong> <?= e($household['address']) ?></p>
            <p><strong>Head:</strong> <?= e($household['first_name'].' '.$household['last_name']) ?></p>
            <p><strong>Total Members:</strong> <?= e($household['total_members']) ?></p>

            <h4>Members</h4>
            <table class="table table-bordered">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Relationship</th><th>Gender</th><th>Birthdate</th></tr>
                </thead>
                <tbody>
                    <?php $i=1; while($m = $members->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= e($m['first_name'].' '.$m['last_name']) ?></td>
                            <td><?= e($m['relationship']) ?></td>
                            <td><?= e($m['gender']) ?></td>
                            <td><?= e($m['birthdate']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <a class="btn btn-secondary" href="admin_portal.php?page=households">Back to Households</a>
        </div>
    </div>
    <?php
    exit;
}

// -------------------- FETCH AVAILABLE RESIDENTS --------------------
function getAvailableResidents($conn) {
    return $conn->query("
        SELECT resident_id, first_name, last_name
        FROM residents
        WHERE resident_id NOT IN (
            SELECT resident_id FROM household_members
        )
        ORDER BY first_name
    ");
}

// -------------------- ADD --------------------
if($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $house_no = $conn->real_escape_string($_POST['house_no']);
    $address = $conn->real_escape_string($_POST['address']);
    $head_id = intval($_POST['head_id']);
    $total_members = intval($_POST['total_members']);

    $conn->query("INSERT INTO households (household_no, address, head_id, total_members) VALUES ('$house_no','$address',$head_id,$total_members)");
    $household_id = $conn->insert_id;
    add_audit_log($conn, $current_user, 'Create', "Added household ID $household_id", 'households', $household_id);

    $conn->query("INSERT INTO household_members (household_id, resident_id, relationship) VALUES ($household_id, $head_id, 'Head')");
    if(isset($_POST['members'])){
        foreach($_POST['members'] as $member_id){
            $member_id = intval($member_id);
            if($member_id != $head_id){
                $conn->query("INSERT INTO household_members (household_id, resident_id, relationship) VALUES ($household_id, $member_id, 'Other')");
            }
        }
    }

    echo "<script>window.location='admin_portal.php?page=households';</script>";
    exit;
}

// -------------------- EDIT --------------------
if($action == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_GET['id']);
    $house_no = $conn->real_escape_string($_POST['house_no']);
    $address = $conn->real_escape_string($_POST['address']);
    $head_id = intval($_POST['head_id']);
    $total_members = intval($_POST['total_members']);

    $conn->query("UPDATE households SET household_no='$house_no', address='$address', head_id=$head_id, total_members=$total_members WHERE household_id=$id");
    add_audit_log($conn, $current_user, 'Update', "Updated household ID $id", 'households', $id);

    $conn->query("DELETE FROM household_members WHERE household_id=$id AND relationship!='Head'");
    if(isset($_POST['members'])){
        foreach($_POST['members'] as $member_id){
            $member_id = intval($member_id);
            if($member_id != $head_id){
                $conn->query("INSERT INTO household_members (household_id, resident_id, relationship) VALUES ($id, $member_id, 'Other')");
            }
        }
    }
    $conn->query("UPDATE household_members SET resident_id=$head_id WHERE household_id=$id AND relationship='Head'");

    echo "<script>window.location='admin_portal.php?page=households';</script>";
    exit;
}

// -------------------- ADD/EDIT FORM --------------------
if($action == 'add' || $action == 'edit'){
    $household = ['household_no'=>'', 'address'=>'', 'head_id'=>'', 'total_members'=>1];
    $existing_members = [];
    if($action == 'edit'){
        $id = intval($_GET['id']);
        $household = $conn->query("SELECT * FROM households WHERE household_id=$id")->fetch_assoc();
        $res = $conn->query("SELECT resident_id FROM household_members WHERE household_id=$id AND relationship!='Head'");
        while($row=$res->fetch_assoc()) $existing_members[] = $row['resident_id'];
    }
    $available_residents = getAvailableResidents($conn);
    ?>
    <div class="pagetitle">
        <h1><?= $action=='add'?'Add':'Edit' ?> Household</h1>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_portal.php">Home</a></li>
            <li class="breadcrumb-item"><a href="admin_portal.php?page=households">Households</a></li>
            <li class="breadcrumb-item active"><?= ucfirst($action) ?></li>
        </ol></nav>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label>Household No</label>
                    <input type="text" name="house_no" class="form-control" value="<?= e($household['household_no']) ?>" required>
                </div>
                <div class="mb-3">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" value="<?= e($household['address']) ?>" required>
                </div>
                <div class="mb-3">
                    <label>Head of Household</label>
                    <select id="head_id" name="head_id" class="form-control" required>
                        <option value="">Select Head</option>
                        <?php
                        $available_residents->data_seek(0);
                        while($r = $available_residents->fetch_assoc()):
                            $selected = ($r['resident_id']==$household['head_id']) ? 'selected':'';
                        ?>
                            <option value="<?= $r['resident_id'] ?>" <?= $selected ?>><?= e($r['first_name'].' '.$r['last_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Total Members</label>
                    <input type="number" name="total_members" id="total_members" class="form-control" min="1" value="<?= e($household['total_members']) ?>" required>
                </div>
                <div class="mb-3" id="members_container"></div>
                <button class="btn btn-primary"><?= $action=='add'?'Save':'Update' ?> Household</button>
            </form>
        </div>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script>
    const totalInput = document.getElementById('total_members');
    const container = document.getElementById('members_container');
    const headSelect = document.getElementById('head_id');
    const existingMembers = <?= json_encode($existing_members) ?>;
    const residents = <?= json_encode(getAvailableResidents($conn)->fetch_all(MYSQLI_ASSOC)) ?>;

    function renderMembers() {
        const total = parseInt(totalInput.value)-1;
        container.innerHTML='';
        for(let i=0;i<total;i++){
            let div = document.createElement('div');
            div.classList.add('mb-2');
            let options = `<option value="">Select Resident</option>`;
            for(let r of residents){
                if(r.resident_id==headSelect.value) continue; 
                options+=`<option value="${r.resident_id}" ${existingMembers[i]==r.resident_id?'selected':''}>${r.first_name} ${r.last_name}</option>`;
            }
            div.innerHTML=`<label>Member ${i+1}</label>
                           <select name="members[]" class="form-control member-select" required>${options}</select>`;
            container.appendChild(div);
        }
        $('.member-select').select2({width:'100%'});
    }
    totalInput.addEventListener('input',renderMembers);
    headSelect.addEventListener('change',renderMembers);
    renderMembers();
    </script>
    <?php
    exit;
}

// -------------------- LIST HOUSEHOLDS --------------------
?>
<div class="pagetitle">
    <h1>Households</h1>
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="admin_portal.php">Home</a></li>
        <li class="breadcrumb-item active">Households</li>
    </ol></nav>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Household List</h5>

        <!-- Search -->
        <form method="GET" class="mb-3">
            <input type="hidden" name="page" value="households">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search resident by name" value="<?= e($search_query) ?>">
                <button class="btn btn-primary">Search</button>
            </div>
        </form>

        <?php if($search_query != ''): ?>
            <?php if(count($search_result)>0): ?>
            <table class="table table-bordered mb-3">
                <thead>
                    <tr><th>Name</th><th>Household No</th><th>Relationship</th></tr>
                </thead>
                <tbody>
                    <?php foreach($search_result as $r): ?>
                        <tr>
                            <td><?= e($r['first_name'].' '.$r['last_name']) ?></td>
                            <td><?= e($r['household_no'] ?? '-') ?></td>
                            <td><?= e($r['relationship'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="alert alert-warning">No resident found.</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        $q = $conn->query("SELECT h.household_id, h.household_no, h.address, h.total_members,
                                  r.first_name, r.last_name
                           FROM households h
                           LEFT JOIN residents r ON h.head_id = r.resident_id
                           ORDER BY h.household_no");
        ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>#</th><th>Household No</th><th>Address</th><th>Head</th><th>Total Members</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while($row = $q->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= e($row['household_no']) ?></td>
                        <td><?= e($row['address']) ?></td>
                        <td><?= e($row['first_name'].' '.$row['last_name']) ?></td>
                        <td><?= e($row['total_members']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="admin_portal.php?page=households&action=view&id=<?= $row['household_id'] ?>">View</a>
                            <a class="btn btn-sm btn-outline-success" href="admin_portal.php?page=households&action=edit&id=<?= $row['household_id'] ?>">Edit</a>
                            <a class="btn btn-sm btn-outline-danger" href="admin_portal.php?page=households&action=delete&id=<?= $row['household_id'] ?>" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <a class="btn btn-primary mt-3" href="admin_portal.php?page=households&action=add">Add New Household</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
