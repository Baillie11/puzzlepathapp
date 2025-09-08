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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to Your Dashboard!</h1>
            <p>Congratulations on completing your Puzzle Path quest!</p>
        </div>
        
        <div class="welcome-message">
            <h3>Registration Successful!</h3>
            <p>Your achievement has been recorded and you're now part of the Puzzle Path community!</p>
        </div>
        
        <div class="dashboard-section">
            <h2>Your Achievements</h2>
            <div class="achievement-card">
                <div class="medal-icon">🏆</div>
                <h3>Quest Completed!</h3>
                <p>You've successfully completed a Puzzle Path adventure. Well done!</p>
            </div>
        </div>
        
        <div class="dashboard-section">
            <h2>Your Stats</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">1</div>
                    <div>Quests Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">🏅</div>
                    <div>Medal Earned</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">⭐</div>
                    <div>Achievement Unlocked</div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-section">
            <h2>What's Next?</h2>
            <p>Ready for another adventure? Explore more quests and challenges!</p>
            <div style="text-align: center;">
                <a href="index.html" class="button primary">Start New Quest</a>
                <a href="https://puzzlepath.com.au" class="button" target="_blank">Visit Puzzle Path Website</a>
            </div>
        </div>
        
        <div class="dashboard-section">
            <h2>Development Note</h2>
            <p><strong>This is a test/development environment.</strong> In production, this dashboard would show:</p>
            <ul>
                <li>Real user statistics from the database</li>
                <li>Complete quest history</li>
                <li>Medal collection and rankings</li>
                <li>Social sharing features</li>
                <li>Links to book new adventures</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Display any stored completion data
        document.addEventListener('DOMContentLoaded', function() {
            // Check for completion data in URL parameters or localStorage
            const urlParams = new URLSearchParams(window.location.search);
            const completionData = JSON.parse(localStorage.getItem('questCompletionData') || '{}');
            
            if (completionData.completionTime) {
                const minutes = Math.floor(completionData.completionTime / 60);
                const seconds = completionData.completionTime % 60;
                console.log(`Quest completed in: ${minutes}:${seconds.toString().padStart(2, '0')}`);
            }
            
            // Clean up stored data
            localStorage.removeItem('questCompletionData');
        });
    </script>
</body>
</html>
