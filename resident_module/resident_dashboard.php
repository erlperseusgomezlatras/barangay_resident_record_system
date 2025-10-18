<?php
include './db_connect.php'; // make sure your DB connection is correct

// Check if resident is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Resident') {
    echo "Resident not logged in!";
    exit;
}

// Get resident_id
$user_id = $_SESSION['user_id'];
$resident_row = $conn->query("SELECT resident_id FROM users WHERE user_id = $user_id")->fetch_assoc();
$resident_id = $resident_row['resident_id'] ?? 0;

if (!$resident_id) {
    echo "Resident not linked!";
    exit;
}

// Fetch stats for this resident
$tax = $conn->query("SELECT IFNULL(SUM(amount),0) as total_tax FROM cedula_records WHERE resident_id=$resident_id")->fetch_assoc()['total_tax'];

$requests_total = $conn->query("SELECT COUNT(*) as total FROM requests WHERE resident_id=$resident_id")->fetch_assoc()['total'];
$requests_approved = $conn->query("SELECT COUNT(*) as approved FROM requests WHERE resident_id=$resident_id AND status='Approved'")->fetch_assoc()['approved'];
$cedulas_total = $conn->query("SELECT COUNT(*) as cedulas FROM cedula_records WHERE resident_id=$resident_id")->fetch_assoc()['cedulas'];

// Requests status for pie chart
$status_data = $conn->query("SELECT status, COUNT(*) as count FROM requests WHERE resident_id=$resident_id GROUP BY status");
$request_status = ['Pending'=>0,'Approved'=>0,'Rejected'=>0];
while($row = $status_data->fetch_assoc()){
    $request_status[$row['status']] = $row['count'];
}

// Requests by type for bar chart
$type_data = $conn->query("SELECT request_type, COUNT(*) as count FROM requests WHERE resident_id=$resident_id GROUP BY request_type");
$request_types = ['Cedula'=>0,'Barangay ID'=>0,'Certificate of Residency'=>0,'Other'=>0];
while($row = $type_data->fetch_assoc()){
    $request_types[$row['request_type']] = $row['count'];
}

// Cedula amount per month (Jan-Dec)
$cedula_months = array_fill(1,12,0);
$cedula_data = $conn->query("SELECT MONTH(issue_date) as month, SUM(amount) as total FROM cedula_records WHERE resident_id=$resident_id GROUP BY MONTH(issue_date)");
while($row = $cedula_data->fetch_assoc()){
    $cedula_months[intval($row['month'])] = floatval($row['total']);
}

// Requests completed vs pending for donut chart
$completed = $conn->query("SELECT COUNT(*) as total FROM requests WHERE resident_id=$resident_id AND status='Approved'")->fetch_assoc()['total'];
$pending = $conn->query("SELECT COUNT(*) as total FROM requests WHERE resident_id=$resident_id AND status='Pending'")->fetch_assoc()['total'];
$rejected = $conn->query("SELECT COUNT(*) as total FROM requests WHERE resident_id=$resident_id AND status='Rejected'")->fetch_assoc()['total'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Resident Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {background:#f5fdf8;}
.card {border-radius:15px;}
.card h5 {color:#2c6f4f;}
</style>
</head>
<body>
<div class="container my-4">
    <h3 class="fw-bold mb-4 text-success"><i class="bi bi-speedometer2 me-2"></i>Resident Dashboard</h3>
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3 bg-white">
                <h5>My Total Tax</h5>
                <h3 class="text-success">₱<?=number_format($tax,2)?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3 bg-white">
                <h5>My Requests</h5>
                <h3 class="text-success"><?=$requests_total?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3 bg-white">
                <h5>My Approved Requests</h5>
                <h3 class="text-success"><?=$requests_approved?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3 bg-white">
                <h5>My Cedulas Issued</h5>
                <h3 class="text-success"><?=$cedulas_total?></h3>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm p-3 bg-white">
                <h5>My Requests Status</h5>
                <canvas id="requestsStatusPie"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm p-3 bg-white">
                <h5>Requests by Type</h5>
                <canvas id="requestsTypeBar"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm p-3 bg-white mt-4">
                <h5>Cedula Amount Over Time</h5>
                <canvas id="cedulaLine"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm p-3 bg-white mt-4">
                <h5>Requests Completed vs Pending</h5>
                <canvas id="requestsDonut"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
const requestsStatusPie = new Chart(document.getElementById('requestsStatusPie'), {
    type: 'pie',
    data: {
        labels: ['Pending','Approved','Rejected'],
        datasets:[{
            label:'Requests Status',
            data: [<?=$request_status['Pending']?>,<?=$request_status['Approved']?>,<?=$request_status['Rejected']?>],
            backgroundColor:['#f7d794','#78e08f','#e55039']
        }]
    }
});

const requestsTypeBar = new Chart(document.getElementById('requestsTypeBar'), {
    type: 'bar',
    data: {
        labels:['Cedula','Barangay ID','Certificate of Residency','Other'],
        datasets:[{
            label:'Requests by Type',
            data:[<?=$request_types['Cedula']?>,<?=$request_types['Barangay ID']?>,<?=$request_types['Certificate of Residency']?>,<?=$request_types['Other']?>],
            backgroundColor:'#60a3bc'
        }]
    },
    options:{scales:{y:{beginAtZero:true}}}
});

const cedulaLine = new Chart(document.getElementById('cedulaLine'), {
    type:'line',
    data:{
        labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets:[{
            label:'Cedula Amount',
            data:[<?=implode(',', $cedula_months)?>],
            borderColor:'#27ae60',
            backgroundColor:'rgba(39,174,96,0.2)',
            fill:true,
            tension:0.4
        }]
    },
    options:{scales:{y:{beginAtZero:true}}}
});

const requestsDonut = new Chart(document.getElementById('requestsDonut'), {
    type:'doughnut',
    data:{
        labels:['Pending','Approved','Rejected'],
        datasets:[{
            data:[<?=$pending?>,<?=$completed?>,<?=$rejected?>],
            backgroundColor:['#f7d794','#78e08f','#e55039']
        }]
    }
});
</script>
</body>
</html>
