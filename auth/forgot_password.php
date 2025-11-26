<?php
session_start();
require_once '../config/db.php';
require_once '../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if email exists
    $check_sql = "SELECT id, username FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $username = $user['username'];

        // Generate OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Update user with OTP
        $update_sql = "UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sss", $otp, $otp_expiry, $email);

        if (mysqli_stmt_execute($update_stmt)) {
            // Send Email
            $mail = getMailer();
            if ($mail) {
                try {
                    $mail->addAddress($email, $username);
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset OTP - Smart Test System';
                    $mail->Body    = "
                        <h2>Password Reset Request</h2>
                        <p>Hello $username,</p>
                        <p>You requested to reset your password. Your OTP is: <strong>$otp</strong></p>
                        <p>This code will expire in 10 minutes.</p>
                        <p>If you did not request this, please ignore this email.</p>
                    ";
                    
                    $mail->send();
                    
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['success'] = "OTP sent to your email address.";
                    header("Location: verify_reset.php");
                    exit();
                } catch (Exception $e) {
                    if (!$is_production) {
                        $_SESSION['reset_email'] = $email;
                        $_SESSION['success'] = "OTP sent (Dev Mode: Your OTP is $otp)";
                        header("Location: verify_reset.php");
                        exit();
                    } else {
                        $_SESSION['error'] = "Failed to send OTP. Please try again later.";
                    }
                }
            } else {
                 if (!$is_production) {
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['success'] = "OTP sent (Dev Mode: Your OTP is $otp)";
                    header("Location: verify_reset.php");
                    exit();
                 } else {
                    $_SESSION['error'] = "Mailer configuration error.";
                 }
            }
        } else {
            $_SESSION['error'] = "Database error. Please try again.";
        }
    } else {
        // Security: Don't reveal if email exists or not, but for UX usually we might say "User not found" or generic message.
        // For this project, let's be explicit for easier testing.
        $_SESSION['error'] = "Email address not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Smart Test System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="position-absolute top-0 end-0 p-3">
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <div class="auth-header">
                <h2><i class="fas fa-key me-2"></i>Forgot Password</h2>
                <p class="text-muted">Enter your email to receive an OTP</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">EMAIL ADDRESS</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-envelope text-primary"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" name="email" placeholder="Enter your registered email" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    Send OTP <i class="fas fa-paper-plane ms-2"></i>
                </button>

                <div class="text-center">
                    <a href="../index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
</body>
</html>

