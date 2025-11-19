/**
 * Geofencing Service for Puzzle Path
 * Handles distance calculations, geofence entry/exit detection, and clue unlocking
 */

class GeofenceService {
    constructor() {
        this.geofences = new Map(); // Map of clue_id -> geofence data
        this.userPosition = null;
        this.onGeofenceEntry = null;
        this.onGeofenceExit = null;
        this.onProximityChange = null;
        this.lastProximityUpdate = 0;
        this.proximityUpdateInterval = 2000; // 2 seconds
        this.minRadius = 25; // Minimum geofence radius in meters
        this.entryDebounce = new Map(); // Debounce geofence entries
        this.entryTimeout = 3000; // 3 seconds
    }

    /**
     * Haversine distance calculation in meters
     */
    haversineDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Earth's radius in meters
        const toRad = (degrees) => degrees * (Math.PI / 180);
        
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        
        return R * c;
    }

    /**
     * Calculate bearing from user to target (in degrees)
     */
    calculateBearing(lat1, lon1, lat2, lon2) {
        const toRad = (degrees) => degrees * (Math.PI / 180);
        const toDeg = (radians) => radians * (180 / Math.PI);
        
        const dLon = toRad(lon2 - lon1);
        const lat1Rad = toRad(lat1);
        const lat2Rad = toRad(lat2);
        
        const y = Math.sin(dLon) * Math.cos(lat2Rad);
        const x = Math.cos(lat1Rad) * Math.sin(lat2Rad) -
                  Math.sin(lat1Rad) * Math.cos(lat2Rad) * Math.cos(dLon);
        
        let bearing = toDeg(Math.atan2(y, x));
        return (bearing + 360) % 360; // Normalize to 0-360
    }

    /**
     * Add or update a geofence for a clue
     */
    addGeofence(clueId, clueData) {
        if (!clueData.latitude || !clueData.longitude) {
            console.log(`Skipping geofence for clue ${clueId} - no coordinates`);
            return false;
        }

        const geofence = {
            id: clueId,
            lat: parseFloat(clueData.latitude),
            lng: parseFloat(clueData.longitude),
            radius: Math.max(this.minRadius, clueData.geofence_radius || this.minRadius),
            title: clueData.title || `Clue ${clueId}`,
            isInside: false,
            lastDistance: null,
            unlocked: false
        };

        this.geofences.set(clueId, geofence);
        console.log(`Added geofence for clue ${clueId}:`, geofence);
        return true;
    }

    /**
     * Remove a geofence
     */
    removeGeofence(clueId) {
        this.geofences.delete(clueId);
        this.entryDebounce.delete(clueId);
    }

    /**
     * Clear all geofences
     */
    clearGeofences() {
        this.geofences.clear();
        this.entryDebounce.clear();
    }

    /**
     * Update user position and check all geofences
     */
    updateUserPosition(position) {
        this.userPosition = position;
        this.checkAllGeofences();
        this.updateProximities();
    }

    /**
     * Check if user is within a specific geofence
     */
    checkGeofence(clueId, userPosition = null) {
        const geofence = this.geofences.get(clueId);
        if (!geofence) return null;

        const pos = userPosition || this.userPosition;
        if (!pos) return null;

        const distance = this.haversineDistance(
            pos.lat, pos.lng,
            geofence.lat, geofence.lng
        );

        // Calculate effective radius (include GPS accuracy)
        const accuracyBuffer = Math.min(pos.accuracy || 0, 50); // Cap accuracy buffer at 50m
        const effectiveRadius = geofence.radius + (accuracyBuffer * 0.5); // Use half of accuracy as buffer

        const isInside = distance <= effectiveRadius;
        const bearing = this.calculateBearing(pos.lat, pos.lng, geofence.lat, geofence.lng);

        return {
            distance: Math.round(distance),
            effectiveRadius: Math.round(effectiveRadius),
            isInside: isInside,
            bearing: Math.round(bearing),
            geofence: geofence
        };
    }

    /**
     * Check all geofences and trigger entry/exit events
     */
    checkAllGeofences() {
        if (!this.userPosition) return;

        for (const [clueId, geofence] of this.geofences) {
            const result = this.checkGeofence(clueId);
            if (!result) continue;

            const wasInside = geofence.isInside;
            const isInside = result.isInside;

            // Update geofence state
            geofence.isInside = isInside;
            geofence.lastDistance = result.distance;

            // Handle entry/exit events with debouncing
            if (isInside && !wasInside) {
                this.handleGeofenceEntry(clueId, geofence, result);
            } else if (!isInside && wasInside) {
                this.handleGeofenceExit(clueId, geofence, result);
            }
        }
    }

    /**
     * Handle geofence entry with debouncing
     */
    handleGeofenceEntry(clueId, geofence, result) {
        // Clear any existing debounce timeout
        if (this.entryDebounce.has(clueId)) {
            clearTimeout(this.entryDebounce.get(clueId));
        }

        // Set a debounce timeout
        const timeoutId = setTimeout(() => {
            console.log(`Entered geofence for clue ${clueId}:`, result);
            
            // Mark as unlocked
            geofence.unlocked = true;
            
            // Trigger callback
            if (this.onGeofenceEntry) {
                this.onGeofenceEntry(clueId, geofence, result);
            }
            
            this.entryDebounce.delete(clueId);
        }, 1000); // 1 second debounce

        this.entryDebounce.set(clueId, timeoutId);
    }

    /**
     * Handle geofence exit
     */
    handleGeofenceExit(clueId, geofence, result) {
        // Clear any pending entry debounce
        if (this.entryDebounce.has(clueId)) {
            clearTimeout(this.entryDebounce.get(clueId));
            this.entryDebounce.delete(clueId);
            return; // Don't trigger exit if we were still debouncing entry
        }

        console.log(`Exited geofence for clue ${clueId}:`, result);

        if (this.onGeofenceExit) {
            this.onGeofenceExit(clueId, geofence, result);
        }
    }

    /**
     * Update proximity information for all clues
     */
    updateProximities() {
        const now = Date.now();
        if (now - this.lastProximityUpdate < this.proximityUpdateInterval) {
            return; // Throttle updates
        }

        if (!this.userPosition || !this.onProximityChange) return;

        const proximities = [];
        
        for (const [clueId, geofence] of this.geofences) {
            const result = this.checkGeofence(clueId);
            if (result) {
                proximities.push({
                    clueId: clueId,
                    distance: result.distance,
                    bearing: result.bearing,
                    isInside: result.isInside,
                    title: geofence.title,
                    unlocked: geofence.unlocked
                });
            }
        }

        // Sort by distance
        proximities.sort((a, b) => a.distance - b.distance);

        this.onProximityChange(proximities);
        this.lastProximityUpdate = now;
    }

    /**
     * Get the closest unlocked clue
     */
    getClosestUnlockedClue() {
        if (!this.userPosition) return null;

        let closest = null;
        let closestDistance = Infinity;

        for (const [clueId, geofence] of this.geofences) {
            if (!geofence.unlocked) continue;

            const result = this.checkGeofence(clueId);
            if (result && result.distance < closestDistance) {
                closestDistance = result.distance;
                closest = { clueId, geofence, result };
            }
        }

        return closest;
    }

    /**
     * Get information about a specific clue's geofence
     */
    getClueInfo(clueId) {
        const geofence = this.geofences.get(clueId);
        if (!geofence) return null;

        const result = this.checkGeofence(clueId);
        return {
            geofence: geofence,
            proximity: result,
            unlocked: geofence.unlocked
        };
    }

    /**
     * Manually unlock a clue (for non-geofenced clues or override)
     */
    unlockClue(clueId) {
        const geofence = this.geofences.get(clueId);
        if (geofence) {
            geofence.unlocked = true;
            console.log(`Manually unlocked clue ${clueId}`);
        }
    }

    /**
     * Check if a clue is unlocked
     */
    isClueUnlocked(clueId) {
        const geofence = this.geofences.get(clueId);
        return geofence ? geofence.unlocked : false;
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
     * Set event callbacks
     */
    setCallbacks(callbacks) {
        if (callbacks.onGeofenceEntry) {
            this.onGeofenceEntry = callbacks.onGeofenceEntry;
        }
        if (callbacks.onGeofenceExit) {
            this.onGeofenceExit = callbacks.onGeofenceExit;
        }
        if (callbacks.onProximityChange) {
            this.onProximityChange = callbacks.onProximityChange;
        }
    }

    /**
     * Get all geofences (for debugging or map display)
     */
    getAllGeofences() {
        return Array.from(this.geofences.entries()).map(([clueId, geofence]) => ({
            clueId,
            ...geofence,
            proximity: this.checkGeofence(clueId)
        }));
    }

    /**
     * Estimate time to reach a clue
     */
    estimateTimeToClue(clueId, walkingSpeed = 1.35) { // 1.35 m/s ≈ 5 km/h
        const result = this.checkGeofence(clueId);
        if (!result) return null;

        const timeSeconds = result.distance / walkingSpeed;
        const timeMinutes = Math.round(timeSeconds / 60);
        
        return {
            seconds: Math.round(timeSeconds),
            minutes: timeMinutes,
            formatted: timeMinutes < 60 ? `${timeMinutes}m` : `${Math.round(timeMinutes / 60)}h ${timeMinutes % 60}m`
        };
    }
}

// Export for use in other modules
window.GeofenceService = GeofenceService;