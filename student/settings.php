<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
	header('Location: ../index.php');
	exit();
}

// Ensure required user columns exist
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN id_document_path VARCHAR(255) NULL");
@mysqli_query($conn, "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");

$success = null;
$error = null;

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
	$current = $_POST['current_password'] ?? '';
	$new = $_POST['new_password'] ?? '';
	$confirm = $_POST['confirm_password'] ?? '';

	if ($new !== $confirm) {
		$error = 'New password and confirmation do not match.';
	} elseif (strlen($new) < 8) {
		$error = 'New password must be at least 8 characters.';
	} else {
		$sql = 'SELECT password FROM users WHERE id = ?';
		$stmt = mysqli_prepare($conn, $sql);
		mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		$user = mysqli_fetch_assoc($result);
		if (!$user || !password_verify($current, $user['password'])) {
			$error = 'Current password is incorrect.';
		} else {
			$newHash = password_hash($new, PASSWORD_DEFAULT);
			$u = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
			mysqli_stmt_bind_param($u, 'si', $newHash, $_SESSION['user_id']);
			if (mysqli_stmt_execute($u)) {
				$success = 'Password updated successfully.';
			} else {
				$error = 'Failed to update password.';
			}
		}
	}
}

// Handle ID upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_id') {
	if (!isset($_FILES['id_document']) || $_FILES['id_document']['error'] !== UPLOAD_ERR_OK) {
		$error = 'Please select a valid image file (JPG, PNG, PDF).';
	} else {
		$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
		$type = mime_content_type($_FILES['id_document']['tmp_name']);
		if (!isset($allowed[$type])) {
			$error = 'Only JPG, PNG, or PDF files are allowed.';
		} else {
			$ext = $allowed[$type];
			$dir = realpath(__DIR__ . '/../uploads');
			if ($dir === false) {
				mkdir(__DIR__ . '/../uploads');
				$dir = realpath(__DIR__ . '/../uploads');
			}
			$targetDir = $dir . DIRECTORY_SEPARATOR . 'ids';
			if (!is_dir($targetDir)) {
				mkdir($targetDir);
			}
			$filename = 'student_' . (int)$_SESSION['user_id'] . '_' . time() . '.' . $ext;
			$targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
			if (move_uploaded_file($_FILES['id_document']['tmp_name'], $targetPath)) {
				$relativePath = 'uploads/ids/' . $filename;
				$u = mysqli_prepare($conn, 'UPDATE users SET id_document_path = ? WHERE id = ?');
				mysqli_stmt_bind_param($u, 'si', $relativePath, $_SESSION['user_id']);
				if (mysqli_stmt_execute($u)) {
					$success = 'ID document uploaded successfully.';
				} else {
					$error = 'Failed to save ID document path.';
				}
			} else {
				$error = 'Failed to upload file.';
			}
		}
	}
}

// Fetch current profile info
$info = ['email' => '', 'id_document_path' => null, 'email_verified' => 0];
$stmt = mysqli_prepare($conn, 'SELECT email, id_document_path, email_verified FROM users WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res) {
	$info = mysqli_fetch_assoc($res) ?: $info;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Student Settings</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
	<div class="container py-4">
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h1 class="h4 mb-0"><i class="fas fa-cog me-2"></i>Student Settings</h1>
			<a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
		</div>

		<?php if ($error): ?>
			<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
		<?php endif; ?>
		<?php if ($success): ?>
			<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
		<?php endif; ?>

		<div class="row g-4">
			<div class="col-md-6">
				<div class="card">
					<div class="card-header"><i class="fas fa-key me-2"></i> Change Password</div>
					<div class="card-body">
						<form method="POST">
							<input type="hidden" name="action" value="change_password">
							<div class="mb-3">
								<label class="form-label">Current Password</label>
								<input type="password" name="current_password" class="form-control" required>
							</div>
							<div class="mb-3">
								<label class="form-label">New Password</label>
								<input type="password" name="new_password" class="form-control" required>
								<small class="text-muted">At least 8 characters, include upper, lower, number, special.</small>
							</div>
							<div class="mb-3">
								<label class="form-label">Confirm New Password</label>
								<input type="password" name="confirm_password" class="form-control" required>
							</div>
							<button class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Password</button>
						</form>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card">
					<div class="card-header"><i class="fas fa-id-card me-2"></i> Upload ID (College ID)</div>
					<div class="card-body">
						<?php if (!empty($info['id_document_path'])): ?>
							<p class="mb-2">Current document:</p>
							<?php if (preg_match('/\\.(jpg|jpeg|png)$/i', $info['id_document_path'])): ?>
								<img src="../<?php echo htmlspecialchars($info['id_document_path']); ?>" alt="ID" style="max-width:100%;height:auto;border:1px solid #eee;border-radius:8px;" />
							<?php else: ?>
								<a href="../<?php echo htmlspecialchars($info['id_document_path']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="fas fa-file"></i> View Document</a>
							<?php endif; ?>
							<hr>
						<?php endif; ?>
						<form method="POST" enctype="multipart/form-data">
							<input type="hidden" name="action" value="upload_id">
							<div class="mb-3">
								<label class="form-label">Select File (JPG, PNG, or PDF)</label>
								<input type="file" name="id_document" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
							</div>
							<button class="btn btn-primary"><i class="fas fa-upload me-1"></i> Upload</button>
						</form>
					</div>
				</div>
				<div class="card mt-3">
					<div class="card-header"><i class="fas fa-envelope me-2"></i> Email Verification (OTP)</div>
					<div class="card-body">
						<p class="mb-2">Status: <?php echo !empty($info['email_verified']) ? '<span class="badge bg-success">Verified</span>' : '<span class="badge bg-warning text-dark">Not Verified</span>'; ?></p>
						<div class="d-flex gap-2">
							<input type="text" id="otp-input" class="form-control" placeholder="Enter OTP" style="max-width:200px;">
							<button class="btn btn-outline-primary" id="send-otp"><i class="fas fa-paper-plane me-1"></i> Send OTP</button>
							<button class="btn btn-primary" id="verify-otp"><i class="fas fa-check me-1"></i> Verify</button>
						</div>
						<small class="text-muted d-block mt-2">OTP will be sent to <?php echo htmlspecialchars($info['email']); ?>.</small>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		document.getElementById('send-otp').addEventListener('click', async function() {
			try {
				const res = await fetch('../auth/send_otp.php', { method: 'POST' });
				const data = await res.json();
				alert(data.success ? 'OTP sent to your email.' : (data.error || 'Failed to send OTP'));
			} catch (e) { alert('Failed to send OTP'); }
		});
		document.getElementById('verify-otp').addEventListener('click', async function() {
			const code = document.getElementById('otp-input').value.trim();
			if (!code) { alert('Enter OTP'); return; }
			try {
				const res = await fetch('../auth/verify_otp.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ code }) });
				const data = await res.json();
				if (data.success) { alert('Email verified'); location.reload(); } else { alert(data.error || 'Invalid OTP'); }
			} catch (e) { alert('Failed to verify OTP'); }
		});
	</script>
</body>
</html>


