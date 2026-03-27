<?php
session_start();
// Regenerate session ID logic
$newSessionId = 'session_' . date('Ymd_His') . '_' . uniqid();
$_SESSION['photobooth_session_id'] = $newSessionId;
// Record this as the only active session
file_put_contents(__DIR__ . '/active_session.txt', $newSessionId);
chmod(__DIR__ . '/active_session.txt', 0666);
header("Location: index.php");
exit;
