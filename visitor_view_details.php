<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';
$admin_name = $_SESSION['user_name'] ?? 'Admin User';

$visitor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$visitor = null;
$visits = [];
$error = '';

if ($visitor_id > 0) {
    // Fetch visitor info
    $stmt = $conn->prepare("SELECT * FROM visitors WHERE id = ?");
    $stmt->bind_param('i', $visitor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $visitor = $row;
    } else {
        $error = 'Visitor not found.';
    }
    $stmt->close();

    // Fetch visit history
    $stmt = $conn->prepare("SELECT * FROM visits WHERE visitor_id = ? ORDER BY date DESC, time_in DESC");
    $stmt->bind_param('i', $visitor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $visits[] = $row;
    }
    $stmt->close();
} else {
    $error = 'Invalid visitor ID.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VisitorConnect - Visitor Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
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
        .main-content {
            margin-left: 200px;
            padding: 20px;
            transition: margin-left 0.3s;
        }
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
        .header a {
            color: #0d6efd;
        }
        .header a:hover {
            color: #0a58ca;
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
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .card-body {
            padding: 24px 24px 18px 24px;
        }
        .badge.status {
            background-color: #f8d7da;
            color: #dc3545;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 20px;
            padding: 6px 18px;
        }
        .btn-action {
            border-radius: 4px;
            font-weight: 500;
            padding: 5px 12px;
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></button>
    <div class="sidebar" id="sidebar">
        <div class="logo">VisitorConnect</div>
        <div class="main-menu">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php?screen=add-visitor"><i class="fas fa-user-plus"></i><span>Add Visitor</span></a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php?screen=visitor-list"><i class="fas fa-list"></i><span>Visitor List</span></a></li>
                <li class="nav-item"><a class="nav-link" href="settings.php?screen=settings"><i class="fas fa-gear"></i><span>Settings</span></a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
            </ul>
        </div>
    </div>
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header d-flex align-items-center justify-content-between mb-4" style="background:#fff; border-radius:0 12px 12px 0; box-shadow:0 1px 3px rgba(0,0,0,0.04); padding:18px 32px 18px 0; margin-left:-20px; margin-top:-20px;">
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
            <h1 class="h4 mb-1">Visitor Details</h1>
            <p class="text-muted mb-3" style="font-size:1rem;">View and manage visitor information</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($visitor): ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0"><?php echo htmlspecialchars($visitor['full_name']); ?></h2>
                            <?php if (!empty($visits[0]['status'])): ?>
                                <span class="badge status"><?php echo htmlspecialchars($visits[0]['status']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2">
                                <i class="fas fa-envelope me-2 text-muted"></i><span class="text-muted"><?php echo htmlspecialchars($visitor['email']); ?></span>
                            </div>
                            <div class="col-md-6 mb-2">
                                <i class="fas fa-phone me-2 text-muted"></i><span class="text-muted"><?php echo htmlspecialchars($visitor['phone']); ?></span>
                            </div>
                            <div class="col-md-6 mb-2">
                                <i class="fas fa-building me-2 text-muted"></i><span class="text-muted"><?php echo htmlspecialchars($visitor['company']); ?></span>
                            </div>
                        </div>
                        <?php if (!empty($visits)): ?>
                        <div class="mb-3">
                            <div class="fw-semibold mb-1">Latest Visit:</div>
                            <div class="row mb-2">
                                <div class="col-md-6 mb-2">
                                    <i class="fas fa-user me-2 text-muted"></i><span class="text-muted">Meeting with <strong><?php echo htmlspecialchars($visits[0]['person_to_meet']); ?></strong></span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <i class="fas fa-calendar me-2 text-muted"></i><span class="text-muted">Date: <?php echo date('d-m-Y', strtotime($visits[0]['date'])); ?></span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <i class="fas fa-clock me-2 text-muted"></i><span class="text-muted">Time In: <?php echo htmlspecialchars($visits[0]['time_in']); ?></span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <i class="fas fa-clock me-2 text-muted"></i><span class="text-muted">Time Out: <?php echo $visits[0]['time_out'] !== null ? htmlspecialchars($visits[0]['time_out']) : '-'; ?></span>
                                </div>
                            </div>
                            <div class="fw-semibold mb-1">Purpose of Visit:</div>
                            <div class="text-muted mb-2"><?php echo htmlspecialchars($visits[0]['purpose']); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="text-end">
                            <a href="settings.php?screen=add-visitor&fullName=<?php echo urlencode($visitor['full_name']); ?>&email=<?php echo urlencode($visitor['email']); ?>&phone=<?php echo urlencode($visitor['phone']); ?>&company=<?php echo urlencode($visitor['company']); ?>" class="btn btn-outline-primary">New Visit</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Visit History</h5>
                        <?php if (!empty($visits)): ?>
                            <?php foreach ($visits as $visit): ?>
                                <div class="border-start border-3 border-primary ps-3 mb-3">
                                    <div class="fw-semibold"><?php echo date('d-m-Y', strtotime($visit['date'])); ?></div>
                                    <div class="mb-1"><strong>Visit to <?php echo htmlspecialchars($visit['person_to_meet']); ?></strong></div>
                                    <div class="text-muted mb-1"><?php echo htmlspecialchars($visit['purpose']); ?></div>
                                    <div class="small">Time In: <?php echo htmlspecialchars($visit['time_in']); ?></div>
                                    <div class="small mb-2">Time Out: <?php echo $visit['time_out'] !== null ? htmlspecialchars($visit['time_out']) : '-'; ?></div>
                                    <span class="badge status"><?php echo htmlspecialchars($visit['status']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-muted">No visit history found.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Actions</h5>
                        <a href="settings.php?screen=add-visitor&fullName=<?php echo urlencode($visitor['full_name']); ?>&email=<?php echo urlencode($visitor['email']); ?>&phone=<?php echo urlencode($visitor['phone']); ?>&company=<?php echo urlencode($visitor['company']); ?>" class="btn btn-outline-secondary w-100 mb-2">Schedule New Visit</a>
                        <a href="#" class="btn btn-outline-secondary w-100" id="editVisitorBtn">Edit Visitor Info</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Edit Visitor Modal -->
        <div class="modal fade" id="editVisitorModal" tabindex="-1" aria-labelledby="editVisitorModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <form id="editVisitorForm">
                <div class="modal-header">
                  <h5 class="modal-title" id="editVisitorModalLabel">Edit Visitor Info</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div id="editVisitorMsg"></div>
                  <div class="mb-3">
                    <label for="editFullName" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="editFullName" name="full_name" value="<?php echo htmlspecialchars($visitor['full_name']); ?>" required>
                  </div>
                  <div class="mb-3">
                    <label for="editEmail" class="form-label">Email</label>
                    <input type="email" class="form-control" id="editEmail" name="email" value="<?php echo htmlspecialchars($visitor['email']); ?>" required>
                  </div>
                  <div class="mb-3">
                    <label for="editPhone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="editPhone" name="phone" value="<?php echo htmlspecialchars($visitor['phone']); ?>" required>
                  </div>
                  <div class="mb-3">
                    <label for="editCompany" class="form-label">Company</label>
                    <input type="text" class="form-control" id="editCompany" name="company" value="<?php echo htmlspecialchars($visitor['company']); ?>" required>
                  </div>
                  <input type="hidden" name="visitor_id" value="<?php echo $visitor_id; ?>">
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary">Save Changes</button>
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
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

    document.getElementById('editVisitorBtn').addEventListener('click', function(e) {
      e.preventDefault();
      var modal = new bootstrap.Modal(document.getElementById('editVisitorModal'));
      modal.show();
    });

    document.getElementById('editVisitorForm').addEventListener('submit', function(e) {
      e.preventDefault();
      var form = e.target;
      var msgDiv = document.getElementById('editVisitorMsg');
      msgDiv.innerHTML = '';
      var formData = new FormData(form);
      fetch('update_visitor_info.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          msgDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
          setTimeout(() => { window.location.reload(); }, 1200);
        } else {
          msgDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Failed to update visitor info.') + '</div>';
        }
      })
      .catch(() => {
        msgDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
      });
    });

    // Attach modal to all logout links
    document.addEventListener('DOMContentLoaded', function() {
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