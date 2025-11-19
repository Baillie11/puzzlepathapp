# Geofencing Feature Deployment Package

## 📦 Package Contents

### JavaScript Files (assets/js/geo/)
- **geolocation.js** - GPS tracking with accuracy filtering & permissions
- **geofence.js** - Distance calculations & clue unlocking logic
- **notifications.js** - Toast notifications, sounds & vibration
- **directions.js** - Native app deep links (Apple/Google Maps)
- **settings.js** - Settings UI with localStorage persistence
- **geo-manager.js** - Master coordinator integrating all services

### PHP Files
- **config-secure.php** - Updated configuration with geofencing settings
- **verify_location.php** - Server-side location verification endpoint

### Database
- **geofencing_schema.sql** - Database migration script

### Documentation
- **GEOFENCING.md** - Complete feature guide and API reference

---

## 🚀 Deployment Steps

### 1. Upload Files to Live Server

Upload the following files to your live server:

#### Upload to root directory:
- config-secure.php
- verify_location.php

#### Upload to assets/js/geo/ directory:
- Create the directory structure if it doesn't exist
- Upload all 6 JavaScript files from assets/js/geo/

### 2. Run Database Migration

**IMPORTANT:** Run this BEFORE enabling geofencing in the app.

Log into your database (phpMyAdmin, MySQL Workbench, or command line) and execute:

```sql
-- Run the entire geofencing_schema.sql file
```

This will:
- Add latitude, longitude, and geofence_radius columns to wp2s_pp_clues
- Create pp_location_audit table
- Create pp_user_geo_preferences table
- Create pp_geofence_stats table

### 3. Update Your Clues with Coordinates

For each clue you want to geofence, update it with coordinates:

```sql
UPDATE wp2s_pp_clues 
SET 
    latitude = -28.0289,          -- Your clue's latitude
    longitude = 153.4326,          -- Your clue's longitude
    geofence_radius = 30           -- Radius in meters
WHERE hunt_id = 1 AND clue_order = 1;
```

**Finding Coordinates:**
1. Open Google Maps
2. Right-click on the location
3. Click the coordinates to copy them
4. Use format: latitude, longitude

**Recommended Radius:**
- Urban areas: 25-40 meters
- Open spaces: 15-25 meters
- Large landmarks: 40-60 meters

### 4. Integrate with Your Quest Page (index.html)

Add these script tags to your index.html BEFORE the closing </body> tag:

```html
<!-- Load geofencing services -->
<script src=\"assets/js/geo/geolocation.js\"></script>
<script src=\"assets/js/geo/geofence.js\"></script>
<script src=\"assets/js/geo/notifications.js\"></script>
<script src=\"assets/js/geo/directions.js\"></script>
<script src=\"assets/js/geo/settings.js\"></script>
<script src=\"assets/js/geo/geo-manager.js\"></script>
```

Then initialize geofencing in your quest start code:

```javascript
async function startQuest() {
    // ... your existing code ...
    
    // Initialize geofencing
    if (window.geoManager) {
        await window.geoManager.startQuest(huntData, cluesData);
        
        // Set up callbacks
        window.geoManager.setCallbacks({
            onClueUnlocked: (clueId, geofence, result) => {
                console.log('Clue unlocked!', clueId);
                // Show notification or update UI
            },
            onProximityUpdate: (proximities) => {
                // Update distance display
                updateDistanceDisplay(proximities);
            }
        });
    }
}
```

### 5. Configuration Options

The config-secure.php file includes these geofencing settings:

- **GEOFENCING_ENABLED**: true/false - Enable/disable globally
- **GEOFENCE_ENFORCEMENT_MODE**: 'soft' or 'hard'
  - **soft**: Notifies users but allows completion anywhere (default)
  - **hard**: Requires users to be within geofence to submit answers
- **MIN_GEOFENCE_RADIUS**: 25 - Minimum radius in meters
- **MAPS_PROVIDER**: 'leaflet' or 'google' - Map provider preference

---

## ⚠️ Important Notes

1. **config-secure.php**: Make sure your live database credentials are correct in this file

2. **HTTPS Required**: Geolocation API requires HTTPS in production

3. **Privacy**: Location is only tracked during active quests

4. **Soft Mode First**: Test with soft enforcement mode before enabling hard mode

5. **User Permissions**: Users must grant location permission in their browser

---

## 🧪 Testing Checklist

After deployment:

- [ ] Verify all JavaScript files are accessible (check browser console)
- [ ] Confirm database tables were created successfully
- [ ] Test location permission prompt appears
- [ ] Verify clues with coordinates show distance
- [ ] Test geofence entry notification
- [ ] Check navigation buttons work
- [ ] Test settings panel

---

## 📖 Full Documentation

See GEOFENCING.md for:
- Complete API reference
- Advanced integration examples
- User settings guide
- Troubleshooting tips
- Performance optimization

---

## 🆘 Support

If you encounter issues:
1. Check browser console for JavaScript errors
2. Verify database migration ran successfully
3. Confirm HTTPS is enabled
4. Check that coordinates are valid (latitude: -90 to 90, longitude: -180 to 180)

---

**Deployment Date:** 2025-11-19 09:06
**Branch:** GeoFencing
