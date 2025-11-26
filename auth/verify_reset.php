<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp_input = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];
    
    // Verify OTP
    $sql = "SELECT * FROM users WHERE email = ? AND otp_code = ? AND otp_expiry > NOW()";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $email, $otp_input);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // OTP Valid
        $_SESSION['allow_password_reset'] = true;
        header("Location: reset_new_password.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid or expired OTP. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Smart Test System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .otp-input {
            width: 45px;
            height: 45px;
            text-align: center;
            font-size: 1.2rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg);
            color: var(--text-color);
        }
        .otp-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 148, 136, 0.25);
            outline: none;
        }
    </style>
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
                <h2><i class="fas fa-shield-alt me-2"></i>Verify OTP</h2>
                <p class="text-muted">Enter the 6-digit code sent to <?php echo htmlspecialchars($email); ?></p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger mb-4" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="otpForm">
                <div class="mb-4 text-center">
                    <div class="d-flex justify-content-center gap-2">
                        <input type="text" class="otp-input" name="otp1" maxlength="1" pattern="[0-9]" required autofocus onkeyup="moveToNext(this, 'otp2')">
                        <input type="text" class="otp-input" name="otp2" maxlength="1" pattern="[0-9]" required onkeyup="moveToNext(this, 'otp3', 'otp1')">
                        <input type="text" class="otp-input" name="otp3" maxlength="1" pattern="[0-9]" required onkeyup="moveToNext(this, 'otp4', 'otp2')">
                        <input type="text" class="otp-input" name="otp4" maxlength="1" pattern="[0-9]" required onkeyup="moveToNext(this, 'otp5', 'otp3')">
                        <input type="text" class="otp-input" name="otp5" maxlength="1" pattern="[0-9]" required onkeyup="moveToNext(this, 'otp6', 'otp4')">
                        <input type="text" class="otp-input" name="otp6" maxlength="1" pattern="[0-9]" required onkeyup="moveToNext(this, null, 'otp5')">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    Verify & Continue <i class="fas fa-arrow-right ms-2"></i>
                </button>

                <div class="text-center">
                    <p class="text-muted mb-0">Didn't receive code? <a href="forgot_password.php" class="text-primary fw-bold">Resend</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script>
        function moveToNext(current, nextFieldID, prevFieldID) {
            if (current.value.length >= 1) {
                if (nextFieldID) {
                    document.getElementsByName(nextFieldID)[0].focus();
                }
            } else if (prevFieldID && event.key === "Backspace") {
                 document.getElementsByName(prevFieldID)[0].focus();
            }
        }
    </script>
</body>
</html>

