<?php
session_start();
require_once '../config/db.php';
require_once '../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if username or email already exists
    $check_sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['error'] = "Username or email already exists";
        header("Location: signup.php");
        exit();
    }

    // Generate OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user with is_verified = 0
    $sql = "INSERT INTO users (username, password, role, email, is_verified, otp_code, otp_expiry) VALUES (?, ?, ?, ?, 0, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $username, $hashed_password, $role, $email, $otp, $otp_expiry);
    
    if (mysqli_stmt_execute($stmt)) {
        // Send OTP via Email
        $mail = getMailer();
        if ($mail) {
            try {
                $mail->addAddress($email, $username);
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Email - Smart Test System';
                $mail->Body    = "
                    <h2>Welcome to Smart Test System!</h2>
                    <p>Your verification code is: <strong>$otp</strong></p>
                    <p>This code will expire in 10 minutes.</p>
                ";
                
                $mail->send();
                
                $_SESSION['verify_email'] = $email;
                $_SESSION['success'] = "Registration successful! Please check your email for the verification code.";
                header("Location: verify_email.php");
                exit();
                
            } catch (Exception $e) {
                // If mail fails, maybe delete the user or just show error?
                // Ideally, keep user but let them resend OTP.
                $_SESSION['error'] = "Registration successful but failed to send email. Please try logging in to resend verification.";
                $_SESSION['verify_email'] = $email;
                header("Location: verify_email.php"); // Or allow manual resend
                exit();
            }
        } else {
             $_SESSION['error'] = "Mailer configuration error.";
             header("Location: signup.php");
             exit();
        }
    } else {
        $_SESSION['error'] = "Registration failed. Please try again.";
        header("Location: signup.php");
        exit();
    }
} else {
    header("Location: signup.php");
    exit();
}
?>