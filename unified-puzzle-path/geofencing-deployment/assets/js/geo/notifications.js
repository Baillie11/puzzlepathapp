/**
 * Notification Service for Puzzle Path Geofencing
 * Handles toast notifications, vibration, and audio feedback
 */

class NotificationService {
    constructor() {
        this.settings = this.loadSettings();
        this.toastContainer = null;
        this.activeToasts = new Set();
        this.audioContext = null;
        this.sounds = new Map();
        
        this.createToastContainer();
        this.initializeAudio();
    }

    loadSettings() {
        try {
            const saved = JSON.parse(localStorage.getItem('pp_notification_settings') || '{}');
            return {
                enabled: saved.enabled !== false, // Default true
                sound: saved.sound !== false,     // Default true
                vibration: saved.vibration !== false, // Default true
                volume: saved.volume || 0.3       // Default 30%
            };
        } catch (e) {
            console.warn('Failed to load notification settings:', e);
            return {
                enabled: true,
                sound: true,
                vibration: true,
                volume: 0.3
            };
        }
    }

    saveSettings() {
        try {
            localStorage.setItem('pp_notification_settings', JSON.stringify(this.settings));
        } catch (e) {
            console.warn('Failed to save notification settings:', e);
        }
    }

    createToastContainer() {
        // Check if container already exists
        this.toastContainer = document.getElementById('pp-toast-container');
        
        if (!this.toastContainer) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.id = 'pp-toast-container';
            this.toastContainer.className = 'pp-toast-container';
            
            // Add CSS styles
            const style = document.createElement('style');
            style.textContent = `
                .pp-toast-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    pointer-events: none;
                }
                
                .pp-toast {
                    background: rgba(0, 0, 0, 0.85);
                    color: white;
                    padding: 12px 16px;
                    margin-bottom: 10px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                    font-size: 14px;
                    line-height: 1.4;
                    max-width: 300px;
                    min-width: 200px;
                    opacity: 0;
                    transform: translateX(100%);
                    transition: all 0.3s ease;
                    pointer-events: auto;
                    border-left: 4px solid #007cba;
                }
                
                .pp-toast.show {
                    opacity: 1;
                    transform: translateX(0);
                }
                
                .pp-toast.success {
                    border-left-color: #28a745;
                }
                
                .pp-toast.warning {
                    border-left-color: #ffc107;
                }
                
                .pp-toast.error {
                    border-left-color: #dc3545;
                }
                
                .pp-toast.geofence {
                    border-left-color: #17a2b8;
                    background: rgba(23, 162, 184, 0.15);
                    backdrop-filter: blur(10px);
                    color: #fff;
                }
                
                .pp-toast-icon {
                    display: inline-block;
                    margin-right: 8px;
                    font-size: 16px;
                }
                
                .pp-toast-title {
                    font-weight: bold;
                    margin-bottom: 4px;
                }
                
                .pp-toast-message {
                    font-size: 13px;
                    opacity: 0.9;
                }
                
                @media (max-width: 480px) {
                    .pp-toast-container {
                        top: 10px;
                        right: 10px;
                        left: 10px;
                    }
                    
                    .pp-toast {
                        max-width: none;
                    }
                }
            `;
            
            document.head.appendChild(style);
            document.body.appendChild(this.toastContainer);
        }
    }

    async initializeAudio() {
        if (!this.settings.sound) return;

        try {
            // Create audio context on first user interaction
            document.addEventListener('click', this.enableAudio.bind(this), { once: true });
            document.addEventListener('touchstart', this.enableAudio.bind(this), { once: true });
        } catch (e) {
            console.warn('Audio initialization failed:', e);
        }
    }

    enableAudio() {
        if (this.audioContext) return;

        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.createSounds();
        } catch (e) {
            console.warn('Failed to create audio context:', e);
        }
    }

    createSounds() {
        if (!this.audioContext) return;

        // Create simple synthesized sounds
        this.sounds.set('entry', this.createTone(800, 0.1, 'sine')); // Higher pitch for entry
        this.sounds.set('exit', this.createTone(400, 0.1, 'sine'));  // Lower pitch for exit
        this.sounds.set('success', this.createChord([523, 659, 784], 0.2)); // C major chord
        this.sounds.set('warning', this.createTone(600, 0.15, 'triangle'));
        this.sounds.set('error', this.createTone(300, 0.3, 'sawtooth'));
    }

    createTone(frequency, duration, waveType = 'sine') {
        return () => {
            if (!this.audioContext || !this.settings.sound) return;

            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);

            oscillator.frequency.value = frequency;
            oscillator.type = waveType;

            gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(this.settings.volume, this.audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + duration);

            oscillator.start(this.audioContext.currentTime);
            oscillator.stop(this.audioContext.currentTime + duration);
        };
    }

    createChord(frequencies, duration) {
        return () => {
            if (!this.audioContext || !this.settings.sound) return;

            frequencies.forEach((freq, index) => {
                setTimeout(() => {
                    this.createTone(freq, duration * 0.8)();
                }, index * 50); // Slight delay between notes
            });
        };
    }

    showToast(message, type = 'info', duration = 4000, options = {}) {
        if (!this.settings.enabled) return null;

        const toast = document.createElement('div');
        toast.className = `pp-toast ${type}`;
        
        // Add icon based on type
        const icons = {
            success: '✅',
            warning: '⚠️',
            error: '❌',
            geofence: '📍',
            info: 'ℹ️'
        };

        const icon = options.icon || icons[type] || icons.info;
        const title = options.title || '';
        
        toast.innerHTML = `
            <div class="pp-toast-content">
                ${icon ? `<span class="pp-toast-icon">${icon}</span>` : ''}
                ${title ? `<div class="pp-toast-title">${title}</div>` : ''}
                <div class="pp-toast-message">${message}</div>
            </div>
        `;

        // Add to container and show
        this.toastContainer.appendChild(toast);
        this.activeToasts.add(toast);

        // Trigger animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        // Auto-remove after duration
        const removeTimeout = setTimeout(() => {
            this.removeToast(toast);
        }, duration);

        // Add click to dismiss
        toast.addEventListener('click', () => {
            clearTimeout(removeTimeout);
            this.removeToast(toast);
        });

        return toast;
    }

    removeToast(toast) {
        if (!toast.parentNode) return;

        toast.classList.remove('show');
        this.activeToasts.delete(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300); // Match CSS transition time
    }

    vibrate(pattern = [100]) {
        if (!this.settings.vibration || !navigator.vibrate) return;

        try {
            navigator.vibrate(pattern);
        } catch (e) {
            console.warn('Vibration failed:', e);
        }
    }

    playSound(soundName) {
        if (!this.settings.sound) return;

        const sound = this.sounds.get(soundName);
        if (sound) {
            try {
                sound();
            } catch (e) {
                console.warn('Failed to play sound:', e);
            }
        }
    }

    // Geofencing-specific notifications
    notifyGeofenceEntry(clueName, distance) {
        const message = `You've reached ${clueName}! (${Math.round(distance)}m away)`;
        
        this.showToast(message, 'geofence', 5000, {
            title: 'Clue Unlocked! 🎯',
            icon: '🗝️'
        });

        this.playSound('entry');
        this.vibrate([100, 50, 100]);
    }

    notifyGeofenceExit(clueName) {
        const message = `You've left the ${clueName} area`;
        
        this.showToast(message, 'info', 3000, {
            title: 'Area Left',
            icon: '👋'
        });

        this.playSound('exit');
    }

    notifyAccuracyWarning(accuracy) {
        const message = `GPS accuracy is low (${Math.round(accuracy)}m). Try moving to an open area for better location detection.`;
        
        this.showToast(message, 'warning', 6000, {
            title: 'Location Accuracy Low',
            icon: '📡'
        });

        this.playSound('warning');
    }

    notifyLocationError(errorType) {
        let message = 'Location services unavailable.';
        let title = 'Location Error';

        switch (errorType) {
            case 'PERMISSION_DENIED':
                message = 'Location access denied. Enable location services to use geofencing features.';
                title = 'Location Access Denied';
                break;
            case 'POSITION_UNAVAILABLE':
                message = 'Unable to determine your location. Please check your GPS settings.';
                title = 'GPS Unavailable';
                break;
            case 'TIMEOUT':
                message = 'Location request timed out. Please try again.';
                title = 'Location Timeout';
                break;
        }

        this.showToast(message, 'error', 8000, {
            title: title,
            icon: '🚫'
        });

        this.playSound('error');
    }

    notifyProximityUpdate(closestClue) {
        if (!closestClue) return;

        const message = `${closestClue.title} is ${this.formatDistance(closestClue.distance)} away`;
        
        // Only show proximity updates occasionally to avoid spam
        if (Math.random() > 0.7) { // 30% chance
            this.showToast(message, 'info', 2000, {
                title: 'Nearby Clue',
                icon: '🧭'
            });
        }
    }

    notifyQuestComplete() {
        this.showToast('Congratulations! Quest completed! 🎉', 'success', 6000, {
            title: 'Quest Complete!',
            icon: '🏆'
        });

        this.playSound('success');
        this.vibrate([200, 100, 200, 100, 300]);
    }

    // Utility methods
    formatDistance(meters) {
        if (meters < 1000) {
            return `${Math.round(meters)}m`;
        } else {
            return `${(meters / 1000).toFixed(1)}km`;
        }
    }

    // Settings management
    setEnabled(enabled) {
        this.settings.enabled = enabled;
        this.saveSettings();
    }

    setSoundEnabled(enabled) {
        this.settings.sound = enabled;
        this.saveSettings();
        
        if (enabled && !this.audioContext) {
            this.enableAudio();
        }
    }

    setVibrationEnabled(enabled) {
        this.settings.vibration = enabled;
        this.saveSettings();
    }

    setVolume(volume) {
        this.settings.volume = Math.max(0, Math.min(1, volume));
        this.saveSettings();
    }

    getSettings() {
        return { ...this.settings };
    }

    // Clear all active notifications
    clearAll() {
        for (const toast of this.activeToasts) {
            this.removeToast(toast);
        }
    }

    // Test notification (for settings)
    testNotification() {
        this.showToast('This is a test notification with sound and vibration', 'info', 3000, {
            title: 'Test Notification'
        });
        
        this.playSound('entry');
        this.vibrate([100]);
    }
}

// Export for use in other modules
window.NotificationService = NotificationService;