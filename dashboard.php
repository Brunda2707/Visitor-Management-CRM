<?php
date_default_timezone_set('Asia/Kolkata');
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';
$admin_name = $_SESSION['user_name'] ?? 'Admin User';

// 1. Visitors Today (checked in today)
$visitors_today = 0;
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(DISTINCT visitor_id) FROM visits WHERE date = ? AND status = 'Checked In'");
$stmt->bind_param('s', $today);
$stmt->execute();
$stmt->bind_result($visitors_today);
$stmt->fetch();
$stmt->close();

// 2. Total Visitors (cumulative unique visitors)
$total_visitors = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM visitors");
$stmt->execute();
$stmt->bind_result($total_visitors);
$stmt->fetch();
$stmt->close();

// 3. Checked In Now (currently in building)
$checked_in_now = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM visits WHERE status = 'Checked In'");
$stmt->execute();
$stmt->bind_result($checked_in_now);
$stmt->fetch();
$stmt->close();

// 4. Fetch 3 most recent checked in or checked out visitors for today only
$recent_visitors = [];
$stmt = $conn->prepare("SELECT v.id as visitor_id, v.full_name, vs.person_to_meet, vs.status, vs.date, vs.time_in, vs.time_out, vs.id FROM visits vs JOIN visitors v ON vs.visitor_id = v.id WHERE vs.status IN ('Checked In', 'Checked Out') AND vs.date = CURDATE() ORDER BY vs.date DESC, vs.time_in DESC LIMIT 3");
if ($stmt && $stmt->execute()) {
    $stmt->bind_result($visitor_id, $full_name, $person_to_meet, $status, $date, $time_in, $time_out, $visit_id);
    while ($stmt->fetch()) {
        $recent_visitors[] = [
            'visitor_id' => $visitor_id,
            'full_name' => $full_name,
            'person_to_meet' => $person_to_meet,
            'status' => $status,
            'date' => $date,
            'time_in' => $time_in,
            'time_out' => $time_out,
            'visit_id' => $visit_id
        ];
    }
    $stmt->close();
}

// 5. Fetch upcoming visits (status = 'Scheduled', date >= today), order by date and time_in ascending
$upcoming_visits = [];
$stmt = $conn->prepare("SELECT v.id as visitor_id, v.full_name, vs.person_to_meet, vs.date, vs.time_in, vs.id FROM visits vs JOIN visitors v ON vs.visitor_id = v.id WHERE vs.status = 'Scheduled' AND vs.date >= CURDATE() ORDER BY vs.date ASC, vs.time_in ASC LIMIT 3");
if ($stmt && $stmt->execute()) {
    $stmt->bind_result($visitor_id, $full_name, $person_to_meet, $date, $time_in, $visit_id);
    while ($stmt->fetch()) {
        $upcoming_visits[] = [
            'visitor_id' => $visitor_id,
            'full_name' => $full_name,
            'person_to_meet' => $person_to_meet,
            'date' => $date,
            'time_in' => $time_in,
            'visit_id' => $visit_id
        ];
    }
    $stmt->close();
}

// 6. Visitors Tab Pagination + Search
$visitors_per_page = 10;
$visitors_page = isset($_GET['visitors_page']) ? max(1, intval($_GET['visitors_page'])) : 1;
$visitors_offset = ($visitors_page - 1) * $visitors_per_page;
$search_query = isset($_GET['visitor_search']) ? trim($_GET['visitor_search']) : '';

// Build WHERE clause for search
$where = '';
$params = [];
$types = '';
if ($search_query !== '') {
    $where = "WHERE full_name LIKE ? OR company LIKE ? OR email LIKE ?";
    $search_term = '%' . $search_query . '%';
    $params = [$search_term, $search_term, $search_term];
    $types = 'sss';
}

// Get total count for pagination
$total_visitors_count = 0;
if ($where) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM visitors $where");
    $stmt->bind_param($types, ...$params);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM visitors");
}
if ($stmt && $stmt->execute()) {
    $stmt->bind_result($total_visitors_count);
    $stmt->fetch();
    $stmt->close();
}

