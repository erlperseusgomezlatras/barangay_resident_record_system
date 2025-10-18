<?php
require_once './db_connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// --- Date filter ---
$from_date = $_GET['from_date'] ?? date('Y-01-01');
$to_date = $_GET['to_date'] ?? date('Y-12-31');

// --- Current totals ---
$residentsCount = $conn->query("SELECT COUNT(*) as total FROM residents WHERE created_at BETWEEN '$from_date' AND '$to_date'")->fetch_assoc()['total'];
$householdsCount = $conn->query("SELECT COUNT(*) as total FROM households WHERE created_at BETWEEN '$from_date' AND '$to_date'")->fetch_assoc()['total'];
$cedulasCount = $conn->query("SELECT COUNT(*) as total FROM cedula_records WHERE issue_date BETWEEN '$from_date' AND '$to_date'")->fetch_assoc()['total'];
$totalTaxIncome = $conn->query("SELECT SUM(amount) as total FROM cedula_records WHERE issue_date BETWEEN '$from_date' AND '$to_date'")->fetch_assoc()['total'] ?? 0;

// --- Previous year totals ---
$prevFrom = date('Y-m-d', strtotime('-1 year', strtotime($from_date)));
$prevTo = date('Y-m-d', strtotime('-1 year', strtotime($to_date)));

$prevResidents = $conn->query("SELECT COUNT(*) as total FROM residents WHERE created_at BETWEEN '$prevFrom' AND '$prevTo'")->fetch_assoc()['total'];
$prevHouseholds = $conn->query("SELECT COUNT(*) as total FROM households WHERE created_at BETWEEN '$prevFrom' AND '$prevTo'")->fetch_assoc()['total'];
$prevCedulas = $conn->query("SELECT COUNT(*) as total FROM cedula_records WHERE issue_date BETWEEN '$prevFrom' AND '$prevTo'")->fetch_assoc()['total'];
$prevTaxIncome = $conn->query("SELECT SUM(amount) as total FROM cedula_records WHERE issue_date BETWEEN '$prevFrom' AND '$prevTo'")->fetch_assoc()['total'] ?? 0;

// --- Growth calculation ---
function growthRate($current, $previous) {
    if ($previous == 0) $previous = 1; // prevent division by zero
    return round((($current - $previous)/$previous)*100,2);
}

$residentsRate = growthRate($residentsCount, $prevResidents);
$householdsRate = growthRate($householdsCount, $prevHouseholds);
$cedulasRate = growthRate($cedulasCount, $prevCedulas);
$taxRate = growthRate($totalTaxIncome, $prevTaxIncome);

// --- Residents per zone ---
$zoneData = [];
for ($i=1;$i<=10;$i++) {
    $sql = $conn->query("SELECT COUNT(*) as total FROM residents WHERE address LIKE '%Zone $i%'");
    $zoneData[] = $sql ? (int)$sql->fetch_assoc()['total'] : 0;
}

// --- Monthly cedula payments ---
$months = [];
$payments = [];
$start = new DateTime($from_date);
$end = new DateTime($to_date);
$end->modify('first day of next month');
$period = new DatePeriod($start, new DateInterval('P1M'), $end);

foreach ($period as $dt) {
    $monthStart = $dt->format("Y-m-01");
    $monthEnd = $dt->format("Y-m-t");
    $sql = $conn->query("SELECT SUM(amount) as total FROM cedula_records WHERE issue_date BETWEEN '$monthStart' AND '$monthEnd'");
    $total = $sql->fetch_assoc()['total'] ?? 0;
    $months[] = $dt->format('F');
    $payments[] = $total;
}

// --- Residents vs Cedula paid ---
$cedulaPaidResidents = $conn->query("SELECT COUNT(DISTINCT resident_id) as total FROM cedula_records")->fetch_assoc()['total'] ?? 0;

