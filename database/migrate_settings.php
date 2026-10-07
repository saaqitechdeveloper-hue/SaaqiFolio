<?php
require_once __DIR__ . '/../config/db.php';

$sql = "CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating table: " . mysqli_error($conn) . "\n");
}

$defaults = [
    'tutorial_video_enabled' => '1',
    'tutorial_video_type' => 'url', // 'url' or 'file'
    'tutorial_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'tutorial_video_file' => '',
    'tutorial_video_title' => 'SaaqiFolio Tutorial Walkthrough',
    'tutorial_video_desc' => 'Watch this quick guide to learn how to create your portfolio, organize categories, and export client PDFs.',
];

foreach ($defaults as $key => $val) {
    $stmt = mysqli_prepare($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_key=setting_key");
    mysqli_stmt_bind_param($stmt, 'ss', $key, $val);
    mysqli_stmt_execute($stmt);
}

echo "Migration successful. Settings in database:\n";
$res = mysqli_query($conn, "SELECT * FROM site_settings");
while ($r = mysqli_fetch_assoc($res)) {
    echo "- " . $r['setting_key'] . " = " . $r['setting_value'] . "\n";
}
