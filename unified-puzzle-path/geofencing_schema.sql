-- Geofencing Database Schema for Puzzle Path
-- Location audit and tracking tables

-- Location audit table for tracking user positions during quests
CREATE TABLE IF NOT EXISTS pp_location_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(50) NOT NULL,
    hunt_id INT NOT NULL,
    clue_id INT NOT NULL,
    user_lat DECIMAL(10,7) NOT NULL,
    user_lng DECIMAL(10,7) NOT NULL,
    user_accuracy_m FLOAT DEFAULT NULL,
    distance_m FLOAT DEFAULT NULL,
    within_geofence BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_booking (booking_code),
    INDEX idx_hunt_clue (hunt_id, clue_id),
    INDEX idx_timestamp (created_at)
);

-- Add geofencing columns to existing clues table if they don't exist
-- Note: This uses IF NOT EXISTS style checks to be safe

-- Check if latitude column exists, if not add it
SET @query = (
    SELECT CASE 
        WHEN COUNT(*) = 0 THEN 
            'ALTER TABLE wp2s_pp_clues ADD COLUMN latitude DECIMAL(10,7) NULL AFTER answer;'
        ELSE 
            'SELECT "latitude column already exists" as result;'
    END
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'wp2s_pp_clues' 
    AND COLUMN_NAME = 'latitude'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if longitude column exists, if not add it
SET @query = (
    SELECT CASE 
        WHEN COUNT(*) = 0 THEN 
            'ALTER TABLE wp2s_pp_clues ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude;'
        ELSE 
            'SELECT "longitude column already exists" as result;'
    END
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'wp2s_pp_clues' 
    AND COLUMN_NAME = 'longitude'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if geofence_radius column exists, if not add it
SET @query = (
    SELECT CASE 
        WHEN COUNT(*) = 0 THEN 
            'ALTER TABLE wp2s_pp_clues ADD COLUMN geofence_radius INT DEFAULT 50 AFTER longitude;'
        ELSE 
            'SELECT "geofence_radius column already exists" as result;'
    END
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'wp2s_pp_clues' 
    AND COLUMN_NAME = 'geofence_radius'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Sample coordinates for existing clues (Gold Coast area)
-- These are example coordinates - update with actual clue locations

-- Example: Broadbeach clues coordinates
-- UPDATE wp2s_pp_clues SET 
--     latitude = -28.0289, longitude = 153.4326, geofence_radius = 30
-- WHERE hunt_id = 1 AND clue_order = 1; -- Broadbeach Oasis Shopping Centre

-- UPDATE wp2s_pp_clues SET 
--     latitude = -28.0276, longitude = 153.4336, geofence_radius = 25  
-- WHERE hunt_id = 1 AND clue_order = 2; -- Broadbeach Parks area

-- Example: Emerald Lakes clues coordinates  
-- UPDATE wp2s_pp_clues SET
--     latitude = -28.0642, longitude = 153.3968, geofence_radius = 40
-- WHERE hunt_id = 2 AND clue_order = 1; -- Emerald Lakes playground

-- Add index for geofencing queries
CREATE INDEX IF NOT EXISTS idx_clue_coords ON wp2s_pp_clues (hunt_id, latitude, longitude);

-- User preferences table for geofencing settings (optional)
CREATE TABLE IF NOT EXISTS pp_user_geo_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(128) DEFAULT NULL,
    location_enabled BOOLEAN DEFAULT TRUE,
    notifications_enabled BOOLEAN DEFAULT TRUE,
    sound_enabled BOOLEAN DEFAULT TRUE,
    vibration_enabled BOOLEAN DEFAULT TRUE,
    accuracy_threshold INT DEFAULT 75,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user (user_id),
    UNIQUE KEY unique_session (session_id)
);

-- Geofencing statistics table (optional - for analytics)
CREATE TABLE IF NOT EXISTS pp_geofence_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hunt_id INT NOT NULL,
    clue_id INT NOT NULL,
    total_entries INT DEFAULT 0,
    avg_distance_m FLOAT DEFAULT NULL,
    min_distance_m FLOAT DEFAULT NULL,
    max_distance_m FLOAT DEFAULT NULL,
    avg_accuracy_m FLOAT DEFAULT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_hunt_clue (hunt_id, clue_id)
);

-- Sample data inserts (commented out - uncomment and customize as needed)
/*
-- Example coordinates for popular Gold Coast locations:

-- SkyPoint Observation Deck
INSERT IGNORE INTO wp2s_pp_clues (hunt_id, clue_order, title, clue_text, task_description, latitude, longitude, geofence_radius) 
VALUES (1, 1, 'Sky High Start', 'Begin where the view is supreme...', 'Take a photo at the observation deck', -28.0025, 153.4302, 50);

-- Broadbeach Beach
INSERT IGNORE INTO wp2s_pp_clues (hunt_id, clue_order, title, clue_text, task_description, latitude, longitude, geofence_radius) 
VALUES (1, 2, 'Golden Sands', 'Where the waves meet the shore...', 'Find the surf lifesaver flags', -28.0276, 153.4351, 40);

-- Currumbin Wildlife Sanctuary  
INSERT IGNORE INTO wp2s_pp_clues (hunt_id, clue_order, title, clue_text, task_description, latitude, longitude, geofence_radius)
VALUES (2, 1, 'Wildlife Haven', 'Where the koalas call home...', 'Spot a native Australian animal', -28.1441, 153.4842, 60);
*/

-- Create a view for easy geofencing queries
CREATE OR REPLACE VIEW vw_geofenced_clues AS
SELECT 
    c.id,
    c.hunt_id,
    c.clue_order,
    c.title,
    c.clue_text,
    c.task_description,
    c.latitude,
    c.longitude,
    c.geofence_radius,
    h.hunt_name,
    h.location as hunt_location,
    CASE 
        WHEN c.latitude IS NOT NULL AND c.longitude IS NOT NULL THEN TRUE 
        ELSE FALSE 
    END as is_geofenced
FROM wp2s_pp_clues c
LEFT JOIN wp2s_pp_events h ON c.hunt_id = h.id
WHERE c.is_active = 1
ORDER BY c.hunt_id, c.clue_order;

-- Grant appropriate permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT ON pp_location_audit TO 'puzzle_path_user'@'%';
-- GRANT SELECT ON vw_geofenced_clues TO 'puzzle_path_user'@'%';