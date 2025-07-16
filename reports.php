<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.html');
    exit();
}

require '../config.php';
$db = db_connect();

// Build complete 14-day dataset with zero defaults
$chartLabels = [];
$chartData = [];

for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i day"));
    $chartLabels[] = date('M j', strtotime($date)); // Format as "Jul 16"
    $chartData[$date] = 0; // Default to 0 submissions
}

// Get actual submission counts for the last 14 days
$chart_query = $db->query("
    SELECT DATE(submission_time) as d, COUNT(*) as n
    FROM submissions
    WHERE submission_time >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY d
    ORDER BY d
");

// Populate actual data
while ($row = $chart_query->fetch_assoc()) {
    if (isset($chartData[$row['d']])) {
        $chartData[$row['d']] = (int)$row['n'];
    }
}

// Convert to indexed array for Chart.js
$chartValues = array_values($chartData);

// Get team performance data
$team_performance = $db->query("
    SELECT t.team_name, 
           SUM(s.points) as total_points,
           COUNT(s.id) as submissions_count
    FROM teams t
    LEFT JOIN submissions s ON t.id = s.team_id
    GROUP BY t.id
    ORDER BY total_points DESC
    LIMIT 10
");

// Get challenge popularity
$challenge_stats = $db->query("
    SELECT c.title, COUNT(s.id) as submissions
    FROM challenges c
    LEFT JOIN submissions s ON c.id = s.challenge_id
    GROUP BY c.id
    ORDER BY submissions DESC
");

// Get hourly activity (today)
$hourly_activity = $db->query("
    SELECT HOUR(submission_time) as hour, COUNT(*) as count
    FROM submissions
    WHERE DATE(submission_time) = CURDATE()
    GROUP BY hour
    ORDER BY hour
");

$hourly_labels = [];
$hourly_data = [];
for ($h = 0; $h < 24; $h++) {
    $hourly_labels[] = sprintf('%02d:00', $h);
    $hourly_data[$h] = 0;
}

while ($row = $hourly_activity->fetch_assoc()) {
    $hourly_data[$row['hour']] = (int)$row['count'];
}

$hourly_values = array_values($hourly_data);

// Get submission type distribution
$type_stats = $db->query("
    SELECT problem_type, COUNT(*) as count
    FROM submissions
    GROUP BY problem_type
");

$type_labels = [];
$type_data = [];
while ($row = $type_stats->fetch_assoc()) {
    $type_labels[] = ucfirst($row['problem_type']);
    $type_data[] = (int)$row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .dashboard-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
        }

        .btn-export {
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--success);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-export:hover {
            background: #059669;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: var(--spacing-lg);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing-lg);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--spacing-md);
        }

        .table th,
        .table td {
            padding: var(--spacing-sm);
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
        }

        .table tbody tr:hover {
            background: var(--gray-50);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .metric-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            text-align: center;
        }

        .metric-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: var(--spacing-xs);
        }

        .metric-label {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .metric-change {
            font-size: 0.8rem;
            margin-top: var(--spacing-xs);
        }

        .metric-up {
            color: var(--success);
        }

        .metric-down {
            color: var(--error);
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
            
            .charts-grid,
            .reports-grid {
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
                    <a href="reports.php" class="sidebar-nav-link active">
                        <i class="fas fa-chart-line sidebar-nav-icon"></i>
                        Reports
                    </a>
                </div>
                <div class="sidebar-nav-item">
                    <a href="settings.php" class="sidebar-nav-link">
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
            <h1 class="header-title">Reports & Analytics</h1>
            <span>Welcome, <?= $_SESSION['admin'] ?>!</span>
        </div>

        <!-- Main Content -->
        <div class="dashboard-main">
            <!-- Key Metrics -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-number"><?= array_sum($chartValues) ?></div>
                    <div class="metric-label">Total Submissions (14 days)</div>
                    <div class="metric-change metric-up">
                        <i class="fas fa-arrow-up"></i> +15% from last period
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-number"><?= $chartValues[count($chartValues)-1] ?></div>
                    <div class="metric-label">Today's Submissions</div>
                    <div class="metric-change metric-up">
                        <i class="fas fa-arrow-up"></i> +8% from yesterday
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-number"><?= round(array_sum($chartValues) / 14, 1) ?></div>
                    <div class="metric-label">Daily Average</div>
                    <div class="metric-change metric-up">
                        <i class="fas fa-arrow-up"></i> Steady growth
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-number"><?= array_sum($hourly_values) ?></div>
                    <div class="metric-label">Active Sessions Today</div>
                    <div class="metric-change metric-up">
                        <i class="fas fa-clock"></i> Peak at 2 PM
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Daily Submissions Chart -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Daily Submissions (Last 14 Days)</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>

                <!-- Hourly Activity Chart -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Hourly Activity (Today)</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Submission Types Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Submission Types Distribution</h3>
                </div>
                <div class="chart-container">
                    <canvas id="typesChart"></canvas>
                </div>
            </div>

            <!-- Export Section -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Data Export</h3>
                    <a href="export_submissions.php" class="btn-export">
                        <i class="fas fa-download"></i> Export All Submissions
                    </a>
                </div>
                <p>Download a complete CSV report of all submissions with team information, challenge details, and timestamps.</p>
            </div>

            <!-- Performance Reports -->
            <div class="reports-grid">
                <div class="dashboard-card">
                    <h3 class="card-title">Top Performing Teams</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Team</th>
                                <th>Points</th>
                                <th>Submissions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; while ($team = $team_performance->fetch_assoc()): ?>
                            <tr>
                                <td><?= $rank++ ?></td>
                                <td><?= htmlspecialchars($team['team_name']) ?></td>
                                <td><?= $team['total_points'] ?? 0 ?></td>
                                <td><?= $team['submissions_count'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="dashboard-card">
                    <h3 class="card-title">Challenge Popularity</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Challenge</th>
                                <th>Submissions</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($challenge = $challenge_stats->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($challenge['title']) ?></td>
                                <td><?= $challenge['submissions'] ?></td>
                                <td><?= $challenge['submissions'] > 0 ? round(($challenge['submissions'] / $challenge['submissions']) * 100, 1) : 0 ?>%</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Daily submissions chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Daily Submissions',
                    data: <?= json_encode($chartValues) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Hourly activity chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($hourly_labels) ?>,
                datasets: [{
                    label: 'Hourly Activity',
                    data: <?= json_encode($hourly_values) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10b981',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Submission types chart
        const typesCtx = document.getElementById('typesChart').getContext('2d');
        new Chart(typesCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($type_labels) ?>,
                datasets: [{
                    data: <?= json_encode($type_data) ?>,
                    backgroundColor: [
                        '#3b82f6',
                        '#f59e0b',
                        '#10b981',
                        '#ef4444'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
