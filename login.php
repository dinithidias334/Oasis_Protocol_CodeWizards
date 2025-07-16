<?php
session_start();
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = db_connect();
    $team_name = $_POST['team_name'];
    $pass = $_POST['password'];
    $q = $db->prepare("SELECT id, password_hash FROM teams WHERE team_name=?");
    $q->bind_param('s', $team_name);
    $q->execute();
    $q->store_result();
    if ($q->num_rows > 0) {
        $q->bind_result($id, $hash);
        $q->fetch();
        if (password_verify($pass, $hash)) {
            $_SESSION['team_id'] = $id;
            $_SESSION['team_name'] = $team_name;
            header('Location: challenges_list.php');
            exit();
        }
    }
    echo "Invalid credentials!";
}
?>
