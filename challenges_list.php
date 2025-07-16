<?php
session_start();
if (!isset($_SESSION['team_id'])) {
    header('Location: login.html');
    exit();
}

require 'config.php';
$db = db_connect();

// Get all active challenges with team progress
$challenges = $db->query("
    SELECT c.*, 
           ap.description as algo_desc,
           bp.description as build_desc,
           f.flag,
           COUNT(DISTINCT s.team_id) as teams_attempted,
           MAX(CASE WHEN s.team_id = {$_SESSION['team_id']} AND s.problem_type = 'algorithmic' THEN 1 ELSE 0 END) as algo_completed,
           MAX(CASE WHEN s.team_id = {$_SESSION['team_id']} AND s.problem_type = 'buildathon' THEN 1 ELSE 0 END) as build_completed,
           tp.unlocked_buildathon
    FROM challenges c 
    LEFT JOIN algorithmic_problems ap ON c.id = ap.challenge_id
    LEFT JOIN buildathon_problems bp ON c.id = bp.challenge_id
    LEFT JOIN flags f ON c.id = f.challenge_id
    LEFT JOIN submissions s ON c.id = s.challenge_id 
    LEFT JOIN team_progress tp ON c.id = tp.challenge_id AND tp.team_id = {$_SESSION['team_id']}
    WHERE c.active = 1 
    GROUP BY c.id 
    ORDER BY c.id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Challenges - Hackathon Platform</title>
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

        .challenge-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .challenge-card {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .challenge-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00ffff, #ff00ff);
        }

        .challenge-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .challenge-card:hover::after {
            left: 100%;
        }

        .challenge-card:hover {
            transform: translateY(-10px);
            border-color: rgba(0, 255, 255, 0.6);
            box-shadow: 0 20px 40px rgba(0, 255, 255, 0.2);
        }

        .challenge-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .challenge-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #00ffff;
            margin: 0;
        }

        .challenge-id {
            background: rgba(255, 0, 255, 0.2);
            color: #ff00ff;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid rgba(255, 0, 255, 0.3);
        }

        .challenge-description {
            color: #b0b0b0;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .challenge-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: rgba(0, 255, 255, 0.1);
            border-radius: 6px;
            font-size: 0.85rem;
            color: #00ffff;
            border: 1px solid rgba(0, 255, 255, 0.2);
        }

        .progress-indicators {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .progress-badge {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-completed {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-unlocked {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge-locked {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .challenge-actions {
            display: flex;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(45deg, #00ffff, #0080ff);
            color: #000;
            border: 1px solid #00ffff;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #0080ff, #00ffff);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 255, 255, 0.3);
        }

        .btn-secondary {
            background: rgba(108, 114, 128, 0.3);
            color: #9ca3af;
            border: 1px solid rgba(108, 114, 128, 0.5);
        }

        .btn-secondary:hover {
            background: rgba(108, 114, 128, 0.5);
        }

        .btn-success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid #10b981;
        }

        .btn-success:hover {
            background: rgba(16, 185, 129, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .navigation {
            text-align: center;
            margin-top: 40px;
        }

        .nav-link {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(0, 0, 0, 0.6);
            color: #00ffff;
            text-decoration: none;
            border-radius: 8px;
            margin: 0 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .nav-link:hover {
            background: rgba(0, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 255, 255, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 20px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            backdrop-filter: blur(15px);
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
            
            .challenge-grid {
                grid-template-columns: 1fr;
            }
            
            .challenge-stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .challenge-actions {
                flex-direction: column;
            }
        }
</style>

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Available Challenges</h1>
            <p>Choose a challenge to test your skills</p>
        </div>

        <?php if ($challenges->num_rows > 0): ?>
            <div class="challenge-grid">
                <?php while ($challenge = $challenges->fetch_assoc()): ?>
                    <div class="challenge-card">
                        <div class="challenge-header">
                            <h2 class="challenge-title"><?= htmlspecialchars($challenge['title']) ?></h2>
                            <span class="challenge-id">#<?= $challenge['id'] ?></span>
                        </div>

                        <div class="challenge-description">
                            <?= htmlspecialchars(substr($challenge['algo_desc'] ?? 'Algorithm challenge description', 0, 150)) ?>...
                        </div>

                        <div class="challenge-stats">
                            <div class="stat-item">
                                <i class="fas fa-users"></i>
                                <span><?= $challenge['teams_attempted'] ?> teams attempted</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-clock"></i>
                                <span>Algorithm + Buildathon</span>
                            </div>
                        </div>

                        <div class="progress-indicators">
                            <?php if ($challenge['algo_completed']): ?>
                                <span class="progress-badge badge-completed">
                                    <i class="fas fa-check"></i> Algorithm Completed
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($challenge['unlocked_buildathon']): ?>
                                <span class="progress-badge badge-unlocked">
                                    <i class="fas fa-unlock"></i> Buildathon Unlocked
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($challenge['build_completed']): ?>
                                <span class="progress-badge badge-completed">
                                    <i class="fas fa-trophy"></i> Buildathon Completed
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="challenge-actions">
                            <a href="challenge_portal.php?id=<?= $challenge['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-code"></i>
                                <?= $challenge['algo_completed'] ? 'Review Algorithm' : 'Start Algorithm' ?>
                            </a>
                            
                            <?php if ($challenge['unlocked_buildathon']): ?>
                                <a href="buildathon.php?challenge_id=<?= $challenge['id'] ?>" class="btn btn-success">
                                    <i class="fas fa-hammer"></i>
                                    <?= $challenge['build_completed'] ? 'Review Project' : 'Start Buildathon' ?>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-lock"></i>
                                    Buildathon Locked
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>No Active Challenges</h3>
                <p>Check back later for new challenges</p>
            </div>
        <?php endif; ?>

        <div class="navigation">
            <a href="leaderboard.php" class="nav-link">
                <i class="fas fa-trophy"></i> Leaderboard
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>
