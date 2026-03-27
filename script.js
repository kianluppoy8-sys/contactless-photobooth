let video = document.getElementById('video');
let canvas = document.getElementById('canvas');
let ctx = canvas.getContext('2d');
let currentStream = null;
let photoCount = 0;
// let sessionId = '...'; // Defined in PHP
let lastCapturedPhoto = null;
let availablePrinters = [];
let selectedPrinter = null;

// DOM elements
const startBtn = document.getElementById('startBtn');
const captureBtn = document.getElementById('captureBtn');
const finishBtn = document.getElementById('finishBtn');
const cameraSelect = document.getElementById('cameraSelect');
const photoCountSpan = document.getElementById('photoCount');
const statusDiv = document.getElementById('status');
const qrSection = document.getElementById('qrSection');
const downloadLink = document.getElementById('downloadLink');
const newSessionBtn = document.getElementById('newSessionBtn');

// New popup elements
const qrPopupOverlay = document.getElementById('qrPopupOverlay');
const qrPopupContainer = document.getElementById('qrPopupContainer');
const qrPopupCode = document.getElementById('qrPopupCode');
const printBtnCorner = document.getElementById('printBtnCorner');
const printPhotosBtn = document.getElementById('printPhotosBtn');
const exitPopupBtn = document.getElementById('exitPopupBtn');
const printerStatus = document.getElementById('printerStatus');
const photoSlots = [
    document.getElementById('photoSlot1'),
    document.getElementById('photoSlot2'),
    document.getElementById('photoSlot3'),
    document.getElementById('photoSlot4')
];

// State
// let stream = null; // Removed: Duplicate of currentStream/unused
// let photoCount = 0; // Removed: Duplicate of line 5
// let photoCount = 0; // Removed: Duplicate of line 5
let MAX_PHOTOS = 4; // Default to 4
let selectedCameraId = localStorage.getItem('selectedCameraId') || null;

// Expose function to global scope (kept for backward compatibility but modified)
window.setPhotoLimit = function (n) {
    // Disabled - specific request to keep it 4
    console.log('Mode switching disabled. Default to 4.');
};
document.addEventListener('DOMContentLoaded', function () {
    // Frame/Border fallback logic removed
    getCameras();
    detectPrinters();
    setupPopupEventListeners();

    // Trigger auto-cleanup every 60 seconds
    setInterval(cleanupSessions, 60000);
    // Also run once on load
    cleanupSessions();

    // Keyboard Shortcuts
    document.addEventListener('keydown', function (e) {
        if (e.code === 'Space' || e.key === ' ') {
            // Prevent scrolling
            e.preventDefault();

            // Check if capture button is active/clickable
            if (captureBtn && !captureBtn.disabled && captureBtn.offsetParent !== null) {
                captureBtn.click();
            }
        }
    });
});

function cleanupSessions() {
    fetch('cleanup.php')
        .then(response => response.json())
        .then(data => {
            if (data.deleted > 0) {
                console.log(`Cleaned up ${data.deleted} old sessions.`);
            }
        })
        .catch(err => console.error('Cleanup error:', err));
}

// Get available cameras
// Get available cameras
async function getCameras() {
    console.log('getCameras() called');
    if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
        console.log('Media API not supported');
        showStatus('Camera API not supported. Please use HTTPS or localhost.', 'error');
        cameraSelect.innerHTML = '<option value="">Camera API unavailable</option>';
        return;
    }

    // Set a timeout to warn if it takes too long (e.g. permission prompt ignored)
    const loadTimeout = setTimeout(() => {
        if (cameraSelect.options[0] && cameraSelect.options[0].text === 'Loading cameras...') {
            cameraSelect.innerHTML = '<option value="">Check permission prompt...</option>';
            showStatus('Please allow camera access in the browser popup.', 'warning');
        }
    }, 3000);

    try {
        console.log('Requesting permission...');
        // Request permission first to ensure we get labels and all devices
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        console.log('Permission granted');

        // Stop the stream immediately, strict permission request
        stream.getTracks().forEach(track => track.stop());

        console.log('Enumerating devices...');
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoDevices = devices.filter(device => device.kind === 'videoinput');
        console.log('Cameras found:', videoDevices.length);

        clearTimeout(loadTimeout);
        // Add default option
        cameraSelect.innerHTML = '<option value="">-- Select Camera --</option>';

        if (videoDevices.length === 0) {
            cameraSelect.innerHTML = '<option value="">No cameras found</option>';
            return;
        }

        // Check for stored camera
        let storedCameraId = localStorage.getItem('photobooth_camera_id');
        let cameraFound = false;

        videoDevices.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.text = device.label || `Camera ${index + 1}`;
            cameraSelect.appendChild(option);

            if (storedCameraId && device.deviceId === storedCameraId) {
                cameraFound = true;
            }
        });

        // Auto-select stored camera if it exists
        if (cameraFound) {
            cameraSelect.value = storedCameraId;
            // Auto-start camera
            setTimeout(() => {
                if (startBtn && !startBtn.disabled) {
                    startBtn.click();
                }
            }, 500);
        }

        // Removed auto-select default (first item) logic
    } catch (error) {
        clearTimeout(loadTimeout);
        console.error('Error getting cameras:', error);
        // If permission denied, show specific error
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            cameraSelect.innerHTML = '<option value="">Permission denied</option>';
            showStatus('Camera permission denied. Please allow access.', 'error');
        } else {
            cameraSelect.innerHTML = '<option value="">Error loading cameras</option>';
            showStatus('Error accessing cameras: ' + error.message, 'error');
        }
    }
}

