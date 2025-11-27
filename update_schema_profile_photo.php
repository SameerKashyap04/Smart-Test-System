<?php
require_once 'config/db.php';

// Check and add 'profile_image' column to 'users' table
$column = 'profile_image';
$definition = "VARCHAR(255) NULL AFTER email";

$check_sql = "SHOW COLUMNS FROM users LIKE '$column'";
$result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($result) == 0) {
    $alter_sql = "ALTER TABLE users ADD COLUMN $column $definition";
    if (mysqli_query($conn, $alter_sql)) {
        echo "Column '$column' added to 'users' table.<br>";
    } else {
        echo "Error adding '$column' to 'users': " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Column '$column' already exists in 'users' table.<br>";
}

echo "Database schema update for Profile Image completed.";
?>
