<?php
session_start();
require_once '../config/db.php';
require_once 'password_validator.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Initialize password validator
    $passwordValidator = new PasswordValidator(8);

    // Validate password strength
    $passwordValidation = $passwordValidator->validatePassword($password);
    if (!$passwordValidation['valid']) {
        $_SESSION['error'] = "Password requirements not met:<br>" . implode("<br>", $passwordValidation['errors']);
        header("Location: ../index.php");
        exit();
    }

    // Validate password match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: ../index.php");
        exit();
    }

    // Check if username or email already exists
    $check_sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error'] = "Username or email already exists";
        header("Location: ../index.php");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insert_sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $email, $hashed_password, $role);

    if (mysqli_stmt_execute($insert_stmt)) {
        $_SESSION['success'] = "Account created successfully. Please login.";
        header("Location: ../index.php");
    } else {
        $_SESSION['error'] = "Error creating account";
        header("Location: ../index.php");
    }
    exit();
}
?> 