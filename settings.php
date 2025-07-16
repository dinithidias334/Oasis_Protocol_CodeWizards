<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.html');
    exit();
}

require '../config.php';
$db = db_connect();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['change_password'])) {
        $old = $_POST['old_pass'];
        $new = $_POST['new_pass'];
        $confirm = $_POST['confirm_pass'];

        if ($new !== $confirm) {
            $msg = '<div class="alert alert-error">New passwords do not match!</div>';
        } else {
            $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
            $stmt->bind_param('s', $_SESSION['admin']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $hash = $result->fetch_assoc()['password_hash'];
                
                if ($old === $hash || password_verify($old, $hash)) {
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
                    $stmt->bind_param('ss', $newHash, $_SESSION['admin']);
                    $stmt->execute();
                    $msg = '<div class="alert alert-success">Password updated successfully!</div>';
                } else {
                    $msg = '<div class="alert alert-error">Current password is incorrect!</div>';
                }
            }
        }
    }
}

// Get platform statistics
$total_teams = $db->query("SELECT COUNT(*) FROM teams")->fetch_row()[0];
$total_challenges = $db->query("SELECT COUNT(*) FROM challenges")->fetch_row()[0];
$total_submissions = $db->query("SELECT COUNT(*) FROM submissions")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-dark: #2563eb;
            --primary-blue-light: #dbeafe;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #06b6d4;
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
        }

        .admin-dashboard {
            display: grid;
            grid-template-areas: 
                "sidebar header header"
                "sidebar main main";
            grid-template-columns: 280px 1fr;
            grid-template-rows: 70px 1fr;
            height: 100vh;
        }

        .sidebar {
            grid-area: sidebar;
            background: linear-gradient(180deg, var(--gray-900) 0%, var(--gray-800) 100%);
            padding: var(--spacing-lg);
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            padding-bottom: var(--spacing-lg);
            border-bottom: 1px solid var(--gray-600);
            margin-bottom: var(--spacing-lg);
        }

        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: var(--primary-blue);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: var(--spacing-md);
        }

        .sidebar-title {
            color: white;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav-item {
            margin-bottom: var(--spacing-sm);
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: var(--spacing-md);
            color: var(--gray-300);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }

        .sidebar-nav-link:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-blue-light);
        }

        .sidebar-nav-link.active {
            background: var(--primary-blue);
            color: white;
        }

        .sidebar-nav-icon {
            width: 20px;
            margin-right: var(--spacing-md);
            text-align: center;
        }

        .dashboard-header {
            grid-area: header;
            background: white;
            padding: 0 var(--spacing-xl);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-800);
        }

        .dashboard-main {
            grid-area: main;
            padding: var(--spacing-xl);
            overflow-y: auto;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: var(--spacing-lg);
        }

        .dashboard-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            height: fit-content;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-lg);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
        }

        .form-group {
            margin-bottom: var(--spacing-md);
        }

        .form-group label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-group input {
            width: 100%;
            padding: var(--spacing-md);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: var(--spacing-md);
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
        }

        .btn-primary:hover {
            background: var(--primary-blue-dark);
        }

        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-md) 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: var(--gray-600);
        }

        .stat-value {
            font-weight: 600;
            color: var(--primary-blue);
        }

        .info-box {
            background: var(--primary-blue-light);
            border: 1px solid var(--primary-blue);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .info-box h4 {
            color: var(--primary-blue-dark);
            margin-bottom: var(--spacing-xs);
        }

        .info-box p {
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .admin-dashboard {
                grid-template-areas: 
                    "header"
                    "main";
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-code"></i>
                </div>
                <div class="sidebar-title">Admin Panel</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="sidebar-nav-item">
                    <a href="dashboard.php" class="sidebar-nav-link">
                        <i class="fas fa-chart-bar sidebar-nav-icon"></i>
                        Dashboard
                    </a>
                </div>
                <div class="sidebar-nav-item">
                    <a href="challenges.php" class="sidebar-nav-link">
                        <i class="fas fa-tasks sidebar-nav-icon"></i>
                        Challenges
                    </a>
                </div>
                <div class="sidebar-nav-item">
                    <a href="teams.php" class="sidebar-nav-link">
                        <i class="fas fa-users sidebar-nav-icon"></i>
                        Teams
                    </a>
                </div>
                <div class="sidebar-nav-item">
                    <a href="submissions.php" class="sidebar-nav-link">
                        <i class="fas fa-file-alt sidebar-nav-icon"></i>
                        Submissions
                    </a>
                </div>
                <div class="sidebar-nav-item">
                    <a href="reports.php" class="sidebar-nav-link">
                        <i class="fas fa-chart-line sidebar-nav-icon"></i>
                        Reports
                    </a>
                </div>
                <div class="sidebar-nav-item">
                    <a href="settings.php" class="sidebar-nav-link active">
                        <i class="fas fa-cog sidebar-nav-icon"></i>
                        Settings
                    </a>
                </div>
                <div class="sidebar-nav-item">
                    <a href="admin_logout.php" class="sidebar-nav-link">
                        <i class="fas fa-sign-out-alt sidebar-nav-icon"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </div>

        <!-- Header -->
        <div class="dashboard-header">
            <h1 class="header-title">Platform Settings</h1>
            <span>Welcome, <?= $_SESSION['admin'] ?>!</span>
        </div>

        <!-- Main Content -->
        <div class="dashboard-main">
            <?= $msg ?>
            
            <div class="settings-grid">
                <!-- Change Password -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-lock"></i>
                        <h3 class="card-title">Change Admin Password</h3>
                    </div>
                    
                    <div class="info-box">
                        <h4>Security Notice</h4>
                        <p>Use a strong password with at least 8 characters including uppercase, lowercase, numbers, and symbols.</p>
                    </div>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="old_pass">Current Password</label>
                            <input type="password" id="old_pass" name="old_pass" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_pass">New Password</label>
                            <input type="password" id="new_pass" name="new_pass" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_pass">Confirm New Password</label>
                            <input type="password" id="confirm_pass" name="confirm_pass" required minlength="6">
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-primary">
                            <i class="fas fa-save"></i> Update Password
                        </button>
                    </form>
                </div>

                <!-- Platform Statistics -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie"></i>
                        <h3 class="card-title">Platform Overview</h3>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Total Registered Teams</span>
                        <span class="stat-value"><?= $total_teams ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Total Challenges</span>
                        <span class="stat-value"><?= $total_challenges ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Total Submissions</span>
                        <span class="stat-value"><?= $total_submissions ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Admin Account</span>
                        <span class="stat-value"><?= $_SESSION['admin'] ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Platform Status</span>
                        <span class="stat-value">Active</span>
                    </div>
                </div>

                <!-- System Information -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-server"></i>
                        <h3 class="card-title">System Information</h3>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">PHP Version</span>
                        <span class="stat-value"><?= phpversion() ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Server Software</span>
                        <span class="stat-value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Database Connection</span>
                        <span class="stat-value">Connected</span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Judge0 API</span>
                        <span class="stat-value">Configured</span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Platform Version</span>
                        <span class="stat-value">v1.0.0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_pass').addEventListener('input', function() {
            const password = document.getElementById('new_pass').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
