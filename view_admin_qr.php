<?php
session_start();
// Login check removed so you can see your QR code to login!
require_once('phpqrcode/qrlib.php');
$secret = 'CYBER_SOCIETY_ADMIN_LOGIN_2026';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login QR</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background: #222; color: white; }
        .qr-card { background: white; padding: 20px; display: inline-block; border-radius: 10px; margin-top: 20px; }
        .secret-text { margin-top: 20px; font-family: monospace; color: #00c8ff; }
    </style>
</head>
<body>
    <h1>Admin Login QR Code</h1>
    <p>Print this or save it on your phone. Show this to the booth camera to log in instantly.</p>
    
    <div class="qr-card">
        <?php
        ob_start();
        QRcode::png($secret, null, QR_ECLEVEL_L, 10);
        $imageData = ob_get_contents();
        ob_end_clean();
        echo '<img src="data:image/png;base64,'.base64_encode($imageData).'" />';
        ?>
    </div>
    
    <p class="secret-text">Secret: <?php echo $secret; ?></p>
    <br>
    <a href="index.php" style="color: white;">Back to Booth</a>
</body>
</html>
