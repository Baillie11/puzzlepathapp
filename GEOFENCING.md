# 🗺️ Geofencing Feature Guide

## Overview

The Puzzle Path geofencing system provides location-based clue unlocking, navigation, and proximity tracking for interactive treasure hunts. Users receive real-time notifications when approaching clues and can get directions to each location.

**Version**: 3.1.0  
**Default Mode**: Soft enforcement (warnings, not blocking)  
**Privacy-First**: Location only tracked during active quests

---

## 🚀 Quick Start

### For Developers

**1. Include the geofencing scripts in your quest page:**

```html
<!-- Load geofencing services -->
<script src="assets/js/geo/geolocation.js"></script>
<script src="assets/js/geo/geofence.js"></script>
<script src="assets/js/geo/notifications.js"></script>
<script src="assets/js/geo/directions.js"></script>
<script src="assets/js/geo/settings.js"></script>
<script src="assets/js/geo/geo-manager.js"></script>
```

**2. Initialize geofencing when quest starts:**

```javascript
// After loading clues
await window.geoManager.startQuest(huntData, cluesData);

// Set up callbacks for your quest UI
window.geoManager.setCallbacks({
    onClueUnlocked: (clueId, geofence, result) => {
        // Handle clue unlock in your UI
        console.log(`Clue ${clueId} unlocked!`, result);
    },
    onProximityUpdate: (proximities) => {
        // Update UI with distance info
        updateDistanceDisplay(proximities);
    }
});
```

**3. Add navigation buttons to clues:**

```javascript
function addNavigationButton(clueId) {
    const btn = document.createElement('button');
    btn.textContent = '🧭 Navigate to Clue';
    btn.onclick = () => window.geoManager.navigateToClue(clueId);
    return btn;
}
```

---

## 📋 Features

### Core Capabilities

✅ **GPS Tracking**: Real-time location monitoring with accuracy filtering  
✅ **Geofence Detection**: Automatic clue unlocking when user enters radius  
✅ **Distance Calculation**: Haversine formula for precise measurements  
✅ **Notifications**: Toast, sound, and vibration alerts  
✅ **Navigation**: Native app deep links (Apple/Google Maps)  
✅ **Settings**: User-controlled preferences with localStorage  
✅ **Privacy**: Location only during active quests

### User Experience

- **Smooth Position Tracking**: Moving average filter reduces GPS noise
- **Accuracy Warnings**: Alerts when GPS accuracy is poor (>75m)
- **Battery Optimization**: Throttled checks, auto-pause when inactive
- **Offline Support**: Distance calculations work without network
- **Graceful Degradation**: Works with non-geofenced clues

---

## 🗄️ Database Setup

### 1. Run the schema migration

```bash
mysql -u your_user -p your_database < geofencing_schema.sql
```

This creates:
- `pp_location_audit` - tracks user positions
- `pp_user_geo_preferences` - stores user settings
- `pp_geofence_stats` - analytics data
- Adds `latitude`, `longitude`, `geofence_radius` to `wp2s_pp_clues`

### 2. Add coordinates to your clues

```sql
-- Example: Update Broadbeach clue coordinates
UPDATE wp2s_pp_clues 
SET 
    latitude = -28.0289, 
    longitude = 153.4326, 
    geofence_radius = 30
WHERE hunt_id = 1 AND clue_order = 1;
```

**Finding Coordinates:**
1. Open Google Maps
2. Right-click on the clue location
3. Click coordinates to copy
4. Use format: `latitude, longitude` (e.g., `-28.0289, 153.4326`)

**Recommended Geofence Radius:**
- **Urban areas**: 25-40 meters (accounts for GPS drift, buildings)
- **Open spaces**: 15-25 meters (better GPS accuracy)
- **Large landmarks**: 40-60 meters (flexible arrival area)

---

## ⚙️ Configuration

### PHP Configuration (`config-secure.php`)