// Get paginated results
$all_visitors = [];
if ($where) {
    $stmt = $conn->prepare("SELECT id, full_name, company, email, phone FROM visitors $where ORDER BY id DESC LIMIT ? OFFSET ?");
    $params2 = array_merge($params, [$visitors_per_page, $visitors_offset]);
    $types2 = $types . 'ii';
    $stmt->bind_param($types2, ...$params2);
} else {
    $stmt = $conn->prepare("SELECT id, full_name, company, email, phone FROM visitors ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $visitors_per_page, $visitors_offset);
}
if ($stmt && $stmt->execute()) {
    $stmt->bind_result($id, $full_name, $company, $email, $phone);
    while ($stmt->fetch()) {
        $all_visitors[] = [
            'id' => $id,
            'full_name' => $full_name,
            'company' => $company,
            'email' => $email,
            'phone' => $phone
        ];
    }
    $stmt->close();
}

// For each visitor, fetch their most recent visit's status
$visitor_status_map = [];
if (count($all_visitors) > 0) {
    $ids = array_column($all_visitors, 'id');
    $in = str_repeat('?,', count($ids) - 1) . '?';
    $types = str_repeat('i', count($ids));
    $sql = "SELECT visitor_id, status FROM visits WHERE visitor_id IN ($in) ORDER BY date DESC, time_in DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$ids);
    if ($stmt && $stmt->execute()) {
        $stmt->bind_result($visitor_id, $status);
        while ($stmt->fetch()) {
            if (!isset($visitor_status_map[$visitor_id])) {
                $visitor_status_map[$visitor_id] = $status;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VisitorConnect - Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 200px;
            padding: 20px 15px;
            background-color: #fff;
            border-right: 1px solid #e9ecef;
            z-index: 1000;
            transition: left 0.3s;
        }
        .sidebar.collapsed {
            left: -200px;
        }
        .sidebar .logo {
            color: #0d6efd;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 25px;
            padding-left: 5px;
        }
        .sidebar .nav-link {
            color: #495057;
            border-radius: 5px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover {
            background-color: #f0f2f5;
        }
        .sidebar .nav-link.active {
            background-color: #e7f1ff;
            color: #0d6efd;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        /* Main Content */
        .main-content {
            margin-left: 200px;
            padding: 20px;
            transition: margin-left 0.3s;
        }
        /* Header */
        .header {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-left: 12px;
        }
        .header-logo img {
            height: 38px;
            width: 38px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(52,152,219,0.10);
            background: #fff;
        }
        .header-logo .logo-text {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1976d2;
            letter-spacing: 0.5px;
        }
        .search-container {
            position: relative;
            flex: 1 1 300px;
            min-width: 200px;
            margin-bottom: 10px;
        }
        .search-container .form-control {
            padding-left: 40px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .search-container .form-control:focus {
            box-shadow: none;
            border-color: #80bdff;
        }
        .header a {
            color: #0d6efd;
        }
        .header a:hover {
            color: #0a58ca;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            background-color: #e7f1ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0d6efd;
            margin-right: 10px;
        }
        /* Responsive Sidebar Toggle */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #0d6efd;
            margin-right: 10px;
        }
        @media (max-width: 991px) {
            .sidebar:not(.open) .nav-link span {
                display: none;
            }
            .sidebar {
                width: 70px;
                padding: 20px 5px;
            }
            .sidebar .logo {
                display: none;
            }
            .sidebar .nav-link i {
                margin-right: 0;
            }
            .main-content {
                margin-left: 70px;
            }
        }
        @media (max-width: 767px) {
            .sidebar {
                left: -200px;
                width: 200px;
            }
            .sidebar.open {
                left: 0;
            }
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            .sidebar-toggle {
                display: inline-block;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px;
            }
            .header-logo {
                margin-bottom: 10px;
                padding-left: 0;
            }
        }
        /* Responsive Cards and Tabs */
        .stats-card {
            min-width: 0;
        }
        @media (max-width: 767px) {
            .stats-card {
                margin-bottom: 15px;
                padding: 12px;
            }
            .dashboard-tabs .nav-link {
                padding: 8px 10px;
                font-size: 0.95rem;
            }
        }
        /* Dashboard Tabs */
        .dashboard-tabs {
            margin-bottom: 20px;
        }

        .dashboard-tabs .nav-link {
            color: #6c757d;
            border: none;
            padding: 10px 20px;
            font-weight: 500;
            margin-right: 10px;
        }

        .dashboard-tabs .nav-link.active {
            color: #0d6efd;
            background-color: transparent;
            border-bottom: 3px solid #0d6efd;
        }

        /* Tab Content */
        .tab-content {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        /* Dashboard Content */
        .stats-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .stats-card .title {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .stats-card .subtitle {
            color: #adb5bd;
            font-size: 0.8rem;
            margin-bottom: 15px;
        }

        .stats-card .number {
            font-size: 2.2rem;
            font-weight: 600;
        }

        .stats-card .number.blue {
            color: #0d6efd;
        }

        .stats-card .number.green {
            color: #28a745;
        }

        /* Visitor Records Table */
        .visitor-table th {
            font-weight: 500;
            color: #495057;
        }

        .visitor-table .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            width: fit-content;
        }

        .visitor-table .status.checked-in {
            background-color: #d1e7dd;
            color: #198754;
        }

        .visitor-table .status.checked-out {
            background-color: #f8d7da;
            color: #dc3545;
        }

        .visitor-table .status.scheduled {
            background-color: #fff3cd;
            color: #ffc107;
        }

        .visitor-table .action-btn {
            padding: 5px 15px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Register Form */
        .form-card {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card .form-label {
            font-weight: 500;
            color: #343a40;
        }

        .form-card .form-control {
            padding: 10px 15px;
            border-radius: 5px;
        }

        .form-card .btn {
            padding: 10px 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></button>
    <div class="sidebar" id="sidebar">
        <div class="logo">VisitorConnect</div>
        <div class="main-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-gauge-high"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php?screen=add-visitor">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Visitor</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php?screen=visitor-list">
                        <i class="fas fa-list"></i>
                        <span>Visitor List</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php?screen=settings">
                        <i class="fas fa-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?logout=1">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header d-flex align-items-center justify-content-between mb-4" style="background:#fff; border-radius:0 12px 12px 0; box-shadow:0 1px 3px rgba(0,0,0,0.04); padding:18px 32px 18px 0; margin-left:-20px;">
            <div class="header-logo">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Visitor Management Logo">
                <span class="logo-text">VisitorConnect</span>
            </div>
            <div class="d-flex align-items-center gap-3" style="margin-right:24px;">
                <span class="d-inline-flex align-items-center justify-content-center bg-light rounded-3" style="width:40px; height:40px;"><i class="fa-regular fa-bell"></i></span>
                <span class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" style="width:40px; height:40px;"><i class="fa-regular fa-user" style="color:#0d6efd;"></i></span>
                <span class="fw-semibold ms-1 me-1"><?php echo htmlspecialchars($admin_name); ?></span>
                <a href="index.php?logout=1" class="text-decoration-none" title="Logout"><i class="fa-solid fa-arrow-right fa-lg" style="color:#0d6efd;"></i></a>
            </div>
        </div>

        <!-- Page Title -->
        <div class="mb-4">
            <h1 class="h4 mb-1">Visitor Management System</h1>
            <p class="text-muted mb-3" style="font-size:1rem;">Welcome to your visitor management dashboard</p>
        </div>

        <!-- Dashboard Tabs -->
        <ul class="nav dashboard-tabs" id="visitorTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="true">Dashboard</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="visitors-tab" data-bs-toggle="tab" data-bs-target="#visitors" type="button" role="tab" aria-controls="visitors" aria-selected="false">Visitors</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab" aria-controls="register" aria-selected="false">Register</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="visitorTabsContent">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="stats-card">
                            <div class="title">Visitors Today</div>
                            <div class="subtitle">Total visitors checked in today</div>
                            <div class="number blue"><?php echo $visitors_today; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="stats-card">
                            <div class="title">Total Visitors</div>
                            <div class="subtitle">Cumulative visitor count</div>
                            <div class="number"><?php echo $total_visitors; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="stats-card">
                            <div class="title">Checked In Now</div>
                            <div class="subtitle">Visitors currently in building</div>
                            <div class="number green"><?php echo $checked_in_now; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Additional Dashboard Content -->
                <div class="row">
                    <!-- Recent Visitors -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                Recent Visitors
                                <div class="small text-muted mt-1">Showing only today's visitors</div>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php if (count($recent_visitors) === 0): ?>
                                        <li class="list-group-item text-center text-muted">No recent visitors today.</li>
                                    <?php else: ?>
                                        <?php foreach ($recent_visitors as $rv): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center visitor-card"
                                                data-id="<?php echo $rv['visit_id']; ?>"
                                                data-visitor-id="<?php echo $rv['visitor_id']; ?>"
                                                style="cursor:pointer;">
                                                <div>
                                                    <?php
                                                        // Status badge
                                                        $statusDot = '';
                                                        if ($rv['status'] === 'Checked In') {
                                                            $statusDot = '<span title="Checked In" style="display:inline-block;width:10px;height:10px;background:#198754;border-radius:50%;margin-right:8px;vertical-align:middle;"></span>';
                                                        } else if ($rv['status'] === 'Checked Out') {
                                                            $statusDot = '<span title="Checked Out" style="display:inline-block;width:10px;height:10px;background:#6c757d;border-radius:50%;margin-right:8px;vertical-align:middle;"></span>';
                                                        }
                                                    ?>
                                                    <?php echo $statusDot; ?><h6 class="mb-0 d-inline-block align-middle"><?php echo htmlspecialchars($rv['full_name']); ?></h6>
                                                    <br><small class="text-muted">Meeting with: <?php echo htmlspecialchars($rv['person_to_meet']); ?></small>
                                                </div>
                                                <?php
                                                    $tz = new DateTimeZone('Asia/Kolkata');
                                                    $dt_in = new DateTime($rv['date'] . ' ' . $rv['time_in'], $tz);
                                                    $now = new DateTime('now', $tz);
                                                    $interval = $dt_in->diff($now);
                                                    $parts = [];
                                                    if ($interval->d > 0) $parts[] = $interval->d . 'd';
                                                    if ($interval->h > 0) $parts[] = $interval->h . 'h';
                                                    if ($interval->i > 0) $parts[] = $interval->i . 'm';
                                                    if (empty($parts)) $parts[] = 'Now';
                                                    $badge = '<span class="badge bg-success rounded-pill">' . implode(' ', $parts) . '</span>';
                                                    echo $badge;
                                                ?>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="card-footer text-end">
                                <a href="settings.php?screen=visitor-list" class="text-decoration-none">View all <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Visits -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                Upcoming Visits
                                <div class="small text-muted mt-1">Showing only upcoming scheduled visits</div>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php if (count($upcoming_visits) === 0): ?>
                                        <li class="list-group-item text-center text-muted">No upcoming visits.</li>
                                    <?php else: ?>
                                        <?php foreach ($upcoming_visits as $uv): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center visitor-card"
                                                data-id="<?php echo $uv['visit_id']; ?>"
                                                data-visitor-id="<?php echo $uv['visitor_id']; ?>"
                                                style="cursor:pointer;">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($uv['full_name']); ?></h6>
                                                    <small class="text-muted">Meeting with: <?php echo htmlspecialchars($uv['person_to_meet']); ?></small>
                                                </div>
                                                <?php
                                                    $badge = '';
                                                    $visit_date = new DateTime($uv['date']);
                                                    $today = new DateTime();
                                                    $diff = $visit_date->diff($today)->days;
                                                    if ($uv['date'] === $today->format('Y-m-d')) {
                                                        $badge = '<span class="badge bg-warning text-dark rounded-pill">Today</span>';
                                                    } else if ($uv['date'] === $today->modify('+1 day')->format('Y-m-d')) {
                                                        $badge = '<span class="badge bg-warning text-dark rounded-pill">Tomorrow</span>';
                                                    } else {
                                                        $badge = '<span class="badge bg-warning text-dark rounded-pill">' . date('M j', strtotime($uv['date'])) . '</span>';
                                                    }
                                                    echo $badge;
                                                ?>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="card-footer text-end">
                                <a href="settings.php?screen=visitor-list" class="text-decoration-none">View schedule <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visitors Tab -->
            <div class="tab-pane fade" id="visitors" role="tabpanel" aria-labelledby="visitors-tab">
                <h4 class="mb-4">Visitor Records</h4>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="position-relative flex-grow-1" style="max-width:350px;">
                        <i class="fas fa-search position-absolute" style="left: 14px; top: 10px; color: #adb5bd;"></i>
                        <input type="text" id="ajaxVisitorSearch" class="form-control ps-5" placeholder="Search visitors..." autocomplete="off">
                    </div>
                    <div class="d-flex ms-3 align-items-center">
                        <select class="form-select me-2" style="width: 150px;" id="ajaxVisitorStatusFilter">
                            <option value="All Status">All Status</option>
                            <option value="Checked In">Checked In</option>
                            <option value="Checked Out">Checked Out</option>
                            <option value="Scheduled">Scheduled</option>
                        </select>
                        <button class="btn btn-primary" id="ajaxVisitorExportBtn">Export</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table visitor-table" id="ajaxVisitorTable">
                        <thead>
                            <tr>
                                <th>Visitor Name</th>
                                <th>Company</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Amount Status</th>
                                <th>Date & Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- AJAX results here -->
                        </tbody>
                    </table>
                </div>
                <div id="ajaxVisitorPagination" class="mt-3"></div>
            </div>

            <!-- Register Tab -->
            <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
                <h4 class="mb-4">Register New Visitor</h4>
                <div class="form-card">
                    <form id="dashboardRegisterVisitorForm">
                        <div id="dashboard-register-message"></div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="regFullName" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="regFullName" name="fullName" required placeholder="Enter visitor's full name">
                            </div>
                            <div class="col-md-6">
                                <label for="regEmail" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="regEmail" name="email" required placeholder="Enter email address">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="regPhone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="regPhone" name="phone" required pattern="^[0-9\-\+\s\(\)]+$" placeholder="Enter phone number">
                            </div>
                            <div class="col-md-6">
                                <label for="regCompany" class="form-label">Company</label>
                                <input type="text" class="form-control" id="regCompany" name="company" required placeholder="Enter company name">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="regPersonToMeet" class="form-label">Person to Meet</label>
                                <select class="form-select" id="regPersonToMeet" name="personToMeet" required>
                                    <option value="" disabled selected>Select person to meet</option>
                                    <option value="Sarah Johnson">Sarah Johnson</option>
                                    <option value="Michael Brown">Michael Brown</option>
                                    <option value="David Lee">David Lee</option>
                                    <option value="Jennifer Kim">Jennifer Kim</option>
                                    <option value="Elizabeth Moore">Elizabeth Moore</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="regVisitDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="regVisitDate" name="visitDate" required placeholder="Select date">
                            </div>
                            <div class="col-md-6">
                                <label for="regVisitTime" class="form-label">Time</label>
                                <input type="time" class="form-control" id="regVisitTime" name="visitTime" required placeholder="Select time">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="regPurpose" class="form-label">Purpose of Visit</label>
                                <textarea class="form-control" id="regPurpose" name="purpose" rows="4" required placeholder="Enter purpose of visit"></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="regVehicleNumberPlate" class="form-label">Vehicle Number Plate</label>
                                <input type="text" class="form-control" id="regVehicleNumberPlate" name="vehicleNumberPlate" required placeholder="Enter vehicle number plate">
                            </div>
                            <div class="col-md-6">
                                <label for="regVehicleOwnerName" class="form-label">Vehicle Owner Name</label>
                                <input type="text" class="form-control" id="regVehicleOwnerName" name="vehicleOwnerName" required placeholder="Enter vehicle owner name">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="regAmountPayable" class="form-label">Amount Payable</label>
                                <input type="number" class="form-control" id="regAmountPayable" name="amountPayable" required min="0.01" step="0.01" placeholder="Enter amount payable">
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-light me-2" id="dashboardRegisterCancelBtn">Cancel</button>
                                <button type="submit" class="btn btn-primary">Register Visitor</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Sidebar toggle for mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        var sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('open');
    });
    // Optional: close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        var sidebar = document.getElementById('sidebar');
        var toggle = document.getElementById('sidebarToggle');
        if (window.innerWidth <= 767) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        // Activate tab based on hash in URL
        if (window.location.hash) {
            var hash = window.location.hash;
            var tabTrigger = document.querySelector('button[data-bs-target="' + hash + '"]');
            if (tabTrigger) {
                var tab = new bootstrap.Tab(tabTrigger);
                tab.show();
            }
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.getElementById('ajaxVisitorSearch');
        var searchForm = document.getElementById('visitorSearchForm');
        if (searchInput && searchForm) {
            let timeout = null;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    searchForm.submit();
                }, 400); // Debounce for 400ms
            });
        }
    });
    </script>
    <script>
    // AJAX-powered visitor search and pagination for Visitors tab
    (function() {
        let currentPage = 1;
        let currentSearch = '';
        let currentStatus = 'All Status';
        const searchInput = document.getElementById('ajaxVisitorSearch');
        const statusFilter = document.getElementById('ajaxVisitorStatusFilter');
        const exportBtn = document.getElementById('ajaxVisitorExportBtn');
        const tableBody = document.querySelector('#ajaxVisitorTable tbody');
        const paginationDiv = document.getElementById('ajaxVisitorPagination');

        function fetchVisitors(page = 1) {
            currentPage = page;
            const params = new URLSearchParams({
                page: currentPage,
                search: currentSearch,
                status: currentStatus
            });
            fetch('get_visitors.php?' + params.toString())
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderVisitorTable(data.data);
                        renderPagination(data.total, data.perPage, data.page);
                    }
                });
        }

        function renderVisitorTable(rows) {
            const tbody = document.querySelector('#ajaxVisitorTable tbody');
            tbody.innerHTML = '';
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No visitors found.</td></tr>';
                return;
            }
            rows.forEach(row => {
                let statusBadge = '';
                if (row.status === 'Checked In') statusBadge = '<span class="badge bg-success bg-opacity-25 text-success fw-semibold" style="border-radius:12px;">Checked In</span>';
                else if (row.status === 'Checked Out') statusBadge = '<span class="badge bg-danger bg-opacity-25 text-danger fw-semibold" style="border-radius:12px;">Checked Out</span>';
                else if (row.status === 'Scheduled') statusBadge = '<span class="badge bg-primary bg-opacity-25 text-primary fw-semibold" style="border-radius:12px;">Scheduled</span>';
                else statusBadge = '<span class="badge bg-secondary bg-opacity-25 text-secondary fw-semibold" style="border-radius:12px;">No Visits</span>';

                // Amount status badge
                let amountStatus = '';
                if (row.amount_paid === 'Yes') {
                    amountStatus = '<span class="badge bg-success">Paid</span>';
                } else if (row.amount_paid === 'No') {
                    amountStatus = '<span class="badge bg-danger">Unpaid</span>';
                } else {
                    amountStatus = '<span class="badge bg-secondary">-</span>';
                }

                // Convert UTC to local for display, fallback to '-' if invalid
                let localDate = '-', localTime = '-';
                if (window.utcToLocal) {
                    const local = window.utcToLocal(row.date, row.time_in);
                    localDate = local.localDate;
                    localTime = local.localTime;
                }
                tbody.innerHTML += `
                    <tr class="visitor-row" data-visitor-id="${row.visitor_id}" style="cursor:pointer;">
                        <td>${row.full_name}</td>
                        <td>${row.company}</td>
                        <td>${row.email || ''}</td>
                        <td>${row.phone || ''}</td>
                        <td>${statusBadge}</td>
                        <td>${amountStatus}</td>
                        <td>${localDate} ${localTime}</td>
                        <td><a href="visitor_view_details.php?id=${row.visitor_id}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                `;
            });
        }

        function renderPagination(total, perPage, page) {
            const totalPages = Math.ceil(total / perPage);
            if (totalPages <= 1) { paginationDiv.innerHTML = ''; return; }
            let html = '<nav><ul class="pagination justify-content-center">';
            for (let i = 1; i <= totalPages; i++) {
                html += `<li class="page-item${i === page ? ' active' : ''}">
                    <a class="page-link" href="#" onclick="return false;" data-page="${i}">${i}</a>
                </li>`;
            }
            html += '</ul></nav>';
            paginationDiv.innerHTML = html;
            // Add click listeners
            paginationDiv.querySelectorAll('a.page-link').forEach(link => {
                link.addEventListener('click', function() {
                    const p = parseInt(this.getAttribute('data-page'));
                    if (!isNaN(p)) fetchVisitors(p);
                });
            });
        }

        let debounceTimeout = null;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimeout);
                currentSearch = this.value;
                debounceTimeout = setTimeout(() => fetchVisitors(1), 350);
            });
        }
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                currentStatus = this.value;
                fetchVisitors(1);
            });
        }
        if (exportBtn) {
            exportBtn.addEventListener('click', function() {
                const params = new URLSearchParams({
                    search: currentSearch,
                    status: currentStatus,
                    export: 1
                });
                window.open('get_visitors.php?' + params.toString(), '_blank');
            });
        }
        // Initial fetch
        fetchVisitors(1);
    })();
    </script>
    <script>
    // Register Visitor logic for dashboard Register tab
    (function() {
        const form = document.getElementById('dashboardRegisterVisitorForm');
        const messageDiv = document.getElementById('dashboard-register-message');
        if (!form) return;
        // Set min date to today
        const dateInput = document.getElementById('regVisitDate');
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const minDate = `${yyyy}-${mm}-${dd}`;
        dateInput.setAttribute('min', minDate);
        // Open date picker on focus/click
        dateInput.addEventListener('focus', function() { this.showPicker && this.showPicker(); });
        dateInput.addEventListener('click', function() { this.showPicker && this.showPicker(); });
        // Time picker logic
        const timeInput = document.getElementById('regVisitTime');
        timeInput.addEventListener('focus', function() { this.showPicker && this.showPicker(); });
        timeInput.addEventListener('click', function() { this.showPicker && this.showPicker(); });
        // Prevent past time if today is selected
        dateInput.addEventListener('change', function() {
            if (dateInput.value === minDate) {
                const now = new Date();
                let hours = now.getHours();
                let minutes = now.getMinutes();
                minutes = Math.ceil(minutes / 15) * 15;
                if (minutes === 60) { hours++; minutes = 0; }
                const minTime = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                timeInput.setAttribute('min', minTime);
            } else {
                timeInput.removeAttribute('min');
            }
        });
        // Cancel button resets form
        document.getElementById('dashboardRegisterCancelBtn').addEventListener('click', function() {
            form.reset();
            messageDiv.innerHTML = '';
        });
        // AJAX form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            messageDiv.innerHTML = '';
            // Client-side validation
            let errors = [];
            const fullName = form.fullName.value.trim();
            const email = form.email.value.trim();
            const phone = form.phone.value.trim();
            const company = form.company.value.trim();
            const personToMeet = form.personToMeet.value;
            const visitDate = form.visitDate.value;
            const visitTime = form.visitTime.value;
            const purpose = form.purpose.value.trim();
            const phoneRegex = /^[0-9\-\+\s\(\)]+$/;
            if (!fullName) errors.push('Full name is required.');
            if (!email || !/^[^@]+@[^@]+\.[^@]+$/.test(email)) errors.push('Invalid email address.');
            if (!phone || !phoneRegex.test(phone)) errors.push('Invalid phone number.');
            if (!company) errors.push('Company is required.');
            if (!personToMeet) errors.push('Person to meet is required.');
            if (!visitDate) errors.push('Date is required.');
            if (!visitTime) errors.push('Time is required.');
            if (!purpose) errors.push('Purpose is required.');
            const vehicleNumberPlate = form.vehicleNumberPlate.value.trim();
            const vehicleOwnerName = form.vehicleOwnerName.value.trim();
            const amountPayable = form.amountPayable.value.trim();
            if (!vehicleNumberPlate) errors.push('Vehicle number plate is required.');
            if (!vehicleOwnerName) errors.push('Vehicle owner name is required.');
            if (!amountPayable || isNaN(amountPayable) || Number(amountPayable) <= 0) {
                errors.push('Amount payable must be greater than 0.');
            }
            if (errors.length > 0) {
                let errorHtml = `<div class="alert alert-danger position-relative" role="alert" id="visitor-alert">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" aria-label="Close" onclick="this.parentElement.style.display='none';"></button>
                    <ul class="mb-0">`;
                errors.forEach(e => errorHtml += `<li>${e}</li>`);
                errorHtml += '</ul></div>';
                messageDiv.innerHTML = errorHtml;
                return;
            }
            // Remove errors on input
            form.querySelectorAll('input, select, textarea').forEach(el => {
                el.addEventListener('input', () => messageDiv.innerHTML = '');
            });
            // Submit via AJAX
            const formData = new FormData(form);
            fetch('add_visitor.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = `<div class="alert alert-success position-relative" role="alert" id="visitor-alert">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" aria-label="Close" onclick="this.parentElement.style.display='none';"></button>
                        ${data.message}
                    </div>`;
                    form.reset();
                } else if (data.errors) {
                    let errorHtml = `<div class="alert alert-danger position-relative" role="alert" id="visitor-alert">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" aria-label="Close" onclick="this.parentElement.style.display='none';"></button>
                        <ul class="mb-0">`;
                    for (const key in data.errors) {
                        errorHtml += `<li>${data.errors[key]}</li>`;
                    }
                    errorHtml += '</ul></div>';
                    messageDiv.innerHTML = errorHtml;
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger">${data.message || 'An error occurred.'}</div>`;
                }
            })
            .catch(() => {
                messageDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            });
        });
        // --- Improved UTC to Local utility ---
        window.utcToLocal = function(dateStr, timeStr) {
            if (!dateStr || !timeStr) return { localDate: '-', localTime: '-' };
            const utcDate = new Date(dateStr + 'T' + timeStr);
            if (isNaN(utcDate.getTime())) return { localDate: '-', localTime: '-' };
            // Format date as dd/mm/yyyy
            const day = String(utcDate.getDate()).padStart(2, '0');
            const month = String(utcDate.getMonth() + 1).padStart(2, '0');
            const year = utcDate.getFullYear();
            const localDate = `${day}/${month}/${year}`;
            // Format time as HH:MM (24-hour)
            const localTime = utcDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
            return { localDate, localTime };
        };
    })();
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Make recent and upcoming visitor rows clickable
        document.querySelectorAll('.visitor-card[data-visitor-id]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                // Prevent click if a link or button inside is clicked
                if (e.target.tagName === 'A' || e.target.closest('a,button')) return;
                var visitorId = this.getAttribute('data-visitor-id');
                if (visitorId) {
                    window.location.href = 'visitor_view_details.php?id=' + visitorId;
                }
            });
        });
    });
    </script>
    <!-- Add Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to log out?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="confirmLogoutBtn">Yes, Log Out</button>
          </div>
        </div>
      </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Attach modal to all logout links
        document.querySelectorAll('a[href="index.php?logout=1"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var modal = new bootstrap.Modal(document.getElementById('logoutModal'));
                modal.show();
            });
        });
        document.getElementById('confirmLogoutBtn').addEventListener('click', function() {
            window.location.href = 'index.php?logout=1';
        });
    });
    </script>
</body>
</html>
