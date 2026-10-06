<?php
require_once __DIR__ . '/../config/db.php';

// 1. Add sort_order to portfolio_images
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM portfolio_images LIKE 'sort_order'");
if (mysqli_num_rows($colCheck) === 0) {
    if (mysqli_query($conn, "ALTER TABLE portfolio_images ADD COLUMN sort_order INT NOT NULL DEFAULT 0, ADD INDEX idx_user_cat_sort (user_id, category, sort_order)")) {
        echo "sort_order column added to portfolio_images.\n";
    } else {
        echo "Error adding sort_order: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "sort_order column already exists.\n";
}

// 2. Add banner_pos_x to users
$colCheck2 = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'banner_pos_x'");
if (mysqli_num_rows($colCheck2) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN banner_pos_x INT NOT NULL DEFAULT 50")) {
        echo "banner_pos_x column added to users.\n";
    } else {
        echo "Error adding banner_pos_x: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "banner_pos_x column already exists.\n";
}

// 3. Seed sort_order for existing images
$res = mysqli_query($conn, "SELECT id, user_id, category FROM portfolio_images ORDER BY user_id, category, id ASC");
$currentGroup = '';
$order = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $grp = $row['user_id'] . '_' . $row['category'];
    if ($grp !== $currentGroup) {
        $currentGroup = $grp;
        $order = 0;
    }
    $order++;
    mysqli_query($conn, "UPDATE portfolio_images SET sort_order = $order WHERE id = " . (int)$row['id']);
}
echo "Existing images sort_order seeded successfully.\n";
