<?php
require_once './db_connect.php';

if(session_status() === PHP_SESSION_NONE) session_start();

// Get current logged-in user
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../login.php");
    exit();
}

// Get resident_id from users table
$resident_stmt = $conn->prepare("SELECT resident_id FROM users WHERE user_id = ?");
$resident_stmt->bind_param("i", $user_id);
$resident_stmt->execute();
$resident_result = $resident_stmt->get_result();
$resident = $resident_result->fetch_assoc();
$resident_id = $resident['resident_id'] ?? null;

// Handle new request submission
$success_msg = $error_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_type'])) {
    $request_type = $_POST['request_type'];
    $purpose = $_POST['purpose'] ?? '';

    if ($resident_id && $request_type && $purpose) {
        $stmt = $conn->prepare("INSERT INTO requests (resident_id, request_type, purpose, status, request_date) VALUES (?, ?, ?, 'Pending', NOW())");
        $stmt->bind_param("iss", $resident_id, $request_type, $purpose);
        if ($stmt->execute()) {
            $success_msg = "Request submitted successfully and is pending approval!";
        } else {
            $error_msg = "Failed to submit request.";
        }
        $stmt->close();
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// Fetch resident requests
$requests_stmt = $conn->prepare("
    SELECT request_id, request_type, purpose, status, request_date
    FROM requests
    WHERE resident_id = ?
    ORDER BY request_date DESC
");
$requests_stmt->bind_param("i", $resident_id);
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();
?>

<div class="pagetitle">
    <h1>Residents</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="resident_portal.php">Home</a></li>
            <li class="breadcrumb-item active">Requests</li>
        </ol>
    </nav>
</div>

<?php if ($success_msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#requestModal">
        <i class="bi bi-plus-circle me-1"></i> Request New Cedula
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
        <h5 class="card-title text-success fw-bold mb-3"><i class="bi bi-hourglass-split me-2"></i>Pending / Completed Requests</h5>
        <table class="table table-hover align-middle">
            <thead class="table-success">
                <tr>
                    <th>#</th>
                    <th>Certificate Type</th>
                    <th>Purpose</th>
                    <th>Date Requested</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if($requests_result->num_rows == 0): ?>
                    <tr>
                        <td colspan="5" class="text-center">No requests found.</td>
                    </tr>
                <?php else: $i=1; while($req = $requests_result->fetch_assoc()): ?>
                    <?php
                        $status_class = 'bg-secondary text-white';
                        switch(strtolower($req['status'])) {
                            case 'pending':
                                $status_class = 'bg-warning text-dark';
                                break;
                            case 'processing':
                                $status_class = 'bg-info text-white';
                                break;
                            case 'approved':
                            case 'completed':
                                $status_class = 'bg-success text-white';
                                break;
                            case 'rejected':
                                $status_class = 'bg-danger text-white';
                                break;
                        }
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($req['request_type']) ?></td>
                        <td><?= htmlspecialchars($req['purpose']) ?></td>
                        <td><?= date('M d, Y', strtotime($req['request_date'])) ?></td>
                        <td><span class="badge <?= $status_class ?>"><?= ucfirst($req['status']) ?></span></td>
                    </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="requestModalLabel">Request New Cedula</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="mb-2">
              <label class="form-label small">Certificate Type</label>
              <input type="text" class="form-control form-control-sm" name="request_type" value="Cedula" readonly>
          </div>
          <div class="mb-2">
              <label class="form-label small">Purpose</label>
              <input type="text" class="form-control form-control-sm" name="purpose" placeholder="Enter purpose..." required>
          </div>
      </div>
      <div class="modal-footer py-2">
        <button type="submit" class="btn btn-success btn-sm">Submit Request</button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

