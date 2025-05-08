<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$visit_id = intval($_GET['id'] ?? 0);
if (!$visit_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid visit ID']);
    exit;
}

$stmt = $conn->prepare("SELECT v.full_name, v.email, v.phone, v.company, 
    vi.person_to_meet, vi.purpose, vi.vehicle_number_plate, vi.vehicle_owner_name, 
    vi.amount_payable, vi.amount_paid, vi.date, vi.time_in, vi.status 
    FROM visits vi 
    JOIN visitors v ON vi.visitor_id = v.id 
    WHERE vi.id = ?");
$stmt->bind_param("i", $visit_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true] + $row);
} else {
    echo json_encode(['success' => false, 'message' => 'Visit not found']);
}
$stmt->close();
$conn->close();
?>