```php
// Geofencing Settings
define('GEOFENCING_ENABLED', true); // Enable/disable globally
define('GEOFENCE_ENFORCEMENT_MODE', 'soft'); // 'soft' or 'hard'
define('MAPS_PROVIDER', 'leaflet'); // 'leaflet' or 'google'
define('GOOGLE_MAPS_API_KEY', ''); // Optional Google Maps API key
define('MIN_GEOFENCE_RADIUS', 25); // Minimum radius in meters
```

### Enforcement Modes

**Soft Mode** (Default):
- Notifies user when entering/leaving geofences
- Allows clue completion anywhere
- Best for exploration-based hunts

**Hard Mode**:
- Requires user to be within geofence
- Blocks clue submission until verified
- Calls `verify_location.php` for server-side check
- Best for location-specific challenges

### Environment Variables

```bash
# Optional - override defaults
export PP_GEOFENCING_ENABLED=true
export PP_GEOFENCE_MODE=soft
export PP_MAPS_PROVIDER=leaflet
export PP_MIN_GEOFENCE_RADIUS=25
```

---

## 🎮 User Settings

Users can customize their experience via the settings panel:

### Location Services
- **Enable/Disable Tracking**: Full control over GPS usage
- **Accuracy Threshold**: Minimum acceptable GPS precision

### Navigation
- **Travel Mode**: Walking (5 km/h) or Driving (40 km/h)
- **Native Apps**: Prefer device map apps over web
- **Provider**: Apple Maps, Google Maps, or auto-detect

### Notifications
- **Enable/Disable**: Master toggle for all alerts
- **Sound Effects**: Synthesized tones for events
- **Vibration**: Haptic feedback on geofence entry
- **Volume**: Adjustable sound level (0-100%)

### Display
- **Show Distance**: Display meters to each clue
- **Show Bearing**: Compass direction indicator
- **Show ETA**: Estimated walking/driving time

**Access settings:**
```javascript
// Show settings modal
window.geoManager.showSettings();

// Programmatic access
window.geoManager.setTravelMode('walking');
window.geoManager.setGeolocationEnabled(true);
```

---

## 🔌 Integration Examples

### Example 1: Basic Quest Integration

```javascript
// When quest is verified and ready to start
async function startQuestWithGeofencing() {
    const success = await window.geoManager.startQuest(huntData, cluesData);
    
    if (success) {
        console.log('Geofencing active!');
    } else {
        console.log('Geofencing unavailable - quest works without it');
    }
}

// When quest completes
function finishQuest() {
    window.geoManager.stopQuest();
}
```

### Example 2: Display Distance to Clues

```javascript
// Update clue cards with proximity info
function updateClueProximity(clueId, clueElement) {
    const proximity = window.geoManager.getClueProximity(clueId);
    
    if (proximity && proximity.proximity) {
        const distanceEl = clueElement.querySelector('.clue-distance');
        distanceEl.textContent = window.geoManager.formatDistance(proximity.proximity.distance);
        
        // Color code based on proximity
        if (proximity.proximity.isInside) {
            distanceEl.className = 'clue-distance nearby';
        } else if (proximity.proximity.distance < proximity.proximity.effectiveRadius * 2) {
            distanceEl.className = 'clue-distance close';
        } else {
            distanceEl.className = 'clue-distance far';
        }
    }
}
```

### Example 3: Custom Unlock Logic

```javascript
// Set up custom unlock handler
window.geoManager.setCallbacks({
    onClueUnlocked: (clueId, geofence, result) => {
        // Flash the clue card
        const clueCard = document.getElementById(`clue-${clueId}`);
        clueCard.classList.add('unlocked', 'pulse-animation');
        
        // Auto-scroll to the clue
        clueCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Enable the clue content
        enableClueInteraction(clueId);
    }
});
```

### Example 4: Server-Side Verification (Hard Mode)

```javascript
// Before allowing clue submission in hard mode
async function submitClueAnswer(clueId, answer) {
    const verification = await window.geoManager.verifyLocation(huntId, clueId);
    
    if (!verification.verified) {
        alert(`You must be within ${verification.required_radius_m}m of the clue location. 
               You are currently ${Math.round(verification.distance_m)}m away.`);
        return false;
    }
    
    // Proceed with answer submission
    return submitAnswer(clueId, answer);
}
```

---

