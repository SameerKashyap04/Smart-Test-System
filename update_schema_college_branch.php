<?php
require_once 'config/db.php';

// Check and add columns to 'users' table
$columns_to_add_users = [
    'college' => "VARCHAR(255) NULL",
    'branch' => "VARCHAR(255) NULL"
];

foreach ($columns_to_add_users as $column => $definition) {
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
}

// Check and add columns to 'exams' table
$columns_to_add_exams = [
    'college' => "VARCHAR(255) NULL",
    'branch' => "VARCHAR(255) NULL",
    'secret_key' => "VARCHAR(255) NULL"
];

foreach ($columns_to_add_exams as $column => $definition) {
    $check_sql = "SHOW COLUMNS FROM exams LIKE '$column'";
    $result = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($result) == 0) {
        $alter_sql = "ALTER TABLE exams ADD COLUMN $column $definition";
        if (mysqli_query($conn, $alter_sql)) {
            echo "Column '$column' added to 'exams' table.<br>";
        } else {
            echo "Error adding '$column' to 'exams': " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Column '$column' already exists in 'exams' table.<br>";
    }
}

echo "Database schema update completed.";
?>
