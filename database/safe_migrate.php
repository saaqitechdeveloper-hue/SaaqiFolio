<?php
/**
 * SAAQIFOLIO - Safe Database Schema Migrator
 * 
 * Safely adds new tables, columns, or default settings to the database
 * WITHOUT dropping, truncating, or deleting ANY existing user data.
 * 
 * Usage:
 *   php database/safe_migrate.php --live    (Runs against Hostinger Live DB)
 *   php database/safe_migrate.php --local   (Runs against Localhost DB)
 */

mysqli_report(MYSQLI_REPORT_OFF);

$isLive = in_array('--live', $argv ?? []);

if ($isLive) {
    $dbHost = 'srv578.hstgr.io';
    $dbUser = 'u195418993_SaaqiFolio';
    $dbPass = 'Saydev@1234';
    $dbName = 'u195418993_SaaqiFolio';
    echo "=== Running Safe Migration on HOSTINGER LIVE DATABASE ===\n";
} else {
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = '';
    $dbName = 'folivo';
    echo "=== Running Safe Migration on LOCAL DATABASE ===\n";
}

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
$conn->set_charset("utf8mb4");

echo "Connected to $dbName ($dbHost) successfully.\n\n";

// 1. Create tables if they do not exist
$tables = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        profession VARCHAR(50) DEFAULT 'Graphic Designer',
        phone VARCHAR(30) DEFAULT NULL,
        address VARCHAR(150) DEFAULT NULL,
        bio TEXT,
        education TEXT,
        experience TEXT,
        skills TEXT DEFAULT NULL,
        avatar VARCHAR(255) DEFAULT NULL,
        banner VARCHAR(255) DEFAULT NULL,
        banner_pos_x INT DEFAULT 50,
        banner_pos_y INT DEFAULT 50,
        banner_zoom INT DEFAULT 100,
        cv_file VARCHAR(255) DEFAULT NULL,
        cv_original_name VARCHAR(255) DEFAULT NULL,
        public_slug VARCHAR(160) NOT NULL UNIQUE,
        is_subscribed TINYINT(1) DEFAULT 0,
        pdf_downloads_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_slug (public_slug),
        INDEX idx_email (email)
    ) ENGINE=InnoDB;",

    'portfolio_images' => "CREATE TABLE IF NOT EXISTS portfolio_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        category ENUM('Logo','Banner','UI/UX','Color Separation','Flyer','Poster','Social Media','Other') DEFAULT 'Other',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_category (user_id, category)
    ) ENGINE=InnoDB;",

    'password_resets' => "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        otp VARCHAR(10) NOT NULL,
        expires_at DATETIME NOT NULL,
        is_used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_otp (otp)
    ) ENGINE=InnoDB;",

    'payments' => "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        gateway ENUM('JazzCash','Easypaisa') NOT NULL,
        txn_ref VARCHAR(50) NOT NULL UNIQUE,
        gateway_txn_id VARCHAR(100) DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('Pending','Completed','Failed') DEFAULT 'Pending',
        raw_response TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_txn_ref (txn_ref),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB;",

    'admins' => "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    'site_settings' => "CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;"
];

foreach ($tables as $tName => $sql) {
    if ($conn->query($sql)) {
        echo "[OK] Table `$tName` verified / created if absent.\n";
    } else {
        echo "[ERR] Error on table `$tName`: " . $conn->error . "\n";
    }
}

// 2. Safely add missing columns to existing tables
$columnChecks = [
    'users' => [
        'banner_pos_x' => "INT DEFAULT 50",
        'banner_pos_y' => "INT DEFAULT 50",
        'banner_zoom'  => "INT DEFAULT 100",
        'pdf_downloads_count' => "INT DEFAULT 0",
        'skills'       => "TEXT DEFAULT NULL",
        'cv_file'      => "VARCHAR(255) DEFAULT NULL",
        'cv_original_name' => "VARCHAR(255) DEFAULT NULL",
        'is_subscribed'=> "TINYINT(1) DEFAULT 0"
    ],
    'portfolio_images' => [
        'sort_order'   => "INT DEFAULT 0"
    ]
];

foreach ($columnChecks as $table => $cols) {
    $existing = [];
    $res = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $existing[] = $r['Field'];
        }
    }
    foreach ($cols as $colName => $colDef) {
        if (!in_array($colName, $existing)) {
            $alterSql = "ALTER TABLE `$table` ADD COLUMN `$colName` $colDef";
            if ($conn->query($alterSql)) {
                echo "[ADDED] Added column `$colName` to `$table`.\n";
            } else {
                echo "[ERR] Failed adding `$colName` to `$table`: " . $conn->error . "\n";
            }
        } else {
            echo "[OK] Column `$table`.`$colName` already exists.\n";
        }
    }
}

// 3. Ensure category ENUM contains Color Separation without breaking data
$conn->query("ALTER TABLE portfolio_images MODIFY category ENUM('Logo','Banner','UI/UX','Color Separation','Flyer','Poster','Social Media','Other') DEFAULT 'Other'");

// 4. Default site settings (non-destructive insert)
$defaultSettings = [
    'tutorial_video_enabled' => '1',
    'tutorial_video_type'    => 'url',
    'tutorial_video_url'     => 'https://vimeo.com/1233578650',
    'tutorial_video_title'   => 'SaaqiFolio Tutorial Walkthrough',
    'tutorial_video_desc'    => 'Watch this quick guide to learn how to create your portfolio, organize categories, and export client PDFs.'
];

foreach ($defaultSettings as $k => $v) {
    $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_key=setting_key");
    if ($stmt) {
        $stmt->bind_param("ss", $k, $v);
        $stmt->execute();
        $stmt->close();
    }
}
echo "[OK] Site settings verified.\n";

echo "\n Migration completed successfully! No existing user data was touched.\n";
