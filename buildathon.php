<?php
session_start();
if (!isset($_SESSION['team_id'])) {
    header('Location: login.html');
    exit();
}

require 'config.php';
$db = db_connect();

$team_id = $_SESSION['team_id'];
$challenge_id = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : 1;

// Check if buildathon is unlocked
$stmt = $db->prepare("SELECT unlocked_buildathon FROM team_progress WHERE team_id = ? AND challenge_id = ?");
$stmt->bind_param('ii', $team_id, $challenge_id);
$stmt->execute();
$progress = $stmt->get_result()->fetch_assoc();

if (!$progress || !$progress['unlocked_buildathon']) {
    echo "<script>
        alert('You need to complete the algorithmic challenge first!');
        window.location.href='challenge_portal.php?id=$challenge_id';
    </script>";
    exit();
}

// Get buildathon problem
$stmt = $db->prepare("SELECT bp.description, c.title FROM buildathon_problems bp JOIN challenges c ON bp.challenge_id = c.id WHERE bp.challenge_id = ?");
$stmt->bind_param('i', $challenge_id);
$stmt->execute();
$buildathon = $stmt->get_result()->fetch_assoc();

// Handle submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $github_link = trim($_POST['github_link']);
    $demo_link = trim($_POST['demo_link']);
    $description = trim($_POST['description']);
    
    if (!empty($github_link)) {
        // Check if already submitted
        $stmt = $db->prepare("SELECT id FROM submissions WHERE team_id = ? AND challenge_id = ? AND problem_type = 'buildathon'");
        $stmt->bind_param('ii', $team_id, $challenge_id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        
        if (!$exists) {
            $stmt = $db->prepare("INSERT INTO submissions (team_id, challenge_id, problem_type, answer, points, submission_time) VALUES (?, ?, 'buildathon', ?, 150, NOW())");
            $answer = json_encode([
                'github_link' => $github_link,
                'demo_link' => $demo_link,
                'description' => $description
            ]);
            $stmt->bind_param('iis', $team_id, $challenge_id, $answer);
            $stmt->execute();
            
            $success = "Buildathon project submitted successfully! You earned 150 points!";
        } else {
            $error = "You have already submitted a buildathon project for this challenge.";
        }
    } else {
        $error = "GitHub link is required.";
    }
}

