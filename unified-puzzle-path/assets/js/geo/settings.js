/**
 * Settings Manager for Puzzle Path Geofencing
 * Handles user preferences and settings UI
 */

class GeoSettingsManager {
    constructor() {
        this.settings = this.loadAllSettings();
        this.listeners = new Map();
    }

    /**
     * Load all settings from localStorage
     */
    loadAllSettings() {
        const defaults = {
            geolocation: {
                enabled: true,
                accuracyThreshold: 75
            },
            notifications: {
                enabled: true,
                sound: true,
                vibration: true,
                volume: 0.3
            },
            directions: {
                travelMode: 'walking',
                preferNative: true,
                provider: 'google'
            },
            display: {
                showDistance: true,
                showBearing: true,
                showETA: true,
                units: 'metric' // 'metric' or 'imperial'
            },
            privacy: {
                accepted: false,
                trackLocation: true,
                shareAnalytics: true
            }
        };

        try {
            // Try to load each category
            const saved = {};
            
            for (const [category, defaultVals] of Object.entries(defaults)) {
                const key = `pp_${category}_settings`;
                const stored = localStorage.getItem(key);
                
                if (stored) {
                    try {
                        saved[category] = { ...defaultVals, ...JSON.parse(stored) };
                    } catch (e) {
                        console.warn(`Failed to parse ${category} settings:`, e);
                        saved[category] = defaultVals;
                    }
                } else {
                    saved[category] = defaultVals;
                }
            }

            return saved;
        } catch (e) {
            console.warn('Failed to load settings, using defaults:', e);
            return defaults;
        }
    }

    /**
     * Save all settings to localStorage
     */
    saveAllSettings() {
        try {
            for (const [category, values] of Object.entries(this.settings)) {
                const key = `pp_${category}_settings`;
                localStorage.setItem(key, JSON.stringify(values));
            }
            
            // Notify listeners
            this.notifyListeners('settings_changed', this.settings);
            
            return true;
        } catch (e) {
            console.error('Failed to save settings:', e);
            return false;
        }
    }

    /**
     * Get a specific setting value
     */
    get(category, key) {
        if (this.settings[category] && key in this.settings[category]) {
            return this.settings[category][key];
        }
        return undefined;
    }

    /**
     * Set a specific setting value
     */
    set(category, key, value) {
        if (this.settings[category]) {
            this.settings[category][key] = value;
            this.saveAllSettings();
            this.notifyListeners(`${category}.${key}`, value);
            return true;
        }
        return false;
    }

    /**
     * Get entire category of settings
     */
    getCategory(category) {
        return this.settings[category] ? { ...this.settings[category] } : null;
    }

    /**
     * Update entire category of settings
     */
    setCategory(category, values) {
        if (this.settings[category]) {
            this.settings[category] = { ...this.settings[category], ...values };
            this.saveAllSettings();
            return true;
        }
        return false;
    }

    /**
     * Reset all settings to defaults
     */
    resetAll() {
        // Clear localStorage
        const keys = Object.keys(this.settings);
        keys.forEach(category => {
            localStorage.removeItem(`pp_${category}_settings`);
        });
        
        // Reload defaults
        this.settings = this.loadAllSettings();
        this.notifyListeners('settings_reset', this.settings);
        
        return true;
    }

    /**
     * Reset specific category
     */
    resetCategory(category) {
        if (this.settings[category]) {
            localStorage.removeItem(`pp_${category}_settings`);
            this.settings = this.loadAllSettings();
            this.notifyListeners(`${category}_reset`, this.settings[category]);
            return true;
        }
        return false;
    }

    /**
     * Export settings as JSON
     */
    exportSettings() {
        return JSON.stringify(this.settings, null, 2);
    }

    /**
     * Import settings from JSON
     */
    importSettings(jsonString) {
        try {
            const imported = JSON.parse(jsonString);
            
            // Validate structure
            for (const category of Object.keys(this.settings)) {
                if (imported[category]) {
                    this.settings[category] = { ...this.settings[category], ...imported[category] };
                }
            }
            
            this.saveAllSettings();
            return true;
        } catch (e) {
            console.error('Failed to import settings:', e);
            return false;
        }
    }

