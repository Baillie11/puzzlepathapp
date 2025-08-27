-- Unified Puzzle Path Database Schema
-- This extends your existing database structure for scalability

-- Table to store different scavenger hunts
CREATE TABLE IF NOT EXISTS pp_hunts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hunt_code VARCHAR(20) UNIQUE NOT NULL,  -- e.g., 'BB' for Broadbeach, 'EP' for Emerald Park
    hunt_name VARCHAR(100) NOT NULL,        -- e.g., 'Broadbeach Quest'
    location VARCHAR(100) NOT NULL,         -- e.g., 'Broadbeach', 'Emerald Park'
    description TEXT,
    instructions TEXT,
    total_clues INT DEFAULT 0,
    estimated_duration INT DEFAULT 60,     -- minutes
    difficulty_level ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Medium',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table to store clues for each hunt
CREATE TABLE IF NOT EXISTS pp_clues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hunt_id INT NOT NULL,
    clue_order INT NOT NULL,               -- 1, 2, 3, etc.
    title VARCHAR(200) NOT NULL,
    clue_text TEXT NOT NULL,
    task_description TEXT NOT NULL,
    hint_text TEXT,
    answer_key VARCHAR(500),               -- For validation if needed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hunt_id) REFERENCES pp_hunts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hunt_clue (hunt_id, clue_order)
);

-- Enhanced booking table to link with hunts
-- Note: This modifies your existing wp2s_pp_bookings table structure
ALTER TABLE wp2s_pp_bookings 
ADD COLUMN hunt_id INT AFTER booking_code,
ADD COLUMN participant_names TEXT AFTER hunt_id,
ADD COLUMN participant_count INT DEFAULT 1 AFTER participant_names,
ADD COLUMN booking_date DATE AFTER participant_count,
ADD COLUMN special_requirements TEXT AFTER booking_date,
ADD FOREIGN KEY (hunt_id) REFERENCES pp_hunts(id);

-- Table to track quest completions
CREATE TABLE IF NOT EXISTS pp_quest_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hunt_id INT NOT NULL,
    booking_code VARCHAR(50) NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    total_time_ms BIGINT NULL,             -- completion time in milliseconds
    completion_date DATE NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hunt_id) REFERENCES pp_hunts(id) ON DELETE CASCADE,
    INDEX idx_completion_time (hunt_id, total_time_ms),
    INDEX idx_completion_date (completion_date)
);

-- Table to track individual clue completions (for progress tracking)
CREATE TABLE IF NOT EXISTS pp_clue_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    completion_id INT NOT NULL,
    clue_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (completion_id) REFERENCES pp_quest_completions(id) ON DELETE CASCADE,
    FOREIGN KEY (clue_id) REFERENCES pp_clues(id) ON DELETE CASCADE,
    UNIQUE KEY unique_completion_clue (completion_id, clue_id)
);

-- Enhanced medals system
CREATE TABLE IF NOT EXISTS pp_medals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medal_code VARCHAR(20) UNIQUE NOT NULL,
    medal_name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    hunt_id INT NULL,                      -- NULL for general medals, specific hunt_id for hunt-specific medals
    requirements JSON,                     -- Flexible requirements (completion time, multiple hunts, etc.)
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hunt_id) REFERENCES pp_hunts(id) ON DELETE SET NULL
);

-- User medals earned
CREATE TABLE IF NOT EXISTS pp_user_medals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    medal_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_id INT NULL,               -- Link to the completion that earned this medal
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (medal_id) REFERENCES pp_medals(id) ON DELETE CASCADE,
    FOREIGN KEY (completion_id) REFERENCES pp_quest_completions(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_medal (user_id, medal_id)
);

-- Sample data for existing hunts
INSERT INTO pp_hunts (hunt_code, hunt_name, location, description, instructions, total_clues, estimated_duration, difficulty_level) VALUES
('BB', 'Broadbeach Quest', 'Broadbeach', 'Explore the vibrant Broadbeach area with this exciting scavenger hunt!', 'Start at the All Abilities Playground and follow the path clockwise around the lake.', 6, 90, 'Medium'),
('EP', 'Emerald Lakes Explorer\'s Quest', 'Emerald Park', 'Discover the beauty of Emerald Lakes in this nature-focused adventure!', 'Start at the All Abilities Playground and follow the path clockwise around the lake.', 8, 120, 'Easy');

-- Sample medals
INSERT INTO pp_medals (medal_code, medal_name, description, image_url, hunt_id, requirements) VALUES
('broadbeach-quest', 'Broadbeach Explorer', 'Completed the Broadbeach Quest', 'Broadbeach Medal.png', 1, '{"type": "completion", "hunt_id": 1}'),
('emerald-quest', 'Emerald Lakes Champion', 'Completed the Emerald Lakes Quest', NULL, 2, '{"type": "completion", "hunt_id": 2}'),
('speed-demon', 'Speed Demon', 'Complete any quest in under 60 minutes', NULL, NULL, '{"type": "time_under", "minutes": 60}'),
('multi-hunter', 'Multi-Hunt Master', 'Complete 3 different quests', NULL, NULL, '{"type": "hunt_count", "count": 3}');
