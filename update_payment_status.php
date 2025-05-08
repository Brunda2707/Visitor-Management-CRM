<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$visit_id = intval($_POST['visit_id'] ?? 0);

if (!$visit_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid visit ID']);
    exit;
}

$stmt = $conn->prepare("UPDATE visits SET amount_paid = 'Yes' WHERE id = ?");
$stmt->bind_param("i", $visit_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
}
$stmt->close();
$conn->close();
?>
