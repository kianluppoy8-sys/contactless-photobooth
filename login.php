<?php
session_start();

// Hardcoded Credentials
$ADMIN_USER = 'ictadmin';
$ADMIN_PASS = '1d693edb28';
$ADMIN_QR_SECRET = 'CYBER_SOCIETY_ADMIN_LOGIN_2026'; // This is what the QR should contain

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // QR Login Handler
    if (isset($_POST['qr_login'])) {
        if ($_POST['qr_login'] === $ADMIN_QR_SECRET) {
            $_SESSION['loggedin'] = true;
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid QR Code']);
            exit;
        }
    }

    // Normal Login Handler
    $usn = $_POST['usn'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($usn === $ADMIN_USER && $pass === $ADMIN_PASS) {
        $_SESSION['loggedin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photobooth Login</title>
    <link rel="stylesheet" href="login.css">
    <script src="html5-qrcode.min.js"></script>
    <style>
        .error { color: red; text-align: center; margin-bottom: 15px; font-weight: bold; background: rgba(255,255,255,0.8); padding: 5px; border-radius: 5px; }
        
        /* Layout overrides */
        .card-layout {
            position: absolute !important;
            top: 50%;
            left: 50px;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 45%; 
            padding: 0 !important;
            margin: 0 !important;
            gap: 10px !important;
        }

        .login-logo {
            width: 650px; 
            height: auto;
            margin-top: -20px; 
            filter: drop-shadow(0 0 20px rgba(255,255,255,0.7));
            display: block;
        }

        .txt, .txt1 {
            position: static !important;
            translate: none !important;
            margin: 2px 0 !important;
            text-align: center;
            width: auto !important;
        }

        .txt { 
            font-size: 36px; 
            font-weight: 800; 
            color: white; 
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
            line-height: 1.1;
        }

        .txt1 { 
            font-size: 16px; 
            color: #00c8ff; 
            font-weight: 600;
            letter-spacing: 2px;
            margin-bottom: 5px !important;
            z-index: 10;
        }

        /* QR Login Styles */
        #qrLoginContainer {
            display: none;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        #reader {
            width: 300px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 15px;
            border: 2px solid #00c8ff;
        }
        .qr-btn {
            background: #27ae60;
            margin-top: 10px;
            font-size: 14px;
        }
        .cancel-btn {
            background: #e74c3c;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <img class="bk" src="background.png" alt="bk">
    
 <div class="main-container">
     <div class="left-container" style="position: relative; height: 100%;">
         <div class="card-layout">
                <h1 class="txt">CYBER SOCIETY</h1>
                <h3 class="txt1">ACLC COLLEGE OF TAYTAY</h3>
                <img src="cybersociety_logo.png" alt="Cyber Society Logo" class="login-logo">
            </div>

         <div class="login-box">
              
              <!-- Traditional Login Form -->
              <form method="post" action="" id="loginForm">
                 <h2 class="form-title">Login</h2>
 
                 <?php if($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                 <?php endif; ?>

                  <div class="input-box">
                     <label for="username">Username</label>
                     <input type="text" name="usn" placeholder="Enter Username" required>
                 </div>

                 <div class="input-box">
                     <label for="password">Password</label>
                     <input type="password" name="password" placeholder="Enter Password" required>
                 </div>

                  <div class="remember-forgot">
                     <label><input type="checkbox">Remember me</label>
                 </div>

                 <input type="submit" class="btn" value="Sign In" name="signIn">
                 
                 <button type="button" class="btn qr-btn" id="toggleQRBtn">📱 Use Admin QR Code</button>
             </form>

             <!-- QR Scanner Container -->
             <div id="qrLoginContainer">
                 <h2 class="form-title">Scan Admin QR</h2>
                 <div id="reader"></div>
                 <div id="qrStatus" style="color: white; margin-bottom: 10px;">Waiting for scanner...</div>
                 <button class="btn cancel-btn" id="cancelQRBtn">Cancel</button>
             </div>

           </div>
     </div>
 </div>

 <script>
    const loginForm = document.getElementById('loginForm');
    const qrLoginContainer = document.getElementById('qrLoginContainer');
    const toggleQRBtn = document.getElementById('toggleQRBtn');
    const cancelQRBtn = document.getElementById('cancelQRBtn');
    const qrStatus = document.getElementById('qrStatus');
    
    let html5QrCode = null;

    toggleQRBtn.addEventListener('click', async () => {
        loginForm.style.display = 'none';
        qrLoginContainer.style.display = 'flex';
        
        startScanner();
    });

    cancelQRBtn.addEventListener('click', async () => {
        if (html5QrCode) {
            await html5QrCode.stop().catch(err => console.error(err));
        }
        loginForm.style.display = 'block';
        qrLoginContainer.style.display = 'none';
        qrStatus.textContent = "Scanner Stopped";
        qrStatus.style.color = 'white';
    });

    function startScanner() {
        html5QrCode = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        html5QrCode.start(
            { facingMode: "user" }, 
            config,
            onScanSuccess
        ).catch(err => {
            qrStatus.textContent = "Camera Error: " + err;
            qrStatus.style.color = 'red';
        });
    }

    function onScanSuccess(decodedText, decodedResult) {
        qrStatus.textContent = "Authenticating...";
        qrStatus.style.color = '#00c8ff';

        // Check against the server
        const formData = new FormData();
        formData.append('qr_login', decodedText);

        fetch('login.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                qrStatus.textContent = "Matched! Logging in...";
                qrStatus.style.color = '#2ecc71';
                setTimeout(() => window.location.href = 'index.php', 500);
            } else {
                qrStatus.textContent = "Invalid QR Code!";
                qrStatus.style.color = 'red';
                // Reset after 2 seconds to allow another scan
                setTimeout(() => {
                    qrStatus.textContent = "Try again...";
                    qrStatus.style.color = 'white';
                }, 2000);
            }
        })
        .catch(err => {
            qrStatus.textContent = "Error: " + err;
        });
    }
 </script>
</body>
</html>