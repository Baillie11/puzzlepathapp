# Unified Puzzle Path Quest System

A comprehensive, scalable scavenger hunt application that supports multiple quests with dynamic content management and user tracking.

## 🎯 Features

### Core Functionality
- **Multi-Quest Support**: Single app supports unlimited scavenger hunts
- **Dynamic Quest Loading**: Hunts and clues loaded from database based on booking number
- **Booking Verification**: Secure payment verification before quest access
- **Real-time Progress Tracking**: Visual progress bar and timer
- **Mobile-First Design**: Fully responsive with excellent mobile experience

### Enhanced Features
- **User Registration & Login**: Account system with completion tracking
- **Medal System**: Automatic medal awarding with flexible requirements
- **Leaderboards**: Speed and completion leaderboards per hunt
- **Quest Analytics**: Detailed completion tracking and statistics
- **Admin-Friendly**: Database-driven content management

### Improvements Over Original Apps
1. **Progress Bar**: Visual indicator showing quest completion percentage
2. **Hunt Information Display**: Shows quest details, estimated time, and clue count before starting
3. **Enhanced Mobile UI**: Better button layouts and touch-friendly interface
4. **Error Handling**: Comprehensive error logging and user feedback
5. **Security**: CSRF protection, input sanitization, and secure session management
6. **Analytics**: Detailed tracking of quest starts, clue completions, and finish times

## 🗂️ File Structure

```
unified-puzzle-path/
├── index.html              # Main quest interface
├── config.php              # Database configuration & helper functions
├── verify_booking.php      # Booking verification API
├── get_clues.php          # Dynamic clue loading API
├── track_quest.php        # Quest completion tracking
├── register.html          # User registration page
├── register.php           # Registration processing
├── login.html             # User login page (to be created)
├── login.php              # Login processing (to be created)
├── dashboard.php          # User dashboard (to be created)
├── database_schema.sql    # Database table definitions
├── populate_clues.sql     # Initial clue data
├── puzzlepath-logo-web.png # App logo
├── Broadbeach Medal.png   # Medal image
└── README.md              # This file
```

## 🚀 Setup Instructions

### 1. Database Setup
```sql
-- Run these SQL files in order:
-- 1. First, run the schema file to create tables
SOURCE database_schema.sql;

-- 2. Then populate with initial quest data
SOURCE populate_clues.sql;
```

### 2. Configuration
Update `config.php` with your database credentials:
```php
// Main database (WordPress with bookings)
define('DB_HOST', 'your_host');
define('DB_NAME', 'your_wp_database');
define('DB_USER', 'your_wp_user');
define('DB_PASS', 'your_wp_password');

// User database (quest user accounts)
define('USER_DB_HOST', 'your_host');
define('USER_DB_NAME', 'your_user_database');
define('USER_DB_USER', 'your_user_db_user');
define('USER_DB_PASS', 'your_user_db_password');
```

### 3. File Permissions
Ensure PHP has write permissions for:
- Error logging: `error.log`
- Session data (default PHP session directory)

### 4. Dependencies
- PHP 7.4+
- MySQL 5.7+
- Web server (Apache/Nginx)

## 📊 Database Schema

### Core Tables
- `pp_hunts`: Store different scavenger hunts
- `pp_clues`: Hunt-specific clues with ordering
- `pp_medals`: Flexible medal system with JSON requirements
- `pp_quest_completions`: User completion tracking
- `pp_user_medals`: Medal awards tracking

### Integration
- Extends existing `wp2s_pp_bookings` table
- Links to existing `users` table for registration
- Maintains compatibility with current booking system

## 🎮 How It Works

### Booking Flow
1. User enters booking number
2. System extracts hunt code (BB, EP, etc.)
3. Validates booking and payment status
4. Loads appropriate hunt and clues
5. Presents hunt information before starting

### Quest Flow
1. User starts quest (timer begins)
2. Clues revealed one by one
3. Progress tracked in real-time
4. Completion triggers medal awards
5. Redirect to registration for leaderboard entry

