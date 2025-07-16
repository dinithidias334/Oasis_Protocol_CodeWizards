<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.html');
    exit();
}

require '../config.php';
$db = db_connect();

// Get dashboard statistics
$stats = [];

// Team statistics
$team_count = $db->query("SELECT COUNT(*) FROM teams")->fetch_row()[0];
$active_teams = $db->query("SELECT COUNT(DISTINCT team_id) FROM submissions WHERE DATE(submission_time) = CURDATE()")->fetch_row()[0];

// Challenge statistics
$total_challenges = $db->query("SELECT COUNT(*) FROM challenges")->fetch_row()[0];
$active_challenges = $db->query("SELECT COUNT(*) FROM challenges WHERE active = 1")->fetch_row()[0];

// Submission statistics
$total_submissions = $db->query("SELECT COUNT(*) FROM submissions")->fetch_row()[0];
$today_submissions = $db->query("SELECT COUNT(*) FROM submissions WHERE DATE(submission_time) = CURDATE()")->fetch_row()[0];

// Points statistics
$total_points = $db->query("SELECT SUM(points) FROM submissions")->fetch_row()[0] ?? 0;
$avg_points = $db->query("SELECT AVG(points) FROM submissions")->fetch_row()[0] ?? 0;

// Recent submissions
$recent_submissions = $db->query("
    SELECT s.*, t.team_name, c.title as challenge_title 
    FROM submissions s 
    JOIN teams t ON s.team_id = t.id 
    JOIN challenges c ON s.challenge_id = c.id 
    ORDER BY s.submission_time DESC 
    LIMIT 10
");

// Top teams
$top_teams = $db->query("
    SELECT t.team_name, SUM(s.points) as total_points, COUNT(s.id) as submissions_count
    FROM teams t 
    LEFT JOIN submissions s ON t.id = s.team_id 
    GROUP BY t.id 
    ORDER BY total_points DESC 
    LIMIT 5
");

// Challenge completion rates
$challenge_stats = $db->query("
    SELECT c.title, COUNT(s.id) as submissions, c.active
    FROM challenges c 
    LEFT JOIN submissions s ON c.id = s.challenge_id 
    GROUP BY c.id 
    ORDER BY submissions DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hackathon Platform</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            /* Primary Colors */
            --primary-blue: #3b82f6;
            --primary-blue-dark: #2563eb;
            --primary-blue-light: #dbeafe;
            
            /* Neutral Colors */
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            /* Status Colors */
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #06b6d4;
            
            /* Spacing */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            
            /* Shadows */
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

        /* Sidebar Styles */
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
            transform: translateX(4px);
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

        /* Header Styles */
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

        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .header-notification {
            position: relative;
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .header-notification:hover {
            background: var(--gray-100);
        }

        .header-notification .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--error);
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            background: var(--gray-50);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .admin-profile:hover {
            background: var(--gray-100);
        }

        .admin-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Main Content Styles */
        .dashboard-main {
            grid-area: main;
            padding: var(--spacing-xl);
            overflow-y: auto;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .dashboard-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
        }

        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-blue);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--spacing-md);
        }

        .card-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .card-metric {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: var(--spacing-sm) 0;
        }

        .card-trend {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: var(--success);
        }

        .card-trend.negative {
            color: var(--error);
        }

        .card-trend-icon {
            width: 16px;
            height: 16px;
            margin-right: var(--spacing-xs);
        }

        /* Data Table Styles */
        .data-table {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--gray-200);
            margin-bottom: var(--spacing-xl);
        }

        .data-table-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .data-table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background: var(--gray-50);
        }

        .status-badge {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: var(--spacing-lg);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-dashboard {
                grid-template-areas: 
                    "header"
                    "main";
                grid-template-columns: 1fr;
                grid-template-rows: 70px 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .dashboard-cards {
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
        <a href="dashboard.php"      class="sidebar-nav-link <?= $active==='dash' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar sidebar-nav-icon"></i> Dashboard
        </a>
    </div>
    <div class="sidebar-nav-item">
        <a href="challenges.php"     class="sidebar-nav-link <?= $active==='chal' ? 'active' : '' ?>">
            <i class="fas fa-tasks sidebar-nav-icon"></i> Challenges
        </a>
    </div>
    <div class="sidebar-nav-item">
        <a href="teams.php"          class="sidebar-nav-link <?= $active==='team' ? 'active' : '' ?>">
            <i class="fas fa-users sidebar-nav-icon"></i> Teams
        </a>
    </div>
    <div class="sidebar-nav-item">
        <a href="submissions.php"    class="sidebar-nav-link <?= $active==='sub'  ? 'active' : '' ?>">
            <i class="fas fa-file-alt sidebar-nav-icon"></i> Submissions
        </a>
    </div>
    <div class="sidebar-nav-item">
        <a href="reports.php"        class="sidebar-nav-link <?= $active==='rep'  ? 'active' : '' ?>">
            <i class="fas fa-chart-line sidebar-nav-icon"></i> Reports
        </a>
    </div>
    <div class="sidebar-nav-item">
        <a href="settings.php"       class="sidebar-nav-link <?= $active==='set'  ? 'active' : '' ?>">
            <i class="fas fa-cog sidebar-nav-icon"></i> Settings
        </a>
    </div>
    <div class="sidebar-nav-item">
        <a href="admin_logout.php"   class="sidebar-nav-link">
            <i class="fas fa-sign-out-alt sidebar-nav-icon"></i> Logout
        </a>
    </div>
</nav>

        </div>

        <!-- Header -->
        <div class="dashboard-header">
            <h1 class="header-title">Dashboard</h1>
            
            <div class="header-actions">
                <div class="header-notification">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                
                <div class="admin-profile">
                    <div class="admin-avatar">A</div>
                    <span><?= $_SESSION['admin'] ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-main">
            <!-- Statistics Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-title">Total Teams</div>
                        <div class="card-icon" style="background: var(--primary-blue);">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-metric"><?= $team_count ?></div>
                    <div class="card-trend">
                        <i class="fas fa-arrow-up card-trend-icon"></i>
                        Active today: <?= $active_teams ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-title">Challenges</div>
                        <div class="card-icon" style="background: var(--success);">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                    <div class="card-metric"><?= $active_challenges ?></div>
                    <div class="card-trend">
                        <i class="fas fa-info-circle card-trend-icon"></i>
                        Total: <?= $total_challenges ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-title">Submissions</div>
                        <div class="card-icon" style="background: var(--warning);">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="card-metric"><?= $total_submissions ?></div>
                    <div class="card-trend">
                        <i class="fas fa-arrow-up card-trend-icon"></i>
                        Today: <?= $today_submissions ?>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-title">Total Points</div>
                        <div class="card-icon" style="background: var(--info);">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="card-metric"><?= number_format($total_points) ?></div>
                    <div class="card-trend">
                        <i class="fas fa-calculator card-trend-icon"></i>
                        Average: <?= number_format($avg_points, 1) ?>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="dashboard-cards">
                <div class="dashboard-card" style="grid-column: 1 / -1;">
                    <div class="card-header">
                        <div class="card-title">Submission Statistics</div>
                    </div>
                    <div class="chart-container">
                        <canvas id="submissionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Submissions Table -->
            <div class="data-table">
                <div class="data-table-header">
                    <h3 class="data-table-title">Recent Submissions</h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>Challenge</th>
                                <th>Type</th>
                                <th>Points</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $recent_submissions->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['team_name']) ?></td>
                                <td><?= htmlspecialchars($row['challenge_title']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $row['problem_type'] == 'algorithmic' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($row['problem_type']) ?>
                                    </span>
                                </td>
                                <td><?= $row['points'] ?></td>
                                <td><?= date('M d, Y H:i', strtotime($row['submission_time'])) ?></td>
                                <td>
                                    <span class="status-badge status-success">Accepted</span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Teams Table -->
            <div class="data-table">
                <div class="data-table-header">
                    <h3 class="data-table-title">Top Teams</h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Team Name</th>
                                <th>Total Points</th>
                                <th>Submissions</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            while($row = $top_teams->fetch_assoc()): 
                            ?>
                            <tr>
                                <td>
                                    <span class="status-badge status-<?= $rank <= 3 ? 'success' : 'warning' ?>">
                                        #<?= $rank ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['team_name']) ?></td>
                                <td><?= $row['total_points'] ?? 0 ?></td>
                                <td><?= $row['submissions_count'] ?></td>
                                <td>
                                    <div style="width: 100px; height: 8px; background: var(--gray-200); border-radius: 4px; overflow: hidden;">
                                        <div style="height: 100%; background: var(--success); width: <?= min(100, ($row['total_points'] ?? 0) / 10) ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                            $rank++;
                            endwhile; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart.js implementation
        const ctx = document.getElementById('submissionChart').getContext('2d');
        const submissionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Submissions',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Auto-refresh dashboard every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
