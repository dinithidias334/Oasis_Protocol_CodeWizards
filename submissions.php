<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.html');
    exit();
}

require '../config.php';
$db = db_connect();

// Handle search/filter
$where = '';
if (!empty($_GET['team'])) {
    $where .= " AND t.team_name LIKE '%" . $db->real_escape_string($_GET['team']) . "%'";
}
if (!empty($_GET['type'])) {
    $where .= " AND s.problem_type = '" . $db->real_escape_string($_GET['type']) . "'";
}

$sql = "
    SELECT s.*, t.team_name, c.title AS challenge_title
    FROM submissions s
    JOIN teams t ON s.team_id = t.id
    JOIN challenges c ON s.challenge_id = c.id
    WHERE 1=1 $where
    ORDER BY s.submission_time DESC
";
$rows = $db->query($sql);

// Get statistics
$total_submissions = $db->query("SELECT COUNT(*) FROM submissions")->fetch_row()[0];
$today_submissions = $db->query("SELECT COUNT(*) FROM submissions WHERE DATE(submission_time) = CURDATE()")->fetch_row()[0];
$algo_submissions = $db->query("SELECT COUNT(*) FROM submissions WHERE problem_type = 'algorithmic'")->fetch_row()[0];
$build_submissions = $db->query("SELECT COUNT(*) FROM submissions WHERE problem_type = 'buildathon'")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions - Admin Panel</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .stat-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-top: var(--spacing-xs);
        }

        .search-filters {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            display: flex;
            gap: var(--spacing-md);
            align-items: center;
        }

        .search-filters input,
        .search-filters select {
            padding: var(--spacing-md);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 1rem;
        }

        .search-filters input:focus,
        .search-filters select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-primary {
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: var(--primary-blue-dark);
        }

        .data-table {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .data-table-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .type-badge {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .type-algorithmic {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .type-buildathon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .answer-cell {
            max-width: 250px;
            word-break: break-all;
            font-size: 0.875rem;
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
            
            .search-filters {
                flex-direction: column;
                align-items: stretch;
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
                    <a href="submissions.php" class="sidebar-nav-link active">
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
            <h1 class="header-title">All Submissions</h1>
            <span>Welcome, <?= $_SESSION['admin'] ?>!</span>
        </div>

        <!-- Main Content -->
        <div class="dashboard-main">
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_submissions ?></div>
                    <div class="stat-label">Total Submissions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $today_submissions ?></div>
                    <div class="stat-label">Today's Submissions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $algo_submissions ?></div>
                    <div class="stat-label">Algorithmic</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $build_submissions ?></div>
                    <div class="stat-label">Buildathon</div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="search-filters">
                <form method="GET" style="display: flex; gap: var(--spacing-md); width: 100%;">
                    <input type="text" name="team" placeholder="Search by team name..." 
                           value="<?= htmlspecialchars($_GET['team'] ?? '') ?>" style="flex: 1;">
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="algorithmic" <?= ($_GET['type'] ?? '') == 'algorithmic' ? 'selected' : '' ?>>Algorithmic</option>
                        <option value="buildathon" <?= ($_GET['type'] ?? '') == 'buildathon' ? 'selected' : '' ?>>Buildathon</option>
                    </select>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </form>
            </div>

            <!-- Submissions Table -->
            <div class="data-table">
                <div class="data-table-header">
                    <h3 class="data-table-title">Submission History (<?= $rows->num_rows ?>)</h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Team</th>
                                <th>Challenge</th>
                                <th>Type</th>
                                <th>Points</th>
                                <th>Answer/Link</th>
                                <th>Submission Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; while ($r = $rows->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($r['team_name']) ?></td>
                                <td><?= htmlspecialchars($r['challenge_title']) ?></td>
                                <td>
                                    <span class="type-badge type-<?= $r['problem_type'] ?>">
                                        <?= ucfirst($r['problem_type']) ?>
                                    </span>
                                </td>
                                <td><?= $r['points'] ?></td>
                                <td class="answer-cell"><?= htmlspecialchars($r['answer']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($r['submission_time'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
