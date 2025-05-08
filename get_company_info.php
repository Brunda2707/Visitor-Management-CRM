<?php
require_once 'db.php';
header('Content-Type: application/json');
$row = $conn->query("SELECT company_name, address FROM company_info WHERE id=1")->fetch_assoc();
if ($row) {
    echo json_encode(['success' => true, 'company_name' => $row['company_name'], 'company_address' => $row['address']]);
} else {
    echo json_encode(['success' => false]);
}
?>