// Start camera
startBtn.addEventListener('click', async function () {
    try {
        const selectedCamera = cameraSelect.value;

        if (!selectedCamera) {
            showStatus('Please select a camera from the list first.', 'warning');
            return;
        }

        // Save selected camera
        localStorage.setItem('photobooth_camera_id', selectedCamera);

        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
        }

        const constraints = {
            video: {
                deviceId: selectedCamera ? { exact: selectedCamera } : undefined
                // Removed ideal width/height to maximize compatibility
            }
        };

        try {
            currentStream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch (err) {
            // First fallback: Try without exact deviceId if it failed
            console.warn('Camera start failed, trying fallback...', err);
            if (selectedCamera) {
                const fallbackConstraints = { video: true };
                currentStream = await navigator.mediaDevices.getUserMedia(fallbackConstraints);
            } else {
                throw err;
            }
        }

        video.srcObject = currentStream;
        await video.play(); // Explicitly play

        startBtn.disabled = true;
        captureBtn.disabled = false;
        cameraSelect.disabled = true;

        showStatus('Camera started successfully!', 'success');
    } catch (error) {
        console.error('Error starting camera:', error);
        let msg = 'Error starting camera: ' + error.message;
        if (error.name === 'NotReadableError') {
            msg = 'Camera is in use by another application.';
        } else if (error.name === 'OverconstrainedError') {
            msg = 'Camera does not support requested resolution.';
        } else if (error.name === 'NotAllowedError') {
            msg = 'Camera permission denied.';
        }
        showStatus(msg, 'error');
    }
});

// Capture photo
captureBtn.addEventListener('click', function () {
    if (photoCount >= MAX_PHOTOS) {
        showStatus('Maximum of ' + MAX_PHOTOS + ' photos reached', 'error');
        captureBtn.disabled = true;
        return;
    }
    if (!currentStream) {
        showStatus('Camera not started', 'error');
        return;
    }

    if (timerEnabled) {
        startCountdownAndCapture();
    } else {
        performCapture();
    }
});

// Timer Logic
let timerEnabled = false;
const timerToggleBtn = document.getElementById('timerToggleBtn');
const countdownEl = document.getElementById('countdown');

if (timerToggleBtn) {
    timerToggleBtn.addEventListener('click', function () {
        timerEnabled = !timerEnabled;
        if (timerEnabled) {
            timerToggleBtn.textContent = '⏱️ Timer: 3s';
            timerToggleBtn.style.background = '#27ae60'; // Green for ON
            showStatus('Timer enabled (3 seconds)', 'success');
        } else {
            timerToggleBtn.textContent = '⏱️ Timer: OFF';
            timerToggleBtn.style.background = '#e67e22'; // Orange/Default
            showStatus('Timer disabled', 'warning');
        }
        // Release focus so spacebar doesn't trigger toggle
        this.blur();
    });
}

