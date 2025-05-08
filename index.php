<?php
session_start();
if (isset($_GET['logout'])) {
    // Unset all session variables
    $_SESSION = array();
    // Destroy the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    // Destroy the session
    session_destroy();
    header('Location: index.php');
    exit;
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die('Query failed: ' . mysqli_error($conn));
    }

    if ($row = mysqli_fetch_assoc($result)) {
        if ($password === $row['password_hash']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VisitorConnect - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            background-color: #f0f5ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .logo-text {
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 0;
        }
        .logo-text span {
            color: #3498db;
        }
        .subtitle {
            color: #5a6a85;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .login-container {
            max-width: 450px;
            margin: 3rem auto;
        }
        .login-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }
        .user-icon {
            width: 70px;
            height: 70px;
            background-color: #e6f0ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .user-icon i {
            color: #3498db;
            font-size: 28px;
        }
        .login-title {
            font-size: 1.75rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            color: #6c757d;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid #ced4da;
        }
        .input-group-text {
            background-color: transparent;
            border-right: none;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #3498db;
        }
        .forgot-pw {
            color: #3498db;
            text-decoration: none;
            float: right;
            font-size: 0.9rem;
        }
        .forgot-pw:hover {
            text-decoration: underline;
        }
        .btn-login {
            background-color: #3498db;
            color: white;
            padding: 0.75rem;
            font-weight: 500;
            border: none;
            width: 100%;
            margin-top: 1rem;
        }
        .btn-login:hover {
            background-color: #2980b9;
        }
        .divider {
            border-top: 1px solid #eeeeee;
            margin: 1.5rem 0;
        }
        .login-footer {
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
        }
        .login-footer a {
            color: #3498db;
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .login-credentials {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 1rem;
        }
        .login-credentials code {
            color: #e83e8c;
            background-color: #f1f1f1;
            padding: 2px 4px;
            border-radius: 3px;
        }
        .copyright {
            text-align: center;
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="text-center mb-4">
            <h1 class="logo-text">Visitor<span>Connect</span></h1>
            <p class="subtitle">Visitor Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="login-card">
            <div class="user-icon">
                <i class="fa-solid fa-user"></i>
            </div>
            <h2 class="login-title">Sign In</h2>
            <p class="login-subtitle">Enter your credentials to access your account</p>

            <form id="loginForm" method="POST" action="index.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="admin@example.com" required>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="password" class="form-label">Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember me for 30 days</label>
                </div>

                <button type="submit" class="btn btn-login">Sign in</button>
            </form>

            <div class="divider"></div>

            <div class="login-footer">
                Don't have an account? <a href="mailto:admin@example.com">Contact admin</a>
            </div>

            <div class="login-credentials">
                To login, use: <code>admin@example.com</code> / <code>admin123</code>
            </div>
        </div>

        <div class="copyright">
            © 2025 VisitorConnect. All rights reserved.
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
