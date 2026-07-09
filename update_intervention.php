<?php
// FIX: This file was completely missing from the project.
// dashboard.php calls fetch('update_intervention.php', ...) when the teacher
// clicks "Notify" or "Counsel", but the file did not exist, causing a 404
// and the intervention status was never saved.

require_once 'config.php';
require_once 'RiskPredictor.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (!isset($body['student_id']) || !isset($body['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing student_id or status']);
    exit;
}

$allowedStatuses = ['Notified', 'Counselling', 'Resolved', 'Pending'];
if (!in_array($body['status'], $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

try {
    $predictor = new RiskPredictor();
    $predictor->updateIntervention((int)$body['student_id'], $body['status']);
    echo json_encode(['success' => true, 'message' => 'Intervention status updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
