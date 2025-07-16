<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.html');
    exit();
}

require '../config.php';
$db = db_connect();

// Get search parameter
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Build query based on filters
$query = "SELECT t.*, 
          COUNT(s.id) as submission_count,
          SUM(s.points) as total_points,
          MAX(s.submission_time) as last_submission
          FROM teams t 
          LEFT JOIN submissions s ON t.id = s.team_id";

if ($search) {
    $query .= " WHERE t.team_name LIKE '%$search%'";
}

$query .= " GROUP BY t.id ORDER BY total_points DESC";

$teams = $db->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Include the same CSS variables and styles from dashboard.php */
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

        /* Sidebar - same as dashboard */
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

        .sidebar-nav-link:hover, .sidebar-nav-link.active {
            background: var(--primary-blue);
            color: white;
        }

        .sidebar-nav-icon {
            width: 20px;
            margin-right: var(--spacing-md);
            text-align: center;
        }

        /* Header - same as dashboard */
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

        .dashboard-main {
            grid-area: main;
            padding: var(--spacing-xl);
            overflow-y: auto;
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

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: var(--spacing-md);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 1rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .filter-select {
            padding: var(--spacing-md);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            background: white;
        }

        .btn-primary {
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
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

        .table-actions {
            display: flex;
            gap: var(--spacing-sm);
        }

        .btn-table {
            padding: var(--spacing-xs) var(--spacing-sm);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-sm);
            background: white;
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .btn-table:hover {
            background: var(--gray-50);
            color: var(--gray-800);
        }

        .btn-table.edit {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }

        .btn-table.delete {
            border-color: var(--error);
            color: var(--error);
        }

        .status-badge {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }

        .team-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }

        .stat-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-top: var(--spacing-xs);
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
                    <a href="teams.php" class="sidebar-nav-link active">
                        <i class="fas fa-users sidebar-nav-icon"></i>
                        Teams
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
            <h1 class="header-title">Team Management</h1>
            <div class="header-actions">
                <span>Welcome, <?= $_SESSION['admin'] ?>!</span>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-main">
            <!-- Team Statistics -->
            <div class="team-stats">
                <div class="stat-card">
                    <div class="stat-number"><?= $db->query("SELECT COUNT(*) FROM teams")->fetch_row()[0] ?></div>
                    <div class="stat-label">Total Teams</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $db->query("SELECT COUNT(DISTINCT team_id) FROM submissions")->fetch_row()[0] ?></div>
                    <div class="stat-label">Active Teams</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $db->query("SELECT COUNT(*) FROM submissions")->fetch_row()[0] ?></div>
                    <div class="stat-label">Total Submissions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($db->query("SELECT AVG(points) FROM submissions")->fetch_row()[0] ?? 0, 1) ?></div>
                    <div class="stat-label">Average Points</div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="search-filters">
                <div class="search-box">
                    <form method="GET" style="display: flex; gap: var(--spacing-md);">
                        <input type="text" name="search" placeholder="Search teams..." value="<?= htmlspecialchars($search) ?>">
                        <select name="filter" class="filter-select">
                            <option value="all">All Teams</option>
                            <option value="active" <?= $filter == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                </div>
            </div>

            <!-- Teams Table -->
            <div class="data-table">
                <div class="data-table-header">
                    <h3 class="data-table-title">Teams Overview</h3>
                    <button class="btn-primary">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Team Name</th>
                                <th>Created</th>
                                <th>Submissions</th>
                                <th>Total Points</th>
                                <th>Last Activity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($team = $teams->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($team['team_name']) ?></strong>
                                </td>
                                <td><?= date('M d, Y', strtotime($team['created_at'])) ?></td>
                                <td><?= $team['submission_count'] ?></td>
                                <td><?= $team['total_points'] ?? 0 ?></td>
                                <td><?= $team['last_submission'] ? date('M d, Y H:i', strtotime($team['last_submission'])) : 'Never' ?></td>
                                <td>
                                    <span class="status-badge <?= $team['submission_count'] > 0 ? 'status-active' : 'status-inactive' ?>">
                                        <?= $team['submission_count'] > 0 ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="team_detail.php?id=<?= $team['id'] ?>" class="btn-table edit">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="team_edit.php?id=<?= $team['id'] ?>" class="btn-table edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="team_delete.php?id=<?= $team['id'] ?>" class="btn-table delete" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
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
