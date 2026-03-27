<?php
error_reporting(0);
header('Content-Type: application/json');

// Debug logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    if (!isset($_POST['session_id'])) {
        throw new Exception('Session ID required');
    }

    $sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_POST['session_id']);
    $sessionDir = __DIR__ . '/photos/' . $sessionId;

    if (!is_dir($sessionDir)) {
        throw new Exception('Session not found: ' . $sessionDir);
    }

    // Create ZIP file (Optional)
    if (class_exists('ZipArchive')) {
        $zipFile = $sessionDir . '/photos.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add all JPG files (photos + strip)
            $files = glob($sessionDir . '/*.jpg');
            if ($files !== false) {
                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
            }
        } else {
            error_log("Warning: Could not create zip file at $zipFile");
        }
    } else {
        error_log("Warning: ZipArchive class not found. Skipping zip creation.");
    }

    // Check for QRcode library
    if (!file_exists('phpqrcode/qrlib.php')) {
        throw new Exception('phpqrcode/qrlib.php not found');
    }
    require_once('phpqrcode/qrlib.php');

    // Generate QR Code pointing to the ZIP download
    // Determine current protocol (http or https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = dirname($_SERVER['PHP_SELF']); 
    $downloadUrl = $protocol . $host . $scriptDir . '/download_page.php?session_id=' . $sessionId;

    $qrFile = $sessionDir . '/qrcode.png';
    
    if (!is_writable($sessionDir)) {
        throw new Exception('Session directory is not writable: ' . $sessionDir);
    }

    QRcode::png($downloadUrl, $qrFile, QR_ECLEVEL_L, 10);

    echo json_encode([
        'success' => true,
        'qr_image' => 'photos/' . $sessionId . '/qrcode.png',
        'download_url' => $downloadUrl
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    error_log("QR Error: " . $e->getMessage());
}
