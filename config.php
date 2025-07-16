<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Your MySQL password
define('DB_NAME', 'hackathon_db');

// Judge0 CE API Configs
define('JUDGE0_API_URL', 'http://10.3.5.139:2358');
define('JUDGE0_API_TOKEN', 'ZHVvdGhhbjUuMA==');

function db_connect() {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_error) {
        die('Database connection failed: ' . $mysqli->connect_error);
    }
    return $mysqli;
}
?>
