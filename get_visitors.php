<?php
session_start();
require_once 'db.php';

$perPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$status = $_GET['status'] ?? '';
$export = isset($_GET['export']) && $_GET['export'] == '1';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(v.full_name LIKE ? OR v.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}
// Date and status filters only apply if there is a visit
if ($dateFrom && $dateTo) {
    $where[] = "(vi.date BETWEEN ? AND ?)";
    $params[] = $dateFrom;
    $params[] = $dateTo;
    $types .= 'ss';
}
if ($status && $status !== 'All Status') {
    $where[] = "vi.status = ?";
    $params[] = $status;
    $types .= 's';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get all visits for all visitors
$sql = "SELECT v.id as visitor_id, v.full_name, v.company, v.email, v.phone, 
               vi.id as visit_id, vi.person_to_meet, vi.date, vi.time_in, vi.status
        FROM visitors v
        LEFT JOIN visits vi ON v.id = vi.visitor_id
        $whereSql
        ORDER BY vi.date DESC, vi.time_in DESC";

$paramsForQuery = $params;
$typesForQuery = $types;

if (!$export) {
    $sql .= " LIMIT ? OFFSET ?";
    $paramsForQuery[] = $perPage;
    $paramsForQuery[] = ($page - 1) * $perPage;
    $typesForQuery .= 'ii';
}

$stmt = $conn->prepare($sql);
if ($paramsForQuery) $stmt->bind_param($typesForQuery, ...$paramsForQuery);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    // Only format if not null
    // Do not format date/time here, let frontend handle it
    $rows[] = $row;
}

if ($export) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="visitors.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Visitor Name', 'Company', 'Email', 'Phone', 'Person To Meet', 'Date', 'Time In', 'Status']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['full_name'], $r['company'], $r['email'], $r['phone'], $r['person_to_meet'], $r['date'] ?? '', $r['time_in'] ?? '', $r['status'] ?? '']);
    }
    fclose($out);
    exit;
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM visitors v";
$countStmt = $conn->prepare($countSql);
$countStmt->execute();
$countStmt->bind_result($total);
$countStmt->fetch();
$countStmt->close();

echo json_encode([
    'success' => true,
    'data' => $rows,
    'total' => $total,
    'perPage' => $perPage,
    'page' => $page
]);
?>
