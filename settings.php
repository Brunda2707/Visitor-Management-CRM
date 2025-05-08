<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';
$admin_name = $_SESSION['user_name'] ?? 'Admin User';

// Fetch company info (id=1)
$company_name = '';
$company_phone = '';
$company_address = '';
$notification_email = '';

$stmt = $conn->prepare("SELECT company_name, phone, address, notification_email FROM company_info WHERE id=1");
if ($stmt && $stmt->execute()) {
    $stmt->bind_result($company_name, $company_phone, $company_address, $notification_email);
    $stmt->fetch();
    $stmt->close();
}

// Pre-fill values from GET if available
$prefill_fullName = isset($_GET['fullName']) ? htmlspecialchars($_GET['fullName']) : '';
$prefill_email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
$prefill_phone = isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : '';
$prefill_company = isset($_GET['company']) ? htmlspecialchars($_GET['company']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VisitorConnect - Visitor Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        }

        .sidebar .logo {
            color: #0d6efd;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 25px;
            padding-left: 5px;
        }

        .sidebar .main-menu .nav-link {
            color: #495057;
            border-radius: 5px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .sidebar .main-menu .nav-link:hover {
            background-color: #f0f2f5;
        }

        .sidebar .main-menu .nav-link.active {
            background-color: #e7f1ff;
            color: #0d6efd;
        }

        .sidebar .main-menu .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 200px;
            padding: 20px;
        }

        /* Header */
        .header {
            background-color: #fff;
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-top: -20px;
        }

        .search-container {
            position: relative;
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

        .header .user-menu {
            display: flex;
            align-items: center;
        }

        .header .user-menu .user-info {
            margin-right: 10px;
        }

        .header .user-menu .notifications {
            margin-right: 15px;
            position: relative;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 4px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
        }

        .table {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 1px solid #e0e0e0;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-scheduled {
            background-color: rgba(13, 110, 253, 0.1);
            color: var(--primary-color);
        }

        .status-checked-in {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .status-checked-out {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .btn-action {
            border-radius: 4px;
            font-weight: 500;
            padding: 5px 12px;
        }

        .btn-check-in {
            background-color: #f8f9fa;
            border-color: #e0e0e0;
            color: #495057;
        }

        .btn-check-out {
            background-color: #212529;
            border-color: #212529;
            color: #fff;
        }

        .btn-view {
            background-color: #f8f9fa;
            border-color: #e0e0e0;
            color: #495057;
            text-decoration: none;
        }

        .filter-section {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        /* Screen specific styles */
        #add-visitor-screen {
            display: none;
        }

        #visitor-list-screen {
            display: none;
        }

        #settings-screen {
            display: none;
        }

        .section-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .section-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 15px;
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
            .header .d-flex.align-items-center.gap-3 {
                margin-right: 0 !important;
            }
        }

        /* Restore button borders and Bootstrap defaults */
        .btn, .btn-primary, .btn-secondary, .btn-light {
            background-color: initial !important;
            color: initial !important;
            opacity: 1 !important;
            /* Remove border: initial !important; */
        }
        .btn-primary {
            background-color: #0d6efd !important;
            color: #fff !important;
            border-color: #0d6efd !important;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #0b5ed7 !important;
            color: #fff !important;
            border-color: #0a58ca !important;
        }
        .btn-secondary {
            background-color: #6c757d !important;
            color: #fff !important;
            border-color: #6c757d !important;
        }
        .btn-light {
            background-color: #f8f9fa !important;
            color: #212529 !important;
            border-color: #dee2e6 !important;
        }
        .btn-outline-primary {
            color: #0d6efd !important;
            border: 1px solid #0d6efd !important;
            background: transparent !important;
        }
        .btn-outline-primary:hover, .btn-outline-primary:focus {
            background: #0d6efd !important;
            color: #fff !important;
        }
        .btn-outline-secondary {
            color: #6c757d !important;
            border: 1px solid #6c757d !important;
            background: transparent !important;
        }
        .btn-outline-secondary:hover, .btn-outline-secondary:focus {
            background: #6c757d !important;
            color: #fff !important;
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
                    <a class="nav-link" href="dashboard.php">
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

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header d-flex align-items-center justify-content-between mb-4" style="background:#fff; border-radius:0 12px 12px 0; box-shadow:0 1px 3px rgba(0,0,0,0.04); padding:18px 32px 18px 0; margin-left:-20px;">
            <div class="header-logo d-flex align-items-center gap-3" style="padding-left:12px;">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Visitor Management Logo" style="height:38px;width:38px;border-radius:10px;object-fit:cover;box-shadow:0 2px 8px rgba(52,152,219,0.10);background:#fff;">
                <span class="logo-text" style="font-size:1.3rem;font-weight:600;color:#1976d2;letter-spacing:0.5px;">VisitorConnect</span>
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

        <!-- Add Visitor Screen -->
        <div id="add-visitor-screen">
            <h2 class="mb-4">Add New Visitor</h2>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Register New Visitor</h5>
                    <div id="visitor-message"></div>
                    <form id="visitorForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fullName" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="fullName" name="fullName" required placeholder="Enter visitor's full name" value="<?php echo $prefill_fullName; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter email address" value="<?php echo $prefill_email; ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" required pattern="^[0-9\-\+\s\(\)]+$" placeholder="Enter phone number" value="<?php echo $prefill_phone; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="company" class="form-label">Company</label>
                                <input type="text" class="form-control" id="company" name="company" required placeholder="Enter company name" value="<?php echo $prefill_company; ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="personToMeet" class="form-label">Person to Meet</label>
                                <select class="form-select" id="personToMeet" name="personToMeet" required>
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
                                <label for="visitDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="visitDate" name="visitDate" required placeholder="Select date">
                            </div>
                            <div class="col-md-6">
                                <label for="visitTime" class="form-label">Time</label>
                                <input type="time" class="form-control" id="visitTime" name="visitTime" required step="900" placeholder="Select time">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="purpose" class="form-label">Purpose of Visit</label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="4" required placeholder="Enter purpose of visit"></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="vehicleNumberPlate" class="form-label">Vehicle Number Plate</label>
                                <input type="text" class="form-control" id="vehicleNumberPlate" name="vehicleNumberPlate" required placeholder="Enter vehicle number plate">
                            </div>
                            <div class="col-md-6">
                                <label for="vehicleOwnerName" class="form-label">Vehicle Owner Name</label>
                                <input type="text" class="form-control" id="vehicleOwnerName" name="vehicleOwnerName" required placeholder="Enter vehicle owner name">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amountPayable" class="form-label">Amount Payable</label>
                                <input type="number" class="form-control" id="amountPayable" name="amountPayable" required min="0.01" step="0.01" placeholder="Enter amount payable">
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-light me-2" onclick="showScreen('visitor-list')">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="registerVisitor()">Register Visitor</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Settings Screen -->
        <div id="settings-screen">
            <div class="d-flex align-items-center mb-4">
                <i class="fas fa-cog me-2"></i>
                <h2>Settings</h2>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Company Information -->
                    <div class="section-card mb-4">
                        <h3 class="section-title">Company Information</h3>
                        <p class="section-subtitle">Update your company details</p>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="companyName" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="companyName" value="<?php echo htmlspecialchars($company_name); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="companyPhone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="companyPhone" value="<?php echo htmlspecialchars($company_phone); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="companyAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="companyAddress" rows="3"><?php echo htmlspecialchars($company_address); ?></textarea>
                        </div>

                        <button class="btn btn-primary">Save Changes</button>
                    </div>

                    <!-- Email Notifications -->
                    <div class="section-card">
                        <h3 class="section-title">Email Notifications</h3>
                        <p class="section-subtitle">Configure visitor notification settings</p>

                        <div class="mb-3">
                            <label for="notificationEmail" class="form-label">Notification Email</label>
                            <input type="email" class="form-control" id="notificationEmail" value="<?php echo htmlspecialchars($notification_email); ?>">
                        </div>

                        <button class="btn btn-primary">Save Changes</button>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- System Information -->
                    <div class="section-card mb-4">
                        <h3 class="section-title">System Information</h3>

                        <div class="mt-3">
                            <p class="info-label">Version</p>
                            <p class="info-value">VisitorConnect 1.0</p>

                            <p class="info-label">Last Updated</p>
                            <p class="info-value">May 1, 2025</p>

                            <p class="info-label">License</p>
                            <p class="info-value">Enterprise</p>
                        </div>
                    </div>

                    <!-- Support -->
                    <div class="section-card">
                        <h3 class="section-title">Support</h3>

                        <p class="mt-3">Need help with your visitor management system? Contact our support team.</p>

                        <div class="d-grid mt-3">
                            <button class="btn btn-outline-primary" onclick="window.location.href='mailto:support@mailinator'">Contact Support</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visitor List Screen -->
        <div id="visitor-list-screen">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Visitor Records</h2>
                <button class="btn btn-primary" onclick="showScreen('add-visitor')">
                    <i class="fas fa-plus me-2"></i> Add Visitor
                </button>
            </div>
            <div class="filter-section mb-4">
                <div class="row g-2">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0" style="margin-right:8px;">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Search by name or email...">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="fas fa-calendar text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="dateRangeInput" placeholder="Select date range..." readonly>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex">
                        <button class="btn btn-outline-secondary flex-grow-1 me-2" id="filterBtn">Filter</button>
                        <button class="btn btn-outline-secondary flex-grow-1" id="resetBtn">Reset</button>
                        </div>
                    </div>
                </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div></div>
                <div class="d-flex align-items-center">
                    <select class="form-select me-2" style="width: 150px;" id="statusFilter">
                        <option>All Status</option>
                        <option>Checked In</option>
                        <option>Checked Out</option>
                        <option>Scheduled</option>
                    </select>
                    <button class="btn btn-outline-primary" id="exportBtn">Export</button>
                    </div>
                </div>
            <div id="dataLoader" class="text-center my-4" style="display:none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="visitorTable">
                    <thead>
                        <tr>
                            <th>Visitor Name</th>
                            <th>Company</th>
                            <th>Person To Meet</th>
                            <th>Date & Time In</th>
                            <th>Amount Status</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be injected here -->
                    </tbody>
                </table>
            </div>
            <div id="pagination" class="mt-3"></div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
        <div id="visitorToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="visitorToastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="checkoutForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="checkoutModalLabel">Check Out Visitor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="checkoutAmountSection" class="mb-3">
                            <label class="form-label">Amount Payable:</label>
                            <div id="checkoutAmountValue" style="font-weight:bold"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Has the visitor paid the amount?</label>
                            <div>
                                <input type="radio" id="amountPaidYes" name="amountPaid" value="Yes" required>
                                <label for="amountPaidYes">Yes</label>
                                <input type="radio" id="amountPaidNo" name="amountPaid" value="No" required>
                                <label for="amountPaidNo">No</label>
                            </div>
                        </div>
                        
                        <!-- Invoice Preview Section (initially hidden) -->
                        <div id="invoicePreviewSection" style="display: none;">
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Invoice Preview</h6>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="printInvoice()">
                                        <i class="fas fa-print"></i> Print Invoice
                                    </button>
                                </div>
                                <div class="card-body" id="invoiceContent">
                                    <!-- Invoice content will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <div id="checkoutTimeSection">
                            <label for="checkoutTime" class="form-label">Select Check Out Time</label>
                            <input type="time" class="form-control" id="checkoutTime" required>
                        </div>
                        <input type="hidden" id="checkoutVisitId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Check Out</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Place this at the top of your <script> tag, before any function or event handler
        let companyInfo = { name: '', address: '' };

        // Function to show different screens
        function showScreen(screenId) {
            // Hide all screens
            document.getElementById('add-visitor-screen').style.display = 'none';
            document.getElementById('visitor-list-screen').style.display = 'none';
            document.getElementById('settings-screen').style.display = 'none';

            // Show the selected screen
            if (screenId === 'add-visitor') {
                document.getElementById('add-visitor-screen').style.display = 'block';
                // Update active nav
                document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.textContent.trim() === 'Add Visitor') {
                        link.classList.add('active');
                    }
                });
            } else if (screenId === 'visitor-list') {
                document.getElementById('visitor-list-screen').style.display = 'block';
                // Update active nav
                document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.textContent.trim() === 'Visitor List') {
                        link.classList.add('active');
                    }
                });
            } else if (screenId === 'settings') {
                document.getElementById('settings-screen').style.display = 'block';
                // Update active nav
                document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.textContent.trim() === 'Settings') {
                        link.classList.add('active');
                    }
                });
            }
        }

        // Function to register a new visitor
        function registerVisitor() {
            const form = document.getElementById('visitorForm');
            const messageDiv = document.getElementById('visitor-message');
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
            const vehicleNumberPlate = form.vehicleNumberPlate.value.trim();
            const vehicleOwnerName = form.vehicleOwnerName.value.trim();
            const amountPayable = form.amountPayable.value.trim();

            // Regex for phone
            const phoneRegex = /^[0-9\-\+\s\(\)]+$/;

            if (!fullName) errors.push('Full name is required.');
            if (!email || !/^[^@]+@[^@]+\.[^@]+$/.test(email)) errors.push('Invalid email address.');
            if (!phone || !phoneRegex.test(phone)) errors.push('Invalid phone number.');
            if (!company) errors.push('Company is required.');
            if (!personToMeet) errors.push('Person to meet is required.');
            if (!visitDate) errors.push('Date is required.');
            if (!visitTime) errors.push('Time is required.');
            if (!purpose) errors.push('Purpose is required.');
            if (!vehicleNumberPlate) errors.push('Vehicle number plate is required.');
            if (!vehicleOwnerName) errors.push('Vehicle owner name is required.');
            if (!amountPayable || isNaN(amountPayable) || Number(amountPayable) <= 0) {
                errors.push('Amount payable must be greater than 0.');
            }

            if (errors.length > 0) {
                let errorHtml = `
                    <div class="alert alert-danger position-relative" role="alert" id="visitor-alert">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" aria-label="Close" onclick="this.parentElement.style.display='none';"></button>
                        <ul class="mb-0">
                `;
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
                    messageDiv.innerHTML = `
                        <div class="alert alert-success position-relative" role="alert" id="visitor-alert">
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" aria-label="Close" onclick="this.parentElement.style.display='none';"></button>
                            ${data.message}
                        </div>
                    `;
                    form.reset();
                    setTimeout(() => {
                        window.location.href = 'settings.php?screen=visitor-list';
                    }, 1500);
                } else if (data.errors) {
                    let errorHtml = `
                        <div class="alert alert-danger position-relative" role="alert" id="visitor-alert">
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" aria-label="Close" onclick="this.parentElement.style.display='none';"></button>
                            <ul class="mb-0">
                    `;
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
        }

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            // Get screen parameter from URL
            const urlParams = new URLSearchParams(window.location.search);
            const screen = urlParams.get('screen') || 'visitor-list';
            
            // Set default screen
            showScreen(screen);

            // Set min date to today
            const dateInput = document.getElementById('visitDate');
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
            const timeInput = document.getElementById('visitTime');

            // Open time picker on focus/click
            timeInput.addEventListener('focus', function() { this.showPicker && this.showPicker(); });
            timeInput.addEventListener('click', function() { this.showPicker && this.showPicker(); });

            // Prevent past time if today is selected
            dateInput.addEventListener('change', function() {
                if (dateInput.value === minDate) {
                    // Set min time to now (rounded to next 15 min)
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

            document.getElementById('checkoutTime').addEventListener('focus', function() {
                if (this.showPicker) this.showPicker();
            });
            document.getElementById('checkoutTime').addEventListener('click', function() {
                if (this.showPicker) this.showPicker();
            });

            // Fix: Remove lingering modal backdrop and modal-open class on modal close
            var checkoutModal = document.getElementById('checkoutModal');
            if (checkoutModal) {
                checkoutModal.addEventListener('hidden.bs.modal', function () {
                    document.body.classList.remove('modal-open');
                    document.body.style = '';
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                });
            }

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

            // Attach event delegation for visitor table actions only once
            const visitorTable = document.querySelector('#visitorTable');
            if (visitorTable) {
                visitorTable.addEventListener('click', function(e) {
                    if (e.target.classList.contains('btn-check-in')) {
                        const visitId = e.target.getAttribute('data-id');
                        // Get the visit date from the row
                        const row = e.target.closest('tr');
                        const dateCell = row.querySelector('td:nth-child(4)');
                        let dateText = dateCell ? dateCell.textContent.split(' at ')[0].trim() : '';
                        // Robust date parsing: support dd/mm/yyyy and fallback
                        let validCheckin = false;
                        let visitDate, today;
                        if (dateText && dateText !== '-' && dateText !== 'No Visits') {
                            let day, month, year;
                            // Try dd/mm/yyyy
                            const parts = dateText.split('/');
                            if (parts.length === 3) {
                                [day, month, year] = parts;
                                // Check for 4-digit year
                                if (year && year.length === 4) {
                                    visitDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
                                }
                            }
                            today = new Date();
                            today.setHours(0,0,0,0);
                            if (visitDate) visitDate.setHours(0,0,0,0);
                            // Debug log
                            console.log('dateText:', dateText, 'Parsed:', day, month, year, 'visitDate:', visitDate, 'today:', today);
                            if (visitDate && visitDate.getTime() <= today.getTime()) {
                                validCheckin = true;
                            }
                        }
                        if (!validCheckin) {
                            showToast('Check-in is only allowed on or after the scheduled date.', 'danger');
                            return;
                        }
                        e.target.disabled = true;
                        fetch('update_visit_status.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `visit_id=${encodeURIComponent(visitId)}&action=checkin`
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Visitor checked in successfully!', 'success');
                                fetchVisitors(currentPage);
                            } else {
                                showToast(data.message || 'Failed to check in', 'danger');
                                e.target.disabled = false;
                            }
                        })
                        .catch(() => {
                            showToast('Failed to check in', 'danger');
                            e.target.disabled = false;
                        });
                    }
                    if (e.target.classList.contains('btn-check-out')) {
                        const visitId = e.target.getAttribute('data-id');
                        openCheckoutModal(visitId);
                    }
                    if (e.target.classList.contains('btn-view')) {
                        // Find the closest row and get the visitor_id
                        const row = e.target.closest('tr');
                        const visitorId = row.getAttribute('data-visitor-id');
                        if (visitorId) {
                            window.location.href = `visitor_view_details.php?id=${visitorId}`;
                        }
                    }
                });
            }

            // Make visitor rows clickable to view details
            document.querySelector('#visitorTable tbody').addEventListener('click', function(e) {
                // Find the closest .visitor-row (in case a cell or span is clicked)
                const row = e.target.closest('.visitor-row');
                if (!row) return;
                // Prevent row click if a button or link inside is clicked
                if (e.target.closest('button') || e.target.tagName === 'A') return;
                const visitorId = row.getAttribute('data-visitor-id');
                if (visitorId) {
                    window.location.href = `visitor_view_details.php?id=${visitorId}`;
                }
            });

            fetch('get_company_info.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        companyInfo.name = data.company_name;
                        companyInfo.address = data.company_address;
                    }
                });
        });

        let currentPage = 1;
        let currentSearch = '';
        let currentDateFrom = '';
        let currentDateTo = '';
        let currentStatus = '';

        function showLoader() {
            document.getElementById('dataLoader').style.display = 'block';
        }
        function hideLoader() {
            document.getElementById('dataLoader').style.display = 'none';
        }

        function fetchVisitors(page = 1) {
            showLoader();
            currentPage = page;
            let params = new URLSearchParams({
                page: currentPage,
                search: currentSearch,
                date_from: currentDateFrom,
                date_to: currentDateTo,
                status: currentStatus
            });
            fetch('get_visitors.php?' + params.toString())
                .then(res => res.json())
                .then(data => {
                    hideLoader();
                    if (data.success) {
                        renderVisitorTable(data.data);
                        renderPagination(data.total, data.perPage, data.page);
                    }
                })
                .catch(() => {
                    hideLoader();
                });
        }

        function renderVisitorTable(rows) {
            const tbody = document.querySelector('#visitorTable tbody');
            tbody.innerHTML = '';
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No records found.</td></tr>';
                return;
            }
            rows.forEach(row => {
                let statusClass = '';
                if (row.status === 'Checked In') statusClass = 'status-badge status-checked-in';
                else if (row.status === 'Checked Out') statusClass = 'status-badge status-checked-out';
                else if (row.status === 'Scheduled') statusClass = 'status-badge status-scheduled';
                let actions = '';
                if (row.status === 'Checked In') actions = `<button class="btn btn-action btn-check-out" data-id="${row.visit_id}" data-action="checkout">Check Out</button>`;
                else if (row.status === 'Scheduled') actions = `<button class="btn btn-action btn-check-in" data-id="${row.visit_id}" data-action="checkin">Check In</button>`;
                else actions = `<button class="btn btn-action btn-view" data-id="${row.visit_id}">View</button>`;
                // Only show date/time if both are present
                let dateTimeDisplay = 'No Visits';
                if (row.date && row.time_in) {
                    const local = window.utcToLocal(row.date, row.time_in);
                    dateTimeDisplay = `${local.localDate} at ${local.localTime}`;
                }
                // Amount status
                let amountStatus = '';
                if (row.amount_paid === 'Yes') {
                    amountStatus = '<span class="badge bg-success">Paid</span>';
                } else {
                    amountStatus = '<span class="badge bg-danger">Unpaid</span>';
                }
                tbody.innerHTML += `
                    <tr class="visitor-row" data-visitor-id="${row.visitor_id}" style="cursor:pointer;">
                        <td>${row.full_name}</td>
                        <td>${row.company}</td>
                        <td>${row.person_to_meet || '-'}</td>
                        <td>${dateTimeDisplay}</td>
                        <td>${amountStatus}</td>
                        <td><span class="${statusClass}">${row.status || '-'}</span></td>
                        <td>${actions}</td>
                    </tr>
                `;
            });
        }

        function renderPagination(total, perPage, page) {
            const pagDiv = document.getElementById('pagination');
            const totalPages = Math.ceil(total / perPage);
            if (totalPages <= 1) { pagDiv.innerHTML = ''; return; }
            let html = '<nav><ul class="pagination justify-content-center">';
            for (let i = 1; i <= totalPages; i++) {
                html += `<li class="page-item${i === page ? ' active' : ''}">
                    <a class="page-link" href="#" onclick="fetchVisitors(${i});return false;">${i}</a>
                </li>`;
            }
            html += '</ul></nav>';
            pagDiv.innerHTML = html;
        }

        // Search by name/email
        document.getElementById('searchInput').addEventListener('input', function() {
            currentSearch = this.value;
            fetchVisitors(1);
        });

        // Status filter
        document.getElementById('statusFilter').addEventListener('change', function() {
            currentStatus = this.value;
            fetchVisitors(1);
        });

        // Filter button
        document.getElementById('filterBtn').addEventListener('click', function() {
            fetchVisitors(1);
        });

        // Reset button
        document.getElementById('resetBtn').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            document.getElementById('dateRangeInput').value = '';
            document.getElementById('statusFilter').value = 'All Status';
            currentSearch = '';
            currentDateFrom = '';
            currentDateTo = '';
            currentStatus = '';
            fetchVisitors(1);
        });

        // Export button
        document.getElementById('exportBtn').addEventListener('click', function() {
            let params = new URLSearchParams({
                search: currentSearch,
                date_from: currentDateFrom,
                date_to: currentDateTo,
                status: currentStatus,
                export: 1
            });
            window.open('get_visitors.php?' + params.toString(), '_blank');
        });

        // Date range picker (using flatpickr for best UX)
        flatpickr("#dateRangeInput", {
            mode: "range",
            dateFormat: "d-m-Y",
            onClose: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    currentDateFrom = selectedDates[0].toISOString().slice(0,10);
                    currentDateTo = selectedDates[1].toISOString().slice(0,10);
                } else {
                    currentDateFrom = '';
                    currentDateTo = '';
                }
                fetchVisitors(1);
            }
        });

        // Initial fetch
        fetchVisitors(1);

        function showToast(message, type = 'primary') {
            const toastEl = document.getElementById('visitorToast');
            const toastMsg = document.getElementById('visitorToastMsg');
            toastEl.className = `toast align-items-center text-bg-${type} border-0`;
            toastMsg.textContent = message;
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        // Add this function to handle invoice generation
        function generateInvoice(visitData) {
            const invoiceContent = document.getElementById('invoiceContent');
            const invoiceHtml = `
                <div class="invoice-container">
                    <div class="text-center mb-4">
                        <h4>INVOICE</h4>
                        <p class="mb-1">${companyInfo.name || 'Your Company Name'}</p>
                        <p class="mb-1">${companyInfo.address || 'Your Company Address'}</p>
                    </div>
                    <div class="row mb-4">
                        <div class="col-6">
                            <p><strong>Visitor Name:</strong> ${visitData.full_name}</p>
                            <p><strong>Company:</strong> ${visitData.company}</p>
                            <p><strong>Phone:</strong> ${visitData.phone}</p>
                            <p><strong>Email:</strong> ${visitData.email}</p>
                        </div>
                        <div class="col-6 text-end">
                            <p><strong>Date:</strong> ${visitData.date}</p>
                            <p><strong>Time In:</strong> ${visitData.time_in}</p>
                            <p><strong>Vehicle Number:</strong> ${visitData.vehicle_number_plate}</p>
                            <p><strong>Vehicle Owner:</strong> ${visitData.vehicle_owner_name}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Visitor Management Fee</td>
                                        <td class="text-end">₹${visitData.amount_payable}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="1" class="text-end"><strong>Total Amount:</strong></td>
                                        <td class="text-end"><strong>₹${visitData.amount_payable}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            invoiceContent.innerHTML = invoiceHtml;
        }

        // Update the openCheckoutModal function
        function openCheckoutModal(visitId) {
            fetch('get_visit_details.php?id=' + visitId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('checkoutAmountValue').textContent =
                            data.amount_payable ? `₹${data.amount_payable}` : 'N/A';
                        document.getElementById('checkoutVisitId').value = visitId;
                        
                        // Store visit data for invoice generation
                        window.currentVisitData = data;
                        
                        // Show the modal
                        var modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
                        modal.show();
                    } else {
                        showToast('Could not fetch visit details.', 'danger');
                    }
                })
                .catch(() => {
                    showToast('Could not fetch visit details.', 'danger');
                });
        }

        // Add event listeners for the radio buttons
        document.querySelectorAll('input[name="amountPaid"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const invoiceSection = document.getElementById('invoicePreviewSection');
                const checkoutTimeSection = document.getElementById('checkoutTimeSection');
                
                if (this.value === 'No') {
                    // Show invoice preview
                    invoiceSection.style.display = 'block';
                    generateInvoice(window.currentVisitData);
                    // ALSO show checkout time section
                    checkoutTimeSection.style.display = 'block';
                } else {
                    // Hide invoice preview and show checkout time
                    invoiceSection.style.display = 'none';
                    checkoutTimeSection.style.display = 'block';
                }
            });
        });

        // Add print function
        function printInvoice() {
            const printContent = document.getElementById('invoiceContent').innerHTML;
            // Open a new window for printing
            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Invoice</title>
                    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; }
                        .invoice-container { max-width: 700px; margin: auto; }
                    </style>
                </head>
                <body>
                    <div class="invoice-container">
                        ${printContent}
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            // Optionally, close the print window after printing
            printWindow.onafterprint = function() { printWindow.close(); };
        }

        // Update the form submit handler
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const visitId = document.getElementById('checkoutVisitId').value;
            const timeOut = document.getElementById('checkoutTime').value;
            const amountPaid = document.querySelector('input[name="amountPaid"]:checked')?.value;

            if (!visitId || !timeOut || !amountPaid) {
                showToast('Please fill all required fields', 'danger');
                return;
            }

            fetch('update_visit_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `visit_id=${encodeURIComponent(visitId)}&action=checkout&time_out=${encodeURIComponent(timeOut)}&amount_paid=${amountPaid}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Visitor checked out successfully!', 'success');
                    fetchVisitors(currentPage);
                    const modalEl = document.getElementById('checkoutModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style = '';
                    }
                } else {
                    showToast(data.message || 'Failed to check out', 'danger');
                }
            })
            .catch(() => {
                showToast('Failed to check out', 'danger');
            });
        });

        function setActiveSidebar(screenId) {
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
                if (
                    (screenId === 'add-visitor' && link.textContent.trim() === 'Add Visitor') ||
                    (screenId === 'visitor-list' && link.textContent.trim() === 'Visitor List') ||
                    (screenId === 'settings' && link.textContent.trim() === 'Settings') ||
                    (screenId === 'dashboard' && link.textContent.trim() === 'Dashboard')
                ) {
                    link.classList.add('active');
                }
            });
        }

        // Sidebar toggle for mobile
        if (document.getElementById('sidebarToggle')) {
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
        }

        // Company Info Save
        const companySaveBtn = document.querySelector('.section-card .btn.btn-primary');
        if (companySaveBtn) {
            companySaveBtn.addEventListener('click', function() {
                const name = document.getElementById('companyName').value.trim();
                const phone = document.getElementById('companyPhone').value.trim();
                const address = document.getElementById('companyAddress').value.trim();
                const email = document.getElementById('notificationEmail').value.trim();
                let error = '';
                if (!name) error = 'Company name is required.';
                else if (!phone) error = 'Phone number is required.';
                else if (!address) error = 'Address is required.';
                else if (!email || !/^\S+@\S+\.\S+$/.test(email)) error = 'Valid notification email is required.';
                if (error) {
                    showToast(error, 'danger');
                    return;
                }
                fetch('update_company_info.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `company_name=${encodeURIComponent(name)}&phone=${encodeURIComponent(phone)}&address=${encodeURIComponent(address)}&notification_email=${encodeURIComponent(email)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) showToast('Company info updated successfully!', 'success');
                    else showToast(data.message || 'Failed to update company info.', 'danger');
                })
                .catch(() => showToast('An error occurred. Please try again.', 'danger'));
            });
        }

        // Email Notification Save
        const notificationSaveBtn = document.querySelectorAll('.section-card .btn.btn-primary')[1];
        if (notificationSaveBtn) {
            notificationSaveBtn.addEventListener('click', function() {
                const email = document.getElementById('notificationEmail').value.trim();
                if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
                    showToast('Valid notification email is required.', 'danger');
                    return;
                }
                fetch('update_company_info.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `notification_email=${encodeURIComponent(email)}&update_type=notification_email`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) showToast('Notification email updated successfully!', 'success');
                    else showToast(data.message || 'Failed to update notification email.', 'danger');
                })
                .catch(() => showToast('An error occurred. Please try again.', 'danger'));
            });
        }

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
    </script>
</body>
</html>
