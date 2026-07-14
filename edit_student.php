<?php
require_once 'config.php';

$db = getDBConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid student ID.");
}

$id = (int)$_GET['id'];

$stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("
            UPDATE students SET
                registration_number = ?,
                full_name = ?,
                gender = ?,
                class_id = ?,
                enrollment_date = ?,
                status = ?
            WHERE student_id = ?
        ");

        $stmt->execute([
            trim($_POST['registration_number']),
            trim($_POST['full_name']),
            $_POST['gender'],
            $_POST['class_id'],
            $_POST['enrollment_date'],
            $_POST['status'],
            $id
        ]);

        header("Location: student_management.php");
        exit;

    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<title>Edit Student</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial,Helvetica,sans-serif;
}

body{
background:#f3f4f6;
padding:30px;
}

h2{
margin-bottom:20px;
color:#0f766e;
}

form{
background:#fff;
padding:25px;
border-radius:12px;
box-shadow:0 4px 12px rgba(0,0,0,.1);
}

input,
select{
width:100%;
padding:12px;
margin-top:6px;
margin-bottom:18px;
border:1px solid #ccc;
border-radius:6px;
font-size:15px;
}

button{
padding:12px 25px;
border:none;
border-radius:6px;
cursor:pointer;
color:#fff;
font-size:15px;
}

button[type=submit]{
background:#16a34a;
}

a{
display:inline-block;
padding:12px 25px;
background:#dc2626;
color:#fff;
text-decoration:none;
border-radius:6px;
margin-left:10px;
}

label{
font-weight:bold;
}

</style>

</head>
<body>

<h2>Edit Student</h2>

<?php if($error) echo "<p style='color:red'>$error</p>"; ?>

<form method="POST">

Registration Number<br>
<input type="text" name="registration_number" value="<?= htmlspecialchars($student['registration_number']) ?>"><br><br>

Full Name<br>
<input type="text" name="full_name" value="<?= htmlspecialchars($student['full_name']) ?>"><br><br>

Gender<br>
<select name="gender">
<option value="M" <?= $student['gender']=='M'?'selected':'' ?>>Male</option>
<option value="F" <?= $student['gender']=='F'?'selected':'' ?>>Female</option>
</select><br><br>

Class<br>
<select name="class_id">
<?php
for($i=1;$i<=4;$i++){
    $selected = ($student['class_id']==$i) ? 'selected' : '';
    echo "<option value='$i' $selected>Form $i</option>";
}
?>
</select><br><br>

Enrollment Date<br>
<input type="date" name="enrollment_date" value="<?= $student['enrollment_date'] ?>"><br><br>

Status<br>
<select name="status">
<?php
$statuses = ['Active','Dropped','Transferred','Graduated'];
foreach($statuses as $status){
    $selected = ($student['status']==$status)?'selected':'';
    echo "<option value='$status' $selected>$status</option>";
}
?>
</select><br><br>

<button type="submit">Update Student</button>
<a href="student_management.php">Cancel</a>

</form>

</body>
</html>