function startCountdownAndCapture() {
    console.log('Starting countdown...');
    // Disable capture during countdown
    captureBtn.disabled = true;
    let count = 3;

    if (countdownEl) {
        countdownEl.style.display = 'flex'; // Use flex to center
        countdownEl.textContent = count;
    }

    const interval = setInterval(() => {
        count--;
        if (count > 0) {
            if (countdownEl) countdownEl.textContent = count;
        } else {
            // Time's up
            clearInterval(interval);
            if (countdownEl) countdownEl.style.display = 'none';
            performCapture();
            // Re-enable button if not maxed out
            if (photoCount < MAX_PHOTOS) {
                captureBtn.disabled = false;
            }
        }
    }, 1000);
}

function performCapture() {
    // Set canvas size to match video
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // Draw current video frame to canvas
    ctx.drawImage(video, 0, 0);

    // Store the captured photo as data URL for background use
    lastCapturedPhoto = canvas.toDataURL('image/jpeg', 0.9);

    // Convert to blob and upload
    canvas.toBlob(function (blob) {
        uploadPhoto(blob);
    }, 'image/jpeg', 0.9);
}

// Upload photo
// Upload photo
function uploadPhoto(blob) {
    const formData = new FormData();
    formData.append('photo', blob, `photo_${String(photoCount + 1).padStart(3, '0')}.jpg`);
    formData.append('session_id', sessionId);

    // Add theme
    if (typeof currentTheme !== 'undefined') {
        formData.append('theme', currentTheme);
    } else {
        formData.append('theme', 'acad');
    }

    fetch('capture.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                photoCount++;
                if (photoCountSpan) {
                    photoCountSpan.textContent = photoCount;
                }

                // Disable theme selection after first photo
                if (photoCount > 0) {
                    document.querySelectorAll('.theme-btn').forEach(btn => {
                        btn.disabled = true;
                        btn.style.opacity = '0.5';
                        btn.style.cursor = 'not-allowed';
                    });
                }

                // Store the server-processed photo URL (with border applied)
                lastCapturedPhoto = 'photos/' + sessionId + '/' + data.filename + '?cb=' + Date.now();
                if (photoSlots[photoCount - 1]) {
                    photoSlots[photoCount - 1].src = lastCapturedPhoto;
                }

                // CHECK FOR MAX PHOTOS -> GENERATE STRIP
                if (photoCount === MAX_PHOTOS) {
                    captureBtn.disabled = true;
                    showStatus('Generating photo strip...', 'success');

                    // Call merge script
                    generateStrip();
                } else if (photoCount < MAX_PHOTOS) {
                    showStatus(`Photo ${photoCount} captured successfully!`, 'success');
                }
            } else {
                showStatus('Error capturing photo: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error uploading photo:', error);
            showStatus('Error uploading photo: ' + error.message, 'error');
        });
}

// DOM Elements for Sidebar View
const slotsGrid = document.getElementById('slotsGrid');
const stripContainer = document.getElementById('stripContainer');
const finalStrip = document.getElementById('finalStrip');

function generateStrip() {
    const formData = new FormData();
    formData.append('session_id', sessionId);
    formData.append('limit', MAX_PHOTOS);

    // Add selected theme
    if (typeof currentTheme !== 'undefined') {
        formData.append('theme', currentTheme);
    } else {
        formData.append('theme', 'acad'); // Default
    }

    fetch('merge_photos.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show Strip and KEEP slots visible
                if (slotsGrid) slotsGrid.style.display = 'grid'; // Ensure slots stay visible

                if (stripContainer) {
                    stripContainer.style.display = 'block';
                    if (finalStrip) {
                        finalStrip.src = data.url || "" + '?cb=' + Date.now();
                    }
                }

                showStatus('Photo strip generated!', 'success');
            } else {
                showStatus('Error generating strip: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error merging photos:', error);
            showStatus('Error generating strip.', 'error');
        })
        .finally(() => {
            console.log('Enabling Finish Button');
            if (finishBtn) {
                finishBtn.disabled = false;
                finishBtn.removeAttribute('disabled'); // Force remove attribute
                finishBtn.style.cursor = 'pointer'; // Visual cue
            } else {
                console.error('Finish button not found in DOM');
            }
        });
}

// Reset UI helper
function resetSidebar() {
    if (slotsGrid) slotsGrid.style.display = 'grid';
    if (stripContainer) stripContainer.style.display = 'none';
    if (finalStrip) finalStrip.src = '';
    // Clear slots
    photoSlots.forEach(slot => {
        if (slot) slot.src = '';
    });
}

// Obsolete listeners removed

