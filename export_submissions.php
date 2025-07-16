<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.html');
    exit();
}

require '../config.php';
$db = db_connect();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="submissions_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// CSV headers
fputcsv($output, ['ID', 'Team Name', 'Challenge', 'Problem Type', 'Points', 'Answer/Link', 'Submission Time']);

// Get all submissions
$query = "
    SELECT s.id, t.team_name, c.title, s.problem_type, s.points, s.answer, s.submission_time
    FROM submissions s
    JOIN teams t ON s.team_id = t.id
    JOIN challenges c ON s.challenge_id = c.id
    ORDER BY s.submission_time DESC
";

$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
?>
