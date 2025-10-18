<?php
require_once './db_connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$search_query = $_GET['search'] ?? '';
$from_datetime = $_GET['from_datetime'] ?? '';
$to_datetime = $_GET['to_datetime'] ?? '';
$page_num = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$logs_per_page = 10;
$offset = ($page_num - 1) * $logs_per_page;

$where = "1";

// --- Search filter ---
if($search_query != '') {
    $search_safe = $conn->real_escape_string($search_query);
    $where .= " AND (u.username LIKE '%$search_safe%' OR a.action_desc LIKE '%$search_safe%')";
}

// --- DateTime filter ---
if($from_datetime != '' && $to_datetime != '') {
    $from_safe = $conn->real_escape_string($from_datetime);
    $to_safe = $conn->real_escape_string($to_datetime);
    $where .= " AND a.created_at BETWEEN '$from_safe' AND '$to_safe'";
}

// --- Count total logs for pagination ---
$total_logs = $conn->query("
    SELECT COUNT(*) as total 
    FROM audit_log a
    LEFT JOIN users u ON a.user_id = u.user_id
    WHERE $where
")->fetch_assoc()['total'];

$total_pages = ceil($total_logs / $logs_per_page);

// --- Fetch logs ---
$q = $conn->query("
    SELECT a.*, u.username
    FROM audit_log a
    LEFT JOIN users u ON a.user_id = u.user_id
    WHERE $where
    ORDER BY a.created_at DESC
    LIMIT $logs_per_page OFFSET $offset
");
?>

<!-- Page Title + Breadcrumb -->
<div class="pagetitle">
    <h1>Audit Logs</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_portal.php">Home</a></li>
            <li class="breadcrumb-item active">Audit Logs</li>
        </ol>
    </nav>
</div>

<div class="card">
  <div class="card-body">

    <!-- Filters -->
    <form method="GET" class="row g-3 mb-4 align-items-end">
        <input type="hidden" name="page" value="audit">

        <div class="col-md-4">
            <label>Search</label>
            <input type="text" name="search" class="form-control" placeholder="User or action" value="<?= htmlspecialchars($search_query) ?>">
        </div>

        <div class="col-md-3">
            <label>From</label>
            <input type="datetime-local" name="from_datetime" class="form-control" value="<?= htmlspecialchars($from_datetime) ?>">
        </div>

        <div class="col-md-3">
            <label>To</label>
            <input type="datetime-local" name="to_datetime" class="form-control" value="<?= htmlspecialchars($to_datetime) ?>">
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a href="admin_portal.php?page=audit" class="btn btn-outline-secondary w-100 mt-1">Reset</a>
        </div>
    </form>

    <!-- Logs Table -->
    <?php if ($q && $q->num_rows > 0): ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-success">
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Action Type</th>
              <th>Description</th>
              <th>Target Table</th>
              <th>Target ID</th>
              <th>Timestamp</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = $offset + 1; while ($row = $q->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($row['action_type']) ?></td>
                <td><?= htmlspecialchars($row['action_desc']) ?></td>
                <td><?= htmlspecialchars($row['target_table']) ?></td>
                <td><?= htmlspecialchars($row['target_id']) ?></td>
                <td><?= date("F d, Y h:i A", strtotime($row['created_at'])) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center mt-3">
          <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <li class="page-item <?= $p == $page_num ? 'active' : '' ?>">
              <a class="page-link" href="?page=audit&page_num=<?= $p ?>&search=<?= urlencode($search_query) ?>&from_datetime=<?= $from_datetime ?>&to_datetime=<?= $to_datetime ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php else: ?>
      <div class="alert alert-info">No logs available for the selected filter.</div>
    <?php endif; ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
