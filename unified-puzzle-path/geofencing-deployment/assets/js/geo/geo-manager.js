/**
 * Geofencing Manager for Puzzle Path
 * Main controller that coordinates all geofencing services
 */

class GeoManager {
    constructor() {
        this.geoService = null;
        this.geofenceService = null;
        this.notificationService = null;
        this.directionsService = null;
        this.settingsManager = null;
        
        this.isInitialized = false;
        this.isTracking = false;
        this.currentQuest = null;
        
        // Callbacks for quest integration
        this.onClueUnlocked = null;
        this.onProximityUpdate = null;
    }

    /**
     * Initialize all geofencing services
     */
    async initialize() {
        if (this.isInitialized) {
            console.log('GeoManager already initialized');
            return true;
        }

        console.log('Initializing GeoManager...');

        try {
            // Initialize all services
            this.geoService = new GeolocationService();
            this.geofenceService = new GeofenceService();
            this.notificationService = new NotificationService();
            this.directionsService = new DirectionsService();
            this.settingsManager = new GeoSettingsManager();

            // Set up geofence callbacks
            this.geofenceService.setCallbacks({
                onGeofenceEntry: (clueId, geofence, result) => {
                    this.handleGeofenceEntry(clueId, geofence, result);
                },
                onGeofenceExit: (clueId, geofence, result) => {
                    this.handleGeofenceExit(clueId, geofence, result);
                },
                onProximityChange: (proximities) => {
                    this.handleProximityChange(proximities);
                }
            });

            this.isInitialized = true;
            console.log('GeoManager initialized successfully');
            return true;
        } catch (e) {
            console.error('Failed to initialize GeoManager:', e);
            return false;
        }
    }

    /**
     * Start geofencing for a quest
     */
    async startQuest(huntData, cluesData) {
        if (!this.isInitialized) {
            await this.initialize();
        }

        console.log('Starting quest with geofencing:', huntData.hunt_name);

        this.currentQuest = {
            huntId: huntData.hunt_id || huntData.id,
            huntName: huntData.hunt_name,
            clues: cluesData || []
        };

        // Clear any existing geofences
        this.geofenceService.clearGeofences();

        // Add geofences for clues with coordinates
        let geofencedCount = 0;
        for (const clue of this.currentQuest.clues) {
            if (this.geofenceService.addGeofence(clue.id, clue)) {
                geofencedCount++;
            }
        }

        console.log(`Added ${geofencedCount} geofences for ${this.currentQuest.clues.length} clues`);

        // Check if geolocation is enabled in settings
        if (!this.settingsManager.isGeolocationEnabled()) {
            console.log('Geolocation disabled in settings');
            this.notificationService.showToast(
                'Location services are disabled. Enable them in settings to use geofencing.',
                'info',
                5000
            );
            return false;
        }

        // Request location permission
        try {
            const permission = await this.geoService.requestPermission();
            console.log('Location permission:', permission);

            if (permission === 'denied') {
                this.notificationService.notifyLocationError('PERMISSION_DENIED');
                return false;
            }

            // Start tracking
            return this.startTracking();
        } catch (e) {
            console.error('Permission request failed:', e);
            return false;
        }
    }

    /**
     * Start location tracking
     */
    startTracking() {
        if (this.isTracking) {
            console.log('Already tracking location');
            return true;
        }

        const success = this.geoService.startTracking(
            (position) => this.handlePositionUpdate(position),
            (error) => this.handleLocationError(error)
        );

        if (success) {
            this.isTracking = true;
            console.log('Location tracking started');
        }

        return success;
    }

    /**
     * Stop location tracking
     */
    stopTracking() {
        if (this.geoService) {
            this.geoService.stopTracking();
        }
        this.isTracking = false;
        console.log('Location tracking stopped');
    }

    /**
     * Stop quest and clean up
     */
    stopQuest() {
        this.stopTracking();
        
        if (this.geofenceService) {
            this.geofenceService.clearGeofences();
        }
        
        this.currentQuest = null;
        console.log('Quest stopped');
    }

    /**
     * Handle position updates from geolocation service
     */
    handlePositionUpdate(position) {
        // Update geofence service with new position
        if (this.geofenceService) {
            this.geofenceService.updateUserPosition(position);
        }

        // Check accuracy and warn if low
        if (position.accuracy > 75) {
            // Only warn occasionally (every 30 seconds)
            if (!this.lastAccuracyWarning || Date.now() - this.lastAccuracyWarning > 30000) {
                this.notificationService.notifyAccuracyWarning(position.accuracy);
                this.lastAccuracyWarning = Date.now();
            }
        }

        // Log position (for debugging)
        console.log('Position update:', {
            lat: position.lat.toFixed(6),
            lng: position.lng.toFixed(6),
            accuracy: Math.round(position.accuracy) + 'm'
        });
    }

    /**
     * Handle location errors
     */
    handleLocationError(error) {
        console.error('Location error:', error);
        this.notificationService.notifyLocationError(error.type);
    }

