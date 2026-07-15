<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">

<div class="logo">
🎓 SAPEWS
</div>

<ul>

<li>
<a href="index.php" class="<?= $current=="index.php"?"active":"" ?>">
🏠 Dashboard
</a>
</li>

<li>
<a href="student_management.php" class="<?= $current=="student_management.php"?"active":"" ?>">
👨‍🎓 Students
</a>
</li>

<li>
<a href="attendance_form.php" class="<?= $current=="attendance_form.php"?"active":"" ?>">
📝 Attendance
</a>
</li>

<li>
<a href="dashboard.php" class="<?= $current=="dashboard.php"?"active":"" ?>">
⚠ Early Warning
</a>
</li>

</ul>

</div>