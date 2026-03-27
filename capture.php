<?php
error_reporting(0);
header('Content-Type: application/json');
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');
ini_set('max_execution_time', '60');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!isset($_FILES['photo']) || !isset($_POST['session_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing photo or session_id']);
        exit;
    }

    $sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_POST['session_id']);
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . $sessionId;

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to create session directory']);
            exit;
        }
    }

    $file = $_FILES['photo'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Upload error: ' . $file['error']]);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ['image/jpeg', 'image/jpg', 'image/png'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }

    $originalName = isset($_POST['filename']) ? $_POST['filename'] : ($file['name'] ?? 'photo.jpg');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext === '') {
        $ext = $mime === 'image/png' ? 'png' : 'jpg';
    }
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName);

    // Save Raw Image
    $rawFilename = 'raw_' . $safeName;
    $rawTargetPath = $uploadDir . DIRECTORY_SEPARATOR . $rawFilename;
    
    // Logic to handle duplicates for raw file
    $i = 1;
    while (file_exists($rawTargetPath)) {
        $base = pathinfo($rawFilename, PATHINFO_FILENAME);
        $rawTargetPath = $uploadDir . DIRECTORY_SEPARATOR . $base . '_' . $i . '.' . $ext;
        $i++;
    }

    if (!move_uploaded_file($file['tmp_name'], $rawTargetPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
        exit;
    }
    
    // --- GENERATE FRAMED 16:9 CARD (photo_X.jpg) ---
    $framedTargetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName; // Original requested name
    
    // Theme Logic
    $theme = isset($_POST['theme']) ? $_POST['theme'] : 'acad';
    
    // Constants (16:9 Card Design)
    $cardWidth = 700;
    $cardHeight = 750; // Shorter height for landscape photo
    $padding = 60;
    $slotWidth = $cardWidth - ($padding * 2); // 580
    $slotHeight = (int)($slotWidth * 9 / 16); // ~326px (16:9)
    $slotX = $padding;
    $slotY = $padding;
    
    $baseFunc = imagecreatetruecolor($cardWidth, $cardHeight);
    $white = imagecolorallocate($baseFunc, 255, 255, 255);

    $fontPath = __DIR__ . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'DejaVuSans-Bold.ttf';
    if (!file_exists($fontPath)) {
        $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    }

    if ($theme === 'valentines') {
        // --- VALENTINES THEME ---
        // Gradient Background (Pink)
        $colorTop = [255, 218, 224]; // #ffdae0
        $colorBottom = [255, 133, 162]; // #ff85a2

        for ($y = 0; $y < $cardHeight; $y++) {
            $r = $colorTop[0] + ($colorBottom[0] - $colorTop[0]) * ($y / $cardHeight);
            $g = $colorTop[1] + ($colorBottom[1] - $colorTop[1]) * ($y / $cardHeight);
            $b = $colorTop[2] + ($colorBottom[2] - $colorTop[2]) * ($y / $cardHeight);
            $color = imagecolorallocate($baseFunc, (int)$r, (int)$g, (int)$b);
            imageline($baseFunc, 0, $y, $cardWidth, $y, $color);
        }

        // Colors
        $pinkText = imagecolorallocate($baseFunc, 255, 77, 109); // #ff4d6d
        $darkPinkText = imagecolorallocate($baseFunc, 255, 0, 84); // #ff0054
        
        // White Slot Background
        imagefilledrectangle($baseFunc, $slotX, $slotY, $slotX + $slotWidth, $slotY + $slotHeight, $white);
        
        // Border: 8px White (Thinner for Valentines or Keep logic?) 
        // Let's use a nice white border around the card edge like Acad
        $borderW = 10;
        imagefilledrectangle($baseFunc, 0, 0, $cardWidth, $borderW, $white);
        imagefilledrectangle($baseFunc, 0, $cardHeight - $borderW, $cardWidth, $cardHeight, $white);
        imagefilledrectangle($baseFunc, 0, 0, $borderW, $cardHeight, $white);
        imagefilledrectangle($baseFunc, $cardWidth - $borderW, 0, $cardWidth, $cardHeight, $white);

        // Text
        $footerCenterY = $slotY + $slotHeight + (($cardHeight - ($slotY + $slotHeight)) / 2);
        
        if (file_exists($fontPath)) {
            // HAPPY HEARTS
            $titleText = "HAPPY HEARTS"; // Single line for snapshot to fit
            $fontSizeTitle = 60;
            
            $bbox = imageftbbox($fontSizeTitle, 0, $fontPath, $titleText);
            $textW = $bbox[2] - $bbox[0];
            $textX = ($cardWidth - $textW) / 2;
            $textY = $footerCenterY - 10;
            
            // Shadows
            imagefttext($baseFunc, $fontSizeTitle, 0, (int)($textX + 4), (int)($textY + 4), $white, $fontPath, $titleText);
            imagefttext($baseFunc, $fontSizeTitle, 0, (int)$textX, (int)$textY, $pinkText, $fontPath, $titleText);
            
            // TECHNO STRIFE
            $subText = "TECHNO STRIFE";
            $fontSizeSub = 24;
            $subY = $textY + 50;
            
            $bboxSub = imageftbbox($fontSizeSub, 0, $fontPath, $subText);
            $textWSub = $bboxSub[2] - $bboxSub[0];
            $textXSub = ($cardWidth - $textWSub) / 2;
            
            imagefttext($baseFunc, $fontSizeSub, 0, (int)$textXSub, (int)$subY, $darkPinkText, $fontPath, $subText);
        }

    } else {
        // --- ACAD FEST THEME (DEFAULT) ---
        $blueBg = imagecolorallocate($baseFunc, 186, 215, 242); // #bad7f2
        $checkColor = imagecolorallocatealpha($baseFunc, 255, 255, 255, 76); // ~0.4 opacity
        $titleColor = imagecolorallocate($baseFunc, 91, 163, 242); // #5ba3f2
        $subTitleColor = imagecolorallocate($baseFunc, 59, 109, 158); // #3b6d9e

        // 1. Background & Pattern
        imagefill($baseFunc, 0, 0, $blueBg);
        $tileSize = 100;
        $halfTile = 50;
        for ($y = 0; $y < $cardHeight; $y += $tileSize) {
            for ($x = 0; $x < $cardWidth; $x += $tileSize) {
                imagefilledrectangle($baseFunc, $x, $y, $x + $cardWidth, $y + $halfTile, $checkColor);
                imagefilledrectangle($baseFunc, $x, $y, $x + $halfTile, $y + $tileSize, $checkColor);
            }
        }
        
        // 2. White Border
        $borderW = 20;
        imagefilledrectangle($baseFunc, 0, 0, $cardWidth, $borderW, $white);
        imagefilledrectangle($baseFunc, 0, $cardHeight - $borderW, $cardWidth, $cardHeight, $white);
        imagefilledrectangle($baseFunc, 0, 0, $borderW, $cardHeight, $white);
        imagefilledrectangle($baseFunc, $cardWidth - $borderW, 0, $cardWidth, $cardHeight, $white);
        
        // 3. Slot Background
        imagefilledrectangle($baseFunc, $slotX, $slotY, $slotX + $slotWidth, $slotY + $slotHeight, $white);

        // Text
        $footerCenterY = $slotY + $slotHeight + (($cardHeight - ($slotY + $slotHeight)) / 2);

        if (file_exists($fontPath)) {
            // ACAD FEST
            $titleText = "ACAD FEST";
            $fontSizeTitle = 72;
            $bbox = imageftbbox($fontSizeTitle, 0, $fontPath, $titleText);
            $textW = $bbox[2] - $bbox[0];
            $textX = ($cardWidth - $textW) / 2;
            $textY = $footerCenterY - 10;
            
            // Shadows
            $offsets = [[-4,-4], [4,-4], [-4,4], [4,4], [0,0]];
            foreach ($offsets as $off) {
                imagefttext($baseFunc, $fontSizeTitle, 0, (int)($textX + $off[0]), (int)($textY + $off[1]), $white, $fontPath, $titleText);
            }
            imagefttext($baseFunc, $fontSizeTitle, 0, (int)$textX, (int)$textY, $titleColor, $fontPath, $titleText);
            
            // TECHNO STRIFE
            $subText = "TECHNO STRIFE";
            $fontSizeSub = 24;
            $subY = $textY + 50;
            $bboxSub = imageftbbox($fontSizeSub, 0, $fontPath, $subText);
            $textWSub = $bboxSub[2] - $bboxSub[0];
            $textXSub = ($cardWidth - $textWSub) / 2;
            imagefttext($baseFunc, $fontSizeSub, 0, (int)$textXSub, (int)$subY, $subTitleColor, $fontPath, $subText);
        }
    }
    
    // 4. Place Photo (Center Crop to 16:9)
    $srcPhoto = imagecreatefromjpeg($rawTargetPath);
    if ($srcPhoto) {
        $pW = imagesx($srcPhoto);
        $pH = imagesy($srcPhoto);
        
        // Calculate crop for 16:9
        $targetRatio = 16 / 9;
        $currentRatio = $pW / $pH;
        
        if ($currentRatio > $targetRatio) {
            // Too wide
            $srcH = $pH;
            $srcW = $pH * $targetRatio;
            $srcX = ($pW - $srcW) / 2;
            $srcY = 0;
        } else {
            // Too tall or exact
            $srcW = $pW;
            $srcH = $pW / $targetRatio;
            $srcX = 0;
            $srcY = ($pH - $srcH) / 2;
        }
        
        imagecopyresampled($baseFunc, $srcPhoto, $slotX, $slotY, $srcX, $srcY, $slotWidth, $slotHeight, $srcW, $srcH);
        imagedestroy($srcPhoto);
    }
    
    // Save Framed Image
    imagejpeg($baseFunc, $framedTargetPath, 90);
    imagedestroy($baseFunc);

    // Return the RAW image for the frontend display (to avoid double-framing with HTML/CSS)
    // The framed version is saved as 'photo_X.jpg' for the download page.
    echo json_encode([
        'success' => true,
        'filename' => $rawFilename, 
        'url' => 'photos/' . $sessionId . '/' . $rawFilename
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
