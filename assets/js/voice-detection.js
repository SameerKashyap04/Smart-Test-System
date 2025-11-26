/**
 * Voice Detection System for Online Exam Platform
 * Monitors student voice activity during exams to detect potential cheating
 */

class VoiceDetection {
    constructor(options = {}) {
        this.isEnabled = options.enabled || false;
        this.sensitivity = options.sensitivity || 0.5; // 0.1 to 1.0
        this.detectionInterval = options.detectionInterval || 1000; // milliseconds
        this.violationThreshold = options.violationThreshold || 3; // number of detections before violation
        this.maxViolations = options.maxViolations || 5; // max violations before auto-submit
        
        this.audioContext = null;
        this.analyser = null;
        this.microphone = null;
        this.dataArray = null;
        this.isListening = false;
        this.violationCount = 0;
        this.detectionHistory = [];
        this.callbacks = {
            onViolation: null,
            onWarning: null,
            onMaxViolations: null
        };
        
        this.init();
    }
    
    init() {
        if (!this.isEnabled) return;
        
        // Check for browser support
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.warn('Voice detection not supported in this browser');
            return;
        }
        
        this.setupAudioContext();
    }
    
    async setupAudioContext() {
        try {
            // Get microphone access
            const stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                } 
            });
            
            // Create audio context
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.analyser = this.audioContext.createAnalyser();
            this.microphone = this.audioContext.createMediaStreamSource(stream);
            
            // Configure analyser
            this.analyser.fftSize = 256;
            this.analyser.smoothingTimeConstant = 0.8;
            this.microphone.connect(this.analyser);
            
            // Create data array for frequency analysis
            const bufferLength = this.analyser.frequencyBinCount;
            this.dataArray = new Uint8Array(bufferLength);
            
            console.log('Voice detection initialized successfully');
            
        } catch (error) {
            console.error('Error setting up voice detection:', error);
            this.handleError('Microphone access denied or not available');
        }
    }
    
    startListening() {
        if (!this.isEnabled || !this.analyser || this.isListening) return;
        
        this.isListening = true;
        this.detectionHistory = [];
        this.violationCount = 0;
        
        console.log('Voice detection started');
        this.detectVoice();
    }
    
    stopListening() {
        this.isListening = false;
        console.log('Voice detection stopped');
    }
    
    detectVoice() {
        if (!this.isListening || !this.analyser) return;
        
        // Get frequency data
        this.analyser.getByteFrequencyData(this.dataArray);
        
        // Calculate average volume
        const average = this.dataArray.reduce((sum, value) => sum + value, 0) / this.dataArray.length;
        
        // Normalize to 0-1 range
        const normalizedVolume = average / 255;
        
        // Check if voice is detected
        const isVoiceDetected = normalizedVolume > this.sensitivity;
        
        // Add to detection history
        this.detectionHistory.push({
            timestamp: Date.now(),
            volume: normalizedVolume,
            detected: isVoiceDetected
        });
        
        // Keep only last 10 seconds of history
        const tenSecondsAgo = Date.now() - 10000;
        this.detectionHistory = this.detectionHistory.filter(d => d.timestamp > tenSecondsAgo);
        
        // Count recent violations
        const recentViolations = this.detectionHistory.filter(d => d.detected).length;
        
        if (recentViolations >= this.violationThreshold) {
            this.handleViolation(normalizedVolume);
        }
        
        // Continue detection
        setTimeout(() => this.detectVoice(), this.detectionInterval);
    }
    
    handleViolation(volume) {
        this.violationCount++;
        
        const violation = {
            timestamp: Date.now(),
            volume: volume,
            violationNumber: this.violationCount
        };
        
        console.warn(`Voice violation detected #${this.violationCount}`, violation);
        
        // Trigger callbacks
        if (this.callbacks.onViolation) {
            this.callbacks.onViolation(violation);
        }
        
        // Check for warning threshold
        if (this.violationCount === 2) {
            if (this.callbacks.onWarning) {
                this.callbacks.onWarning('Warning: Voice detected multiple times. Please maintain silence during the exam.');
            }
        }
        
        // Check for max violations
        if (this.violationCount >= this.maxViolations) {
            if (this.callbacks.onMaxViolations) {
                this.callbacks.onMaxViolations('Maximum voice violations reached. Exam will be submitted automatically.');
            }
        }
        
        // Log violation to server
        this.logViolation(violation);
    }
    
    async logViolation(violation) {
        try {
            const response = await fetch('student/log_violation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'voice_detection',
                    description: `Voice detected - Volume: ${Math.round(violation.volume * 100)}%`,
                    violation_number: violation.violationNumber,
                    timestamp: violation.timestamp
                })
            });
            
            if (!response.ok) {
                console.error('Failed to log voice violation');
            }
        } catch (error) {
            console.error('Error logging voice violation:', error);
        }
    }
    
    handleError(message) {
        console.error('Voice detection error:', message);
        // You can show user-friendly error messages here
    }
    
    // Public methods
    setEnabled(enabled) {
        this.isEnabled = enabled;
        if (!enabled) {
            this.stopListening();
        }
    }
    
    setSensitivity(sensitivity) {
        this.sensitivity = Math.max(0.1, Math.min(1.0, sensitivity));
    }
    
    setViolationThreshold(threshold) {
        this.violationThreshold = Math.max(1, threshold);
    }
    
    setMaxViolations(max) {
        this.maxViolations = Math.max(1, max);
    }
    
    onViolation(callback) {
        this.callbacks.onViolation = callback;
    }
    
    onWarning(callback) {
        this.callbacks.onWarning = callback;
    }
    
    onMaxViolations(callback) {
        this.callbacks.onMaxViolations = callback;
    }
    
    getStatus() {
        return {
            enabled: this.isEnabled,
            listening: this.isListening,
            violationCount: this.violationCount,
            sensitivity: this.sensitivity,
            recentDetections: this.detectionHistory.length
        };
    }
    
    reset() {
        this.violationCount = 0;
        this.detectionHistory = [];
    }
}