// Get existing submission
$stmt = $db->prepare("SELECT * FROM submissions WHERE team_id = ? AND challenge_id = ? AND problem_type = 'buildathon'");
$stmt->bind_param('ii', $team_id, $challenge_id);
$stmt->execute();
$existing_submission = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite Buildathon Arena - <?= htmlspecialchars($buildathon['title']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            overflow-x: hidden;
            position: relative;
            padding: 20px;
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

        .cyber-grid {
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
            z-index: 0;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00ffff, #ff00ff);
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(45deg, #00ffff, #ff00ff, #ffff00);
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
            font-size: 1.3rem;
            opacity: 0.9;
        }

        .main-content {
            padding: 40px;
        }

        .problem-section {
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .problem-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .problem-section:hover::before {
            left: 100%;
        }

        .problem-section:hover {
            transform: translateY(-5px);
            border-color: rgba(0, 255, 255, 0.5);
            box-shadow: 0 20px 40px rgba(0, 255, 255, 0.2);
        }

        .problem-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #00ffff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            z-index: 1;
        }

        .problem-description {
            color: #b0b0b0;
            line-height: 1.6;
            white-space: pre-wrap;
            position: relative;
            z-index: 1;
        }

        .requirements {
            background: rgba(0, 255, 255, 0.1);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            position: relative;
            z-index: 1;
        }

        .requirements h3 {
            color: #00ffff;
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .requirements ul {
            margin-left: 20px;
            color: #e0e0e0;
        }

        .requirements li {
            margin-bottom: 8px;
            position: relative;
        }

        .requirements li::marker {
            color: #00ffff;
        }

        .submission-section {
            margin-top: 30px;
        }

        .submission-section h3 {
            color: #00ffff;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #00ffff;
            font-size: 1rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 8px;
            font-size: 1rem;
            background: rgba(0, 0, 0, 0.6);
            color: #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 0 3px rgba(0, 255, 255, 0.2);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #888;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .form-help {
            font-size: 0.9rem;
            color: #a0a0a0;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(45deg, #00ffff, #0080ff);
            color: #000;
            text-shadow: none;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #0080ff, #00ffff);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 255, 255, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: #00ffff;
            border: 2px solid #00ffff;
        }

        .btn-secondary:hover {
            background: rgba(0, 255, 255, 0.1);
            border-color: #ff00ff;
            color: #ff00ff;
        }

        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .submission-status {
            background: rgba(0, 255, 255, 0.1);
            border: 1px solid rgba(0, 255, 255, 0.3);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
        }

        .submission-status h3 {
            color: #00ffff;
            margin-bottom: 15px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .submission-status p {
            color: #b0b0b0;
            margin-bottom: 20px;
        }

        .submission-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .detail-item {
            background: rgba(0, 0, 0, 0.6);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(0, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            border-color: rgba(0, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .detail-label {
            font-weight: 600;
            color: #00ffff;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .detail-value {
            color: #e0e0e0;
            word-break: break-all;
        }

        .detail-value a {
            color: #00ffff;
            text-decoration: none;
        }

        .detail-value a:hover {
            color: #ff00ff;
        }

        .navigation-footer {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            padding: 25px 40px;
            border-top: 1px solid rgba(0, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: rgba(0, 0, 0, 0.6);
            color: #00ffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 255, 255, 0.3);
        }

        .nav-link:hover {
            background: rgba(0, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 255, 255, 0.2);
        }

        .nav-link.secondary {
            background: rgba(108, 114, 128, 0.3);
            color: #9ca3af;
            border-color: rgba(108, 114, 128, 0.5);
        }

        .nav-link.secondary:hover {
            background: rgba(108, 114, 128, 0.5);
        }

        .nav-group {
            display: flex;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .submission-details {
                grid-template-columns: 1fr;
            }
            
            .navigation-footer {
                flex-direction: column;
                gap: 20px;
            }
            
            .nav-group {
                flex-direction: column;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="cyber-grid"></div>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-hammer"></i> Elite Buildathon Arena</h1>
            <p>Transform your algorithms into revolutionary applications</p>
        </div>

        <div class="main-content">
            <div class="problem-section">
                <h2 class="problem-title">
                    <i class="fas fa-rocket"></i>
                    <?= htmlspecialchars($buildathon['title'] ?? 'Revolutionary Project Challenge') ?>
                </h2>
                <div class="problem-description">
                    <?= nl2br(htmlspecialchars($buildathon['description'] ?? 'Build a comprehensive project based on the algorithmic challenge using cutting-edge development tools and military-grade security standards.')) ?>
                </div>

                <div class="requirements">
                    <h3><i class="fas fa-shield-alt"></i> Elite Project Requirements</h3>
                    <ul>
                        <li>Complete source code hosted on GitHub with military-grade documentation</li>
                        <li>Comprehensive README with quantum-enhanced setup instructions</li>
                        <li>Working demo with real-time processing capabilities</li>
                        <li>Clean, well-documented code with AI-powered optimization</li>
                        <li>Responsive design with fortress-like security architecture</li>
                        <li>Proper error handling and quantum-enhanced testing protocols</li>
                    </ul>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($existing_submission): ?>
                <?php $submission_data = json_decode($existing_submission['answer'], true); ?>
                <div class="submission-status">
                    <h3><i class="fas fa-trophy"></i> Elite Project Submitted Successfully!</h3>
                    <p>Your revolutionary buildathon project has been submitted and is under elite evaluation protocols.</p>
                    
                    <div class="submission-details">
                        <div class="detail-item">
                            <div class="detail-label">GitHub Repository</div>
                            <div class="detail-value">
                                <a href="<?= htmlspecialchars($submission_data['github_link']) ?>" target="_blank">
                                    <?= htmlspecialchars($submission_data['github_link']) ?>
                                </a>
                            </div>
                        </div>
                        
                        <?php if (!empty($submission_data['demo_link'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">Live Demo</div>
                            <div class="detail-value">
                                <a href="<?= htmlspecialchars($submission_data['demo_link']) ?>" target="_blank">
                                    <?= htmlspecialchars($submission_data['demo_link']) ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-item">
                            <div class="detail-label">Submission Time</div>
                            <div class="detail-value">
                                <?= date('M d, Y H:i', strtotime($existing_submission['submission_time'])) ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Elite Points Earned</div>
                            <div class="detail-value"><?= $existing_submission['points'] ?> quantum points</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="submission-section">
                    <h3><i class="fas fa-upload"></i> Submit Your Elite Project</h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="github_link">GitHub Repository URL *</label>
                            <input type="url" id="github_link" name="github_link" required
                                   placeholder="https://github.com/elite-team/revolutionary-project">
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                Public GitHub repository with military-grade source code and documentation
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="demo_link">Live Demo URL (Optional)</label>
                            <input type="url" id="demo_link" name="demo_link"
                                   placeholder="https://elite-project-demo.com">
                            <div class="form-help">
                                <i class="fas fa-rocket"></i>
                                Live demonstration of your revolutionary application
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Project Description</label>
                            <textarea id="description" name="description"
                                      placeholder="Describe your revolutionary project, cutting-edge technologies used, and elite features implemented..."></textarea>
                            <div class="form-help">
                                <i class="fas fa-brain"></i>
                                Optional: Provide elite context about your revolutionary implementation
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Elite Project
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="navigation-footer">
            <a href="challenge_portal.php?id=<?= $challenge_id ?>" class="nav-link secondary">
                <i class="fas fa-arrow-left"></i> Back to Challenge
            </a>
            <div class="nav-group">
                <a href="challenges_list.php" class="nav-link">
                    <i class="fas fa-list"></i> All Challenges
                </a>
                <a href="leaderboard.php" class="nav-link">
                    <i class="fas fa-trophy"></i> Elite Leaderboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
