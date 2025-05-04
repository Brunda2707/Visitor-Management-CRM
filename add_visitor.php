<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$errors = [];
$response = [];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Helper: Validate email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Helper: Validate phone (simple, can be improved)
function is_valid_phone($phone) {
    return preg_match('/^[0-9\-\+\s\(\)]+$/', $phone);
}

// Helper: Validate date
function is_valid_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Helper: Validate time
function is_valid_time($time) {
    return preg_match('/^(2[0-3]|[01]?[0-9]):([0-5]?[0-9])$/', $time);
}

// Get POST data
$full_name = trim($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$company = trim($_POST['company'] ?? '');
$person_to_meet = trim($_POST['personToMeet'] ?? '');
$date = trim($_POST['visitDate'] ?? '');
$time = trim($_POST['visitTime'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');

// Validation
if ($full_name === '') $errors['fullName'] = 'Full name is required.';
if (!is_valid_email($email)) $errors['email'] = 'Invalid email address.';
if (!is_valid_phone($phone)) $errors['phone'] = 'Invalid phone number.';
if ($company === '') $errors['company'] = 'Company is required.';
if ($person_to_meet === '') $errors['personToMeet'] = 'Person to meet is required.';
if (!is_valid_date($date)) $errors['visitDate'] = 'Invalid date format.';
if (!is_valid_time($time)) $errors['visitTime'] = 'Invalid time format.';
if ($purpose === '') $errors['purpose'] = 'Purpose is required.';

if ($errors) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Insert or get visitor
$stmt = $conn->prepare("SELECT id FROM visitors WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($visitor_id);
if ($stmt->fetch()) {
    // Visitor exists
    $stmt->close();
} else {
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO visitors (full_name, email, phone, company) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $full_name, $email, $phone, $company);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to add visitor.']);
        exit;
    }
    $visitor_id = $stmt->insert_id;
    $stmt->close();
}

// Insert visit
$status = 'Scheduled';
$created_at = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO visits (visitor_id, person_to_meet, purpose, date, time_in, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssss", $visitor_id, $person_to_meet, $purpose, $date, $time, $status, $created_at);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Visitor registered successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to register visit.']);
}
$stmt->close();
$conn->close();
?>