// Voice Detection UI Component
class VoiceDetectionUI {
    constructor(containerId, voiceDetection) {
        this.container = document.getElementById(containerId);
        this.voiceDetection = voiceDetection;
        this.isVisible = false;
        
        this.createUI();
        this.setupEventListeners();
    }
    
    createUI() {
        if (!this.container) return;
        
        this.container.innerHTML = `
            <div class="voice-detection-panel" id="voice-detection-panel" style="display: none;">
                <div class="voice-detection-header">
                    <h6><i class="fas fa-microphone"></i> Voice Detection</h6>
                    <div class="voice-status">
                        <span class="status-indicator" id="voice-status-indicator"></span>
                        <span id="voice-status-text">Initializing...</span>
                    </div>
                </div>
                
                <div class="voice-detection-body">
                    <div class="voice-level-meter">
                        <div class="meter-label">Voice Level</div>
                        <div class="meter-bar">
                            <div class="meter-fill" id="voice-meter-fill"></div>
                        </div>
                        <div class="meter-value" id="voice-meter-value">0%</div>
                    </div>
                    
                    <div class="violation-counter">
                        <div class="violation-item">
                            <span class="violation-label">Violations:</span>
                            <span class="violation-count" id="violation-count">0</span>
                        </div>
                        <div class="violation-item">
                            <span class="violation-label">Max Allowed:</span>
                            <span class="violation-max" id="violation-max">5</span>
                        </div>
                    </div>
                    
                    <div class="voice-warnings" id="voice-warnings"></div>
                </div>
                
                <div class="voice-detection-controls">
                    <button class="btn btn-sm btn-outline-primary" id="voice-toggle-btn">
                        <i class="fas fa-microphone-slash"></i> Disable
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="voice-settings-btn">
                        <i class="fas fa-cog"></i> Settings
                    </button>
                </div>
            </div>
        `;
        
        this.addStyles();
    }
    
    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .voice-detection-panel {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 15px;
                margin: 10px 0;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .voice-detection-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #dee2e6;
            }
            
            .voice-detection-header h6 {
                margin: 0;
                color: #495057;
            }
            
            .voice-status {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .status-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: #6c757d;
                transition: background-color 0.3s ease;
            }
            
            .status-indicator.listening {
                background: #28a745;
                animation: pulse 1s infinite;
            }
            
            .status-indicator.warning {
                background: #ffc107;
            }
            
            .status-indicator.error {
                background: #dc3545;
            }
            
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
            
            .voice-level-meter {
                margin-bottom: 15px;
            }
            
            .meter-label {
                font-size: 12px;
                color: #6c757d;
                margin-bottom: 5px;
            }
            
            .meter-bar {
                width: 100%;
                height: 8px;
                background: #e9ecef;
                border-radius: 4px;
                overflow: hidden;
            }
            
