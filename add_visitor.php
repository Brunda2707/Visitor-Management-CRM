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

// Helper: Convert dd-mm-yyyy to yyyy-mm-dd if needed
function normalize_date($date) {
    // If already in yyyy-mm-dd, return as is
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return $date;
    // If in dd-mm-yyyy, convert
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $m)) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    return $date; // fallback, let validation catch errors
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
$vehicle_number_plate = trim($_POST['vehicleNumberPlate'] ?? '');
$vehicle_owner_name = trim($_POST['vehicleOwnerName'] ?? '');
$amount_payable = trim($_POST['amountPayable'] ?? '');

// Normalize date to yyyy-mm-dd
$date = normalize_date($date);

// Validation
if ($full_name === '') $errors['fullName'] = 'Full name is required.';
if (!is_valid_email($email)) $errors['email'] = 'Invalid email address.';
if (!is_valid_phone($phone)) $errors['phone'] = 'Invalid phone number.';
if ($company === '') $errors['company'] = 'Company is required.';
if ($person_to_meet === '') $errors['personToMeet'] = 'Person to meet is required.';
if (!is_valid_date($date)) $errors['visitDate'] = 'Invalid date format.';
if (!is_valid_time($time)) $errors['visitTime'] = 'Invalid time format.';
if ($purpose === '') $errors['purpose'] = 'Purpose is required.';
if ($vehicle_number_plate === '') $errors['vehicleNumberPlate'] = 'Vehicle number plate is required.';
if ($vehicle_owner_name === '') $errors['vehicleOwnerName'] = 'Vehicle owner name is required.';
if (!is_numeric($amount_payable) || $amount_payable <= 0) $errors['amountPayable'] = 'Amount payable must be greater than 0.';

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
    // Visitor exists, $visitor_id is set by bind_result/fetch
    $stmt->close();
} else {
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO visitors (full_name, email, phone, company) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $full_name, $email, $phone, $company);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to add visitor: ' . $stmt->error]);
        exit;
    }
    $visitor_id = $stmt->insert_id;
    $stmt->close();
}

// Defensive: ensure $visitor_id is set
if (!$visitor_id) {
    echo json_encode(['success' => false, 'message' => 'Could not determine visitor ID.']);
    exit;
}

// Insert visit
$status = 'Scheduled';
$created_at = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO visits (visitor_id, person_to_meet, purpose, vehicle_number_plate, vehicle_owner_name, amount_payable, date, time_in, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param("issssdssss", $visitor_id, $person_to_meet, $purpose, $vehicle_number_plate, $vehicle_owner_name, $amount_payable, $date, $time, $status, $created_at);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Visitor registered successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to register visit: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
?>