// Finish session and generate QR
window.finishSession = function () {
    console.log('Global finishSession called. Photo count:', photoCount);

    if (photoCount < MAX_PHOTOS) {
        showStatus(`Please take all ${MAX_PHOTOS} photos first!`, 'warning');
        return;
    }

    // Stop camera
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }

    showStatus('Generating QR code...', 'success');

    // Generate QR code
    fetch('generate_qr.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'session_id=' + encodeURIComponent(sessionId)
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show popup instead of inline QR section
                showQRPopup(data.qr_image, data.download_url);

                // Disable capture controls
                captureBtn.disabled = true;
                finishBtn.disabled = true;

                showStatus('QR Code generated! Session completed.', 'success');
            } else {
                showStatus('Error generating QR code: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error generating QR:', error);
            showStatus('Error generating QR code: ' + error.message, 'error');
        });
};

finishBtn.addEventListener('click', function () {
    window.finishSession();
});

// New session
newSessionBtn.addEventListener('click', function () {
    if (confirm('Start a new session? This will create a new session ID.')) {
        // Hide popup if open
        hideQRPopup();
        resetSidebar(); // Reset the sidebar view
        window.location.href = 'new_session.php';
    }
});

// Refresh cameras
const refreshCamBtn = document.getElementById('refreshCamBtn');
if (refreshCamBtn) {
    refreshCamBtn.addEventListener('click', function () {
        cameraSelect.innerHTML = '<option value="">Refreshing...</option>';
        getCameras();
    });
}

// Show status message
function showStatus(message, type) {
    statusDiv.textContent = message;
    statusDiv.className = 'status ' + type;
    statusDiv.style.display = 'block';

    setTimeout(() => {
        statusDiv.style.display = 'none';
    }, 5000);
}

// Setup popup event listeners
function setupPopupEventListeners() {
    // Print button in corner (kept for admin/future use if needed, or can be hidden)
    if (printBtnCorner) {
        printBtnCorner.style.display = 'none'; // Hide corner print button
    }

    // Download Strip button
    const downloadStripBtn = document.getElementById('downloadStripBtn');
    if (downloadStripBtn) {
        downloadStripBtn.addEventListener('click', function () {
            // Trigger download of the strip
            if (finalStrip && finalStrip.src) {
                const link = document.createElement('a');
                link.href = finalStrip.src;
                link.download = `photo_strip_${sessionId}.jpg`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                showStatus('Download started!', 'success');
            } else {
                showStatus('No strip generated to download.', 'error');
            }
        });
    }

    // Exit popup button
    exitPopupBtn.addEventListener('click', function () {
        hideQRPopup();
    });

    // Close popup when clicking outside
    qrPopupOverlay.addEventListener('click', function (e) {
        if (e.target === qrPopupOverlay) {
            hideQRPopup();
        }
    });

    // Escape key to close popup
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && qrPopupOverlay.style.display === 'flex') {
            hideQRPopup();
        }
    });
}

// Show QR popup
function showQRPopup(qrImageUrl, downloadUrl) {
    // Set the QR code
    if (qrPopupCode) qrPopupCode.innerHTML = `<img src="${qrImageUrl}" alt="QR Code">`;

    // Set photo background if available

    // Store download URL for printing
    qrPopupContainer.dataset.downloadUrl = downloadUrl;

    // Show popup
    qrPopupOverlay.style.display = 'flex';

    // Update printer status
    updatePrinterStatus();
}

// Hide QR popup
window.hideQRPopup = function () {
    const overlay = document.getElementById('qrPopupOverlay');
    if (overlay) overlay.style.display = 'none';

    // Re-enable controls
    const finishBtn = document.getElementById('finishBtn');
    const captureBtn = document.getElementById('captureBtn');
    if (finishBtn) finishBtn.disabled = false;
    // Note: captureBtn stays disabled if 4 photos are taken, this is correct.
    // But if we want to allow retaking, that's a different logic. 
    // For now, assuming user just wants to re-open QR or maybe take more if < 4? 
    // Actually, logic says if photoCount >= 4, capture should stay disabled.
    if (photoCount < MAX_PHOTOS && captureBtn) {
        captureBtn.disabled = false;
    }
};

