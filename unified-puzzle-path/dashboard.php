<?php
session_start();
require_once 'config.php';

// Demo mode support - check for demo parameter
$demoMode = isset($_GET['demo']) && $_GET['demo'] === 'true';
$demoProfile = $_GET['profile'] ?? 'advanced'; // basic, intermediate, advanced

$userData = null;
$completionData = null;
$questStats = null;
$allCompletions = [];
$userMedals = [];

// Function to generate demo data based on profile
function generateDemoData($profile) {
    $profiles = [
        'basic' => [
            'user' => [
                'id' => 1001,
                'name' => 'Sarah Thompson',
                'email' => 'sarah.thompson@email.com',
                'joinDate' => '2024-09-15'
            ],
            'completions' => [
                [
                    'huntName' => 'Broadbeach Quest',
                    'formattedTime' => '01:12:45',
                    'completionDate' => '2024-10-08 14:30:00',
                    'totalTime' => 4365000,
                    'bookingCode' => 'BB-20241008-001',
                    'difficulty' => 'Medium'
                ]
            ],
            'stats' => [
                'totalQuests' => 1,
                'totalMedals' => 1,
                'totalTimeSpent' => '1 hour 12 minutes',
                'averageTime' => '01:12:45'
            ],
            'medals' => [
                ['name' => 'Broadbeach Explorer', 'icon' => '🏅', 'earned' => '2024-10-08']
            ]
        ],
        'intermediate' => [
            'user' => [
                'id' => 2002,
                'name' => 'Marcus Chen',
                'email' => 'marcus.chen@email.com',
                'joinDate' => '2024-08-20'
            ],
            'completions' => [
                [
                    'huntName' => 'Broadbeach Quest',
                    'formattedTime' => '00:45:23',
                    'completionDate' => '2024-10-05 10:15:00',
                    'totalTime' => 2723000,
                    'bookingCode' => 'BB-20241005-012',
                    'difficulty' => 'Medium'
                ],
                [
                    'huntName' => 'Emerald Lakes Explorer',
                    'formattedTime' => '01:03:17',
                    'completionDate' => '2024-09-28 16:45:00',
                    'totalTime' => 3797000,
                    'bookingCode' => 'EP-20240928-025',
                    'difficulty' => 'Easy'
                ],
                [
                    'huntName' => 'Surfers Paradise Adventure',
                    'formattedTime' => '00:52:08',
                    'completionDate' => '2024-09-15 11:30:00',
                    'totalTime' => 3128000,
                    'bookingCode' => 'SP-20240915-008',
                    'difficulty' => 'Hard'
                ]
            ],
            'stats' => [
                'totalQuests' => 3,
                'totalMedals' => 4,
                'totalTimeSpent' => '2 hours 40 minutes',
                'averageTime' => '00:53:36'
            ],
            'medals' => [
                ['name' => 'Broadbeach Explorer', 'icon' => '🏅', 'earned' => '2024-10-05'],
                ['name' => 'Emerald Champion', 'icon' => '🌟', 'earned' => '2024-09-28'],
                ['name' => 'Surfers Paradise Master', 'icon' => '🏆', 'earned' => '2024-09-15'],
                ['name' => 'Speed Demon', 'icon' => '⚡', 'earned' => '2024-10-05']
            ]
        ],
        'advanced' => [
            'user' => [
                'id' => 3003,
                'name' => 'Isabella Rodriguez',
                'email' => 'isabella.rodriguez@email.com',
                'joinDate' => '2024-07-10'
            ],
            'completions' => [
                [
                    'huntName' => 'Coolangatta Coastal Quest',
                    'formattedTime' => '00:38:45',
                    'completionDate' => '2024-10-07 09:20:00',
                    'totalTime' => 2325000,
                    'bookingCode' => 'CQ-20241007-003',
                    'difficulty' => 'Hard'
                ],
                [
                    'huntName' => 'Broadbeach Quest',
                    'formattedTime' => '00:32:12',
                    'completionDate' => '2024-10-01 14:15:00',
                    'totalTime' => 1932000,
                    'bookingCode' => 'BB-20241001-017',
                    'difficulty' => 'Medium'
                ],
                [
                    'huntName' => 'Emerald Lakes Explorer',
                    'formattedTime' => '00:47:33',
                    'completionDate' => '2024-09-22 11:45:00',
                    'totalTime' => 2853000,
                    'bookingCode' => 'EP-20240922-011',
                    'difficulty' => 'Easy'
                ],
                [
                    'huntName' => 'Surfers Paradise Adventure',
                    'formattedTime' => '00:41:29',
                    'completionDate' => '2024-09-08 16:30:00',
                    'totalTime' => 2489000,
                    'bookingCode' => 'SP-20240908-021',
                    'difficulty' => 'Hard'
                ],
                [
                    'huntName' => 'Springbrook Nature Quest',
                    'formattedTime' => '01:15:22',
                    'completionDate' => '2024-08-25 10:00:00',
                    'totalTime' => 4522000,
                    'bookingCode' => 'SB-20240825-005',
                    'difficulty' => 'Expert'
                ]
            ],
            'stats' => [
                'totalQuests' => 5,
                'totalMedals' => 8,
                'totalTimeSpent' => '3 hours 55 minutes',
                'averageTime' => '00:47:04'
            ],
            'medals' => [
                ['name' => 'Coolangatta Champion', 'icon' => '🌊', 'earned' => '2024-10-07'],
                ['name' => 'Broadbeach Explorer', 'icon' => '🏅', 'earned' => '2024-10-01'],
                ['name' => 'Emerald Champion', 'icon' => '🌟', 'earned' => '2024-09-22'],
                ['name' => 'Surfers Paradise Master', 'icon' => '🏆', 'earned' => '2024-09-08'],
                ['name' => 'Springbrook Adventurer', 'icon' => '🌿', 'earned' => '2024-08-25'],
                ['name' => 'Speed Demon', 'icon' => '⚡', 'earned' => '2024-10-01'],
                ['name' => 'Multi-Hunt Master', 'icon' => '🎯', 'earned' => '2024-09-08'],
                ['name' => 'Gold Coast Legend', 'icon' => '👑', 'earned' => '2024-08-25']
            ]
        ]
    ];
    
    return $profiles[$profile] ?? $profiles['basic'];
}

