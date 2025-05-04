<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$visitor_id = isset($_POST['visitor_id']) ? intval($_POST['visitor_id']) : 0;
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$company = trim($_POST['company'] ?? '');

// Basic validation
if ($visitor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid visitor ID.']);
    exit;
}
if ($full_name === '' || $email === '' || $phone === '' || $company === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}
if (!preg_match('/^[0-9\-\+\s\(\)]+$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number.']);
    exit;
}

// Update visitor info
$stmt = $conn->prepare("UPDATE visitors SET full_name = ?, email = ?, phone = ?, company = ? WHERE id = ?");
$stmt->bind_param('ssssi', $full_name, $email, $phone, $company, $visitor_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Visitor info updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update visitor info.']);
}
$stmt->close();
$conn->close(); 