    /**
     * Handle geofence entry
     */
    handleGeofenceEntry(clueId, geofence, result) {
        console.log('Geofence entry:', clueId, geofence.title);

        // Show notification
        this.notificationService.notifyGeofenceEntry(geofence.title, result.distance);

        // Trigger callback for quest integration
        if (this.onClueUnlocked) {
            this.onClueUnlocked(clueId, geofence, result);
        }
    }

    /**
     * Handle geofence exit
     */
    handleGeofenceExit(clueId, geofence, result) {
        console.log('Geofence exit:', clueId, geofence.title);

        // Only notify exit for soft mode (info only)
        // In hard mode, user shouldn't leave without completing
        this.notificationService.notifyGeofenceExit(geofence.title);
    }

    /**
     * Handle proximity changes
     */
    handleProximityChange(proximities) {
        // Trigger callback for quest integration
        if (this.onProximityUpdate) {
            this.onProximityUpdate(proximities);
        }

        // Find closest clue
        if (proximities.length > 0) {
            const closest = proximities[0];
            // Optionally notify about closest clue
            // this.notificationService.notifyProximityUpdate(closest);
        }
    }

    /**
     * Get directions to a clue
     */
    navigateToClue(clueId) {
        const clueInfo = this.geofenceService.getClueInfo(clueId);
        if (!clueInfo || !clueInfo.geofence) {
            console.error('Clue not found:', clueId);
            return false;
        }

        const userPosition = this.geoService.getCurrentPosition();
        return this.directionsService.navigateToClue(clueInfo.geofence, userPosition);
    }

    /**
     * Get clue proximity information
     */
    getClueProximity(clueId) {
        return this.geofenceService.getClueInfo(clueId);
    }

    /**
     * Get all clues proximity information
     */
    getAllProximities() {
        return this.geofenceService.getAllGeofences();
    }

    /**
     * Check if a clue is unlocked
     */
    isClueUnlocked(clueId) {
        return this.geofenceService.isClueUnlocked(clueId);
    }

    /**
     * Manually unlock a clue (for non-geofenced clues)
     */
    unlockClue(clueId) {
        return this.geofenceService.unlockClue(clueId);
    }

    /**
     * Get current user position
     */
    getUserPosition() {
        return this.geoService.getCurrentPosition();
    }

    /**
     * Verify location on server (for hard enforcement)
     */
    async verifyLocation(huntId, clueId) {
        const position = this.getUserPosition();
        if (!position) {
            return { success: false, verified: false, message: 'Location unavailable' };
        }

        try {
            const response = await fetch('verify_location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    hunt_id: huntId,
                    clue_id: clueId,
                    user_lat: position.lat,
                    user_lng: position.lng,
                    user_accuracy: position.accuracy,
                    booking_code: this.currentQuest?.bookingCode || ''
                })
            });

            const data = await response.json();
            return data;
        } catch (e) {
            console.error('Location verification failed:', e);
            return { success: false, verified: false, message: 'Verification error' };
        }
    }

    /**
     * Show settings modal
     */
    showSettings() {
        if (this.settingsManager) {
            return this.settingsManager.showSettingsModal();
        }
        return null;
    }

    /**
     * Set quest integration callbacks
     */
    setCallbacks(callbacks) {
        if (callbacks.onClueUnlocked) {
            this.onClueUnlocked = callbacks.onClueUnlocked;
        }
        if (callbacks.onProximityUpdate) {
            this.onProximityUpdate = callbacks.onProximityUpdate;
        }
    }

    /**
     * Get geofencing status
     */
    getStatus() {
        return {
            initialized: this.isInitialized,
            tracking: this.isTracking,
            hasQuest: !!this.currentQuest,
            geolocationSupported: this.geoService?.isSupported() || false,
            geolocationEnabled: this.settingsManager?.isGeolocationEnabled() || false,
            currentPosition: this.getUserPosition(),
            activeGeofences: this.geofenceService?.getAllGeofences().length || 0
        };
    }

    /**
     * Enable/disable geolocation
     */
    setGeolocationEnabled(enabled) {
        if (this.settingsManager) {
            this.settingsManager.setGeolocationEnabled(enabled);
            
            if (enabled && this.currentQuest && !this.isTracking) {
                this.startTracking();
            } else if (!enabled && this.isTracking) {
                this.stopTracking();
            }
        }
    }

    /**
     * Set travel mode (walking/driving)
     */
    setTravelMode(mode) {
        if (this.settingsManager) {
            return this.settingsManager.setTravelMode(mode);
        }
        return false;
    }

    /**
     * Get travel mode
     */
    getTravelMode() {
        return this.settingsManager?.getTravelMode() || 'walking';
    }

    /**
     * Format distance for display
     */
    formatDistance(meters) {
        return this.geofenceService?.formatDistance(meters) || `${Math.round(meters)}m`;
    }

    /**
     * Calculate ETA to clue
     */
    calculateETA(clueId) {
        return this.geofenceService?.estimateTimeToClue(clueId, this.getTravelMode() === 'walking' ? 1.35 : 11.1);
    }

    /**
     * Cleanup
     */
    destroy() {
        this.stopQuest();
        this.isInitialized = false;
        console.log('GeoManager destroyed');
    }
}

// Create global instance
window.geoManager = new GeoManager();

// Export for use in other modules
window.GeoManager = GeoManager;