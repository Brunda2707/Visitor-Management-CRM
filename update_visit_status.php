<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$visit_id = intval($_POST['visit_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$visit_id || !in_array($action, ['checkin', 'checkout'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($action === 'checkin') {
    $status = 'Checked In';
    $time_in = date('H:i');
    $stmt = $conn->prepare("UPDATE visits SET status=?, time_in=? WHERE id=?");
    $stmt->bind_param("ssi", $status, $time_in, $visit_id);
} else {
    $status = 'Checked Out';
    $time_out = $_POST['time_out'] ?? date('H:i');
    $stmt = $conn->prepare("UPDATE visits SET status=?, time_out=? WHERE id=?");
    $stmt->bind_param("ssi", $status, $time_out, $visit_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}
$stmt->close();
$conn->close();
?>
