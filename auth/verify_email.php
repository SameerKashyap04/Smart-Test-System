<?php
session_start();
require_once '../config/db.php';
require_once '../config/mailer.php';

if (!isset($_SESSION['verify_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['verify_email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['otp'])) {
        $otp = $_POST['otp'];
        
        // Verify OTP
        $sql = "SELECT * FROM users WHERE email = ? AND otp_code = ? AND otp_expiry > NOW() AND is_verified = 0";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $otp);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // Success
            $update_sql = "UPDATE users SET is_verified = 1, otp_code = NULL, otp_expiry = NULL WHERE email = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "s", $email);
            mysqli_stmt_execute($update_stmt);
            
            unset($_SESSION['verify_email']);
            $_SESSION['success'] = "Email verified successfully! You can now login.";
            header("Location: login.php");
            exit();
        } else {
            $error = "Invalid or expired OTP.";
        }
    } elseif (isset($_POST['resend'])) {
        // Resend OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $update_sql = "UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "sss", $otp, $otp_expiry, $email);
        mysqli_stmt_execute($stmt);
        
        $mail = getMailer();
        if ($mail) {
            try {
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Email - Smart Test System';
                $mail->Body    = "
                    <h2>Welcome to Smart Test System!</h2>
                    <p>Your new verification code is: <strong>$otp</strong></p>
                    <p>This code will expire in 10 minutes.</p>
                ";
                $mail->send();
                $success = "New OTP sent to your email.";
            } catch (Exception $e) {
                $error = "Failed to send email. Error: {$mail->ErrorInfo}";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Smart Test System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verify-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .otp-input {
            letter-spacing: 0.5em;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="verify-container text-center">
        <i class="fas fa-envelope-open-text fa-3x text-primary mb-3"></i>
        <h3>Verify Your Email</h3>
        <p class="text-muted">Enter the 6-digit code sent to<br><strong><?php echo htmlspecialchars($email); ?></strong></p>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <input type="text" name="otp" class="form-control otp-input" maxlength="6" pattern="\d{6}" required placeholder="000000">
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Verify Email</button>
        </form>
        
        <form method="POST">
            <input type="hidden" name="resend" value="1">
            <button type="submit" class="btn btn-link text-decoration-none">Resend Code</button>
        </form>
        
        <div class="mt-3">
            <a href="login.php" class="text-muted small">Back to Login</a>
        </div>
    </div>
</body>
</html>