### Medal System
Medals are awarded based on flexible JSON requirements:
```json
{"type": "completion", "hunt_id": 1}
{"type": "time_under", "minutes": 60}
{"type": "hunt_count", "count": 3}
```

## 🔧 Adding New Hunts

### 1. Create Hunt Entry
```sql
INSERT INTO pp_hunts (hunt_code, hunt_name, location, description, instructions, total_clues, estimated_duration, difficulty_level) 
VALUES ('GC', 'Gold Coast Adventure', 'Surfers Paradise', 'Explore the heart of the Gold Coast!', 'Start at SkyPoint and work your way down.', 8, 120, 'Medium');
```

### 2. Add Clues
```sql
INSERT INTO pp_clues (hunt_id, clue_order, title, clue_text, task_description, hint_text, answer_key) 
VALUES (3, 1, 'Sky High Start', 'Begin where the view is supreme...', 'Take a photo at the highest observation point', 'It\'s in a famous tower', 'SkyPoint');
```

### 3. Create Medal
```sql
INSERT INTO pp_medals (medal_code, medal_name, description, hunt_id, requirements) 
VALUES ('gc-explorer', 'Gold Coast Explorer', 'Completed the Gold Coast Adventure', 3, '{"type": "completion", "hunt_id": 3}');
```

### 4. Update Booking System
Ensure booking numbers for the new hunt follow the pattern: `GC-YYYYMMDD-XXXX`

## 🏆 Medal Types

### Hunt-Specific Medals
- Awarded automatically upon quest completion
- Linked to specific hunt_id

### Achievement Medals
- **Speed Demon**: Complete any quest under X minutes
- **Multi-Hunter**: Complete multiple different quests
- **Perfect Score**: Complete quest with no hints used
- **Weekend Warrior**: Complete quests on weekends

### Custom Requirements
Medal requirements use JSON for flexibility:
```json
{
  "type": "time_under",
  "minutes": 45,
  "hunt_ids": [1, 2]  // Optional: specific hunts only
}

{
  "type": "completion_count",
  "count": 5,
  "timeframe": "30days"  // Optional: within timeframe
}

{
  "type": "streak",
  "consecutive_days": 3
}
```

## 🔍 Analytics & Reporting

### Quest Performance
- Average completion times per hunt
- Most challenging clues (where users request hints)
- Completion rates and abandonment points

### User Engagement  
- Repeat participants
- Medal acquisition rates
- Popular hunt preferences

### Business Insights
- Peak booking times
- Geographic distribution of participants
- Revenue per quest type

## 🛠️ Maintenance

### Regular Tasks
1. **Database Cleanup**: Archive old tracking data
2. **Medal Review**: Update requirements based on completion data
3. **Content Updates**: Refresh clues and add seasonal variants
4. **Performance Monitoring**: Check API response times

### Troubleshooting
- Check `error.log` for PHP errors
- Verify database connections in `config.php`
- Test booking verification with sample booking numbers
- Ensure all file permissions are correct

## 🚀 Future Enhancements

### Phase 2 Features
- **Photo Challenges**: Upload photos for clue verification
- **Team Quests**: Multi-participant hunts with shared progress
- **Augmented Reality**: QR codes and AR elements
- **Social Features**: Share completions and challenge friends

### Admin Dashboard
- **Quest Builder**: Visual interface for creating hunts
- **Analytics Dashboard**: Real-time completion statistics  
- **User Management**: View participant profiles and achievements
- **Content Management**: Update clues and medals without SQL

### Advanced Features
- **Dynamic Difficulty**: Adjust clues based on participant skill
- **Weather Integration**: Weather-appropriate clue variations
- **Location Verification**: GPS-based clue unlocking
- **Multilingual Support**: Hunts in multiple languages

## 📞 Support

For technical issues or feature requests:
1. Check the error logs first
2. Verify database connectivity
3. Test with known good booking numbers
4. Review this documentation for configuration steps

## 📄 License

This unified system maintains compatibility with your existing Puzzle Path setup while providing a scalable foundation for unlimited future quests.
