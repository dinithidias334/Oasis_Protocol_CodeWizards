-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 16, 2025 at 12:15 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hackathon_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(32) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`) VALUES
(3, 'admin', '1111');

-- --------------------------------------------------------

--
-- Table structure for table `algorithmic_problems`
--

CREATE TABLE `algorithmic_problems` (
  `id` int(11) NOT NULL,
  `challenge_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `constraints` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `algorithmic_problems`
--

INSERT INTO `algorithmic_problems` (`id`, `challenge_id`, `description`, `constraints`) VALUES
(6, 1, 'Find maximum sum of contiguous subarray.\n\nExample: [1, -3, 2, 1, -1] → Output: 3', 'Array length: 1-1000, Values: -100 to 100, Time: 1s'),
(7, 2, 'Check if string is palindrome.\n\nExample: \"racecar\" → true', 'Length: 1-1000, Time: 1s'),
(8, 3, 'Find shortest path in graph.\n\nInput: nodes, edges, start, end', 'Nodes: 1-100, Time: 2s'),
(9, 4, 'Solve knapsack problem.\n\nInput: items, weights, values, capacity', 'Items: 1-50, Time: 1s'),
(10, 5, 'Category: Encoding\r\nPoints: 50\r\nDescription:\r\n\r\n    We intercepted a message. It looks suspicious... Can you decode it and find the flag?\r\n\r\n    U0tZUkVfQ1RGe19CQU5BTkFTX0JBU0U2NF9SRVZlQUxFRH0=', 'Values: 1-1000, Time: 2s'),
(11, 6, 'Category: Cryptography / Encoding\r\nPoints: 50\r\nDescription:\r\n\r\n    We intercepted this strange message, but it seems to be encoded. Can you figure out what it says?\r\n\r\n    U29tZXRpbWVzIHRoZSBmbGFnIGlzIGp1c3QgcGxhaW4u\r\n\r\nHint: Try using an online tool or built-in Python tools.\r\n\r\nFlag Format: flag{your_answer_here}', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum');

-- --------------------------------------------------------

--
-- Table structure for table `buildathon_problems`
--

CREATE TABLE `buildathon_problems` (
  `id` int(11) NOT NULL,
  `challenge_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buildathon_problems`
--

INSERT INTO `buildathon_problems` (`id`, `challenge_id`, `description`) VALUES
(6, 1, 'Build Array Visualizer:\n- Web app with sorting animations\n- Multiple sorting algorithms\n- Interactive controls\n- Responsive design'),
(7, 2, 'Build Text Analyzer:\n- Upload text files\n- Word frequency count\n- Sentiment analysis\n- Export results'),
(8, 3, 'Build Route Finder:\n- Map integration\n- Shortest path calculator\n- Multiple locations\n- Save routes'),
(9, 4, 'Build Inventory System:\n- Product management\n- Stock tracking\n- Order processing\n- Reports dashboard'),
(10, 5, 'import base64\r\n\r\n# Encoded message\r\nencoded = \"U0tZUkVfQ1RGe19CQU5BTkFTX0JBU0U2NF9SRVZlQUxFRH0=\"\r\n\r\n# Decode Base64\r\ndecoded = base64.b64decode(encoded).decode(\'utf-8\')\r\n\r\n# Print the flag\r\nprint(\"Flag:\", decoded)'),
(11, 6, 'import base64\r\nprint(base64.b64decode(\"U29tZXRpbWVzIHRoZSBmbGFnIGlzIGp1c3QgcGxhaW4u\").decode())');

-- --------------------------------------------------------

--
-- Table structure for table `challenges`
--

CREATE TABLE `challenges` (
  `id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `challenges`
--

INSERT INTO `challenges` (`id`, `title`, `active`) VALUES
(1, 'Array Problem', 1),
(2, 'String Problem', 1),
(3, 'Graph Problem', 0),
(4, 'DP Problem', 1),
(5, 'Hidden in Base64', 1),
(6, '404', 1);

-- --------------------------------------------------------

--
-- Table structure for table `flags`
--

CREATE TABLE `flags` (
  `id` int(11) NOT NULL,
  `challenge_id` int(11) DEFAULT NULL,
  `flag` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flags`
--

INSERT INTO `flags` (`id`, `challenge_id`, `flag`) VALUES
(11, 1, 'FLAG{array_max_sum}'),
(12, 2, 'FLAG{palindrome_check}'),
(13, 3, 'FLAG{shortest_path}'),
(14, 4, 'FLAG{knapsack_solved}'),
(15, 5, 'SKYRE_CTF{BANANAS_BASE64_ReVEALEd}'),
(16, 6, 'flag{Sometimes the flag is just plain}');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `challenge_id` int(11) DEFAULT NULL,
  `problem_type` enum('algorithmic','buildathon') DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `points` int(11) DEFAULT NULL,
  `submission_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `team_id`, `challenge_id`, `problem_type`, `answer`, `points`, `submission_time`) VALUES
(1, 1, 1, 'algorithmic', 'FLAG{array_max_sum}', 100, '2024-01-15 15:30:00'),
(2, 1, 1, 'buildathon', 'https://github.com/team1/array-visualizer', 150, '2024-01-15 17:45:00'),
(3, 1, 2, 'algorithmic', 'FLAG{palindrome_check}', 100, '2024-01-15 18:20:00'),
(4, 2, 1, 'algorithmic', 'FLAG{array_max_sum}', 100, '2024-01-15 15:45:00'),
(5, 2, 1, 'buildathon', 'https://github.com/team2/array-viz', 150, '2024-01-15 18:30:00'),
(6, 3, 1, 'algorithmic', 'FLAG{array_max_sum}', 100, '2024-01-15 16:00:00'),
(7, 4, 1, 'algorithmic', 'FLAG{array_max_sum}', 100, '2024-01-15 16:30:00'),
(8, 5, 1, 'algorithmic', 'FLAG{array_max_sum}', 100, '2024-01-15 17:00:00'),
(9, 11, 1, 'algorithmic', 'FLAG{array_max_sum}', 100, '2025-07-16 10:25:12'),
(10, 12, 1, 'algorithmic', 'FLAG{array_max_sum}', 100, '2025-07-16 11:40:36'),
(11, 12, 1, 'buildathon', '{\"github_link\":\"https:\\/\\/github.com\\/mhasara\\/evenet-maanagement-system.git\",\"demo_link\":\"https:\\/\\/www.speedtest.net\\/\",\"description\":\"our duothan project\"}', 150, '2025-07-16 11:43:38'),
(12, 12, 6, 'algorithmic', 'flag{this_is_the_secret}', 100, '2025-07-16 12:49:31'),
(13, 13, 6, 'algorithmic', 'flag{Sometimes the flag is just plain}', 100, '2025-07-16 15:37:55'),
(14, 13, 6, 'buildathon', '{\"github_link\":\"https:\\/\\/github.com\\/mhasara\\/evenet-maanagement-system.git\",\"demo_link\":\"https:\\/\\/www.speedtest.net\\/\",\"description\":\"duothan project\"}', 150, '2025-07-16 15:38:32');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `team_name` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `team_name`, `password_hash`, `created_at`) VALUES
(1, 'CodeWarriors', '1234', '2024-01-15 10:30:00'),
(2, 'ByteBuilders', '1234', '2024-01-15 11:45:00'),
(3, 'DevDynamos', '1234', '2024-01-15 12:20:00'),
(4, 'TechTitans', '1234', '2024-01-15 13:15:00'),
(5, 'AlgoAces', '1234', '2024-01-15 14:00:00'),
(11, 'hello', '$2y$10$PaWgxLoktycGWeGsnELSnOVhmhUN/jH0D81li507KKjZcxuuxedqO', '2025-07-16 10:14:39'),
(12, 'nadux', '$2y$10$j66eQD/3dYB7NQWF/1wch.kxW5Z3xmhyWRG4XhUhj0Hu/QOZSpRmy', '2025-07-16 11:40:15'),
(13, 'duothan', '$2y$10$E9I1GFMUCv2WQ/Ymdz7V9.QOyQFW6unoxH4saPERc7i2SBrj.PPRK', '2025-07-16 15:36:01');

-- --------------------------------------------------------

--
-- Table structure for table `team_progress`
--

CREATE TABLE `team_progress` (
  `team_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `unlocked_buildathon` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_progress`
--

INSERT INTO `team_progress` (`team_id`, `challenge_id`, `unlocked_buildathon`) VALUES
(1, 1, 1),
(1, 2, 1),
(2, 1, 1),
(3, 1, 1),
(4, 1, 0),
(5, 1, 0),
(11, 1, 1),
(12, 1, 1),
(12, 6, 1),
(13, 6, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `algorithmic_problems`
--
ALTER TABLE `algorithmic_problems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indexes for table `buildathon_problems`
--
ALTER TABLE `buildathon_problems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indexes for table `challenges`
--
ALTER TABLE `challenges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flags`
--
ALTER TABLE `flags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `team_name` (`team_name`);

--
-- Indexes for table `team_progress`
--
ALTER TABLE `team_progress`
  ADD PRIMARY KEY (`team_id`,`challenge_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `algorithmic_problems`
--
ALTER TABLE `algorithmic_problems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `buildathon_problems`
--
ALTER TABLE `buildathon_problems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `challenges`
--
ALTER TABLE `challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `flags`
--
ALTER TABLE `flags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `algorithmic_problems`
--
ALTER TABLE `algorithmic_problems`
  ADD CONSTRAINT `algorithmic_problems_ibfk_1` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`);

--
-- Constraints for table `buildathon_problems`
--
ALTER TABLE `buildathon_problems`
  ADD CONSTRAINT `buildathon_problems_ibfk_1` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`);

--
-- Constraints for table `flags`
--
ALTER TABLE `flags`
  ADD CONSTRAINT `flags_ibfk_1` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`);

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