// Detect available printers
async function detectPrinters() {
    try {
        // Check if Web Print API is available (experimental)
        if ('navigator' in window && 'printing' in navigator) {
            const printers = await navigator.printing.getPrinters();
            availablePrinters = printers;
            if (printers.length > 0) {
                selectedPrinter = printers[0];
            }
        } else {
            // Fallback: Check if browser supports printing
            availablePrinters = [{ name: 'Default Printer', id: 'default' }];
            selectedPrinter = availablePrinters[0];
        }
    } catch (error) {
        console.log('Printer detection not supported:', error);
        // Still allow printing through browser's print dialog
        availablePrinters = [{ name: 'Browser Print', id: 'browser' }];
        selectedPrinter = availablePrinters[0];
    }

    updatePrinterStatus();
}

// Update printer status display
function updatePrinterStatus() {
    if (!printerStatus) return;

    if (availablePrinters && availablePrinters.length > 0) {
        printerStatus.textContent = `📄 ${selectedPrinter ? selectedPrinter.name : "Printer"}`;
        printerStatus.className = "printer-status connected";
        if (printBtnCorner) printBtnCorner.disabled = false;
        if (printPhotosBtn) printPhotosBtn.disabled = false;
    } else {
        printerStatus.textContent = "No Printer";
        printerStatus.className = "printer-status disconnected";
        if (printBtnCorner) printBtnCorner.disabled = true;
        if (printPhotosBtn) printPhotosBtn.disabled = true;
    }
}

// Persist Theme on Load
document.addEventListener('DOMContentLoaded', function () {
    const savedTheme = localStorage.getItem('photobooth_theme');
    if (savedTheme) {
        setTheme(savedTheme);
    }
});


// Print photos function
async function printPhotos() {
    if (!selectedPrinter) {
        showStatus('No printer available', 'error');
        return;
    }

    try {
        showStatus('Preparing photos for printing...', 'success');

        // Create a new window for printing
        const printWindow = window.open('', '_blank');

        // Get session photos for printing
        const response = await fetch('get_session_photos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'session_id=' + encodeURIComponent(sessionId)
        });

        const data = await response.json();

        if (data.success) {
            // Create print-friendly HTML
            let printHTML = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Photobooth Photos - ${sessionId}</title>
                            <style>
                                @media print {
                                    body { margin: 0; padding: 0; }
                                    .photo-page { 
                                        page-break-after: always; 
                                        width: 100%; 
                                        height: 100vh; 
                                        display: flex; 
                                        justify-content: center; 
                                        align-items: center;
                                    }
                                    .photo-page:last-child { page-break-after: avoid; }
                                    .photo-page img { 
                                        max-width: 100%; 
                                        max-height: 100%; 
                                        object-fit: contain;
                                    }
                                }
                                body { 
                                    font-family: Arial, sans-serif; 
                                    text-align: center; 
                                    margin: 20px;
                                }
                                .photo-page { 
                                    margin: 20px 0; 
                                    border: 1px solid #ddd; 
                                    padding: 10px;
                                }
                                .photo-page img { 
                                    max-width: 100%; 
                                    height: auto;
                                }
                            </style>
                        </head>
                        <body>
                            <h1>Photobooth Session: ${sessionId}</h1>
                    `;

            // Add each photo
            data.photos.forEach((photo, index) => {
                printHTML += `
                            <div class="photo-page">
                                <img src="${photo.url}" alt="Photo ${index + 1}">
                                <p>Photo ${index + 1} - ${photo.filename}</p>
                            </div>
                        `;
            });

            // Add long format if available
            if (data.long_format) {
                printHTML += `
                            <div class="photo-page">
                                <img src="${data.long_format.url}" alt="Long Format">
                                <p>Long Format - ${data.long_format.filename}</p>
                            </div>
                        `;
            }

            printHTML += `
                        </body>
                        <scr` + `ipt>
                            window.onload = function() {
                                setTimeout(function() {
                                    window.print();
                                    setTimeout(function() {
                                        window.close();
                                    }, 1000);
                                }, 500);
                            };
                        </scr` + `ipt>
                        </html>
                    `;

            printWindow.document.write(printHTML);
            printWindow.document.close();

            showStatus('Photos sent to printer!', 'success');
        } else {
            showStatus('Error preparing photos for printing: ' + data.error, 'error');
        }
    } catch (error) {
        console.error('Print error:', error);
        showStatus('Error printing photos: ' + error.message, 'error');
    }
}

// Handle page unload
window.addEventListener('beforeunload', function () {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
    }
});