if ($demoMode) {
    // Demo mode - use generated sample data
    $demoData = generateDemoData($demoProfile);
    $userData = $demoData['user'];
    $allCompletions = $demoData['completions'];
    $completionData = $allCompletions[0]; // Most recent
    $questStats = $demoData['stats'];
    $userMedals = $demoData['medals'];
} else {
    // Check for user session (would be set by register.php in production)
    if (isset($_SESSION['user_id'])) {
        // Production: fetch from database
        $userData = [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'Explorer',
            'email' => $_SESSION['user_email'] ?? ''
        ];
    } else {
        // Development: use basic simulated data
        $userData = [
            'id' => 9999,
            'name' => 'Quest Explorer',
            'email' => 'explorer@puzzlepath.com.au'
        ];
    }
    
    // Try to get completion data from various sources
    if (isset($_GET['completion_data'])) {
        $completionData = json_decode(base64_decode($_GET['completion_data']), true);
    } elseif (isset($_SESSION['completion_data'])) {
        $completionData = $_SESSION['completion_data'];
        unset($_SESSION['completion_data']); // Clean up after use
    }
    
    // Default completion data for development
    if (!$completionData) {
        $completionData = [
            'huntName' => 'Broadbeach Quest',
            'formattedTime' => '00:45:23',
            'completionDate' => date('Y-m-d H:i:s'),
            'totalTime' => 2723000, // milliseconds
            'bookingCode' => 'BB-20241008-001'
        ];
    }
    
    // Calculate user statistics (in production, query database)
    $questStats = [
        'totalQuests' => 1,
        'totalMedals' => 1,
        'totalTimeSpent' => $completionData['formattedTime'],
        'averageTime' => $completionData['formattedTime'],
        'favoriteLocation' => $completionData['huntName'] ?? 'Broadbeach'
    ];
    
    $userMedals = [
        ['name' => $completionData['huntName'] . ' Explorer', 'icon' => '🏅', 'earned' => date('Y-m-d')]
    ];
}

// Function to get user's first name
function getFirstName($fullName) {
    $parts = explode(' ', trim($fullName));
    return $parts[0] ?? 'Explorer';
}

// Function to format completion time nicely
function formatCompletionTime($totalTimeMs) {
    $totalSeconds = floor($totalTimeMs / 1000);
    $hours = floor($totalSeconds / 3600);
    $minutes = floor(($totalSeconds % 3600) / 60);
    $seconds = $totalSeconds % 60;
    
    if ($hours > 0) {
        return sprintf("%d hours, %d minutes", $hours, $minutes);
    } elseif ($minutes > 0) {
        return sprintf("%d minutes, %d seconds", $minutes, $seconds);
    } else {
        return sprintf("%d seconds", $seconds);
    }
}

