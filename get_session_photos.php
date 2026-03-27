<?php
header('Content-Type: application/json');

if (!isset($_POST['session_id'])) {
    echo json_encode(['success' => false, 'error' => 'Session ID required']);
    exit;
}

$sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_POST['session_id']);
$sessionDir = __DIR__ . '/photos/' . $sessionId;

if (!is_dir($sessionDir)) {
    echo json_encode(['success' => false, 'error' => 'Session not found']);
    exit;
}

$photos = [];
$images = glob($sessionDir . '/*.jpg');

foreach ($images as $img) {
    if (basename($img) === 'photo_strip.jpg' || strpos(basename($img), 'raw_') === 0) {
        continue;
    }
    $photos[] = [
        'url' => 'photos/' . $sessionId . '/' . basename($img),
        'filename' => basename($img)
    ];
}

$strip = null;
if (file_exists($sessionDir . '/photo_strip.jpg')) {
    $strip = [
        'url' => 'photos/' . $sessionId . '/photo_strip.jpg',
        'filename' => 'photo_strip.jpg'
    ];
}

echo json_encode([
    'success' => true,
    'photos' => $photos,
    'long_format' => $strip
]);
?>
