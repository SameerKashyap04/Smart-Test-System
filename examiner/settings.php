<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
	header('Location: ../index.php');
	exit();
}

    // Check if column exists before trying to add it
    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'id_document_path'");
    if (mysqli_num_rows($result) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN id_document_path VARCHAR(255) NULL");
    }

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
	$current = $_POST['current_password'] ?? '';
	$new = $_POST['new_password'] ?? '';
	$confirm = $_POST['confirm_password'] ?? '';
	if ($new !== $confirm) {
		$error = 'New password and confirmation do not match.';
	} elseif (strlen($new) < 8) {
		$error = 'New password must be at least 8 characters.';
	} else {
		$stmt = mysqli_prepare($conn, 'SELECT password FROM users WHERE id = ?');
		mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
		mysqli_stmt_execute($stmt);
		$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
		if (!$user || !password_verify($current, $user['password'])) {
			$error = 'Current password is incorrect.';
		} else {
			$newHash = password_hash($new, PASSWORD_DEFAULT);
			$u = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
			mysqli_stmt_bind_param($u, 'si', $newHash, $_SESSION['user_id']);
			if (mysqli_stmt_execute($u)) { $success = 'Password updated successfully.'; } else { $error = 'Failed to update password.'; }
		}
	}
}

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
			if ($dir === false) { mkdir(__DIR__ . '/../uploads'); $dir = realpath(__DIR__ . '/../uploads'); }
			$targetDir = $dir . DIRECTORY_SEPARATOR . 'ids';
			if (!is_dir($targetDir)) { mkdir($targetDir); }
			$filename = 'examiner_' . (int)$_SESSION['user_id'] . '_' . time() . '.' . $ext;
			$targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
			if (move_uploaded_file($_FILES['id_document']['tmp_name'], $targetPath)) {
				$relativePath = 'uploads/ids/' . $filename;
				$u = mysqli_prepare($conn, 'UPDATE users SET id_document_path = ? WHERE id = ?');
				mysqli_stmt_bind_param($u, 'si', $relativePath, $_SESSION['user_id']);
				if (mysqli_stmt_execute($u)) { $success = 'ID document uploaded successfully.'; } else { $error = 'Failed to save ID document path.'; }
			} else { $error = 'Failed to upload file.'; }
		}
	}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Examiner Settings</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
	<div class="container py-4">
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h1 class="h4 mb-0"><i class="fas fa-cog me-2"></i>Examiner Settings</h1>
			<a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
		</div>
		<?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
		<?php if (!empty($success)): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
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
					<div class="card-header"><i class="fas fa-id-card me-2"></i> Upload ID</div>
					<div class="card-body">
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
			</div>
		</div>
	</div>
</body>
</html>


