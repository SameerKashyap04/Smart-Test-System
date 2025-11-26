<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit(); }

$input = json_decode(file_get_contents('php://input'), true);
$code = isset($input['code']) ? trim($input['code']) : '';
if ($code === '') { http_response_code(400); echo json_encode(['error' => 'Code required']); exit(); }

// Validate OTP
$stmt = mysqli_prepare($conn, 'SELECT id, expires_at, used FROM user_otps WHERE user_id = ? AND code = ? ORDER BY id DESC LIMIT 1');
mysqli_stmt_bind_param($stmt, 'is', $_SESSION['user_id'], $code);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$otp = mysqli_fetch_assoc($res);

header('Content-Type: application/json');
if (!$otp) { echo json_encode(['error' => 'Invalid code']); exit(); }
if ((int)$otp['used'] === 1) { echo json_encode(['error' => 'Code already used']); exit(); }
if (strtotime($otp['expires_at']) < time()) { echo json_encode(['error' => 'Code expired']); exit(); }

// Mark used
$u = mysqli_prepare($conn, 'UPDATE user_otps SET used = 1 WHERE id = ?');
mysqli_stmt_bind_param($u, 'i', $otp['id']);
mysqli_stmt_execute($u);

// Mark email verified
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
$v = mysqli_prepare($conn, 'UPDATE users SET email_verified = 1 WHERE id = ?');
mysqli_stmt_bind_param($v, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($v);

echo json_encode(['success' => true]);


