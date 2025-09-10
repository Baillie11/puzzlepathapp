# How to Add New Hunts to Puzzle Path

The app now **automatically recognizes new hunts** when you add them to the database! No code changes needed.

## ✅ **Automatic Hunt Recognition**

When you add a new hunt, the app will automatically:
1. Extract the booking code from booking numbers (prefix before first `-`)
2. Match it against hunt codes in your database
3. Load the correct quest details and clues

## 🚀 **Steps to Add a New Hunt**

### 1. Add Hunt to Database
Add a new row to `wp2s_pp_events`:

```sql
INSERT INTO wp2s_pp_events (
    title, 
    hunt_code, 
    hunt_name, 
    location, 
    description
) VALUES (
    'Byron Bay Beach Walk',        -- Display title
    'BYRON',                      -- Hunt code (matches booking prefix)
    'byron_bay_walk',            -- Internal name
    'Byron Bay, NSW',            -- Location
    'Explore the iconic Byron Bay coastline and lighthouse.'
);
```

### 2. Add Clues for the Hunt
Add clues to `wp2s_pp_clues`:

```sql
-- Get the hunt_id from step 1 (e.g., if it was ID 22)
INSERT INTO wp2s_pp_clues (
    hunt_id, 
    clue_order, 
    title, 
    clue_text, 
    task_description, 
    hint_text, 
    answer, 
    latitude, 
    longitude, 
    geofence_radius, 
    is_active
) VALUES 
(22, 1, 'Byron Bay Lighthouse', 'Find Australia\'s easternmost lighthouse...', 'Take a photo at the lighthouse', 'It\'s at Cape Byron', 'photo_submission', -28.6439, 153.6392, 50, 1),
(22, 2, 'Beach Walk', 'Count the beach access paths...', 'Enter the number of paths', 'Look for wooden walkways', '3', -28.6426, 153.6375, 40, 1);
```

### 3. Create Booking Codes
Your booking system should generate codes with the hunt code as prefix:

- **Format**: `HUNTCODE-YYYYMMDD-XXXX`
- **Examples**:
  - `BYRON-20241210-1234` → Finds Byron Bay hunt
  - `NOOSA-20241210-5678` → Finds Noosa hunt
  - `GOLD123-20241210-9012` → Finds hunt with code starting with "GOLD"

## 📋 **Hunt Code Matching Logic**

The app uses intelligent matching:

1. **Exact Match**: `BYRON` booking → `BYRON` hunt code
2. **Prefix Match**: `GOLD123` booking → `GOLD%` hunt code  
3. **Smart Lookup**: Searches hunt_code, hunt_name, and title fields

## 🎯 **Examples**

### Add Noosa Hunt
```sql
-- 1. Add hunt
INSERT INTO wp2s_pp_events (title, hunt_code, hunt_name, location, description) 
VALUES ('Noosa National Park Trail', 'NOOSA', 'noosa_trail', 'Noosa, QLD', 'Coastal walking trail with koala spotting.');

-- 2. Get hunt ID (assume it's 23)
-- 3. Add clues
INSERT INTO wp2s_pp_clues (hunt_id, clue_order, title, clue_text, task_description, answer, latitude, longitude, geofence_radius, is_active) VALUES 
(23, 1, 'Koala Spotting', 'Find the koala viewing platform', 'Take a photo of koalas', 'photo_submission', -26.3812, 153.0889, 60, 1);
```

**Booking codes that work:**
- `NOOSA-20241210-1234` ✅
- `NOOSA123-20241210-5678` ✅  
- Any code starting with "NOOSA"

### Add Multi-Location Hunt
```sql
INSERT INTO wp2s_pp_events (title, hunt_code, hunt_name, location, description) 
VALUES ('Gold Coast Food Trail', 'FOOD', 'food_trail', 'Multiple Locations', 'Culinary adventure across the Gold Coast.');
```

**Booking codes that work:**
- `FOOD-20241210-1234` ✅
- `FOODIE-20241210-5678` ✅

## 🔍 **Testing New Hunts**

Use the test script to verify your new hunt works:

```bash
php test_booking_codes.php
```

Or test through the web interface with a booking code like `NEWHUNT-20241210-1234`

## 📝 **Best Practices**

1. **Hunt Codes**: Use 3-6 character codes that are easy to remember
2. **Unique Prefixes**: Avoid codes that start with same letters (e.g., don't use both "GOLD" and "GOLDCOAST")  
3. **Test First**: Always test with a sample booking code before going live
4. **Clue Count**: Add at least 5-8 clues per hunt for good gameplay
5. **Coordinates**: Double-check latitude/longitude for accurate geofencing

## ⚡ **No Code Changes Needed**

The system is now **completely database-driven**. Just add hunts to your database and they'll work immediately with appropriately formatted booking codes!

## 🚨 **Troubleshooting**

If a new hunt isn't working:

1. Check hunt_code field is not empty
2. Verify booking code format: `CODE-YYYYMMDD-XXXX`  
3. Ensure hunt has active clues (`is_active = 1`)
4. Check error logs for database issues
5. Run test script to debug matching