// --- Household Members Gender Distribution ---
$genderCounts = $conn->query("SELECT r.gender, COUNT(*) as total FROM residents r
JOIN household_members hm ON r.resident_id = hm.resident_id
GROUP BY r.gender")->fetch_all(MYSQLI_ASSOC);

$genderLabels = [];
$genderData = [];
foreach($genderCounts as $row){
    $genderLabels[] = $row['gender'];
    $genderData[] = (int)$row['total'];
}

// --- Household Members by Relationship ---
$relationshipCounts = $conn->query("SELECT relationship, COUNT(*) as total FROM household_members GROUP BY relationship")->fetch_all(MYSQLI_ASSOC);

$relLabels = [];
$relData = [];
foreach($relationshipCounts as $row){
    $relLabels[] = $row['relationship'];
    $relData[] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.card-summary { border-left:5px solid #198754; }
.card-summary h5 { margin-bottom:0.5rem; }
.card-summary small { font-size:0.9rem; color:#555; }
</style>
</head>
<body>
<div class="container-fluid my-4">
    <h2 class="mb-4">Dashboard</h2>

    <!-- Date filter -->
    <form class="row g-3 mb-4" method="GET">
        <div class="col-md-3">
            <label>From</label>
            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
        </div>
        <div class="col-md-3">
            <label>To</label>
            <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
        </div>
        <div class="col-md-3 align-self-end">
            <button class="btn btn-success">Filter</button>
        </div>
    </form>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['title'=>'Total Residents', 'value'=>$residentsCount, 'rate'=>$residentsRate],
            ['title'=>'Total Households', 'value'=>$householdsCount, 'rate'=>$householdsRate],
            ['title'=>'Total Cedulas', 'value'=>$cedulasCount, 'rate'=>$cedulasRate],
            ['title'=>'Total Tax Income', 'value'=>'₱'.number_format($totalTaxIncome,2), 'rate'=>$taxRate]
        ];
        foreach($cards as $card):
        ?>
        <div class="col-md-3">
            <div class="card card-summary shadow-sm p-3">
                <h5><?= $card['title'] ?></h5>
                <h3><?= $card['value'] ?></h3>
                <small>
                <?= ($card['rate']>=0 ? '📈 +' : '📉 ').$card['rate'].'% from last year' ?>
                </small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts -->
    <div class="row g-4">

        <!-- Residents per Zone -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5>Residents per Zone</h5>
                <canvas id="zoneChart"></canvas>
            </div>
        </div>

        <!-- Monthly Tax Payments -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5>Monthly Tax/Cedula Payments</h5>
                <canvas id="paymentChart"></canvas>
            </div>
        </div>

        <!-- Residents vs Cedula Paid -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5>Residents vs Cedula Paid</h5>
                <canvas id="resCedulaChart"></canvas>
            </div>
        </div>

        <!-- Household Members Gender -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5>Household Members by Gender</h5>
                <canvas id="genderChart"></canvas>
            </div>
        </div>

        <!-- Household Members by Relationship -->
        <div class="col-md-12">
            <div class="card shadow-sm p-3">
                <h5>Household Members by Relationship</h5>
                <canvas id="relationshipChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
// Zone Chart
new Chart(document.getElementById('zoneChart'), {
    type: 'bar',
    data: {
        labels: ['Zone 1','Zone 2','Zone 3','Zone 4','Zone 5','Zone 6','Zone 7','Zone 8','Zone 9','Zone 10'],
        datasets: [{label:'Residents',data: <?= json_encode($zoneData) ?>,backgroundColor:'rgba(25,135,84,0.7)',borderColor:'rgba(25,135,84,1)',borderWidth:1}]
    },
    options: { responsive:true, scales:{y:{beginAtZero:true}} }
});

// Monthly Tax Chart
new Chart(document.getElementById('paymentChart'), {
    type: 'line',
    data: {labels: <?= json_encode($months) ?>, datasets:[{label:'Tax Payments (PHP)',data: <?= json_encode($payments) ?>,backgroundColor:'rgba(25,135,84,0.2)',borderColor:'rgba(25,135,84,1)',borderWidth:2,fill:true,tension:0.4}]},
    options: { responsive:true, scales:{y:{beginAtZero:true}} }
});

// Residents vs Cedula Paid
new Chart(document.getElementById('resCedulaChart'), {
    type: 'doughnut',
    data: {
        labels:['Paid Cedula','Not Paid'],
        datasets:[{data:[<?= $cedulaPaidResidents ?>, <?= $residentsCount - $cedulaPaidResidents ?>], backgroundColor:['#198754','#ffc107'] }]
    },
    options:{ responsive:true }
});

// Household Members Gender
new Chart(document.getElementById('genderChart'), {
    type: 'pie',
    data: { labels: <?= json_encode($genderLabels) ?>, datasets:[{data: <?= json_encode($genderData) ?>, backgroundColor:['#0d6efd','#dc3545','#6c757d']] },
    options:{ responsive:true }
});

// Household Members Relationship
new Chart(document.getElementById('relationshipChart'), {
    type: 'bar',
    data: { labels: <?= json_encode($relLabels) ?>, datasets:[{label:'Members', data: <?= json_encode($relData) ?>, backgroundColor:'rgba(25,135,84,0.7)', borderColor:'rgba(25,135,84,1)', borderWidth:1}]},
    options:{ responsive:true, scales:{y:{beginAtZero:true}} }
});
</script>
</body>
</html>
