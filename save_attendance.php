<?php
// FIX: This file was completely missing from the project.
// attendance_form.php posts to save_attendance.php, but the file did not
// exist. Clicking "Save Attendance" produced a 404 and no data was saved.

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: attendance_form.php');
    exit;
}

$db = getDBConnection();

$attendanceDate = $_POST['attendance_date'] ?? date('Y-m-d');
$academicYear   = $_POST['academic_year']   ?? ACADEMIC_YEAR;
$attendanceData = $_POST['attendance']       ?? [];

if (empty($attendanceData)) {
    die('No attendance data received.');
}

// FIX: Use a transaction so either all rows are saved or none are,
// preventing partial attendance records on error.
$db->beginTransaction();

try {
    $stmt = $db->prepare(
        "INSERT INTO attendance (student_id, attendance_date, status, term, academic_year)
         VALUES (:student_id, :date, :status, 'Term 1', :academic_year)
         ON DUPLICATE KEY UPDATE status = VALUES(status)"
        // ON DUPLICATE KEY UPDATE prevents duplicates if there's a unique
        // index on (student_id, attendance_date). Add this index to your
        // schema: ALTER TABLE attendance ADD UNIQUE (student_id, attendance_date);
    );

    foreach ($attendanceData as $studentId => $status) {
        $allowedStatuses = ['Present', 'Absent', 'Late', 'Excused'];
        if (!in_array($status, $allowedStatuses)) continue;

        $stmt->execute([
            ':student_id'    => (int)$studentId,
            ':date'          => $attendanceDate,
            ':status'        => $status,
            ':academic_year' => $academicYear
        ]);
    }

    $db->commit();
    header('Location: attendance_form.php?saved=1');
    exit;

} catch (Exception $e) {
    $db->rollBack();
    die('Error saving attendance: ' . htmlspecialchars($e->getMessage()));
}
?>
