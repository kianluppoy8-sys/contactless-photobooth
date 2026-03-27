<?php
// cleanup.php - Deletes sessions inactive for more than 3 minutes

$photosDir = __DIR__ . '/photos';
$lifetime = 180; // 3 minutes in seconds

if (!is_dir($photosDir)) {
    echo json_encode(['success' => false, 'message' => 'Photos directory not found']);
    exit;
}

$scanned = array_diff(scandir($photosDir), array('..', '.'));
$deletedCount = 0;

foreach ($scanned as $folder) {
    $folderPath = $photosDir . '/' . $folder;
    
    if (is_dir($folderPath)) {
        // Check last modified time
        // We look at the directory mtime, which updates when files are added/removed.
        if (time() - filemtime($folderPath) > $lifetime) {
            // Delete all files in folder
            $files = glob($folderPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            // Delete folder
            if (rmdir($folderPath)) {
                $deletedCount++;
            }
        }
    }
}

echo json_encode(['success' => true, 'deleted' => $deletedCount]);
?>
