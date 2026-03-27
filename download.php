<?php
if (!isset($_GET['session_id'])) {
    die('Session ID required');
}

$sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_GET['session_id']);
$file = __DIR__ . '/photos/' . $sessionId . '/photos.zip';

if (!file_exists($file)) {
    die('Photo zip not found');
}

// Force download
header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="photobooth_photos_' . $sessionId . '.zip"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
