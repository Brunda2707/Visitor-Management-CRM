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
    date_default_timezone_set('Asia/Kolkata');
    $date_today = date('Y-m-d');
    $time_in = date('H:i:s');
    $stmt = $conn->prepare("UPDATE visits SET status=?, date=?, time_in=? WHERE id=?");
    $stmt->bind_param("sssi", $status, $date_today, $time_in, $visit_id);
} else {
    $status = 'Checked Out';
    date_default_timezone_set('Asia/Kolkata');
    $time_out = $_POST['time_out'] ?? date('H:i');
    $amount_paid = $_POST['amount_paid'] ?? null;
    if ($amount_paid === 'Yes' || $amount_paid === 'No') {
        $stmt = $conn->prepare("UPDATE visits SET status=?, time_out=?, amount_paid=? WHERE id=?");
        $stmt->bind_param("sssi", $status, $time_out, $amount_paid, $visit_id);
    } else {
        $stmt = $conn->prepare("UPDATE visits SET status=?, time_out=? WHERE id=?");
        $stmt->bind_param("ssi", $status, $time_out, $visit_id);
    }
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}
$stmt->close();
$conn->close();
?>