    /**
     * Check if privacy policy has been accepted
     */
    hasAcceptedPrivacy() {
        return this.get('privacy', 'accepted') === true;
    }

    /**
     * Accept privacy policy
     */
    acceptPrivacy() {
        return this.set('privacy', 'accepted', true);
    }

    /**
     * Check if geolocation is enabled
     */
    isGeolocationEnabled() {
        return this.get('geolocation', 'enabled') === true;
    }

    /**
     * Enable/disable geolocation
     */
    setGeolocationEnabled(enabled) {
        return this.set('geolocation', 'enabled', !!enabled);
    }

    /**
     * Get travel mode (walking/driving)
     */
    getTravelMode() {
        return this.get('directions', 'travelMode') || 'walking';
    }

    /**
     * Set travel mode
     */
    setTravelMode(mode) {
        if (['walking', 'driving'].includes(mode)) {
            return this.set('directions', 'travelMode', mode);
        }
        return false;
    }

    /**
     * Register a settings change listener
     */
    addListener(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);
    }

    /**
     * Remove a settings change listener
     */
    removeListener(event, callback) {
        if (this.listeners.has(event)) {
            const callbacks = this.listeners.get(event);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    /**
     * Notify all listeners of a change
     */
    notifyListeners(event, data) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (e) {
                    console.error('Listener callback error:', e);
                }
            });
        }

        // Also notify wildcard listeners
        if (this.listeners.has('*')) {
            this.listeners.get('*').forEach(callback => {
                try {
                    callback(event, data);
                } catch (e) {
                    console.error('Wildcard listener callback error:', e);
                }
            });
        }
    }

    /**
     * Create settings UI HTML
     */
    createSettingsUI() {
        const container = document.createElement('div');
        container.className = 'pp-geo-settings';
        container.innerHTML = `
            <div class="pp-settings-header">
                <h3>Location & Navigation Settings</h3>
                <button class="pp-settings-close" aria-label="Close settings">✕</button>
            </div>
            
            <div class="pp-settings-body">
                <!-- Geolocation Settings -->
                <div class="pp-settings-section">
                    <h4>📍 Location Services</h4>
                    <label class="pp-setting-toggle">
                        <input type="checkbox" id="geo-enabled" ${this.isGeolocationEnabled() ? 'checked' : ''}>
                        <span>Enable Location Tracking</span>
                    </label>
                    <p class="pp-setting-note">Location is only tracked during active quests</p>
                </div>

                <!-- Travel Mode -->
                <div class="pp-settings-section">
                    <h4>🚶 Travel Mode</h4>
                    <div class="pp-radio-group">
                        <label>
                            <input type="radio" name="travel-mode" value="walking" ${this.getTravelMode() === 'walking' ? 'checked' : ''}>
                            <span>Walking</span>
                        </label>
                        <label>
                            <input type="radio" name="travel-mode" value="driving" ${this.getTravelMode() === 'driving' ? 'checked' : ''}>
                            <span>Driving</span>
                        </label>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="pp-settings-section">
                    <h4>🔔 Notifications</h4>
                    <label class="pp-setting-toggle">
                        <input type="checkbox" id="notif-enabled" ${this.get('notifications', 'enabled') ? 'checked' : ''}>
                        <span>Enable Notifications</span>
                    </label>
                    <label class="pp-setting-toggle">
                        <input type="checkbox" id="notif-sound" ${this.get('notifications', 'sound') ? 'checked' : ''}>
                        <span>Sound Effects</span>
                    </label>
                    <label class="pp-setting-toggle">
                        <input type="checkbox" id="notif-vibration" ${this.get('notifications', 'vibration') ? 'checked' : ''}>
                        <span>Vibration</span>
                    </label>
                </div>

                <!-- Display Settings -->
                <div class="pp-settings-section">
                    <h4>👁️ Display</h4>
                    <label class="pp-setting-toggle">
                        <input type="checkbox" id="display-distance" ${this.get('display', 'showDistance') ? 'checked' : ''}>
                        <span>Show Distance to Clues</span>
                    </label>
                    <label class="pp-setting-toggle">
                        <input type="checkbox" id="display-bearing" ${this.get('display', 'showBearing') ? 'checked' : ''}>
                        <span>Show Compass Direction</span>
                    </label>
                    <label class="pp-setting-toggle">
                        <input type="checkbox" id="display-eta" ${this.get('display', 'showETA') ? 'checked' : ''}>
                        <span>Show Estimated Time</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="pp-settings-section pp-settings-actions">
                    <button class="pp-btn pp-btn-secondary" id="reset-settings">Reset to Defaults</button>
                    <button class="pp-btn pp-btn-primary" id="save-settings">Save Changes</button>
                </div>
            </div>
        `;

        // Add event listeners
        this.attachEventListeners(container);

        return container;
    }

    /**
     * Attach event listeners to settings UI
     */
    attachEventListeners(container) {
        // Close button
        const closeBtn = container.querySelector('.pp-settings-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                container.remove();
            });
        }

        // Save button
        const saveBtn = container.querySelector('#save-settings');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                this.saveFromUI(container);
            });
        }

        // Reset button
        const resetBtn = container.querySelector('#reset-settings');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (confirm('Reset all settings to defaults?')) {
                    this.resetAll();
                    container.remove();
                    // Re-open with fresh defaults
                    this.showSettingsModal();
                }
            });
        }
    }

    /**
     * Save settings from UI elements
     */
    saveFromUI(container) {
        // Geolocation
        const geoEnabled = container.querySelector('#geo-enabled');
        if (geoEnabled) this.set('geolocation', 'enabled', geoEnabled.checked);

        // Travel mode
        const travelMode = container.querySelector('input[name="travel-mode"]:checked');
        if (travelMode) this.set('directions', 'travelMode', travelMode.value);

        // Notifications
        const notifEnabled = container.querySelector('#notif-enabled');
        if (notifEnabled) this.set('notifications', 'enabled', notifEnabled.checked);
        
        const notifSound = container.querySelector('#notif-sound');
        if (notifSound) this.set('notifications', 'sound', notifSound.checked);
        
        const notifVibration = container.querySelector('#notif-vibration');
        if (notifVibration) this.set('notifications', 'vibration', notifVibration.checked);

        // Display
        const showDistance = container.querySelector('#display-distance');
        if (showDistance) this.set('display', 'showDistance', showDistance.checked);
        
        const showBearing = container.querySelector('#display-bearing');
        if (showBearing) this.set('display', 'showBearing', showBearing.checked);
        
        const showETA = container.querySelector('#display-eta');
        if (showETA) this.set('display', 'showETA', showETA.checked);

        // Show confirmation
        alert('Settings saved successfully!');
        container.remove();
    }

    /**
     * Show settings modal
     */
    showSettingsModal() {
        // Remove existing modal if any
        const existing = document.querySelector('.pp-geo-settings-modal');
        if (existing) existing.remove();

        // Create modal backdrop
        const modal = document.createElement('div');
        modal.className = 'pp-geo-settings-modal';
        modal.innerHTML = `
            <div class="pp-modal-backdrop"></div>
            <div class="pp-modal-content"></div>
        `;

        document.body.appendChild(modal);

        // Add settings UI to modal
        const content = modal.querySelector('.pp-modal-content');
        const settingsUI = this.createSettingsUI();
        content.appendChild(settingsUI);

        // Close on backdrop click
        const backdrop = modal.querySelector('.pp-modal-backdrop');
        backdrop.addEventListener('click', () => {
            modal.remove();
        });

        return modal;
    }
}

// Export for use in other modules
window.GeoSettingsManager = GeoSettingsManager;