<?php
session_start();

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(403);
    exit("Unauthorized access");
}

$file = basename($_GET['file'] ?? '');
$path = __DIR__ . "/../files/$file";

if ($file === 'all.zip') {
    // Example: allow "Download All" if you pre-created a zip
    $path = __DIR__ . "/../files/all.zip";
}

if ($file && file_exists($path)) {
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$file\"");
    readfile($path);
    exit;
} else {
    http_response_code(404);
    echo "File not found.";
}