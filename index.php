<?php
// Set session cookie parameters to allow cross-site/HTTP usage if needed
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', // Current domain
    'secure' => false, // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax' // Or 'None' if Secure is true
]);
session_start();
// Login check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Generate a new session ID if one doesn't exist for this user session or if requested
if (!isset($_SESSION['photobooth_session_id'])) {
    $newSessionId = 'session_' . date('Ymd_His') . '_' . uniqid();
    $_SESSION['photobooth_session_id'] = $newSessionId;
    // Record this as the only active session
    file_put_contents(__DIR__ . '/active_session.txt', $newSessionId);
    if (file_exists(__DIR__ . '/active_session.txt')) {
        chmod(__DIR__ . '/active_session.txt', 0666);
    }
}
$sessionId = $_SESSION['photobooth_session_id'];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contactless QR Photobooth</title>
    <link rel="stylesheet" href="style.css" />
    <script>
        var sessionId = "<?php echo $sessionId; ?>";
        var currentTheme = "acad"; // Default theme

        function setTheme(theme) {
            currentTheme = theme;
            
            // Update UI buttons
            document.querySelectorAll('.theme-btn').forEach(btn => {
                btn.classList.remove('active');
                if(btn.dataset.theme === theme) btn.classList.add('active');
            });

            // Update sidebar text/styles based on theme
            const titles = document.querySelectorAll('.theme-title');
            const subtitles = document.querySelectorAll('.theme-subtitle');
            
            if (theme === 'valentines') {
                titles.forEach(el => {
                    el.innerHTML = "HAPPY<br>HEARTS";
                    el.style.color = "#ff4d6d";
                    el.style.fontFamily = "'Fredoka One', cursive"; // Fallback to installed font if needed
                });
                subtitles.forEach(el => {
                    el.style.color = "#ff0054";
                });
                
                // Update sidebar slots background
                document.querySelectorAll('.photo-slot').forEach(slot => {
                    slot.classList.add('valentines');
                });
            } else {
                // Default: Acad Fest
                titles.forEach(el => {
                    el.innerHTML = "ACAD FEST";
                    el.style.color = ""; // Reset to CSS default
                    el.style.fontFamily = "";
                });
                subtitles.forEach(el => {
                    el.style.color = "";
                });
                
                // Reset sidebar slots
                document.querySelectorAll('.photo-slot').forEach(slot => {
                    slot.classList.remove('valentines');
                    slot.style.backgroundColor = ""; // Clear inline styles if any
                    slot.style.borderColor = "";
                });
            }
        }
        
        // Global fallback functions
        function reloadSession() {
            if (confirm('Start a new session? This will create a new session ID.')) {
                window.location.href = 'new_session.php';
            }
        }
        
        function triggerFinish() {
            console.log('Trigger finish called');
            const btn = document.getElementById('finishBtn');
            if(btn) btn.click(); // Trigger the listener if attached
        }
    </script>
  </head>
  <body>
    <img class="logo" src="aclc-logo.webp" alt="logo" />
    <img class="aclc" src="ACLCLOGO.png" alt="logo" />
    <div class="container">
      <div class="session-info">
        <strong>Session ID:</strong> <?php echo $sessionId; ?>
      </div>

      <div class="theme-selection" style="text-align: center; margin-bottom: 15px;">
        <label style="color: white; font-weight: bold; margin-right: 10px;">Select Theme:</label>
        <div class="btn-group" role="group" aria-label="Theme Selection">
            <button type="button" class="btn theme-btn active" data-theme="acad" onclick="setTheme('acad')">ACAD FEST</button>
            <button type="button" class="btn theme-btn" data-theme="valentines" onclick="setTheme('valentines')">VALENTINES</button>
        </div>
      </div>

      <div class="camera-select">
        <!-- <label for="cameraSelect">Select Camera:</label> -->
        <select id="cameraSelect">
          <option value="">Loading cameras...</option>
        </select>
        <button id="refreshCamBtn" class="btn" style="padding: 5px 10px; font-size: 14px; margin-left: 5px;">🔄</button>
      </div>

      <a href="logout.php" class="btn logout-btn" style="position: fixed; top: 20px; right: 20px; z-index: 1000; padding: 10px 20px; background: #c0392b;">Logout</a>

      <div class="camera-container">
        <video id="video" autoplay muted playsinline></video>
        <canvas id="canvas"></canvas>
        <!-- Timer Overlay -->
        <div id="countdown" class="countdown-overlay" style="display: none;">3</div>
      </div>
      <div class="photo-sidebar">
        <!-- Grid for capturing -->
        <div id="slotsGrid" class="slots-grid">
            <!-- Slot 1 -->
            <div class="photo-slot" id="slot1">
                <div class="slot-inner">
                    <img id="photoSlot1" alt="Photo 1" />
                </div>
                <div class="footer">
                    <h1 class="main-title theme-title">ACAD FEST</h1>
                    <div class="sub-title theme-subtitle">TECHNO STRIFE</div>
                </div>
            </div>
            <!-- Slot 2 -->
            <div class="photo-slot" id="slot2">
                <div class="slot-inner">
                    <img id="photoSlot2" alt="Photo 2" />
                </div>
                <div class="footer">
                    <h1 class="main-title theme-title">ACAD FEST</h1>
                    <div class="sub-title theme-subtitle">TECHNO STRIFE</div>
                </div>
            </div>
            <!-- Slot 3 -->
            <div class="photo-slot" id="slot3">
                <div class="slot-inner">
                    <img id="photoSlot3" alt="Photo 3" />
                </div>
                <div class="footer">
                    <h1 class="main-title theme-title">ACAD FEST</h1>
                    <div class="sub-title theme-subtitle">TECHNO STRIFE</div>
                </div>
            </div>
            <!-- Slot 4 -->
            <div class="photo-slot" id="slot4">
                <div class="slot-inner">
                    <img id="photoSlot4" alt="Photo 4" />
                </div>
                <div class="footer">
                    <h1 class="main-title theme-title">ACAD FEST</h1>
                    <div class="sub-title theme-subtitle">TECHNO STRIFE</div>
                </div>
            </div>
        </div>
      </div>
        
      <!-- Final Strip Display (Separate Fixed Container) -->
      <div id="stripContainer" class="strip-sidebar" style="display: none;">
          <div class="photo-slot" style="height: auto; min-height: 400px; padding: 5px; align-items: flex-start;">
              <img id="finalStrip" src="" alt="Photo Strip" style="width: 100%; height: auto; display: block; border-radius: 8px;">
          </div>
      </div>

      <!-- Removed Popup Overlay -->

      <div class="controls">
        <button id="startBtn" class="btn">Start Camera</button>
        <button id="captureBtn" class="btn" disabled>Capture Photo</button>
        <button id="finishBtn" class="btn-qr" onclick="finishSession()">
          Finish & Generate QR
        </button>
      </div>

      <!-- <div class="photo-count">
            Photos captured: <span id="photoCount">0</span>
        </div> -->

      <div id="status" class="status" style="display: none"></div>

      <div id="qrSection" class="qr-section">
        <h2>📱 Scan QR Code to Download Photos</h2>
        <div class="qr-code" id="qrCode"></div>
        <p>
          <a href="#" id="downloadLink" class="download-link" target="_blank">
            Or click here to download directly
          </a>
        </p>
      </div>

      <div class="admin-controls">
        <button id="timerToggleBtn" class="btn" style="background: #e67e22;">⏱️ Timer: OFF</button>
        <button id="newSessionBtn" class="btn" onclick="reloadSession()">Start New Session</button>
      </div>
    </div>

    <!-- QR Popup Overlay -->
    <div id="qrPopupOverlay" class="qr-popup-overlay" style="display: none">
      <button id="printBtnCorner" class="print-btn-corner">
        🖨️ Print Photos
      </button>
      <div id="printerStatus" class="printer-status"></div>
      <div id="qrPopupContainer" class="qr-popup-container">
        <button class="close-popup-x" onclick="hideQRPopup()">×</button>
        <h2 class="qr-popup-title">📱 Scan to Download Your Photos!</h2>
        <div id="qrPopupCode" class="qr-popup-code"></div>
        <div class="qr-popup-buttons">
          <button id="downloadStripBtn" class="popup-btn download-btn" style="background-color: #3498db;">
            ⬇️ Download Strip
          </button>
          <button id="exitPopupBtn" class="popup-btn exit-btn">✕ Close</button>
        </div>
      </div>
    </div>

    <script src="script.js"></script>
  </body>
</html>
