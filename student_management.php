<?php
require_once 'config.php';
$pageTitle = "Student Management";
include 'header.php';

$db = getDBConnection();

$success = "";
$error   = "";

/* ============================
   ADD NEW STUDENT
=============================*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {

    try {

        $registration = trim($_POST['registration_number']);
        $fullname     = trim($_POST['full_name']);
        $gender       = $_POST['gender'];
        $class        = $_POST['class_id'];
        $enrollment   = $_POST['enrollment_date'];
        $status       = $_POST['status'];

        $stmt = $db->prepare("
            INSERT INTO students
            (registration_number,full_name,gender,class_id,enrollment_date,status)
            VALUES
            (:registration,:fullname,:gender,:class,:enrollment,:status)
        ");

        $stmt->execute([
            ':registration'=>$registration,
            ':fullname'=>$fullname,
            ':gender'=>$gender,
            ':class'=>$class,
            ':enrollment'=>$enrollment,
            ':status'=>$status
        ]);

        $success="Student added successfully.";

    } catch(PDOException $e){

        $error=$e->getMessage();

    }

}

/* ============================
   SEARCH
=============================*/

$search=$_GET['search']??'';
$class=$_GET['class']??'';

$sql="SELECT * FROM students WHERE 1=1";
$params=[];

if($search!=""){
    $sql.=" AND (registration_number LIKE :search OR full_name LIKE :search)";
    $params[':search']="%".$search."%";
}

if($class!=""){
    $sql.=" AND class_id=:class";
    $params[':class']=$class;
}

$sql.=" ORDER BY full_name";

$stmt=$db->prepare($sql);
$stmt->execute($params);

$students=$stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================
   STATISTICS
=============================*/

$totalStudents=count($students);

$activeStudents=0;

$form1=0;
$form2=0;
$form3=0;
$form4=0;

foreach($students as $s){

    if($s['status']=="Active"){
        $activeStudents++;
    }

    switch($s['class_id']){

        case 1:
            $form1++;
        break;

        case 2:
            $form2++;
        break;

        case 3:
            $form3++;
        break;

        case 4:
            $form4++;
        break;
    }

}

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Student Management</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial,Helvetica,sans-serif;
}

body{
background:#eef2f7;
}

.header{

background:#0f766e;

color:#fff;

padding:20px;

}

.header h1{

font-size:28px;

}

.container{

width:95%;

margin:25px auto;

}

.cards{

display:grid;

grid-template-columns:repeat(auto-fit,minmax(180px,1fr));

gap:15px;

margin-bottom:25px;

}

.card{

background:#fff;

padding:20px;

border-radius:10px;

box-shadow:0 3px 10px rgba(0,0,0,.1);

}

.card h3{

color:#777;

font-size:14px;

margin-bottom:10px;

}

.card h2{

color:#0f766e;

font-size:28px;

}

.toolbar{

background:#fff;

padding:20px;

border-radius:10px;

box-shadow:0 3px 10px rgba(0,0,0,.1);

margin-bottom:20px;

}

.toolbar input,
.toolbar select{

padding:10px;

border:1px solid #ccc;

border-radius:6px;

margin-right:10px;

}

.btn{

padding:10px 18px;

border:none;

border-radius:6px;

cursor:pointer;

color:#fff;

}

.btn-search{

background:#0f766e;

}

.btn-add{

background:#2563eb;

float:right;

}

.success{

background:#d1fae5;

padding:12px;

border-radius:6px;

margin-bottom:15px;

color:#065f46;

}

.error{

background:#fee2e2;

padding:12px;

border-radius:6px;

margin-bottom:15px;

color:#991b1b;

}

.form-box{

display:none;

background:#fff;

padding:20px;

margin-top:20px;

border-radius:10px;

box-shadow:0 3px 10px rgba(0,0,0,.1);

}

.form-box input,
.form-box select{

width:100%;

padding:10px;

margin-top:6px;

margin-bottom:15px;

border:1px solid #ccc;

border-radius:6px;

}

.save-btn{

background:#16a34a;

padding:10px 20px;

border:none;

border-radius:6px;

color:#fff;

cursor:pointer;

}

.cancel-btn{

background:#dc2626;

padding:10px 20px;

border:none;

border-radius:6px;

color:#fff;

cursor:pointer;

}

/* ========= EDIT MODAL ========= */

.modal{
    display:none;
    position:fixed;
    z-index:9999;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,.6);
}

.modal-content{
    background:#fff;
    width:85%;
    max-width:900px;
    height:85%;
    margin:40px auto;
    border-radius:12px;
    overflow:hidden;
    position:relative;
    animation:zoom .3s;
}

.modal iframe{
    width:100%;
    height:100%;
    border:none;
}

.close-modal{
    position:absolute;
    top:12px;
    right:18px;
    font-size:30px;
    cursor:pointer;
    color:#fff;
    z-index:10000;
}

@keyframes zoom{
    from{
        transform:scale(.8);
        opacity:0;
    }
    to{
        transform:scale(1);
        opacity:1;
    }
}

</style>

</head>

<body>

<div class="header">

<h1>Student Management System</h1>

<p>School Academic Performance Early Warning System</p>

</div>

<div class="container">

<?php if($success!=""){ ?>

<div class="success">

<?= $success ?>

</div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="error">

<?= htmlspecialchars($error) ?>

</div>

<?php } ?>