$firstName = getFirstName($userData['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Dashboard - Puzzle Path</title>
    <link rel="icon" type="image/png" href="puzzlepath-logo-web.png">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #f4f8fb;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: auto;
            padding: 2em;
        }
        .header {
            background: linear-gradient(135deg, #6a82fb 0%, #fc5c7d 100%);
            color: white;
            text-align: center;
            padding: 2em;
            border-radius: 15px;
            margin-bottom: 2em;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        .dashboard-section {
            background: white;
            padding: 2em;
            border-radius: 15px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.1);
            margin-bottom: 2em;
        }
        .achievement-card {
            background: linear-gradient(145deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 15px;
            padding: 2em;
            text-align: center;
            margin: 1em 0;
        }
        .medal-icon {
            font-size: 4em;
            margin-bottom: 0.5em;
        }
        .button {
            background: #4ca6a8;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin: 0.5em;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background: #3a8a8c;
        }
        .button.primary {
            background: linear-gradient(135deg, #6a82fb 0%, #fc5c7d 100%);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5em;
            margin: 2em 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 1.5em;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e9ecef;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #fc5c7d;
        }
        .welcome-message {
            background: #d4edda;
            color: #155724;
            padding: 1.5em;
            border-radius: 10px;
            margin-bottom: 2em;
            text-align: center;
            border: 1px solid #c3e6cb;
        }
        .completion-details {
            background: #e8f4ff;
            border: 2px solid #0066cc;
            border-radius: 10px;
            padding: 1.5em;
            margin: 1em 0;
            text-align: center;
        }
        .time-badge {
            background: #28a745;
            color: white;
            padding: 0.5em 1em;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin: 0.5em;
        }
        .demo-banner {
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            color: white;
            text-align: center;
            padding: 1em;
            margin-bottom: 1em;
            border-radius: 10px;
            font-weight: bold;
            position: relative;
            overflow: hidden;
        }
        .demo-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .medal-collection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1em;
            margin: 1.5em 0;
        }
        .medal-item {
            background: #fff;
            border: 2px solid #ffd700;
            border-radius: 10px;
            padding: 1em;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .medal-item:hover {
            transform: scale(1.05);
        }
        .medal-icon-small {
            font-size: 2em;
            margin-bottom: 0.5em;
        }
        .quest-history {
            margin: 1.5em 0;
        }
        .quest-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1em;
            margin: 0.5em 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .difficulty-badge {
            padding: 0.2em 0.6em;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .difficulty-easy { background: #d4edda; color: #155724; }
        .difficulty-medium { background: #fff3cd; color: #856404; }
        .difficulty-hard { background: #f8d7da; color: #721c24; }
        .difficulty-expert { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($demoMode): ?>
        <div class="demo-banner">
            🎯 DEMO MODE - Viewing sample data for <?php echo ucfirst($demoProfile); ?> User Profile
            <br><small>Try: <a href="?demo=true&profile=basic" style="color: white;">Basic</a> | 
            <a href="?demo=true&profile=intermediate" style="color: white;">Intermediate</a> | 
            <a href="?demo=true&profile=advanced" style="color: white;">Advanced</a> | 
            <a href="dashboard.php" style="color: white;">Exit Demo</a></small>
        </div>
        <?php endif; ?>
        
        <div class="header">
            <h1>🎉 Welcome to Your Dashboard, <?php echo h($firstName); ?>!</h1>
            <p><?php echo $demoMode ? 'Explore what your dashboard could look like!' : 'Congratulations on completing your Puzzle Path quest!'; ?></p>
        </div>
        
        <div class="welcome-message">
            <h3>Registration Successful!</h3>
            <p>Your achievement has been recorded and you're now part of the Puzzle Path community!</p>
        </div>
        
        <div class="completion-details">
            <h3>🏁 Quest Completed: <?php echo h($completionData['huntName']); ?>!</h3>
            <p><strong>Completion Time:</strong> <span class="time-badge"><?php echo h($completionData['formattedTime']); ?></span></p>
            <p><strong>Completed On:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($completionData['completionDate'])); ?></p>
            <?php if (isset($completionData['bookingCode'])): ?>
            <p><small>Booking Code: <?php echo h($completionData['bookingCode']); ?></small></p>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-section">
            <h2>Your Latest Achievement</h2>
            <div class="achievement-card">
                <div class="medal-icon">🏆</div>
                <h3><?php echo h($completionData['huntName']); ?> Completed!</h3>
                <p>You've successfully completed the <?php echo h($completionData['huntName']); ?> in <?php echo formatCompletionTime($completionData['totalTime']); ?>. Well done!</p>
                <?php if (isset($completionData['difficulty'])): ?>
                <p><span class="difficulty-badge difficulty-<?php echo strtolower($completionData['difficulty']); ?>"><?php echo h($completionData['difficulty']); ?></span></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($demoMode && count($userMedals) > 1): ?>
        <div class="dashboard-section">
            <h2>Your Medal Collection</h2>
            <div class="medal-collection">
                <?php foreach ($userMedals as $medal): ?>
                <div class="medal-item">
                    <div class="medal-icon-small"><?php echo $medal['icon']; ?></div>
                    <h4><?php echo h($medal['name']); ?></h4>
                    <small>Earned: <?php echo date('M j, Y', strtotime($medal['earned'])); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="dashboard-section">
            <h2>Your Stats</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $questStats['totalQuests']; ?></div>
                    <div>Quests Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $questStats['totalMedals']; ?></div>
                    <div>Medals Earned</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo h($questStats['averageTime']); ?></div>
                    <div>Average Time</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">⏱️</div>
                    <div>Total: <?php echo h($questStats['totalTimeSpent']); ?></div>
                </div>
            </div>
        </div>
        
        <?php if ($demoMode && count($allCompletions) > 1): ?>
        <div class="dashboard-section">
            <h2>Quest History</h2>
            <div class="quest-history">
                <?php foreach ($allCompletions as $index => $completion): ?>
                <div class="quest-item">
                    <div>
                        <strong><?php echo h($completion['huntName']); ?></strong>
                        <br><small><?php echo date('M j, Y g:i A', strtotime($completion['completionDate'])); ?></small>
                    </div>
                    <div style="text-align: right;">
                        <div class="time-badge" style="background: #17a2b8;"><?php echo h($completion['formattedTime']); ?></div>
                        <br><span class="difficulty-badge difficulty-<?php echo strtolower($completion['difficulty']); ?>"><?php echo h($completion['difficulty']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="dashboard-section">
            <h2>What's Next?</h2>
            <p>Ready for another adventure? Explore more quests and challenges!</p>
            <div style="text-align: center;">
                <a href="index.html" class="button primary">Start New Quest</a>
                <a href="https://puzzlepath.com.au" class="button" target="_blank">Visit Puzzle Path Website</a>
            </div>
        </div>
        
        <div class="dashboard-section" style="background: #fff3cd; border: 1px solid #ffc107;">
            <h2>🚀 What's Coming Next</h2>
            <p>Your dashboard will soon include:</p>
            <ul>
                <li>📊 <strong>Complete Quest History</strong> - View all your past adventures</li>
                <li>🏆 <strong>Medal Collection</strong> - Display all earned achievements</li>
                <li>📈 <strong>Leaderboards</strong> - Compare your times with other explorers</li>
                <li>📱 <strong>Social Sharing</strong> - Share your achievements on social media</li>
                <li>🎯 <strong>Booking Integration</strong> - Book new adventures directly from your dashboard</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Enhanced dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Check for completion data in sessionStorage (from registration flow)
            const completionData = JSON.parse(sessionStorage.getItem('questCompletion') || '{}');
            
            if (completionData.totalTime) {
                console.log('✅ Quest completion data found:', completionData);
                console.log(`🎯 Quest: ${completionData.huntName}`);
                console.log(`⏱️ Time: ${completionData.formattedTime}`);
                console.log(`📅 Date: ${completionData.completionDate}`);
            }
            
            // Clean up stored data (user has seen their achievement)
            sessionStorage.removeItem('questCompletion');
            
            // Add some visual flair
            setTimeout(() => {
                const achievements = document.querySelectorAll('.achievement-card, .stat-card');
                achievements.forEach((el, index) => {
                    setTimeout(() => {
                        el.style.transform = 'scale(1.05)';
                        setTimeout(() => {
                            el.style.transform = 'scale(1)';
                        }, 200);
                    }, index * 100);
                });
            }, 500);
            
            // Success message for the user
            console.log('🎉 Welcome to your Puzzle Path dashboard!');
        });
        
        // Add smooth transitions
        const style = document.createElement('style');
        style.textContent = `
            .achievement-card, .stat-card {
                transition: transform 0.3s ease;
            }
            .achievement-card:hover, .stat-card:hover {
                transform: translateY(-5px);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
