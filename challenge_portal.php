<?php
session_start();
if (!isset($_SESSION['team_id'])) {
    header('Location: login.html');
    exit();
}

require 'config.php';
$db = db_connect();

// Get challenge ID from URL parameter
$challenge_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Fixed database query - removed non-existent columns
$challenge = $db->query("
    SELECT c.*, 
           ap.description as algo_desc,
           ap.constraints,
           bp.description as build_desc,
           f.flag,
           tp.unlocked_buildathon,
           MAX(CASE WHEN s.team_id = {$_SESSION['team_id']} AND s.problem_type = 'algorithmic' THEN 1 ELSE 0 END) as algo_completed
    FROM challenges c 
    LEFT JOIN algorithmic_problems ap ON c.id = ap.challenge_id
    LEFT JOIN buildathon_problems bp ON c.id = bp.challenge_id
    LEFT JOIN flags f ON c.id = f.challenge_id
    LEFT JOIN team_progress tp ON c.id = tp.challenge_id AND tp.team_id = {$_SESSION['team_id']}
    LEFT JOIN submissions s ON c.id = s.challenge_id AND s.team_id = {$_SESSION['team_id']}
    WHERE c.id = $challenge_id AND c.active = 1
    GROUP BY c.id
")->fetch_assoc();

if (!$challenge) {
    header('Location: challenges_list.php');
    exit();
}

// Define challenges data
$challenges_data = [
    1 => [
        'title' => 'Quantum Array Protocol',
        'description' => 'Implement a quantum-enhanced algorithm to find the maximum sum of a contiguous subarray within a neural matrix. The algorithm must process data streams in real-time and optimize for quantum coherence.',
        'constraints' => 'Matrix dimensions: 1-1000 elements\nQuantum values: -100 to 100 flux units\nProcessing time: O(n) complexity required\nMemory limit: 256MB quantum storage',
        'placeholder' => '# Initialize quantum neural network\ndef quantum_max_subarray(matrix):\n    # Your quantum algorithm here\n    max_sum = float(\'-inf\')\n    current_sum = 0\n    \n    for num in matrix:\n        current_sum = max(num, current_sum + num)\n        max_sum = max(max_sum, current_sum)\n    \n    return max_sum\n\n# Test quantum matrix\nquantum_matrix = [1, -3, 2, 1, -1]\nresult = quantum_max_subarray(quantum_matrix)\nprint(result)'
    ],
    2 => [
        'title' => 'Matrix Cipher Protocol',
        'description' => 'Decode an encrypted matrix using advanced cryptographic algorithms. Your neural processor must identify patterns in the cipher and extract the hidden message using matrix transformations.',
        'constraints' => 'Matrix size: 8x8 to 64x64\nCipher complexity: Level 3 encryption\nDecryption time: O(n²) maximum\nMemory limit: 512MB quantum storage',
        'placeholder' => '# Matrix cipher decoder\nimport base64\n\ndef decode_matrix(cipher_text):\n    # Decode the cipher\n    decoded = base64.b64decode(cipher_text).decode()\n    return decoded\n\n# Test cipher\ncipher = "U29tZXRoaW5nIHNlY3JldA=="\nresult = decode_matrix(cipher)\nprint(result)'
    ],
    3 => [
        'title' => 'Neural Network Optimization',
        'description' => 'Optimize a neural network architecture for maximum efficiency. Balance between accuracy and computational complexity while maintaining real-time processing capabilities.',
        'constraints' => 'Network layers: 3-10 layers\nNeurons per layer: 10-1000\nTraining epochs: Maximum 100\nMemory limit: 1GB quantum storage',
        'placeholder' => '# Neural network optimizer\ndef optimize_network(layers, neurons):\n    # Calculate optimization score\n    efficiency = sum(neurons) / len(layers)\n    return int(efficiency)\n\n# Test network\nlayers = [3, 5, 2]\nneurons = [10, 20, 5]\nresult = optimize_network(layers, neurons)\nprint(result)'
    ]
];

$current_challenge = $challenges_data[$challenge_id] ?? $challenges_data[1];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($current_challenge['title']) ?> - Elite Hackathon Arena</title>
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
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
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

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }

        .challenge-selector {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .challenge-selector label {
            font-weight: 600;
            color: #00ffff;
        }

        .challenge-selector select {
            padding: 8px 12px;
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.8);
            color: #00ffff;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .challenge-selector select:hover {
            border-color: #00ffff;
        }

        .challenge-selector select:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 0 3px rgba(0, 255, 255, 0.1);
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .problem-section {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .problem-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .problem-title {
            font-size: 1.5rem;
            color: #00ffff;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .problem-description {
            color: #b0b0b0;
            line-height: 1.6;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }

        .constraints {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .constraints h4 {
            color: #f59e0b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .constraints p {
            color: #fcd34d;
            margin: 0;
            white-space: pre-wrap;
        }

        .code-editor-section {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .code-editor-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .code-editor-section h3 {
            margin-bottom: 15px;
            color: #00ffff;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #source-code {
            width: 100%;
            height: 300px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 8px;
            resize: vertical;
            background: rgba(0, 0, 0, 0.8);
            color: #00ffff;
            transition: all 0.3s ease;
        }

        #source-code:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 0 3px rgba(0, 255, 255, 0.1);
        }

        #source-code::placeholder {
            color: #6b7280;
        }

        .bottom-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .controls-output {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .controls-output::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        }

        .controls {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
        }

        .controls label {
            color: #00ffff;
            font-weight: 600;
        }

        .controls select {
            padding: 8px 12px;
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.8);
            color: #00ffff;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .controls select:hover {
            border-color: #00ffff;
        }

        .controls button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(45deg, #00ffff, #0080ff);
            color: #000;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .controls button:hover {
            background: linear-gradient(45deg, #0080ff, #00ffff);
            transform: translateY(-2px);
        }

        .status-info {
            margin-bottom: 15px;
            padding: 12px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            color: #3b82f6;
            font-weight: 500;
        }

        .output-section {
            background: #0a0a0a;
            color: #10b981;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            min-height: 120px;
            border: 2px solid rgba(16, 185, 129, 0.3);
            white-space: pre-wrap;
        }

        .output-section h3 {
            color: #10b981;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .flag-section {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .flag-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .flag-section h3 {
            color: #00ffff;
            margin-bottom: 15px;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .flag-section p {
            color: #b0b0b0;
            margin-bottom: 15px;
        }

        .flag-section input {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 16px;
            background: rgba(0, 0, 0, 0.8);
            color: #00ffff;
            transition: all 0.3s ease;
        }

        .flag-section input:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 0 3px rgba(0, 255, 255, 0.1);
        }

        .flag-section input::placeholder {
            color: #6b7280;
        }

        .flag-section button {
            padding: 12px 24px;
            background: linear-gradient(45deg, #f59e0b, #d97706);
            color: #000;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .flag-section button:hover {
            background: linear-gradient(45deg, #d97706, #f59e0b);
            transform: translateY(-2px);
        }

        .navigation-footer {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 20px;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: rgba(0, 0, 0, 0.8);
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
        }

        .nav-link.secondary {
            background: rgba(108, 114, 128, 0.3);
            color: #9ca3af;
        }

        .nav-link.secondary:hover {
            background: rgba(108, 114, 128, 0.5);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .challenge-selector {
                width: 100%;
                justify-content: center;
            }

            .main-content {
                grid-template-columns: 1fr;
            }

            .bottom-section {
                grid-template-columns: 1fr;
            }

            .navigation-footer {
                flex-direction: column;
                gap: 15px;
            }

            .controls {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-code"></i> Quantum Challenge Portal</h1>
                
                <div class="challenge-selector">
                    <label>Protocol:</label>
                    <select id="challenge-select" onchange="switchChallenge()">
                        <option value="1" <?= $challenge_id == 1 ? 'selected' : '' ?>>Quantum Algorithm</option>
                        <option value="2" <?= $challenge_id == 2 ? 'selected' : '' ?>>Matrix Cipher</option>
                        <option value="3" <?= $challenge_id == 3 ? 'selected' : '' ?>>Neural Network</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="main-content">
            <!-- Problem Description -->
            <div class="problem-section">
                <h2 class="problem-title">
                    <i class="fas fa-atom"></i>
                    <?= htmlspecialchars($current_challenge['title']) ?>
                </h2>
                <div class="problem-description">
                    <?= htmlspecialchars($current_challenge['description']) ?>
                </div>
                
                <div class="constraints">
                    <h4><i class="fas fa-microchip"></i> System Constraints</h4>
                    <p><?= htmlspecialchars($current_challenge['constraints']) ?></p>
                </div>
            </div>

            <!-- Code Editor -->
            <div class="code-editor-section">
                <h3><i class="fas fa-brain"></i> Neural Code Editor</h3>
                <textarea id="source-code" placeholder="<?= htmlspecialchars($current_challenge['placeholder']) ?>"></textarea>
            </div>
        </div>

        <div class="bottom-section">
            <!-- Controls & Output -->
            <div class="controls-output">
                <div class="controls">
                    <label><i class="fas fa-cogs"></i> Language:</label>
                    <select id="language-select">
                        <option value="71">Python 3</option>
                        <option value="54">C++</option>
                        <option value="62">Java</option>
                        <option value="63">JavaScript</option>
                    </select>
                    <button onclick="runCode()">
                        <i class="fas fa-play"></i> Execute
                    </button>
                </div>

                <div id="status-info" class="status-info">
                    <i class="fas fa-microchip"></i> Neural processor ready for execution...
                </div>

                <div class="output-section">
                    <h3><i class="fas fa-terminal"></i> Quantum Output:</h3>
                    <div id="output">>>> Awaiting neural execution...</div>
                </div>
            </div>

            <!-- Flag Submission -->
            <div class="flag-section">
                <h3><i class="fas fa-key"></i> Submit Access Code</h3>
                <p>After solving the quantum protocol, submit the access code to unlock the buildathon phase.</p>
                <form method="post" action="submit_flag.php">
                    <input type="hidden" name="challenge_id" value="<?= $challenge_id ?>">
                    <input type="text" name="flag" id="flag-input" placeholder="FLAG{quantum_solution}" required autocomplete="off">
                    <button type="submit">
                        <i class="fas fa-paper-plane"></i> Submit Access Code
                    </button>
                </form>
            </div>
        </div>

        <div class="navigation-footer">
            <a href="challenges_list.php" class="nav-link secondary">
                <i class="fas fa-arrow-left"></i> Back to Challenges
            </a>
            <a href="leaderboard.php" class="nav-link">
                <i class="fas fa-trophy"></i> Leaderboard
            </a>
        </div>
    </div>

    <script>
        function runCode() {
            const code = document.getElementById('source-code').value;
            const languageId = document.getElementById('language-select').value;
            const statusDiv = document.getElementById('status-info');
            const outputDiv = document.getElementById('output');
            
            if (!code.trim()) {
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error: Neural code required for execution!';
                outputDiv.innerHTML = '>>> No quantum code to process';
                return;
            }

            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Executing quantum code...';
            outputDiv.innerHTML = '>>> Quantum processing initiated...';

            fetch('run_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    source_code: code,
                    language_id: parseInt(languageId),
                    challenge_id: <?= $challenge_id ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('API Response:', data);
                
                if (data.error) {
                    statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Quantum execution failed';
                    outputDiv.innerHTML = '>>> Error: ' + data.error;
                } else if (data.stdout && data.stdout.trim()) {
                    statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> Quantum execution completed successfully';
                    outputDiv.innerHTML = '>>> ' + data.stdout;
                } else if (data.stderr && data.stderr.trim()) {
                    statusDiv.innerHTML = '<i class="fas fa-times-circle"></i> Quantum runtime error occurred';
                    outputDiv.innerHTML = '>>> Error: ' + data.stderr;
                } else if (data.compile_output && data.compile_output.trim()) {
                    statusDiv.innerHTML = '<i class="fas fa-times-circle"></i> Quantum compilation failed';
                    outputDiv.innerHTML = '>>> Compile Error: ' + data.compile_output;
                } else {
                    statusDiv.innerHTML = '<i class="fas fa-info-circle"></i> Quantum process completed with no output';
                    outputDiv.innerHTML = '>>> Code executed but produced no quantum output';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Neural network error';
                outputDiv.innerHTML = '>>> Connection Error: ' + error.message;
            });
        }

        function switchChallenge() {
            const select = document.getElementById('challenge-select');
            const selectedValue = select.value;
            window.location.href = `challenge_portal.php?id=${selectedValue}`;
        }
    </script>
</body>
</html>
