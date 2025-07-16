<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.html');
    exit();
}

require '../config.php';
$db = db_connect();

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $db->query("DELETE FROM flags WHERE challenge_id = $id");
    $db->query("DELETE FROM algorithmic_problems WHERE challenge_id = $id");
    $db->query("DELETE FROM buildathon_problems WHERE challenge_id = $id");
    $db->query("DELETE FROM challenges WHERE id = $id");
    header('Location: challenges.php?msg=deleted');
    exit();
}

// Handle toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $db->query("UPDATE challenges SET active = NOT active WHERE id = $id");
    header('Location: challenges.php?msg=toggled');
    exit();
}

// Fetch all challenges with related data
$sql = "SELECT c.*, 
               ap.description as algo_desc,
               bp.description as build_desc,
               f.flag
        FROM challenges c
        LEFT JOIN algorithmic_problems ap ON c.id = ap.challenge_id
        LEFT JOIN buildathon_problems bp ON c.id = bp.challenge_id
        LEFT JOIN flags f ON c.id = f.challenge_id
        ORDER BY c.id DESC";
$result = $db->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Challenge Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-dark: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-800: #1f2937;
            --gray-900: #111827;
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
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-blue-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 12px;
        }

        .alert {
            padding: 16px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid transparent;
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .search-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-form input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-form input:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .table-header {
            background: var(--gray-50);
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
        }

        .table-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: var(--gray-50);
        }

        .status-active {
            color: var(--success);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-inactive {
            color: var(--danger);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .actions-cell {
            white-space: nowrap;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .description-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .flag-cell {
            font-family: 'Courier New', monospace;
            background: var(--gray-100);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-600);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--gray-400);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
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
            font-size: 14px;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
            }

            .header-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }

            .search-form {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons {
                flex-direction: column;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-tasks"></i>
                Challenge Management
            </h1>
            <div class="header-buttons">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i> Dashboard
                </a>
                <a href="teams.php" class="btn btn-primary">
                    <i class="fas fa-users"></i> Teams
                </a>
                <a href="challenge_edit.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Challenge
                </a>
                <a href="admin_logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i>
                <?php 
                switch($_GET['msg']) {
                    case 'deleted': echo 'Challenge deleted successfully!'; break;
                    case 'toggled': echo 'Challenge status updated!'; break;
                    case 'created': echo 'Challenge created successfully!'; break;
                    case 'updated': echo 'Challenge updated successfully!'; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number"><?= $result->num_rows ?></div>
                <div class="stat-label">Total Challenges</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $active_count = 0;
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()) {
                        if ($row['active']) $active_count++;
                    }
                    echo $active_count;
                    ?>
                </div>
                <div class="stat-label">Active Challenges</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $result->num_rows - $active_count ?></div>
                <div class="stat-label">Inactive Challenges</div>
            </div>
        </div>

        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search challenges by title or description..." 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="challenges.php" class="btn btn-warning">
                    <i class="fas fa-times"></i> Clear
                </a>
            </form>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2>
                    <i class="fas fa-list"></i>
                    All Challenges
                </h2>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Algorithm</th>
                        <th>Buildathon</th>
                        <th>Flag</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $result->data_seek(0);
                    if ($result->num_rows > 0): 
                    ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['id'] ?></strong></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td>
                                    <span class="<?= $row['active'] ? 'status-active' : 'status-inactive' ?>">
                                        <i class="fas fa-<?= $row['active'] ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $row['active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="description-cell">
                                    <?= $row['algo_desc'] ? htmlspecialchars(substr($row['algo_desc'], 0, 50)) . '...' : '<em>Not set</em>' ?>
                                </td>
                                <td class="description-cell">
                                    <?= $row['build_desc'] ? htmlspecialchars(substr($row['build_desc'], 0, 50)) . '...' : '<em>Not set</em>' ?>
                                </td>
                                <td>
                                    <?= $row['flag'] ? '<span class="flag-cell">' . htmlspecialchars($row['flag']) . '</span>' : '<em>Not set</em>' ?>
                                </td>
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <a href="challenge_edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="challenges.php?toggle=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-<?= $row['active'] ? 'eye-slash' : 'eye' ?>"></i>
                                            <?= $row['active'] ? 'Disable' : 'Enable' ?>
                                        </a>
                                        <a href="challenges.php?delete=<?= $row['id'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this challenge? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No challenges found</h3>
                                <p>Get started by <a href="challenge_edit.php">creating your first challenge</a></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
