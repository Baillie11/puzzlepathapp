-- SQL Update Query to set all null input_type values to 'text'
-- This will update all clues that have NULL or empty input_type values

UPDATE wp2s_pp_clues 
SET input_type = 'text' 
WHERE input_type IS NULL OR input_type = '' OR input_type = 'none';

-- If you want to be more selective and only update actual NULL values:
-- UPDATE wp2s_pp_clues SET input_type = 'text' WHERE input_type IS NULL;

-- To check the results after running the update:
-- SELECT id, title, input_type FROM wp2s_pp_clues ORDER BY id;