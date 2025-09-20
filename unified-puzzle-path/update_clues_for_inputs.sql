-- Update clues table to support different input types and answer validation
-- Run this on your live database to add the new functionality

-- Add new columns to existing wp2s_pp_clues table
ALTER TABLE wp2s_pp_clues 
ADD COLUMN input_type ENUM('text', 'number', 'photo', 'multiple_choice', 'none') DEFAULT 'none' AFTER hint_text,
ADD COLUMN required_answer VARCHAR(500) NULL AFTER input_type,
ADD COLUMN answer_options JSON NULL AFTER required_answer,
ADD COLUMN is_case_sensitive BOOLEAN DEFAULT FALSE AFTER answer_options,
ADD COLUMN validation_type ENUM('exact', 'contains', 'regex', 'numeric_range', 'photo_upload') DEFAULT 'exact' AFTER is_case_sensitive,
ADD COLUMN min_value DECIMAL(10,2) NULL AFTER validation_type,
ADD COLUMN max_value DECIMAL(10,2) NULL AFTER min_value,
ADD COLUMN photo_required BOOLEAN DEFAULT FALSE AFTER max_value,
ADD COLUMN auto_advance BOOLEAN DEFAULT FALSE AFTER photo_required;

-- Create table to store user answers and progress
CREATE TABLE IF NOT EXISTS wp2s_pp_user_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(50) NOT NULL,
    clue_id INT NOT NULL,
    answer_text VARCHAR(1000) NULL,
    answer_numeric DECIMAL(10,2) NULL,
    photo_filename VARCHAR(255) NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    attempt_count INT DEFAULT 1,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_booking_clue (booking_code, clue_id),
    INDEX idx_submitted_at (submitted_at)
);

-- Create table to store uploaded photos
CREATE TABLE IF NOT EXISTS wp2s_pp_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(50) NOT NULL,
    clue_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    upload_ip VARCHAR(45) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_booking_clue (booking_code, clue_id),
    INDEX idx_uploaded_at (uploaded_at)
);

-- Sample data to show how different input types work
-- Update existing Broadbeach clues with input types (adjust hunt_id as needed)

-- Clue 1: Text input for location name
UPDATE wp2s_pp_clues 
SET input_type = 'text', 
    required_answer = 'Kurrawa Park Amphitheatre',
    validation_type = 'contains',
    is_case_sensitive = FALSE
WHERE hunt_id = 2 AND clue_order = 1;

-- Clue 2: Photo upload for demonstration
UPDATE wp2s_pp_clues 
SET input_type = 'photo', 
    photo_required = TRUE,
    validation_type = 'photo_upload',
    auto_advance = TRUE
WHERE hunt_id = 2 AND clue_order = 2;

-- Clue 3: Number input for counting
UPDATE wp2s_pp_clues 
SET input_type = 'number', 
    required_answer = '5',
    validation_type = 'exact',
    min_value = 1,
    max_value = 20
WHERE hunt_id = 2 AND clue_order = 3;

-- Clue 4: Multiple choice
UPDATE wp2s_pp_clues 
SET input_type = 'multiple_choice', 
    required_answer = 'A',
    answer_options = '{"A": "Red brick", "B": "White stone", "C": "Blue metal", "D": "Yellow wood"}',
    validation_type = 'exact'
WHERE hunt_id = 2 AND clue_order = 4;

-- Clue 5: Text with exact match
UPDATE wp2s_pp_clues 
SET input_type = 'text', 
    required_answer = 'Broadbeach',
    validation_type = 'exact',
    is_case_sensitive = FALSE
WHERE hunt_id = 2 AND clue_order = 5;

-- Clue 6: Photo confirmation (auto-advance)
UPDATE wp2s_pp_clues 
SET input_type = 'photo', 
    photo_required = TRUE,
    validation_type = 'photo_upload',
    auto_advance = TRUE
WHERE hunt_id = 2 AND clue_order = 6;

-- Create indexes for performance
CREATE INDEX idx_clues_input_type ON wp2s_pp_clues (input_type);
CREATE INDEX idx_clues_hunt_order ON wp2s_pp_clues (hunt_id, clue_order);
