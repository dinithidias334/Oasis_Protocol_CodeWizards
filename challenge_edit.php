<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.html');
    exit();
}

require '../config.php';
$db = db_connect();

$editing = false;
$challenge = null;
$algorithmic = null;
$buildathon = null;
$flag = null;

// Check if editing existing challenge
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $editing = true;
    $id = $_GET['id'];
    
    // Fetch challenge data
    $stmt = $db->prepare("SELECT * FROM challenges WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $challenge = $stmt->get_result()->fetch_assoc();
    
    // Fetch algorithmic problem
    $stmt = $db->prepare("SELECT * FROM algorithmic_problems WHERE challenge_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $algorithmic = $stmt->get_result()->fetch_assoc();
    
    // Fetch buildathon problem
    $stmt = $db->prepare("SELECT * FROM buildathon_problems WHERE challenge_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $buildathon = $stmt->get_result()->fetch_assoc();
    
    // Fetch flag
    $stmt = $db->prepare("SELECT * FROM flags WHERE challenge_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $flag = $stmt->get_result()->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $active = isset($_POST['active']) ? 1 : 0;
    $algo_desc = trim($_POST['algo_description']);
    $algo_constraints = trim($_POST['algo_constraints']);
    $build_desc = trim($_POST['build_description']);
    $flag_value = trim($_POST['flag']);
    
    if ($editing) {
        // Update challenge
        $stmt = $db->prepare("UPDATE challenges SET title = ?, active = ? WHERE id = ?");
        $stmt->bind_param('sii', $title, $active, $id);
        $stmt->execute();
        
        // Update algorithmic problem
        if ($algorithmic) {
            $stmt = $db->prepare("UPDATE algorithmic_problems SET description = ?, constraints = ? WHERE challenge_id = ?");
            $stmt->bind_param('ssi', $algo_desc, $algo_constraints, $id);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO algorithmic_problems (challenge_id, description, constraints) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $id, $algo_desc, $algo_constraints);
            $stmt->execute();
        }
        
        // Update buildathon problem
        if ($buildathon) {
            $stmt = $db->prepare("UPDATE buildathon_problems SET description = ? WHERE challenge_id = ?");
            $stmt->bind_param('si', $build_desc, $id);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO buildathon_problems (challenge_id, description) VALUES (?, ?)");
            $stmt->bind_param('is', $id, $build_desc);
            $stmt->execute();
        }
        
        // Update flag
        if ($flag) {
            $stmt = $db->prepare("UPDATE flags SET flag = ? WHERE challenge_id = ?");
            $stmt->bind_param('si', $flag_value, $id);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("INSERT INTO flags (challenge_id, flag) VALUES (?, ?)");
            $stmt->bind_param('is', $id, $flag_value);
            $stmt->execute();
        }
        
        header('Location: challenges.php?msg=updated');
        exit();
    } else {
        // Create new challenge
        $stmt = $db->prepare("INSERT INTO challenges (title, active) VALUES (?, ?)");
        $stmt->bind_param('si', $title, $active);
        $stmt->execute();
        $challenge_id = $db->insert_id;
        
        // Insert algorithmic problem
        $stmt = $db->prepare("INSERT INTO algorithmic_problems (challenge_id, description, constraints) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $challenge_id, $algo_desc, $algo_constraints);
        $stmt->execute();
        
        // Insert buildathon problem
        $stmt = $db->prepare("INSERT INTO buildathon_problems (challenge_id, description) VALUES (?, ?)");
        $stmt->bind_param('is', $challenge_id, $build_desc);
        $stmt->execute();
        
        // Insert flag
        $stmt = $db->prepare("INSERT INTO flags (challenge_id, flag) VALUES (?, ?)");
        $stmt->bind_param('is', $challenge_id, $flag_value);
        $stmt->execute();
        
        header('Location: challenges.php?msg=created');
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - <?= $editing ? 'Edit' : 'Create' ?> Challenge</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 120px; resize: vertical; }
        .btn { padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .checkbox-group { display: flex; align-items: center; }
        .checkbox-group input { width: auto; margin-right: 10px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .section { background-color: #f8f9fa; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        .section h3 { margin-top: 0; color: #495057; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= $editing ? 'Edit Challenge' : 'Create New Challenge' ?></h1>
            <a href="challenges.php" class="btn btn-secondary">← Back to Challenges</a>
        </div>

        <form method="POST">
            <div class="section">
                <h3>Challenge Information</h3>
                <div class="form-group">
                    <label for="title">Challenge Title:</label>
                    <input type="text" id="title" name="title" 
                           value="<?= htmlspecialchars($challenge['title'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="active" name="active" 
                               <?= ($challenge['active'] ?? 1) ? 'checked' : '' ?>>
                        <label for="active">Active Challenge</label>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3>Algorithmic Problem</h3>
                <div class="form-group">
                    <label for="algo_description">Problem Description:</label>
                    <textarea id="algo_description" name="algo_description" 
                              placeholder="Describe the algorithmic problem..."><?= htmlspecialchars($algorithmic['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="algo_constraints">Constraints:</label>
                    <textarea id="algo_constraints" name="algo_constraints" 
                              placeholder="List constraints and limits..."><?= htmlspecialchars($algorithmic['constraints'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="section">
                <h3>Buildathon Problem</h3>
                <div class="form-group">
                    <label for="build_description">Project Description:</label>
                    <textarea id="build_description" name="build_description" 
                              placeholder="Describe the buildathon project requirements..."><?= htmlspecialchars($buildathon['description'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="section">
                <h3>Flag Configuration</h3>
                <div class="form-group">
                    <label for="flag">Flag (Answer to unlock buildathon):</label>
                    <input type="text" id="flag" name="flag" 
                           value="<?= htmlspecialchars($flag['flag'] ?? '') ?>" 
                           placeholder="Enter the correct flag/answer" required>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <?= $editing ? 'Update Challenge' : 'Create Challenge' ?>
                </button>
                <a href="challenges.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
