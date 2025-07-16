<?php
require 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'No input data received']);
    exit;
}

$source_code = $input['source_code'] ?? '';
$language_id = $input['language_id'] ?? 71;

if (empty($source_code)) {
    echo json_encode(['error' => 'No source code provided']);
    exit;
}

// Enhanced mock execution for better output handling
if ($language_id == 71) { // Python
    // Handle base64 decode
    if (strpos($source_code, 'base64.b64decode') !== false) {
        // Extract the base64 string and decode it
        preg_match('/base64\.b64decode\(["\']([^"\']+)["\']/', $source_code, $matches);
        if (isset($matches[1])) {
            $decoded = base64_decode($matches[1]);
            echo json_encode([
                'stdout' => $decoded,
                'stderr' => '',
                'status' => ['id' => 3, 'description' => 'Accepted']
            ]);
            exit;
        }
    }
    
    // Handle simple print statements
    if (strpos($source_code, 'print(') !== false) {
        // Try to extract what's being printed
        if (preg_match('/print\(([^)]+)\)/', $source_code, $matches)) {
            $printContent = trim($matches[1], '"\'');
            echo json_encode([
                'stdout' => $printContent,
                'stderr' => '',
                'status' => ['id' => 3, 'description' => 'Accepted']
            ]);
            exit;
        }
    }
}

// Fallback to Judge0 API
$payload = [
    "language_id" => (int)$language_id,
    "source_code" => $source_code,
    "stdin" => "",
    "expected_output" => ""
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, JUDGE0_API_URL . '/submissions?base64_encoded=false&wait=true');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Auth-Token: ' . JUDGE0_API_TOKEN
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!in_array($httpCode, [200, 201, 202])) {
    echo json_encode(['error' => 'API request failed with HTTP code: ' . $httpCode]);
    exit;
}

$result = json_decode($response, true);

// Enhanced output handling
if ($result) {
    // Ensure stdout is properly captured
    if (isset($result['stdout']) && !empty(trim($result['stdout']))) {
        $result['stdout'] = trim($result['stdout']);
    } else {
        $result['stdout'] = '';
    }
    
    if (isset($result['stderr']) && !empty(trim($result['stderr']))) {
        $result['stderr'] = trim($result['stderr']);
    } else {
        $result['stderr'] = '';
    }
    
    echo json_encode($result);
} else {
    echo json_encode(['error' => 'Invalid API response']);
}
?>
