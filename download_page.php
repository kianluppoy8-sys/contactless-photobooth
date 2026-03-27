<?php
if (!isset($_GET['session_id'])) {
    die('Session ID required');
}

$sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_GET['session_id']);
$sessionDir = __DIR__ . '/photos/' . $sessionId;

if (!is_dir($sessionDir)) {
    die('Session not found');
}

// Get all photos
$images = glob($sessionDir . '/*.jpg');
$photos = [];
$strip = '';

foreach ($images as $img) {
    $filename = basename($img);
    if ($filename === 'photo_strip.jpg') {
        $strip = $filename;
    } elseif (strpos($filename, 'raw_') === 0) {
        // Skip raw photos
        continue;
    } else {
        $photos[] = $filename;
    }
}

// Sort photos to be in order
sort($photos);

// Session Validation Logic
$activeSessionFile = __DIR__ . '/active_session.txt';
$isInvalid = false;
if (file_exists($activeSessionFile)) {
    $activeSession = trim(file_get_contents($activeSessionFile));
    if ($sessionId !== $activeSession) {
        $isInvalid = true;
    }
}

// Theme Logic
$themeFile = $sessionDir . '/theme.txt';
$theme = file_exists($themeFile) ? trim(file_get_contents($themeFile)) : 'acad';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ACLC Photobooth Memories</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --blue: #002366; /* Deep Blue */
            --red: #c41e3a; /* Deep Red */
            --gold: #ffd700;
            --white: #ffffff;
            --green: #008000;               
            --glass: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--blue) 0%, #1a1a1a 50%, var(--red) 100%);
            margin: 0;
            padding: 0;
            color: var(--white);
            min-height: 100vh;
        }

        /* Valentines Properties Override */
        body.valentines-theme {
            --blue: #ff5980ff; /* Swap blue for pink */
            --red: #ff0055ff; /* Brighter red */
            background: linear-gradient(135deg, #ffc2d1 0%, #fff0f5 50%, #ff85a2 100%);
            color: #590d22;
        }
        
        body.valentines-theme .logo {
             border-color: #ff0054;
             box-shadow: 0 0 25px rgba(255, 0, 84, 0.4);
        }

        body.valentines-theme .download-all-btn {
            background: linear-gradient(45deg, #ff4d6d, #ff0054);
            color: white;
            box-shadow: 0 5px 20px rgba(255, 0, 84, 0.3);
        }

        body.valentines-theme .section-label {
            color: #a4133c;
        }
        
        body.valentines-theme .footer {
            color: #a4133c;
            opacity: 0.7;
        }

        .header {
            text-align: center;
            padding: 30px 20px 10px 20px;
        }

        .logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid var(--red);
            box-shadow: 0 0 25px rgba(255, 0, 0, 0.4);
            margin-bottom: 15px;
            background: transparent;
            object-fit: cover;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .header h1 {
            font-size: 18px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            line-height: 1.3;
        }

        .credits-box {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 10px;
            margin: 15px auto;
            max-width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .credits {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            opacity: 0.9;
            letter-spacing: 0.5px;
        }

        .donation {
            font-size: 11px;
            color: var(--green);
            font-weight: 700;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 10px 15px 80px 15px;
            box-sizing: border-box;
        }

        .download-all-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            background: linear-gradient(45deg, var(--gold), #f39c12);
            color: #000;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 800;
            box-shadow: 0 5px 20px rgba(0,0,0,0.4);
            cursor: pointer;
            margin-bottom: 25px;
            text-transform: uppercase;
            -webkit-tap-highlight-color: transparent;
            position: sticky;
            top: 20px;
            z-index: 100;
        }
        
        .download-all-btn span { margin-right: 10px; font-size: 20px; }

        /* Invalid Overlay */
        .invalid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .invalid-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: var(--red);
        }

        .invalid-title {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 10px;
            text-transform: uppercase;
            color: var(--gold);
        }

        .invalid-text {
            font-size: 14px;
            opacity: 0.8;
            max-width: 400px;
            line-height: 1.6;
        }

        .section-label {
            font-size: 12px;
            font-weight: 800;
            color: rgba(255,255,255,0.7);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-align: center;
        }

        /* Simple Stack Layout for Mobile Priority */
        .layout-grid {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Snapshots Grid (2x2) */
        .photo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .grid-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            position: relative;
            transition: transform 0.2s;
        }

        .grid-card:active { transform: scale(0.98); }

        .grid-card.valentines {
            background-color: #ffc2d1;
            border: 2px solid #fff0f5;
        }
        
        .grid-link {
            display: block;
            text-decoration: none;
            position: relative;
        }

        .grid-img {
            width: 100%;
            aspect-ratio: 16/16; /* Square snapshots look great in 2x2 */
            object-fit: cover;
            display: block;
        }

        /* Strip Section */
        .strip-section {
            text-align: center;
        }

        .strip-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            padding: 10px;
            max-width: 300px;
            margin: 0 auto;
        }

        .strip-img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .btn-download-strip {
            display: block;
            background: var(--blue);
            color: white;
            text-align: center;
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
            padding: 12px;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .download-icon-mini {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: var(--red);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.4);
        }

        .footer {
            text-align: center;
            font-size: 11px;
            color: rgba(255,255,255,0.4);
            margin-top: 40px;
            letter-spacing: 1px;
        }
        
        /* Animations */
        .fade-in { animation: fadeIn 0.8s ease forwards; opacity: 0; transform: translateY(20px); }
        @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
        .delay-3 { animation-delay: 0.6s; }
    </style>
</head>
<body class="<?php echo ($theme === 'valentines') ? 'valentines-theme' : ''; ?>">

    <?php if ($isInvalid): ?>
    <div class="invalid-overlay">
        <div class="invalid-icon">⚠️</div>
        <div class="invalid-title">Session Expired</div>
        <div class="invalid-text">
            For privacy and security, this session has expired because a new session has started.<br>
            Please take a new photo at the booth.
        </div>
    </div>
    <?php else: ?>
    
    <div class="header">
        <img src="cybersociety_logo.png" alt="Logo" class="logo fade-in">
        <h1 class="fade-in">ACLC College of Taytay<br>Cyber Society</h1>
        
        <div class="credits-box fade-in delay-1">
            <div class="credits">CREATED BY KIAN LUPPPOY && JANELLA MANLAPAS</div>
            <div class="donation">DONATE: 09693523473 (G***A L.)</div>
        </div>
    </div>

    <div class="container">
        
        <button onclick="downloadAll()" class="download-all-btn fade-in delay-1">
            <span>⬇️</span> Download All (4 Photos + Strip)
        </button>

        <div class="layout-grid fade-in delay-2">
            
            <!-- Individual Photos Section -->
            <div class="photos-section">
                <div class="section-label">Your Snapshots (4 Photos)</div>
                <div class="photo-grid">
                    <?php 
                    // Ensure we show exactly 4 slots if possible
                    for($i=0; $i<4; $i++): 
                        if(isset($photos[$i])):
                            $photo = $photos[$i];
                    ?>
                        <div class="grid-card <?php echo ($theme === 'valentines') ? 'valentines' : ''; ?>">
                            <a href="photos/<?php echo $sessionId; ?>/<?php echo $photo; ?>" download class="grid-link">
                                <img src="photos/<?php echo $sessionId; ?>/<?php echo $photo; ?>" alt="Photo <?php echo $i+1; ?>" class="grid-img">
                                <div class="download-icon-mini">↓</div>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid-card" style="background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; aspect-ratio:1/1;">
                            <span style="opacity: 0.2;">empty</span>
                        </div>
                    <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Strip Section -->
            <?php if ($strip): ?>
            <div class="strip-section">
                <div class="section-label">Official Photo Strip</div>
                <div class="strip-card <?php echo ($theme === 'valentines') ? 'valentines' : ''; ?>">
                    <img src="photos/<?php echo $sessionId; ?>/<?php echo $strip; ?>" alt="Strip" class="strip-img">
                    <a href="photos/<?php echo $sessionId; ?>/<?php echo $strip; ?>" download class="btn-download-strip">SAVE STRIP</a>
                </div>
            </div>
            <?php endif; ?>
            
        </div>

        <div class="footer fade-in delay-3">Session ID: <?php echo $sessionId; ?></div>
    </div>
    <?php endif; ?>

    <script>
    function downloadAll() {
        const files = [
            <?php if ($strip) echo "'photos/$sessionId/$strip',"; ?>
            <?php foreach ($photos as $photo) echo "'photos/$sessionId/$photo',"; ?>
        ];
        
        const btn = document.querySelector('.download-all-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Starting...';
        btn.style.opacity = '0.8';

        let delay = 0;
        files.forEach((file, index) => {
            setTimeout(() => {
                const link = document.createElement('a');
                link.href = file;
                link.download = file.split('/').pop();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                if(index === files.length - 1) {
                    setTimeout(() => {
                        btn.innerHTML = 'Done!';
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.style.opacity = '1';
                        }, 2000);
                    }, 1000);
                }
            }, delay);
            delay += 1000; // Stagger downloads for reliability
        });
    }
    </script>
</body>
</html>