## 📱 Mobile Platform Support

### iOS (Safari)
- ✅ Geolocation API fully supported
- ✅ Apple Maps deep linking
- ✅ Vibration API supported
- ⚠️ Requires HTTPS for location access
- ⚠️ Web Audio API requires user interaction

### Android (Chrome)
- ✅ Geolocation API fully supported
- ✅ Google Maps app linking
- ✅ Vibration API supported
- ✅ Web Audio API fully supported
- ✅ Background location (while app open)

### Desktop
- ✅ Geolocation via WiFi/IP (lower accuracy)
- ✅ Opens Google Maps in new tab
- ⚠️ Accuracy typically 50-200m
- 💡 Best for development/testing with Chrome DevTools

---

## 🧪 Testing

### Browser Developer Tools

**Chrome DevTools Sensors:**
1. Open DevTools (F12)
2. Go to "More tools" → "Sensors"
3. Select "Location" dropdown
4. Choose preset or enter custom coordinates

**Test Locations (Gold Coast):**
```javascript
// Broadbeach - Oasis Shopping Centre
// Lat: -28.0289, Lng: 153.4326

// Surfers Paradise Beach
// Lat: -28.0023, Lng: 153.4302

// Emerald Lakes Playground
// Lat: -28.0642, Lng: 153.3968
```

### Testing Checklist

- [ ] **Permission Flow**: Deny, then accept, then try quest
- [ ] **Accuracy Variations**: Test with 10m, 50m, 100m accuracy
- [ ] **Movement**: Simulate walking between clues
- [ ] **Entry/Exit**: Cross geofence boundaries multiple times
- [ ] **Navigation**: Test each platform's deep links
- [ ] **Notifications**: Verify sound, vibration, toast display
- [ ] **Settings**: Toggle each option and confirm behavior
- [ ] **Offline Mode**: Disable network, verify distance calc works
- [ ] **Quest Flow**: Start, pause, resume, complete with geofencing

### Manual Field Testing

**Recommended Approach:**
1. Use actual devices (iOS/Android)
2. Test at real clue locations
3. Walk approach paths from different angles
4. Verify unlock happens at expected radius
5. Check notification timing and content
6. Test navigation to next clue
7. Monitor battery drain over full quest

---

## 🔒 Privacy & Security

### Privacy Protections

- **No Background Tracking**: Location only during active quest session
- **User Consent**: Settings require explicit enable
- **Minimal Storage**: Only completion locations stored
- **No Third-Party Sharing**: Location data stays on your server
- **Clear Disclosure**: Privacy notice before first location request

### Security Measures

- **Server Verification**: `verify_location.php` prevents client-side spoofing
- **HTTPS Required**: iOS Safari enforces secure connections
- **Input Validation**: All coordinates validated before processing
- **Rate Limiting**: Location checks throttled to prevent abuse
- **Secure Logging**: Sensitive data redacted from error logs

### GDPR Compliance

- User can disable geofencing at any time
- Location data deleted after quest completion (optional)
- Clear privacy policy explaining data usage
- Export/delete user data upon request

---

## 🐛 Troubleshooting

### "Location Access Denied"

**Cause**: User blocked location permissions  
**Solution**:
1. iOS: Settings → Safari → Location → Allow
2. Android: Settings → Chrome → Permissions → Location → Allow
3. Desktop: Click lock icon in address bar → Location → Allow

### "GPS Accuracy Too Low"

**Cause**: Poor satellite visibility or device GPS issues  
**Solution**:
- Move to an open area away from tall buildings
- Wait 30-60 seconds for GPS to acquire satellites
- Restart device GPS (airplane mode toggle)
- Check device location settings (High Accuracy mode)

### "Clue Won't Unlock"

**Cause**: GPS drift or geofence radius too small  
**Solution**:
- Check actual distance in console logs
- Increase `geofence_radius` in database
- Verify clue coordinates are correct
- Check effective radius calculation (includes accuracy buffer)

### "Navigation Not Working"

**Cause**: Map app not installed or incorrect URL scheme  
**Solution**:
- Install Google Maps or Apple Maps on device
- Falls back to web directions automatically
- Check console for URL being generated
- Verify platform detection is correct

