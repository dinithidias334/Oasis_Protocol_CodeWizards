<?php
session_start();
require 'config.php';
$db = db_connect();

// Get leaderboard data
$leaderboard = $db->query("
    SELECT t.team_name, 
           SUM(s.points) as total_points,
           COUNT(s.id) as submissions_count,
           MAX(s.submission_time) as last_submission,
           MIN(s.submission_time) as first_submission
    FROM teams t 
    LEFT JOIN submissions s ON t.id = s.team_id 
    GROUP BY t.id 
    ORDER BY total_points DESC, first_submission ASC
");

// Get statistics
$total_teams = $db->query("SELECT COUNT(*) FROM teams")->fetch_row()[0];
$active_teams = $db->query("SELECT COUNT(DISTINCT team_id) FROM submissions")->fetch_row()[0];
$total_submissions = $db->query("SELECT COUNT(*) FROM submissions")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Leaderboard - Hackathon Platform</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
       <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%);
            min-height: 100vh;
            color: #e0e0e0;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(0, 255, 255, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255, 0, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 1;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .header {
            text-align: center;
            padding: 40px 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 20px;
            margin-bottom: 40px;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00ffff, #ff00ff);
            border-radius: 20px 20px 0 0;
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
            margin-bottom: 15px;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { text-shadow: 0 0 30px rgba(0, 255, 255, 0.5); }
            to { text-shadow: 0 0 50px rgba(0, 255, 255, 0.8), 0 0 70px rgba(255, 0, 255, 0.3); }
        }

        .header p {
            font-size: 1.2rem;
            color: #a0a0a0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 0, 255, 0.3);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 0, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 0, 255, 0.6);
            box-shadow: 0 15px 30px rgba(255, 0, 255, 0.2);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(45deg, #ff00ff, #ffff00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #a0a0a0;
            font-size: 0.875rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .leaderboard-card {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .leaderboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00ffff, #ff00ff);
        }

        .leaderboard-header {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 255, 255, 0.2);
        }

        .leaderboard-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #00ffff;
            margin-bottom: 10px;
        }

        .leaderboard-header p {
            color: #a0a0a0;
            font-size: 1.1rem;
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }

        .leaderboard-table th,
        .leaderboard-table td {
            padding: 20px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 255, 255, 0.1);
        }

        .leaderboard-table th {
            background: rgba(0, 0, 0, 0.8);
            font-weight: 600;
            color: #00ffff;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .leaderboard-table tbody tr {
            transition: all 0.3s ease;
        }

        .leaderboard-table tbody tr:hover {
            background: rgba(0, 255, 255, 0.05);
        }

        .rank-cell {
            width: 80px;
            text-align: center;
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 1.2rem;
            color: #000;
            position: relative;
        }

        .rank-1 {
            background: linear-gradient(135deg, #ffd700, #ffed4a);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }

        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0, #e5e7eb);
            box-shadow: 0 0 20px rgba(192, 192, 192, 0.5);
        }

        .rank-3 {
            background: linear-gradient(135deg, #cd7f32, #d69e2e);
            box-shadow: 0 0 20px rgba(205, 127, 50, 0.5);
        }

        .rank-other {
            background: rgba(0, 255, 255, 0.2);
            color: #00ffff;
            border: 1px solid rgba(0, 255, 255, 0.3);
        }

        .team-name {
            font-weight: 700;
            color: #00ffff;
            font-size: 1.1rem;
        }

        .points-cell {
            font-weight: 700;
            color: #10b981;
            font-size: 1.2rem;
        }

        .progress-bar {
            width: 120px;
            height: 10px;
            background: rgba(0, 255, 255, 0.1);
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid rgba(0, 255, 255, 0.2);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.3s ease;
            border-radius: 5px;
        }

        .last-activity {
            font-size: 0.9rem;
            color: #a0a0a0;
        }

        .navigation {
            text-align: center;
            margin-top: 40px;
        }

        .nav-link {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            color: #00ffff;
            text-decoration: none;
            border-radius: 8px;
            margin: 0 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 255, 255, 0.3);
        }

        .nav-link:hover {
            background: rgba(0, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 255, 255, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 20px;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .empty-state i {
            font-size: 4rem;
            color: #ef4444;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        .empty-state h3 {
            color: #ef4444;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #a0a0a0;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .leaderboard-table {
                font-size: 0.9rem;
            }
            
            .leaderboard-table th,
            .leaderboard-table td {
                padding: 15px 10px;
            }
            
            .progress-bar {
                width: 80px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
</style>

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> Team Leaderboard</h1>
            <p>Real-time rankings and team performance</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_teams ?></div>
                <div class="stat-label">Total Teams</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $active_teams ?></div>
                <div class="stat-label">Active Teams</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_submissions ?></div>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= date('M d, Y') ?></div>
                <div class="stat-label">Last Updated</div>
            </div>
        </div>

        <div class="leaderboard-card">
            <div class="leaderboard-header">
                <h2><i class="fas fa-medal"></i> Team Rankings</h2>
                <p>Ranked by total points and submission time</p>
            </div>

            <?php if ($leaderboard->num_rows > 0): ?>
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Team Name</th>
                            <th>Total Points</th>
                            <th>Submissions</th>
                            <th>Progress</th>
                            <th>Last Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        $max_points = 0;
                        $teams_data = [];
                        
                        // Get max points for progress calculation
                        while ($row = $leaderboard->fetch_assoc()) {
                            $teams_data[] = $row;
                            if ($row['total_points'] > $max_points) {
                                $max_points = $row['total_points'];
                            }
                        }
                        
                        foreach ($teams_data as $team): 
                            $rank_class = '';
                            if ($rank == 1) $rank_class = 'rank-1';
                            elseif ($rank == 2) $rank_class = 'rank-2';
                            elseif ($rank == 3) $rank_class = 'rank-3';
                            else $rank_class = 'rank-other';
                            
                            $progress = $max_points > 0 ? ($team['total_points'] / $max_points) * 100 : 0;
                        ?>
                        <tr>
                            <td class="rank-cell">
                                <div class="rank-badge <?= $rank_class ?>">
                                    <?= $rank ?>
                                </div>
                            </td>
                            <td class="team-name"><?= htmlspecialchars($team['team_name']) ?></td>
                            <td class="points-cell"><?= $team['total_points'] ?? 0 ?></td>
                            <td><?= $team['submissions_count'] ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                                </div>
                            </td>
                            <td class="last-activity">
                                <?= $team['last_submission'] ? date('M d, H:i', strtotime($team['last_submission'])) : 'No activity' ?>
                            </td>
                        </tr>
                        <?php 
                        $rank++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No teams registered yet</h3>
                    <p>Teams will appear here once they start participating</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="navigation">
            <?php if (isset($_SESSION['team_id'])): ?>
                <a href="challenge_portal.php" class="nav-link">
                    <i class="fas fa-code"></i> Back to Challenges
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="login.html" class="nav-link">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="signup.html" class="nav-link">
                    <i class="fas fa-user-plus"></i> Sign Up
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-refresh leaderboard every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
