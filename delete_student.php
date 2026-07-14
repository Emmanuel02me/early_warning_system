<?php
require_once 'config.php';

$db = getDBConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid student ID.");
}

$id = (int)$_GET['id'];

$stmt = $db->prepare("DELETE FROM students WHERE student_id = ?");
$stmt->execute([$id]);

header("Location: student_management.php");
exit;