### "No Position Updates"

**Cause**: Geolocation service not starting  
**Solution**:
```javascript
// Check geofencing status
const status = window.geoManager.getStatus();
console.log('Geofencing status:', status);

// Manually restart tracking
window.geoManager.stopTracking();
await window.geoManager.startTracking();
```

---

## 📊 Analytics

### Location Audit Table

Query user paths and engagement:

```sql
-- View all clue approaches for a booking
SELECT 
    booking_code,
    clue_id,
    distance_m,
    within_geofence,
    created_at
FROM pp_location_audit
WHERE booking_code = 'BB-20250122-001'
ORDER BY created_at;

-- Calculate average approach distance per clue
SELECT 
    hunt_id,
    clue_id,
    COUNT(*) as total_approaches,
    AVG(distance_m) as avg_distance,
    MIN(distance_m) as closest_approach,
    SUM(within_geofence) as successful_unlocks
FROM pp_location_audit
GROUP BY hunt_id, clue_id;
```

### Geofencing Statistics

```sql
-- Hunt completion rates with geofencing
SELECT 
    h.hunt_name,
    COUNT(DISTINCT qc.user_id) as participants,
    COUNT(DISTINCT CASE WHEN la.within_geofence THEN qc.user_id END) as used_geofencing,
    AVG(la.distance_m) as avg_proximity
FROM pp_quest_completions qc
LEFT JOIN pp_hunts h ON qc.hunt_id = h.id
LEFT JOIN pp_location_audit la ON qc.booking_code = la.booking_code
GROUP BY h.id;
```

---

## 🔄 Updating

### Add Geofencing to Existing Clues

```sql
-- Batch update clues with coordinates
UPDATE wp2s_pp_clues c
JOIN clue_coordinates cc ON c.id = cc.clue_id
SET 
    c.latitude = cc.lat,
    c.longitude = cc.lng,
    c.geofence_radius = 30;
```

### Change Enforcement Mode

```php
// In config-secure.php
define('GEOFENCE_ENFORCEMENT_MODE', 'hard'); // Switch to hard mode

// Or via environment variable
export PP_GEOFENCE_MODE=hard
```

### Disable Geofencing Temporarily

```php
// Quick disable without code changes
define('GEOFENCING_ENABLED', false);
```

---

## 🚀 Advanced Features

### Custom Geofence Shapes

Current: Circular geofences  
Future: Polygon support for complex boundaries

```javascript
// Example of planned polygon support
geofenceService.addPolygonGeofence(clueId, {
    type: 'polygon',
    coordinates: [
        [-28.0289, 153.4326],
        [-28.0290, 153.4330],
        [-28.0292, 153.4328],
        [-28.0289, 153.4326] // Close polygon
    ]
});
```

### Dynamic Radius Adjustment

```javascript
// Adjust radius based on time of day or conditions
function getDynamicRadius(clue, userAccuracy) {
    let baseRadius = clue.geofence_radius || 25;
    
    // Larger radius at night (harder to pinpoint)
    const hour = new Date().getHours();
    if (hour < 6 || hour > 20) {
        baseRadius *= 1.5;
    }
    
    // Account for user's GPS accuracy
    return Math.max(baseRadius, userAccuracy + 10);
}
```

---

## 📚 API Reference

See individual service files for complete API documentation:

- **GeolocationService** (`geolocation.js`) - GPS tracking
- **GeofenceService** (`geofence.js`) - Distance & unlocking
- **NotificationService** (`notifications.js`) - User alerts
- **DirectionsService** (`directions.js`) - Navigation
- **GeoSettingsManager** (`settings.js`) - User preferences
- **GeoManager** (`geo-manager.js`) - Main coordinator

---

## 🤝 Support

**Issues**: https://github.com/Baillie11/puzzlepathapp/issues  
**Discussions**: GitHub Discussions  
**Email**: [your support email]

**Common Questions**: See Troubleshooting section above

---

## 📄 License

Same as Puzzle Path main project.

**Last Updated**: January 2025  
**Version**: 3.1.0-geofencing