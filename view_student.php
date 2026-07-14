<?php
require_once 'config.php';

$db = getDBConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Student ID.");
}

$id = (int)$_GET['id'];

$stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$id]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Profile</title>

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

.container{

width:80%;
max-width:900px;

margin:40px auto;

background:white;

padding:30px;

border-radius:10px;

box-shadow:0 5px 15px rgba(0,0,0,.15);

}

.header{

background:#0f766e;

color:white;

padding:20px;

margin:-30px -30px 30px;

border-radius:10px 10px 0 0;

}

.header h1{

font-size:28px;

}

.profile{

display:grid;

grid-template-columns:180px 1fr;

gap:30px;

align-items:center;

margin-bottom:30px;

}

.avatar{

width:160px;

height:160px;

border-radius:50%;

background:#0f766e;

display:flex;

align-items:center;

justify-content:center;

font-size:70px;

color:white;

margin:auto;

}

.details{

display:grid;

grid-template-columns:180px 1fr;

row-gap:15px;

}

.label{

font-weight:bold;

color:#555;

}

.value{

color:#111;

}

.status{

display:inline-block;

padding:8px 18px;

border-radius:30px;

background:#16a34a;

color:white;

}

.buttons{

margin-top:30px;

display:flex;

gap:10px;

flex-wrap:wrap;

}

.btn{

padding:12px 20px;

text-decoration:none;

border-radius:6px;

color:white;

}

.edit{

background:#d97706;

}

.back{

background:#2563eb;

}

.delete{

background:#dc2626;

}

</style>

</head>

<body>

<div class="container">

<div class="header">

<h1>Student Profile</h1>

</div>

<div class="profile">

<div class="avatar">

🎓

</div>

<div>

<h2><?= htmlspecialchars($student['full_name']) ?></h2>

<p><?= htmlspecialchars($student['registration_number']) ?></p>

</div>

</div>

<div class="details">

<div class="label">
Registration Number
</div>

<div class="value">
<?= htmlspecialchars($student['registration_number']) ?>
</div>

<div class="label">
Full Name
</div>

<div class="value">
<?= htmlspecialchars($student['full_name']) ?>
</div>

<div class="label">
Gender
</div>

<div class="value">
<?= htmlspecialchars($student['gender']) ?>
</div>

<div class="label">
Class
</div>

<div class="value">
Form <?= htmlspecialchars($student['class_id']) ?>
</div>

<div class="label">
Enrollment Date
</div>

<div class="value">
<?= htmlspecialchars($student['enrollment_date']) ?>
</div>

<div class="label">
Status
</div>

<div class="value">

<span class="status">

<?= htmlspecialchars($student['status']) ?>

</span>

</div>

</div>

<div class="buttons">

<a
href="student_management.php"
class="btn back">

← Back

</a>

<a
href="edit_student.php?id=<?= $student['student_id'] ?>"
class="btn edit">

Edit Student

</a>

<a
href="delete_student.php?id=<?= $student['student_id'] ?>"
class="btn delete"
onclick="return confirm('Delete this student?')">

Delete

</a>

</div>

</div>

</body>

</html>