            .meter-fill {
                height: 100%;
                background: linear-gradient(90deg, #28a745 0%, #ffc107 50%, #dc3545 100%);
                width: 0%;
                transition: width 0.1s ease;
            }
            
            .meter-value {
                font-size: 11px;
                color: #6c757d;
                text-align: right;
                margin-top: 2px;
            }
            
            .violation-counter {
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
            }
            
            .violation-item {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .violation-label {
                font-size: 11px;
                color: #6c757d;
            }
            
            .violation-count, .violation-max {
                font-weight: bold;
                font-size: 16px;
            }
            
            .violation-count {
                color: #dc3545;
            }
            
            .violation-max {
                color: #6c757d;
            }
            
            .voice-warnings {
                min-height: 20px;
                margin-bottom: 15px;
            }
            
            .warning-message {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                margin-bottom: 5px;
            }
            
            .voice-detection-controls {
                display: flex;
                gap: 8px;
            }
            
            .voice-detection-controls .btn {
                flex: 1;
                font-size: 12px;
            }
        `;
        document.head.appendChild(style);
    }
    
    setupEventListeners() {
        // Toggle button
        const toggleBtn = document.getElementById('voice-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                this.toggleVoiceDetection();
            });
        }
        
        // Settings button
        const settingsBtn = document.getElementById('voice-settings-btn');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', () => {
                this.showSettings();
            });
        }
        
        // Setup voice detection callbacks
        this.voiceDetection.onViolation((violation) => {
            this.updateViolationCount(violation.violationNumber);
            this.showWarning(`Voice detected! Violation #${violation.violationNumber}`);
        });
        
        this.voiceDetection.onWarning((message) => {
            this.showWarning(message, 'warning');
        });
        
        this.voiceDetection.onMaxViolations((message) => {
            this.showWarning(message, 'error');
        });
    }
    
    show() {
        this.isVisible = true;
        const panel = document.getElementById('voice-detection-panel');
        if (panel) {
            panel.style.display = 'block';
        }
    }
    
    hide() {
        this.isVisible = false;
        const panel = document.getElementById('voice-detection-panel');
        if (panel) {
            panel.style.display = 'none';
        }
    }
    
    updateStatus(status, text) {
        const indicator = document.getElementById('voice-status-indicator');
        const statusText = document.getElementById('voice-status-text');
        
        if (indicator) {
            indicator.className = `status-indicator ${status}`;
        }
        
        if (statusText) {
            statusText.textContent = text;
        }
    }
    
    updateMeter(level) {
        const meterFill = document.getElementById('voice-meter-fill');
        const meterValue = document.getElementById('voice-meter-value');
        
        if (meterFill) {
            meterFill.style.width = `${level * 100}%`;
        }
        
        if (meterValue) {
            meterValue.textContent = `${Math.round(level * 100)}%`;
        }
    }
    
    updateViolationCount(count) {
        const countElement = document.getElementById('violation-count');
        if (countElement) {
            countElement.textContent = count;
        }
    }
    
    showWarning(message, type = 'info') {
        const warningsContainer = document.getElementById('voice-warnings');
        if (!warningsContainer) return;
        
        const warningDiv = document.createElement('div');
        warningDiv.className = `warning-message ${type}`;
        warningDiv.textContent = message;
        
        warningsContainer.appendChild(warningDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (warningDiv.parentNode) {
                warningDiv.parentNode.removeChild(warningDiv);
            }
        }, 5000);
    }
    
    toggleVoiceDetection() {
        const toggleBtn = document.getElementById('voice-toggle-btn');
        
        if (this.voiceDetection.isEnabled) {
            this.voiceDetection.setEnabled(false);
            this.voiceDetection.stopListening();
            this.updateStatus('error', 'Disabled');
            if (toggleBtn) {
                toggleBtn.innerHTML = '<i class="fas fa-microphone"></i> Enable';
            }
        } else {
            this.voiceDetection.setEnabled(true);
            this.voiceDetection.startListening();
            this.updateStatus('listening', 'Listening...');
            if (toggleBtn) {
                toggleBtn.innerHTML = '<i class="fas fa-microphone-slash"></i> Disable';
            }
        }
    }
    
    showSettings() {
        // Simple settings modal
        const sensitivity = prompt('Enter sensitivity (0.1 - 1.0):', this.voiceDetection.sensitivity);
        if (sensitivity !== null) {
            const value = parseFloat(sensitivity);
            if (value >= 0.1 && value <= 1.0) {
                this.voiceDetection.setSensitivity(value);
            }
        }
    }
}

// Export for use in other files
window.VoiceDetection = VoiceDetection;
window.VoiceDetectionUI = VoiceDetectionUI;
