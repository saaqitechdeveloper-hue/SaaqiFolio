-- ============================================
-- SAAQIFOLIO - Designer Portfolio Platform
-- Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS folivo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE folivo;

-- ============================================
-- Table: users
-- ============================================
CREATE TABLE users (
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
    skills TEXT DEFAULT NULL,                 -- comma-separated list, e.g. "Figma,Photoshop"
    avatar VARCHAR(255) DEFAULT NULL,
    banner VARCHAR(255) DEFAULT NULL,
    banner_pos_x INT DEFAULT 50,                -- 0-100 (horizontal banner focal point)
    banner_pos_y INT DEFAULT 50,                -- 0-100 (vertical banner focal point)
    banner_zoom INT DEFAULT 100,                -- 100-180
    cv_file VARCHAR(255) DEFAULT NULL,          -- stored filename on disk
    cv_original_name VARCHAR(255) DEFAULT NULL, -- original filename for download
    public_slug VARCHAR(160) NOT NULL UNIQUE,
    is_subscribed TINYINT(1) DEFAULT 0,         -- Pro plan flag — set to 1 automatically once a payment is verified
    pdf_downloads_count INT DEFAULT 0,          -- how many portfolio PDFs this account has downloaded (trial = 3 max)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (public_slug),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ============================================
-- Table: portfolio_images
-- ============================================
CREATE TABLE portfolio_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    category ENUM('Logo','Banner','UI/UX','Color Separation','Flyer','Poster','Social Media','Other') DEFAULT 'Other',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_category (user_id, category)
) ENGINE=InnoDB;

-- ============================================
-- Table: password_resets
-- (simple email-based reset, matching the original demo's flow —
--  no OTP/email verification is wired up; see README for production notes)
-- ============================================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ============================================
-- Table: payments
-- Records every Pro-upgrade payment attempt (JazzCash / Easypaisa).
-- A user is marked Pro (users.is_subscribed = 1) only once a payment
-- row here reaches status = 'Completed' via the gateway's callback.
-- ============================================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gateway ENUM('JazzCash','Easypaisa') NOT NULL,
    txn_ref VARCHAR(50) NOT NULL UNIQUE,        -- our own transaction reference sent to the gateway
    gateway_txn_id VARCHAR(100) DEFAULT NULL,   -- the ID the gateway gives back
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending','Completed','Failed') DEFAULT 'Pending',
    raw_response TEXT DEFAULT NULL,             -- full callback payload, kept for support/debugging
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_txn_ref (txn_ref),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ============================================
-- Table: admins
-- Site-owner accounts for the Admin Panel (/admin/) — completely separate
-- from regular user accounts (users table). Log in at /admin/login.php.
-- No default admin is inserted here on purpose — run admin_setup.php once
-- after importing this file to create your first admin login (it uses
-- PHP's password_hash() so the password is stored securely). Delete
-- admin_setup.php afterwards.
-- ============================================
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Table: site_settings
-- System-wide dynamic settings (Tutorial video, etc.)
-- ============================================
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default settings seed
INSERT INTO site_settings (setting_key, setting_value) VALUES
('tutorial_video_enabled', '1'),
('tutorial_video_type', 'url'),
('tutorial_video_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'),
('tutorial_video_file', ''),
('tutorial_video_title', 'SaaqiFolio Tutorial Walkthrough'),
('tutorial_video_desc', 'Watch this quick guide to learn how to create your portfolio, organize categories, and export client PDFs.')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- ============================================
-- MIGRATION NOTES for existing installs (already have data):
--
-- 1) Admin panel & site settings:
--    CREATE TABLE IF NOT EXISTS site_settings (
--        id INT AUTO_INCREMENT PRIMARY KEY,
--        setting_key VARCHAR(100) UNIQUE NOT NULL,
--        setting_value TEXT NULL,
--        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
--    ) ENGINE=InnoDB;
--
-- 2) "Website" category renamed to "Color Separation":
--    ALTER TABLE portfolio_images
--      MODIFY category ENUM('Logo','Banner','UI/UX','Color Separation','Flyer','Poster','Social Media','Other') DEFAULT 'Other';
--    UPDATE portfolio_images SET category = 'Color Separation' WHERE category = 'Website';
--
-- 3) Sort order and banner positioning:
--    ALTER TABLE users ADD COLUMN IF NOT EXISTS banner_pos_x INT DEFAULT 50;
--    ALTER TABLE portfolio_images ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0;
-- ============================================