<div class="cards">

<div class="card">
<h3>Total Students</h3>
<h2><?= $totalStudents ?></h2>
</div>

<div class="card">
<h3>Active Students</h3>
<h2><?= $activeStudents ?></h2>
</div>

<div class="card">
<h3>Form One</h3>
<h2><?= $form1 ?></h2>
</div>

<div class="card">
<h3>Form Two</h3>
<h2><?= $form2 ?></h2>
</div>

<div class="card">
<h3>Form Three</h3>
<h2><?= $form3 ?></h2>
</div>

<div class="card">
<h3>Form Four</h3>
<h2><?= $form4 ?></h2>
</div>

</div>

<div class="toolbar">

<form method="GET">

<input type="text" name="search" placeholder="Search Student..." value="<?= htmlspecialchars($search) ?>">

<select name="class">

<option value="">All Classes</option>

<?php
for($i=1;$i<=4;$i++){
?>
<option value="<?= $i ?>" <?=($class==$i)?'selected':'';?>>
Form <?= $i ?>
</option>
<?php } ?>

</select>

<button class="btn btn-search">Search</button>

<button type="button"
class="btn btn-add"
onclick="document.getElementById('studentForm').style.display='block'">
Add Student
</button>

</form>

</div>

<div id="studentForm" class="form-box">

<h2 style="margin-bottom:20px;color:#0f766e;">
Add New Student
</h2>

<form method="POST">

<input type="hidden" name="add_student">

<label>Registration Number</label>

<input
type="text"
name="registration_number"
required>

<label>Full Name</label>

<input
type="text"
name="full_name"
required>

<label>Gender</label>

<select name="gender">

<option value="M">Male</option>

<option value="F">Female</option>

</select>

<label>Class</label>

<select name="class_id">

<?php
for($i=1;$i<=4;$i++){
?>
<option value="<?= $i ?>">
Form <?= $i ?>
</option>
<?php } ?>

</select>

<label>Enrollment Date</label>

<input
type="date"
name="enrollment_date"
value="<?= date('Y-m-d') ?>"
required>

<label>Status</label>

<select name="status">

<option value="Active">Active</option>

<option value="Transferred">Transferred</option>

<option value="Graduated">Graduated</option>

<option value="Dropped">Dropped</option>

</select>

<button
class="save-btn"
type="submit">

Save Student

</button>

<button
class="cancel-btn"
type="button"
onclick="document.getElementById('studentForm').style.display='none'">

Cancel

</button>

</form>

</div>

<br>

<div
style="
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 3px 10px rgba(0,0,0,.1);
">

<table
style="
width:100%;
border-collapse:collapse;
">

<thead>

<tr
style="
background:#0f766e;
color:white;
">

<th style="padding:12px;">Reg Number</th>

<th>Name</th>

<th>Gender</th>

<th>Class</th>

<th>Status</th>

<th width="250">
Actions
</th>

</tr>

</thead>

<tbody>

<?php if(count($students)>0){ ?>

<?php foreach($students as $student){ ?>

<tr
style="
border-bottom:1px solid #ddd;
">

<td style="padding:12px;">
<?= htmlspecialchars($student['registration_number']) ?>
</td>

<td>
<?= htmlspecialchars($student['full_name']) ?>
</td>

<td>
<?= htmlspecialchars($student['gender']) ?>
</td>

<td>
Form <?= htmlspecialchars($student['class_id']) ?>
</td>

<td>

<?php

$color="green";

if($student['status']=="Dropped")
$color="red";

elseif($student['status']=="Transferred")
$color="orange";

elseif($student['status']=="Graduated")
$color="blue";

?>

<span
style="
background:<?= $color ?>;
color:white;
padding:5px 10px;
border-radius:20px;
font-size:13px;
">

<?= htmlspecialchars($student['status']) ?>

</span>

</td>

<td>

<a
href="view_student.php?id=<?= $student['student_id'] ?>"
style="
background:#0284c7;
color:white;
padding:8px 12px;
text-decoration:none;
border-radius:5px;
margin-right:5px;
">

View

</a>


<a
href="delete_student.php?id=<?= $student['student_id'] ?>"
onclick="return confirm('Delete this student?')"
style="
background:#dc2626;
color:white;
padding:8px 12px;
text-decoration:none;
border-radius:5px;
">

Delete

</a>

</td>

</tr>

<?php } ?>

<?php } else { ?>

<tr>

<td
colspan="6"
style="
padding:20px;
text-align:center;
">

No students found.

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<script>

function closeForm(){

    document.getElementById("studentForm").style.display="none";

}

function openEditModal(id){

    document.getElementById("editFrame").src =
        "edit_student.php?id="+id;

    document.getElementById("editModal").style.display="block";

}

function closeEditModal(){

    document.getElementById("editModal").style.display="none";

    document.getElementById("editFrame").src="";

}

window.onclick=function(e){

    var modal=document.getElementById("editModal");

    if(e.target==modal){

        closeEditModal();

    }

}

</script>

</div>

<div id="editModal" class="modal">

<span class="close-modal" onclick="closeEditModal()">
&times;
</span>

<div class="modal-content">

<iframe id="editFrame"></iframe>

</div>

</div>

</body>

</html>