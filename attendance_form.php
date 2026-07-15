<?php
require_once 'config.php';
$pageTitle = "Attendance Management";
include 'header.php';
$db = getDBConnection();

// FIX: Added ORDER BY class_id, full_name explicitly and added a class
// filter parameter so teachers can view one class at a time.
// The original form loaded ALL active students with no class filter,
// which becomes unmanageable with many students.
$selectedClass = isset($_GET['class_id']) ? $_GET['class_id'] : null;

if ($selectedClass) {
    $stmt = $db->prepare(
        "SELECT * FROM students WHERE status = 'Active' AND class_id = :class_id
         ORDER BY full_name"
    );
    $stmt->execute([':class_id' => $selectedClass]);
} else {
    $stmt = $db->prepare(
        "SELECT * FROM students WHERE status = 'Active' ORDER BY class_id, full_name"
    );
    $stmt->execute();
}
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FIX: Check for duplicate attendance for today before rendering the form.
// The original form had no guard: submitting it twice on the same day would
// insert duplicate rows into the attendance table, corrupting attendance_rate
// calculations (doubling the number of days counted for each student).
$today = date('Y-m-d');
if ($selectedClass) {
    $dupCheck = $db->prepare(
        "SELECT COUNT(*) FROM attendance a
         INNER JOIN students s ON a.student_id = s.student_id
         WHERE a.attendance_date = :today AND s.class_id = :class_id"
    );
    $dupCheck->execute([':today' => $today, ':class_id' => $selectedClass]);
} else {
    $dupCheck = $db->prepare(
        "SELECT COUNT(*) FROM attendance WHERE attendance_date = :today"
    );
    $dupCheck->execute([':today' => $today]);
}
$alreadySubmitted = $dupCheck->fetchColumn() > 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daily Attendance Entry</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #2d8b81; color: white; }
        .radio-group { display: flex; gap: 15px; }
        button { padding: 10px 20px; background: #2c3e50; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .filters { margin-bottom: 15px; }
        .filters select { padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Daily Attendance Entry &mdash; <?php echo $today; ?></h2>

    <!-- Class filter -->
    <div class="filters">
        <form method="GET">
            <label>Filter by Class:
                <select name="class_id" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    <?php foreach (['1','2','3','4'] as $cls): ?>
                        <option value="<?php echo $cls; ?>"
                            <?php if ($selectedClass === $cls) echo 'selected'; ?>>
                            Form <?php echo $cls; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
    </div>

    <?php if ($alreadySubmitted): ?>
    <div class="warning">
        &#9888; Attendance for
        <?php echo $selectedClass ? "Form $selectedClass" : "all classes"; ?>
        has already been submitted today. Submitting again will create duplicate
        records. Use the edit page if you need to make corrections.
    </div>
    <?php endif; ?>

    <form method="POST" action="save_attendance.php">
        <!-- FIX: Pass today's date and academic year as hidden fields
             so save_attendance.php does not need to recalculate them,
             and they remain consistent with seed data. -->
        <input type="hidden" name="attendance_date" value="<?php echo $today; ?>">
        <input type="hidden" name="academic_year"   value="<?php echo ACADEMIC_YEAR; ?>">

        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                    <td>Form <?php echo htmlspecialchars($student['class_id']); ?></td>
                    <td>
                        <div class="radio-group">
                            <label>
                                <input type="radio"
                                       name="attendance[<?php echo $student['student_id']; ?>]"
                                       value="Present" checked> Present
                            </label>
                            <label>
                                <input type="radio"
                                       name="attendance[<?php echo $student['student_id']; ?>]"
                                       value="Absent"> Absent
                            </label>
                            <label>
                                <input type="radio"
                                       name="attendance[<?php echo $student['student_id']; ?>]"
                                       value="Late"> Late
                            </label>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" style="margin-top: 20px;"
            <?php if ($alreadySubmitted) echo 'onclick="return confirm(\'Attendance already submitted. Submit again anyway?\')"'; ?>>
            Save Attendance
        </button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
