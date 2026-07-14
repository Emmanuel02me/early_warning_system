<?php
require_once 'config.php';

$db = getDBConnection();

$totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeStudents = $db->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetchColumn();

try{
    $attendance = $db->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
}catch(Exception $e){
    $attendance = 0;
}

try{
    $scores = $db->query("SELECT COUNT(*) FROM assessment_scores")->fetchColumn();
}catch(Exception $e){
    $scores = 0;
}

try{
    $warnings = $db->query("SELECT COUNT(*) FROM risk_flags")->fetchColumn();
}catch(Exception $e){
    $warnings = 0;
}
?>
<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<title>School Academic Performance Early Warning System</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Segoe UI,Arial,sans-serif;
}

body{
background:#eef3f8;
}

.sidebar{
position:fixed;
left:0;
top:0;
width:240px;
height:100%;
background:#0f766e;
padding-top:20px;
}

.sidebar h2{
color:white;
text-align:center;
margin-bottom:30px;
font-size:20px;
}

.sidebar a{
display:block;
padding:15px 25px;
color:white;
text-decoration:none;
transition:.3s;
}

.sidebar a:hover{
background:#115e59;
padding-left:35px;
}

.main{
margin-left:240px;
padding:30px;
}

.header{
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 3px 12px rgba(0,0,0,.1);
margin-bottom:25px;
}

.header h1{
color:#0f766e;
}

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:20px;
margin-bottom:30px;
}

.card{
background:white;
padding:25px;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,.1);
text-align:center;
transition:.3s;
}

.card:hover{
transform:translateY(-5px);
}

.card h2{
font-size:36px;
color:#0f766e;
margin-bottom:10px;
}

.card p{
color:#666;
}

.modules{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:20px;
}

.module{
background:white;
padding:25px;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,.1);
text-align:center;
}

.module h3{
margin-bottom:15px;
color:#0f766e;
}

.module a{
display:inline-block;
padding:10px 20px;
background:#0f766e;
color:white;
text-decoration:none;
border-radius:6px;
}

.footer{
margin-top:40px;
text-align:center;
color:#777;
}

</style>

</head>

<body>

<div class="sidebar">

<h2>🎓 SAPEWS</h2>

<a href="index.php">🏠 Dashboard</a>
<a href="student_management.php">👨‍🎓 Students</a>
<a href="attendance_form.php">📝 Attendance</a>
<a href="dashboard.php">⚠ Early Warning</a>
<a href="#">📊 Performance</a>
<a href="#">📄 Reports</a>

</div>

<div class="main">

<div class="header">

<h1>School Academic Performance Early Warning System</h1>

<p>Welcome to the Administration Dashboard</p>

</div>

<div class="cards">

<div class="card">
<h2><?= $totalStudents ?></h2>
<p>Total Students</p>
</div>

<div class="card">
<h2><?= $activeStudents ?></h2>
<p>Active Students</p>
</div>

<div class="card">
<h2><?= $attendance ?></h2>
<p>Attendance Records</p>
</div>

<div class="card">
<h2><?= $scores ?></h2>
<p>Assessment Scores</p>
</div>

<div class="card">
<h2><?= $warnings ?></h2>
<p>Risk Alerts</p>
</div>

</div>

<h2 style="margin-bottom:20px;color:#0f766e;">System Modules</h2>

<div class="modules">

<div class="module">
<h3>Student Management</h3>
<a href="student_management.php">Open</a>
</div>

<div class="module">
<h3>Attendance</h3>
<a href="attendance_form.php">Open</a>
</div>

<div class="module">
<h3>Early Warning Dashboard</h3>
<a href="dashboard.php">Open</a>
</div>

<div class="module">
<h3>Machine Learning Predictions</h3>
<a href="dashboard.php">View</a>
</div>

</div>

<div class="footer">

School Academic Performance Early Warning System © <?= date("Y") ?>

</div>

</div>

</body>
</html>