<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$update_type = $_POST['update_type'] ?? '';

if ($update_type === 'notification_email') {
    $notification_email = trim($_POST['notification_email'] ?? '');
    if ($notification_email === '') {
        echo json_encode(['success' => false, 'message' => 'Notification email is required.']);
        exit;
    }
    if (!filter_var($notification_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid notification email.']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE company_info SET notification_email=? WHERE id=1");
    $stmt->bind_param('s', $notification_email);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notification email.']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

$company_name = trim($_POST['company_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$notification_email = trim($_POST['notification_email'] ?? '');

if ($company_name === '' || $phone === '' || $address === '' || $notification_email === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (!filter_var($notification_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification email.']);
    exit;
}

$stmt = $conn->prepare("UPDATE company_info SET company_name=?, phone=?, address=?, notification_email=? WHERE id=1");
$stmt->bind_param('ssss', $company_name, $phone, $address, $notification_email);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update company info.']);
}
$stmt->close();
$conn->close(); 