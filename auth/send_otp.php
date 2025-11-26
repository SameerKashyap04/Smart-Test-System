<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit(); }

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Generate 6-digit code
$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', time() + 10 * 60);

// Save
$stmt = mysqli_prepare($conn, 'INSERT INTO user_otps (user_id, code, expires_at) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'iss', $_SESSION['user_id'], $code, $expiresAt);
if (!mysqli_stmt_execute($stmt)) { http_response_code(500); echo json_encode(['error' => 'DB error']); exit(); }

// Fetch email
$u = mysqli_prepare($conn, 'SELECT email FROM users WHERE id = ?');
mysqli_stmt_bind_param($u, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($u);
$emailRes = mysqli_stmt_get_result($u);
$row = mysqli_fetch_assoc($emailRes);
$email = $row ? $row['email'] : null;

// Send mail (simple PHP mail for demo)
$sent = false;
if ($email) {
    $subject = 'Your Verification Code';
    $message = "Your OTP is: $code\nIt expires in 10 minutes.";
    $headers = 'From: no-reply@smart-test-system.local';
    $sent = @mail($email, $subject, $message, $headers);
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'sent' => $sent]);


