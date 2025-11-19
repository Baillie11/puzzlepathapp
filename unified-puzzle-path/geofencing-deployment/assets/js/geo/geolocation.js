/**
 * Geolocation Service for Puzzle Path
 * Handles browser geolocation with permission management and accuracy filtering
 */

class GeolocationService {
    constructor() {
        this.watchId = null;
        this.isTracking = false;
        this.currentPosition = null;
        this.onPositionCallback = null;
        this.onErrorCallback = null;
        this.lastKnownPosition = null;
        this.positionBuffer = [];
        this.bufferSize = 3; // For smoothing noisy readings
        
        // Default options
        this.options = {
            enableHighAccuracy: true,
            maximumAge: 5000, // 5 seconds
            timeout: 15000    // 15 seconds
        };
        
        // Load saved settings
        this.loadSettings();
    }

    loadSettings() {
        try {
            const settings = JSON.parse(localStorage.getItem('pp_geo_settings') || '{}');
            this.enabled = settings.enabled !== false; // Default true
            this.accuracy_threshold = settings.accuracy_threshold || 75; // meters
        } catch (e) {
            console.warn('Failed to load geo settings:', e);
            this.enabled = true;
            this.accuracy_threshold = 75;
        }
    }

    saveSettings() {
        try {
            localStorage.setItem('pp_geo_settings', JSON.stringify({
                enabled: this.enabled,
                accuracy_threshold: this.accuracy_threshold
            }));
        } catch (e) {
            console.warn('Failed to save geo settings:', e);
        }
    }

    isSupported() {
        return 'geolocation' in navigator;
    }

    async requestPermission() {
        if (!this.isSupported()) {
            throw new Error('Geolocation is not supported by this browser');
        }

        // For browsers that support permissions API
        if ('permissions' in navigator) {
            try {
                const permission = await navigator.permissions.query({name: 'geolocation'});
                return permission.state;
            } catch (e) {
                console.warn('Permissions API not fully supported:', e);
            }
        }

        // Fallback: try to get position to check permission
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                () => resolve('granted'),
                (error) => {
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            resolve('denied');
                            break;
                        case error.POSITION_UNAVAILABLE:
                            resolve('granted'); // Permission was granted but location unavailable
                            break;
                        default:
                            resolve('prompt');
                    }
                },
                { ...this.options, timeout: 5000 }
            );
        });
    }

    startTracking(onPosition, onError) {
        if (!this.isSupported()) {
            onError && onError({ code: 'NOT_SUPPORTED', message: 'Geolocation not supported' });
            return false;
        }

        if (!this.enabled) {
            onError && onError({ code: 'DISABLED', message: 'Location services disabled by user' });
            return false;
        }

        this.onPositionCallback = onPosition;
        this.onErrorCallback = onError;

        if (this.isTracking) {
            console.log('Geolocation already tracking');
            return true;
        }

        console.log('Starting geolocation tracking...');
        
        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.handlePosition(position),
            (error) => this.handleError(error),
            this.options
        );

        if (this.watchId) {
            this.isTracking = true;
            console.log('Geolocation tracking started with watchId:', this.watchId);
            return true;
        }

        return false;
    }

    stopTracking() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        this.isTracking = false;
        this.positionBuffer = [];
        console.log('Geolocation tracking stopped');
    }

    handlePosition(position) {
        const coords = position.coords;
        
        // Basic validation
        if (!coords || coords.latitude === null || coords.longitude === null) {
            console.warn('Invalid position received:', position);
            return;
        }

        // Check accuracy threshold
        if (coords.accuracy > this.accuracy_threshold) {
            console.warn(`Low accuracy position: ${coords.accuracy}m (threshold: ${this.accuracy_threshold}m)`);
            // Still process but flag as low accuracy
        }

        // Create normalized position object
        const normalizedPos = {
            lat: coords.latitude,
            lng: coords.longitude,
            accuracy: coords.accuracy || 999,
            heading: coords.heading || null,
            speed: coords.speed || null,
            timestamp: position.timestamp || Date.now(),
            altitude: coords.altitude || null,
            raw: position // Keep original for debugging
        };

        // Add to buffer for smoothing
        this.positionBuffer.push(normalizedPos);
        if (this.positionBuffer.length > this.bufferSize) {
            this.positionBuffer.shift();
        }

        // Get smoothed position
        const smoothedPos = this.getSmoothPosition();
        
        this.currentPosition = smoothedPos;
        this.lastKnownPosition = smoothedPos;

        // Call the callback
        if (this.onPositionCallback) {
            this.onPositionCallback(smoothedPos);
        }
    }

    handleError(error) {
        console.error('Geolocation error:', error);
        
        const normalizedError = {
            code: error.code,
            message: error.message,
            type: this.getErrorType(error.code)
        };

        if (this.onErrorCallback) {
            this.onErrorCallback(normalizedError);
        }
    }

    getErrorType(code) {
        switch (code) {
            case 1: return 'PERMISSION_DENIED';
            case 2: return 'POSITION_UNAVAILABLE';
            case 3: return 'TIMEOUT';
            default: return 'UNKNOWN';
        }
    }

    getSmoothPosition() {
        if (this.positionBuffer.length === 0) return null;
        
        if (this.positionBuffer.length === 1) {
            return this.positionBuffer[0];
        }

        // Simple averaging for smoothing
        const validPositions = this.positionBuffer.filter(pos => 
            pos.accuracy <= this.accuracy_threshold * 1.5
        );

        if (validPositions.length === 0) {
            // Return most recent even if accuracy is poor
            return this.positionBuffer[this.positionBuffer.length - 1];
        }

        const avgLat = validPositions.reduce((sum, pos) => sum + pos.lat, 0) / validPositions.length;
        const avgLng = validPositions.reduce((sum, pos) => sum + pos.lng, 0) / validPositions.length;
        const bestAccuracy = Math.min(...validPositions.map(pos => pos.accuracy));
        const mostRecent = validPositions[validPositions.length - 1];

        return {
            lat: avgLat,
            lng: avgLng,
            accuracy: bestAccuracy,
            heading: mostRecent.heading,
            speed: mostRecent.speed,
            timestamp: mostRecent.timestamp,
            altitude: mostRecent.altitude,
            smoothed: true
        };
    }

    getCurrentPosition() {
        return this.currentPosition;
    }

    getLastKnownPosition() {
        return this.lastKnownPosition;
    }

    setEnabled(enabled) {
        this.enabled = enabled;
        this.saveSettings();
        
        if (!enabled && this.isTracking) {
            this.stopTracking();
        }
    }

    isEnabled() {
        return this.enabled;
    }

    setAccuracyThreshold(threshold) {
        this.accuracy_threshold = Math.max(10, Math.min(200, threshold)); // Clamp between 10-200m
        this.saveSettings();
    }

    getAccuracyThreshold() {
        return this.accuracy_threshold;
    }

    // Get single position (one-time)
    async getSinglePosition(timeout = 10000) {
        if (!this.isSupported()) {
            throw new Error('Geolocation not supported');
        }

        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const normalizedPos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy || 999,
                        heading: position.coords.heading || null,
                        speed: position.coords.speed || null,
                        timestamp: position.timestamp || Date.now()
                    };
                    resolve(normalizedPos);
                },
                (error) => reject(this.getErrorType(error.code)),
                { ...this.options, timeout }
            );
        });
    }
}

// Export for use in other modules
window.GeolocationService = GeolocationService;