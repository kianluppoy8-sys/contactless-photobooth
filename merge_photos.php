<?php
error_reporting(0);
if (!isset($_POST['session_id'])) {
    die("Session ID missing");
}

$sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_POST['session_id']);
$sessionDir = __DIR__ . '/photos/' . $sessionId;
// Output File
$outputFile = $sessionDir . '/photo_strip.jpg';
// $frameFile removed

// Count images
$images = glob($sessionDir . '/photo_*.jpg');
$count = count($images);

// Check if limit is enforcing 3 photos
$forceMode = isset($_POST['limit']) ? (int)$_POST['limit'] : $count;

    // --- THEME SELECTION ---
    $theme = isset($_POST['theme']) ? $_POST['theme'] : 'acad'; // Default to acad
    
    // Save theme to file for download_page.php
    file_put_contents($sessionDir . '/theme.txt', $theme);

    if ($theme === 'valentines') {
        // --- VALENTINES THEME (PINK GRADIENT) ---

        // Constants
        $stripWidth = 600;
        
        // Layout Calculation
        $paddingSide = 40; 
        $paddingTop = 60;  
        $gap = 30;         
        
        // Photo Settings (4:3 Ratio for Valentines)
        $photoWidth = $stripWidth - ($paddingSide * 2); // 520px
        $photoHeight = (int)($photoWidth * 3 / 4); // 390px
        
        // Footer space
        $footerHeight = 400; 
        
        // Total Height (4 photos)
        $stripHeight = $paddingTop + (4 * ($photoHeight + $gap)) - $gap + $footerHeight;
        
        $base = imagecreatetruecolor($stripWidth, $stripHeight);
        
        // Colors
        // Gradient Background (Top: #ffdae0, Bottom: #ff85a2)
        // GD doesn't support gradients natively easily, so we simulate or check if we can fill solid for now.
        // Let's do a vertical gradient fill manually.
        
        $colorTop = [255, 218, 224]; // #ffdae0
        $colorBottom = [255, 133, 162]; // #ff85a2

        for ($y = 0; $y < $stripHeight; $y++) {
            $r = $colorTop[0] + ($colorBottom[0] - $colorTop[0]) * ($y / $stripHeight);
            $g = $colorTop[1] + ($colorBottom[1] - $colorTop[1]) * ($y / $stripHeight);
            $b = $colorTop[2] + ($colorBottom[2] - $colorTop[2]) * ($y / $stripHeight);
            $color = imagecolorallocate($base, (int)$r, (int)$g, (int)$b);
            imageline($base, 0, $y, $stripWidth, $y, $color);
        }
        
        $white = imagecolorallocate($base, 255, 255, 255);
        $pinkText = imagecolorallocate($base, 255, 77, 109); // #ff4d6d
        $darkPinkText = imagecolorallocate($base, 255, 0, 84); // #ff0054
        
        // Draw White Border around the whole strip (4px)
        $borderW = 8; // scaled up
        // Draw by filling rectangles on edges
        imagefilledrectangle($base, 0, 0, $stripWidth, $borderW, $white); // Top
        imagefilledrectangle($base, 0, $stripHeight - $borderW, $stripWidth, $stripHeight, $white); // Bottom
        imagefilledrectangle($base, 0, 0, $borderW, $stripHeight, $white); // Left
        imagefilledrectangle($base, $stripWidth - $borderW, 0, $stripWidth, $stripHeight, $white); // Right

        // Place Photos
        $currentY = $paddingTop;
        
        // Get sorted images
        $images = glob($sessionDir . '/raw_photo_*.jpg');
        sort($images);
        
        for ($i = 0; $i < 4; $i++) {
            // Draw White Slot Background
            imagefilledrectangle($base, $paddingSide, $currentY, $paddingSide + $photoWidth, $currentY + $photoHeight, $white);
            
            if (isset($images[$i])) {
                $photo = imagecreatefromjpeg($images[$i]);
                $pW = imagesx($photo);
                $pH = imagesy($photo);
                
                // Calculate crop for 4:3
                $targetRatio = 4 / 3;
                $currentRatio = $pW / $pH;
                
                if ($currentRatio > $targetRatio) {
                    $srcH = $pH;
                    $srcW = $pH * $targetRatio;
                    $srcX = ($pW - $srcW) / 2;
                    $srcY = 0;
                } else {
                    $srcW = $pW;
                    $srcH = $pW / $targetRatio;
                    $srcX = 0;
                    $srcY = ($pH - $srcH) / 2;
                }
                
                // Copy photo onto base
                imagecopyresampled($base, $photo, $paddingSide, $currentY, $srcX, $srcY, $photoWidth, $photoHeight, $srcW, $srcH);
                imagedestroy($photo);
            }
            
            $currentY += $photoHeight + $gap;
        }
        
        // Footer Text
        $fontPath = __DIR__ . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'DejaVuSans-Bold.ttf';
        if (!file_exists($fontPath)) {
            $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        }

        $textCenterY = $currentY + 60;
        
        if (file_exists($fontPath)) {
            // HAPPY HEARTS
            // We want it stacked: HAPPY <br> HEARTS
            $titleSize = 70;
            
            // "HAPPY"
            $text1 = "HAPPY";
            $bbox1 = imageftbbox($titleSize, 0, $fontPath, $text1);
            $w1 = $bbox1[2] - $bbox1[0];
            $x1 = ($stripWidth - $w1) / 2;
            $y1 = $textCenterY + 50;

            // Shadow
            imagefttext($base, $titleSize, 0, $x1+4, $y1+4, $white, $fontPath, $text1);
            // Main
            imagefttext($base, $titleSize, 0, $x1, $y1, $pinkText, $fontPath, $text1);

            // "HEARTS"
            $text2 = "HEARTS";
            $bbox2 = imageftbbox($titleSize, 0, $fontPath, $text2);
            $w2 = $bbox2[2] - $bbox2[0];
            $x2 = ($stripWidth - $w2) / 2;
            $y2 = $y1 + 90;

            // Shadow
            imagefttext($base, $titleSize, 0, $x2+4, $y2+4, $white, $fontPath, $text2);
            // Main
            imagefttext($base, $titleSize, 0, $x2, $y2, $pinkText, $fontPath, $text2);

            // TECHNO STRIFE
            $subText = "TECHNO STRIFE";
            $subSize = 30;
            $subY = $y2 + 80;
            
            $bboxSub = imageftbbox($subSize, 0, $fontPath, $subText);
            $wSub = $bboxSub[2] - $bboxSub[0];
            $xSub = ($stripWidth - $wSub) / 2;
            
            imagefttext($base, $subSize, 0, $xSub, $subY, $darkPinkText, $fontPath, $subText);
        }

    } else {
        // --- ACAD FEST THEME (DEFAULT BLUE) ---
        
        // Constants (Scaled 2x from HTML reference)
        $stripWidth = 600;
        
        // Layout Calculation
        $paddingSide = 50; // 25px -> 50px
        $paddingTop = 80;  // 40px -> 80px
        $gap = 40;         // 20px -> 40px
        
        // Photo Settings
        // 16:9 Aspect Ratio for strip
        $photoWidth = $stripWidth - ($paddingSide * 2); // 500px
        $photoHeight = (int)($photoWidth * 9 / 16); // 281px
        
        // Footer space
        $footerHeight = 400; 
        
        // Total Height (4 photos)
        $stripHeight = $paddingTop + (4 * ($photoHeight + $gap)) - $gap + $footerHeight;
        
        $base = imagecreatetruecolor($stripWidth, $stripHeight);
        
        // Colors
        $blueBg = imagecolorallocate($base, 186, 215, 242); // #bad7f2
        $white = imagecolorallocate($base, 255, 255, 255);
        $checkColor = imagecolorallocatealpha($base, 255, 255, 255, 76); // ~0.4 opacity
    
        $titleColor = imagecolorallocate($base, 91, 163, 242); // #5ba3f2
        $subTitleColor = imagecolorallocate($base, 59, 109, 158); // #3b6d9e
        
        // 1. Fill Background
        imagefill($base, 0, 0, $blueBg);
        
        // 2. Draw Checkered Pattern
        $tileSize = 100;
        $halfTile = 50;
        
        for ($y = 0; $y < $stripHeight; $y += $tileSize) {
            for ($x = 0; $x < $stripWidth; $x += $tileSize) {
                // Horizontal bar (Top Half)
                imagefilledrectangle($base, $x, $y, $x + $stripWidth, $y + $halfTile, $checkColor);
                // Vertical bar (Left Half)
                imagefilledrectangle($base, $x, $y, $x + $halfTile, $y + $tileSize, $checkColor);
            }
        }
        
        // 3. Draw Main White Border (20px)
        $borderW = 20;
        // Top
        imagefilledrectangle($base, 0, 0, $stripWidth, $borderW, $white);
        // Bottom
        imagefilledrectangle($base, 0, $stripHeight - $borderW, $stripWidth, $stripHeight, $white);
        // Left
        imagefilledrectangle($base, 0, 0, $borderW, $stripHeight, $white);
        // Right
        imagefilledrectangle($base, $stripWidth - $borderW, 0, $stripWidth, $stripHeight, $white);
        
        // 4. Place Photos (Stacked)
        $currentY = $paddingTop;
        
        // Get sorted images (Use RAW photos to avoid double framing)
        $images = glob($sessionDir . '/raw_photo_*.jpg');
        sort($images);
        
        for ($i = 0; $i < 4; $i++) {
            // Draw White Slot Background
            imagefilledrectangle($base, $paddingSide, $currentY, $paddingSide + $photoWidth, $currentY + $photoHeight, $white);
            
            if (isset($images[$i])) {
                $photo = imagecreatefromjpeg($images[$i]);
                $pW = imagesx($photo);
                $pH = imagesy($photo);
                
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
                
                // Copy photo onto base
                imagecopyresampled($base, $photo, $paddingSide, $currentY, $srcX, $srcY, $photoWidth, $photoHeight, $srcW, $srcH);
                imagedestroy($photo);
            }
            
            $currentY += $photoHeight + $gap;
        }
        
        // 5. Draw Footer Text
        $fontPath = __DIR__ . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'DejaVuSans-Bold.ttf';
        if (!file_exists($fontPath)) {
            $fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        }
        
        // Text Positioning
        $textCenterY = $currentY + 120; // Start title here
        
        if (file_exists($fontPath)) {
            // TITLE: ACAD FEST
            $titleText = "ACAD FEST";
            $fontSizeTitle = 70;
            
            $bbox = imageftbbox($fontSizeTitle, 0, $fontPath, $titleText);
            $textW = $bbox[2] - $bbox[0];
            $textX = ($stripWidth - $textW) / 2;
            $textY = $textCenterY;
            
            // Draw Shadows (White Outline)
            $offsets = [[-4,-4], [4,-4], [-4,4], [4,4], [0,0]];
            foreach ($offsets as $off) {
                imagefttext($base, $fontSizeTitle, 0, (int)($textX + $off[0]), (int)($textY + $off[1]), $white, $fontPath, $titleText);
            }
            
            // Draw Main Blue Text
            imagefttext($base, $fontSizeTitle, 0, (int)$textX, (int)$textY, $titleColor, $fontPath, $titleText);
            
            // SUBTITLE: TECHNO STRIFE
            $subText = "TECHNO STRIFE";
            $fontSizeSub = 30;
            $subY = $textY + 60;
            
            $bboxSub = imageftbbox($fontSizeSub, 0, $fontPath, $subText);
            $textWSub = $bboxSub[2] - $bboxSub[0];
            $textXSub = ($stripWidth - $textWSub) / 2;
            
            imagefttext($base, $fontSizeSub, 0, (int)$textXSub, (int)$subY, $subTitleColor, $fontPath, $subText);
        }
    }
    
    // Output
    imagejpeg($base, $outputFile, 90);
    imagedestroy($base);

    // Return success and URL
    echo json_encode([
        'success' => true, 
        'url' => 'photos/' . $sessionId . '/photo_strip.jpg'
    ]);
?>
