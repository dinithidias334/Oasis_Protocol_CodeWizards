<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = db_connect();
    $team_name = trim($_POST['team_name']);
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO teams (team_name, password_hash) VALUES (?, ?)");
    $stmt->bind_param('ss', $team_name, $hash);
    if ($stmt->execute()) {
        header('Location: login.html');
    } else {
        echo "Team name already exists!";
    }
}
?>
