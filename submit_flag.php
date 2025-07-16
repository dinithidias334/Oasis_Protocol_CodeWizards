<?php
session_start();
require 'config.php';

if (!isset($_SESSION['team_id'])) {
    header('Location: login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = db_connect();
    $submitted_flag = trim($_POST['flag']);
    $team_id = $_SESSION['team_id'];
    
    // Get challenge ID from POST data
    $challenge_id = isset($_POST['challenge_id']) ? (int)$_POST['challenge_id'] : 1;
    
    // Get correct flag from database
    $stmt = $db->prepare("SELECT flag FROM flags WHERE challenge_id = ?");
    $stmt->bind_param('i', $challenge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $correct_flag = $result->fetch_assoc()['flag'];
        
        if ($submitted_flag === $correct_flag) {
            // Check if already submitted
            $stmt = $db->prepare("SELECT id FROM submissions WHERE team_id = ? AND challenge_id = ? AND problem_type = 'algorithmic'");
            $stmt->bind_param('ii', $team_id, $challenge_id);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            
            if (!$exists) {
                // Record submission
                $stmt = $db->prepare("INSERT INTO submissions (team_id, challenge_id, problem_type, answer, points, submission_time) VALUES (?, ?, 'algorithmic', ?, 100, NOW())");
                $stmt->bind_param('iis', $team_id, $challenge_id, $submitted_flag);
                $stmt->execute();
                
                // Update progress to unlock buildathon
                $stmt = $db->prepare("INSERT INTO team_progress (team_id, challenge_id, unlocked_buildathon) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE unlocked_buildathon = 1");
                $stmt->bind_param('ii', $team_id, $challenge_id);
                $stmt->execute();
                
                echo "<script>
                    alert('Correct! Buildathon phase unlocked. You earned 100 points!');
                    window.location.href='buildathon.php?challenge_id=$challenge_id';
                </script>";
            } else {
                echo "<script>
                    alert('You have already submitted this flag!');
                    window.location.href='challenge_portal.php?id=$challenge_id';
                </script>";
            }
        } else {
            echo "<script>
                alert('Incorrect flag. Try again!');
                window.location.href='challenge_portal.php?id=$challenge_id';
            </script>";
        }
    } else {
        echo "<script>
            alert('Challenge not found!');
            window.location.href='challenges_list.php';
        </script>";
    }
} else {
    header('Location: challenges_list.php');
}
?>
