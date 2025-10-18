<?php
require_once './db_connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// --- AJAX handler for gender by zone ---
if(isset($_GET['ajax_gender_zone'])){
    $zone = $_GET['ajax_gender_zone'];
    $result = ['Male'=>0,'Female'=>0,'Other'=>0];
    if($zone){
        $stmt = $conn->prepare("SELECT gender, COUNT(*) as total FROM residents WHERE address LIKE ? GROUP BY gender");
        $like = "%$zone%";
        $stmt->bind_param("s",$like);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){
            $result[$row['gender']] = (int)$row['total'];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

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
    if ($previous == 0) $previous = 1;
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

// --- Residents Gender Overall ---
$genderData = $conn->query("SELECT gender, COUNT(*) as total FROM residents GROUP BY gender");
$genderCounts = ['Male'=>0,'Female'=>0,'Other'=>0];
while($row = $genderData->fetch_assoc()){
    $genderCounts[$row['gender']] = (int)$row['total'];
}

// --- Regular vs Taxpayer Residents ---
$taxPayersData = $conn->query("
    SELECT 
        SUM(CASE WHEN cedula_records.resident_id IS NULL THEN 1 ELSE 0 END) as regular,
        SUM(CASE WHEN cedula_records.resident_id IS NOT NULL THEN 1 ELSE 0 END) as taxpayer
    FROM residents
    LEFT JOIN cedula_records ON residents.resident_id = cedula_records.resident_id 
        AND cedula_records.issue_date BETWEEN '$from_date' AND '$to_date'
");
$taxRow = $taxPayersData->fetch_assoc();
$taxCounts = ['Regular'=>(int)$taxRow['regular'],'Taxpayer'=>(int)$taxRow['taxpayer']];

// --- Household Members Distribution ---
$householdMembers = $conn->query("
    SELECT total_members, COUNT(*) as households FROM households 
    GROUP BY total_members ORDER BY total_members ASC
");
$householdLabels = [];
$householdData = [];
while($row = $householdMembers->fetch_assoc()){
    $householdLabels[] = $row['total_members'].' members';
    $householdData[] = (int)$row['households'];
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
.pointer {cursor:pointer;}
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
                <small><?= ($card['rate']>=0 ? '📈 +' : '📉 ').$card['rate'].'% from last year' ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5 class="pointer" id="zoneTitle">Residents per Zone (Click bar to filter Gender)</h5>
                <canvas id="zoneChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5>Monthly Tax/Cedula Payments</h5>
                <canvas id="paymentChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h5>Gender of Residents</h5>
                <canvas id="genderChart"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h5>Regular vs Taxpayer Residents</h5>
                <canvas id="taxChart"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h5>Household Members Distribution</h5>
                <canvas id="householdChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Residents per Zone Chart
const zoneCtx = document.getElementById('zoneChart').getContext('2d');
const zoneChart = new Chart(zoneCtx, {
    type: 'bar',
    data: {
        labels: ['Zone 1','Zone 2','Zone 3','Zone 4','Zone 5','Zone 6','Zone 7','Zone 8','Zone 9','Zone 10'],
        datasets: [{
            label: 'Residents',
            data: <?= json_encode($zoneData) ?>,
            backgroundColor: 'rgba(25,135,84,0.7)',
            borderColor: 'rgba(25,135,84,1)',
            borderWidth: 1
        }]
    },
    options: { responsive:true, scales: { y: { beginAtZero:true } } }
});

// Monthly Cedula Payments Chart
const paymentCtx = document.getElementById('paymentChart').getContext('2d');
new Chart(paymentCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Tax Payments (PHP)',
            data: <?= json_encode($payments) ?>,
            backgroundColor: 'rgba(25,135,84,0.2)',
            borderColor: 'rgba(25,135,84,1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: { responsive:true, scales: { y: { beginAtZero:true } } }
});

// Gender Chart
const genderCtx = document.getElementById('genderChart').getContext('2d');
let genderChart = new Chart(genderCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($genderCounts)) ?>,
        datasets: [{
            label: 'Residents Gender',
            data: <?= json_encode(array_values($genderCounts)) ?>,
            backgroundColor: ['#198754','#0d6efd','#ffc107'],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: { responsive:true }
});

// Taxpayer Chart
const taxCtx = document.getElementById('taxChart').getContext('2d');
new Chart(taxCtx, {
    type: 'pie',
    data: {
        labels: ['Regular','Taxpayer'],
        datasets: [{
            label: 'Residents',
            data: <?= json_encode(array_values($taxCounts)) ?>,
            backgroundColor: ['#6c757d','#198754'],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: { responsive:true }
});

// Household Members Chart
const householdCtx = document.getElementById('householdChart').getContext('2d');
new Chart(householdCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($householdLabels) ?>,
        datasets: [{
            label: 'Households',
            data: <?= json_encode($householdData) ?>,
            backgroundColor: 'rgba(13,110,253,0.7)',
            borderColor: 'rgba(13,110,253,1)',
            borderWidth: 1
        }]
    },
    options: { responsive:true, scales: { y: { beginAtZero:true } } }
});

// --- Interactive Gender by Zone ---
document.getElementById('zoneChart').onclick = function(evt){
    const points = zoneChart.getElementsAtEventForMode(evt,'nearest',{intersect:true},true);
    if(points.length){
        const index = points[0].index;
        const zoneLabel = zoneChart.data.labels[index];
        fetch('<?= $_SERVER['PHP_SELF'] ?>?ajax_gender_zone='+encodeURIComponent(zoneLabel))
        .then(res=>res.json())
        .then(data=>{
            genderChart.data.datasets[0].data = [data.Male||0, data.Female||0, data.Other||0];
            genderChart.update();
            document.getElementById('zoneTitle').innerText = 'Residents Gender ('+zoneLabel+')';
        });
    }else{
        // click outside, reset to all residents
        genderChart.data.datasets[0].data = <?= json_encode(array_values($genderCounts)) ?>;
        genderChart.update();
        document.getElementById('zoneTitle').innerText = 'Residents per Zone (Click bar to filter Gender)';
    }
};
</script>
</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
