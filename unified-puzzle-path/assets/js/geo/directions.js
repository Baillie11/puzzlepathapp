/**
 * Directions Service for Puzzle Path
 * Handles navigation to clues via native apps and in-app routing
 */

class DirectionsService {
    constructor() {
        this.settings = this.loadSettings();
        this.currentRoute = null;
    }

    loadSettings() {
        try {
            const saved = JSON.parse(localStorage.getItem('pp_directions_settings') || '{}');
            return {
                travelMode: saved.travelMode || 'walking', // 'walking' or 'driving'
                preferNative: saved.preferNative !== false, // Default true
                provider: saved.provider || 'google' // 'google', 'apple', or 'geo'
            };
        } catch (e) {
            console.warn('Failed to load directions settings:', e);
            return {
                travelMode: 'walking',
                preferNative: true,
                provider: 'google'
            };
        }
    }

    saveSettings() {
        try {
            localStorage.setItem('pp_directions_settings', JSON.stringify(this.settings));
        } catch (e) {
            console.warn('Failed to save directions settings:', e);
        }
    }

    /**
     * Detect device platform
     */
    detectPlatform() {
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        
        if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
            return 'ios';
        }
        
        if (/android/i.test(userAgent)) {
            return 'android';
        }
        
        return 'web';
    }

    /**
     * Navigate to a clue location
     */
    navigateToClue(clue, userPosition = null) {
        if (!clue || !clue.lat || !clue.lng) {
            console.error('Invalid clue coordinates');
            return false;
        }

        const lat = clue.lat;
        const lng = clue.lng;
        const label = encodeURIComponent(clue.title || 'Clue Location');
        const platform = this.detectPlatform();

        console.log(`Navigate to: ${label} (${lat}, ${lng}) on ${platform}`);

        if (this.settings.preferNative) {
            return this.openNativeApp(lat, lng, label, platform);
        } else {
            return this.openWebDirections(lat, lng, label);
        }
    }

    /**
     * Open native navigation app
     */
    openNativeApp(lat, lng, label, platform) {
        let url;

        switch (platform) {
            case 'ios':
                // Try Apple Maps first, fall back to Google Maps
                if (this.settings.provider === 'apple' || !this.isGoogleMapsInstalled()) {
                    url = this.getAppleMapsUrl(lat, lng, label);
                } else {
                    url = this.getGoogleMapsAppUrl(lat, lng, label);
                }
                break;

            case 'android':
                // Try Google Maps app first, fall back to geo intent
                url = this.getAndroidGeoUrl(lat, lng, label);
                break;

            default:
                // Desktop or unknown - use web version
                return this.openWebDirections(lat, lng, label);
        }

        console.log('Opening URL:', url);
        
        try {
            // Try to open the URL
            window.location.href = url;
            return true;
        } catch (e) {
            console.warn('Failed to open native app:', e);
            // Fall back to web directions
            return this.openWebDirections(lat, lng, label);
        }
    }

    /**
     * Get Apple Maps URL
     */
    getAppleMapsUrl(lat, lng, label) {
        const dirFlag = this.settings.travelMode === 'driving' ? 'd' : 'w';
        return `maps://?daddr=${lat},${lng}&dirflg=${dirFlag}`;
    }

    /**
     * Get Google Maps app URL (for iOS)
     */
    getGoogleMapsAppUrl(lat, lng, label) {
        const mode = this.settings.travelMode;
        return `comgooglemaps://?daddr=${lat},${lng}&directionsmode=${mode}`;
    }

    /**
     * Get Android geo intent URL
     */
    getAndroidGeoUrl(lat, lng, label) {
        // This will open Google Maps if installed, otherwise browser
        return `geo:${lat},${lng}?q=${lat},${lng}(${label})`;
    }

    /**
     * Open web-based directions (Google Maps website)
     */
    openWebDirections(lat, lng, label) {
        const mode = this.settings.travelMode;
        const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=${mode}`;
        
        console.log('Opening web directions:', url);
        
        try {
            window.open(url, '_blank');
            return true;
        } catch (e) {
            console.error('Failed to open web directions:', e);
            return false;
        }
    }

    /**
     * Check if Google Maps app is likely installed (heuristic)
     */
    isGoogleMapsInstalled() {
        // We can't reliably detect this, so assume it is on Android and maybe on iOS
        const platform = this.detectPlatform();
        return platform === 'android'; // Conservative assumption
    }

    /**
     * Calculate straight-line ETA
     */
    calculateETA(distanceMeters, travelMode = null) {
        const mode = travelMode || this.settings.travelMode;
        
        // Speed in meters per second
        const speeds = {
            walking: 1.35,  // ~5 km/h
            driving: 11.1   // ~40 km/h (accounting for city driving)
        };

        const speed = speeds[mode] || speeds.walking;
        const timeSeconds = distanceMeters / speed;
        const timeMinutes = Math.ceil(timeSeconds / 60);

        return {
            seconds: Math.round(timeSeconds),
            minutes: timeMinutes,
            formatted: this.formatTime(timeMinutes),
            mode: mode
        };
    }

    /**
     * Format time for display
     */
    formatTime(minutes) {
        if (minutes < 60) {
            return `${minutes} min`;
        } else {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
        }
    }

    /**
     * Get directions summary (distance + ETA)
     */
    getDirectionsSummary(clue, userPosition) {
        if (!clue || !userPosition || !clue.lat || !clue.lng) {
            return null;
        }

        // Calculate distance using Haversine
        const distance = this.calculateDistance(
            userPosition.lat, userPosition.lng,
            clue.lat, clue.lng
        );

        const eta = this.calculateETA(distance);

        return {
            distance: distance,
            distanceFormatted: this.formatDistance(distance),
            eta: eta,
            bearing: this.calculateBearing(
                userPosition.lat, userPosition.lng,
                clue.lat, clue.lng
            ),
            canNavigate: true
        };
    }

    /**
     * Haversine distance calculation
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371000; // Earth's radius in meters
        const toRad = (deg) => deg * (Math.PI / 180);
        
        const dLat = toRad(lat2 - lat1);
        const dLng = toRad(lng2 - lng1);
        
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                  Math.sin(dLng / 2) * Math.sin(dLng / 2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        
        return R * c;
    }

    /**
     * Calculate bearing (compass direction)
     */
    calculateBearing(lat1, lng1, lat2, lng2) {
        const toRad = (deg) => deg * (Math.PI / 180);
        const toDeg = (rad) => rad * (180 / Math.PI);
        
        const dLng = toRad(lng2 - lng1);
        const lat1Rad = toRad(lat1);
        const lat2Rad = toRad(lat2);
        
        const y = Math.sin(dLng) * Math.cos(lat2Rad);
        const x = Math.cos(lat1Rad) * Math.sin(lat2Rad) -
                  Math.sin(lat1Rad) * Math.cos(lat2Rad) * Math.cos(dLng);
        
        let bearing = toDeg(Math.atan2(y, x));
        return (bearing + 360) % 360; // Normalize to 0-360
    }

    /**
     * Format distance for display
     */
    formatDistance(meters) {
        if (meters < 1000) {
            return `${Math.round(meters)}m`;
        } else {
            return `${(meters / 1000).toFixed(1)}km`;
        }
    }

    /**
     * Get compass direction from bearing
     */
    getCompassDirection(bearing) {
        const directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        const index = Math.round(bearing / 45) % 8;
        return directions[index];
    }

    /**
     * Settings management
     */
    setTravelMode(mode) {
        if (['walking', 'driving'].includes(mode)) {
            this.settings.travelMode = mode;
            this.saveSettings();
            return true;
        }
        return false;
    }

    getTravelMode() {
        return this.settings.travelMode;
    }

    setPreferNative(prefer) {
        this.settings.preferNative = !!prefer;
        this.saveSettings();
    }

    getPreferNative() {
        return this.settings.preferNative;
    }

    setProvider(provider) {
        if (['google', 'apple', 'geo'].includes(provider)) {
            this.settings.provider = provider;
            this.saveSettings();
            return true;
        }
        return false;
    }

    /**
     * Create a shareable directions link
     */
    getShareableLink(clue) {
        if (!clue || !clue.lat || !clue.lng) return null;
        
        const label = encodeURIComponent(clue.title || 'Puzzle Path Clue');
        return `https://www.google.com/maps/search/?api=1&query=${clue.lat},${clue.lng}&query_place_id=${label}`;
    }

    /**
     * Check if directions are available
     */
    isAvailable() {
        return true; // Directions always available via fallback to web
    }

    /**
     * Get platform-specific instructions
     */
    getPlatformInstructions() {
        const platform = this.detectPlatform();
        
        const instructions = {
            ios: 'Tap "Navigate" to open directions in Apple Maps or Google Maps.',
            android: 'Tap "Navigate" to open directions in Google Maps.',
            web: 'Click "Navigate" to open directions in a new tab.'
        };

        return instructions[platform] || instructions.web;
    }
}

// Export for use in other modules
window.DirectionsService = DirectionsService;