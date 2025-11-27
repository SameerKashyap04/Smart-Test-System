<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'examiner') {
	header('Location: ../index.php');
	exit();
}

$success = null;
$error = null;

// Handle Profile Update (Text Details)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $college = mysqli_real_escape_string($conn, $_POST['college']);
    $branch = mysqli_real_escape_string($conn, $_POST['branch']);
    
    $sql = "UPDATE users SET college = ?, branch = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $college, $branch, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Profile details updated successfully.";
        $_SESSION['college'] = $college;
        $_SESSION['branch'] = $branch;
    } else {
        $error = "Failed to update profile details.";
    }
}

// Handle Profile Photo Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid image file.';
    } else {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $type = mime_content_type($_FILES['profile_photo']['tmp_name']);
        if (!isset($allowed[$type])) {
            $error = 'Only JPG or PNG files are allowed.';
        } else {
            $ext = $allowed[$type];
            $dir = realpath(__DIR__ . '/../uploads');
            if ($dir === false) { mkdir(__DIR__ . '/../uploads'); $dir = realpath(__DIR__ . '/../uploads'); }
            $targetDir = $dir . DIRECTORY_SEPARATOR . 'profiles';
            if (!is_dir($targetDir)) { mkdir($targetDir); }
            
            $filename = 'profile_' . (int)$_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetPath)) {
                $relativePath = 'uploads/profiles/' . $filename;
                $u = mysqli_prepare($conn, 'UPDATE users SET profile_image = ? WHERE id = ?');
                mysqli_stmt_bind_param($u, 'si', $relativePath, $_SESSION['user_id']);
                if (mysqli_stmt_execute($u)) {
                    $success = 'Profile photo updated successfully.';
                } else {
                    $error = 'Failed to save profile photo path.';
                }
            } else {
                $error = 'Failed to upload file.';
            }
        }
    }
}

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

// Fetch current profile info
$info = [
    'username' => '', 
    'email' => '', 
    'college' => '', 
    'branch' => '', 
    'profile_image' => null, 
    'id_document_path' => null
];
$stmt = mysqli_prepare($conn, 'SELECT username, email, college, branch, profile_image, id_document_path FROM users WHERE id = ?');
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
	<title>Examiner Profile & Settings</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-img-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px;
            border: 5px solid #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            position: relative;
        }
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
        }
        .upload-icon {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0,0,0,0.6);
            color: white;
            text-align: center;
            padding: 5px;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .profile-img-container:hover .upload-icon {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-light">
	<div class="container py-4">
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h1 class="h4 mb-0"><i class="fas fa-user-tie me-2"></i>Examiner Profile</h1>
			<a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
		</div>
		<?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
		<?php if (!empty($success)): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
		
        <div class="row g-4">
            <!-- Profile Photo & Basic Info -->
            <div class="col-md-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="photoForm">
                            <input type="hidden" name="action" value="upload_photo">
                            <div class="profile-img-container" onclick="document.getElementById('profile_photo').click()">
                                <?php if (!empty($info['profile_image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($info['profile_image']); ?>" class="profile-img" alt="Profile">
                                <?php else: ?>
                                    <div class="profile-placeholder">
                                        <?php echo strtoupper(substr($info['username'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="upload-icon"><i class="fas fa-camera"></i> Change</div>
                            </div>
                            <input type="file" name="profile_photo" id="profile_photo" class="d-none" accept=".jpg,.jpeg,.png" onchange="document.getElementById('photoForm').submit()">
                        </form>
                        
                        <h4 class="mb-1"><?php echo htmlspecialchars($info['username']); ?></h4>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($info['email']); ?></p>
                        
                        <div class="list-group list-group-flush text-start">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-university me-2 text-primary"></i>College</span>
                                <span class="fw-bold text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($info['college'] ?: 'Not Set'); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-code-branch me-2 text-primary"></i>Branch</span>
                                <span class="fw-bold"><?php echo htmlspecialchars($info['branch'] ?: 'Not Set'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Details & Settings -->
			<div class="col-md-8">
                <!-- Personal Details Form -->
				<div class="card mb-4">
					<div class="card-header"><i class="fas fa-edit me-2"></i> Edit Personal Details</div>
					<div class="card-body">
						<form method="POST">
							<input type="hidden" name="action" value="update_profile">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">College Name</label>
                                    <input type="text" name="college" class="form-control" value="<?php echo htmlspecialchars($info['college']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Branch / Department</label>
                                    <select class="form-select" name="branch" required>
                                        <option value="">Select Branch</option>
                                        <?php 
                                        $branches = ["Computer Science", "Information Technology", "Electronics & Communication", "Mechanical Engineering", "Civil Engineering", "Electrical Engineering", "Other"];
                                        foreach($branches as $b) {
                                            $selected = ($info['branch'] == $b) ? 'selected' : '';
                                            echo "<option value='$b' $selected>$b</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
							<button class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
						</form>
					</div>
				</div>

                <!-- ID Card Section -->
				<div class="card mb-4">
					<div class="card-header"><i class="fas fa-id-card me-2"></i> Examiner ID Card</div>
					<div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center">
                                <?php if (!empty($info['id_document_path'])): ?>
                                    <?php if (preg_match('/\\.(jpg|jpeg|png)$/i', $info['id_document_path'])): ?>
                                        <img src="../<?php echo htmlspecialchars($info['id_document_path']); ?>" alt="ID" class="img-fluid rounded border mb-2" style="max-height: 150px;" />
                                    <?php else: ?>
                                        <div class="p-4 border rounded bg-light mb-2">
                                            <i class="fas fa-file-pdf fa-3x text-danger"></i>
                                        </div>
                                        <a href="../<?php echo htmlspecialchars($info['id_document_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-2">View Document</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="p-4 border rounded bg-light text-muted mb-2">
                                        <i class="fas fa-id-card fa-3x mb-2"></i><br>No ID Uploaded
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="upload_id">
                                    <div class="mb-3">
                                        <label class="form-label">Upload New ID (JPG, PNG, PDF)</label>
                                        <input type="file" name="id_document" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                                    </div>
                                    <button class="btn btn-primary"><i class="fas fa-upload me-1"></i> Upload ID</button>
                                </form>
                            </div>
                        </div>
					</div>
				</div>

                <!-- Change Password -->
				<div class="card">
					<div class="card-header"><i class="fas fa-key me-2"></i> Security</div>
					<div class="card-body">
						<form method="POST">
							<input type="hidden" name="action" value="change_password">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
							<button class="btn btn-warning text-white"><i class="fas fa-lock me-1"></i> Update Password</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>