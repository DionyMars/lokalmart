<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$stmt = $conn->prepare("SELECT name, email, avatar FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadOk = true;
    $avatarFileName = $user['avatar']; // Keep old avatar by default

    // If user uploaded new image
    if (!empty($_FILES['avatar']['name'])) {
        $targetDir = "uploads/avatars/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = basename($_FILES["avatar"]["name"]);
        $targetFile = $targetDir . uniqid() . "_" . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validate image type
        $allowedTypes = ['jpg', 'jpeg', 'png'];
        if (!in_array($fileType, $allowedTypes)) {
            $message = "<div class='alert alert-danger'>Only JPG, JPEG, and PNG files are allowed.</div>";
            $uploadOk = false;
        }

        // Validate size (2MB max)
        if ($_FILES["avatar"]["size"] > 2 * 1024 * 1024) {
            $message = "<div class='alert alert-danger'>File size must be less than 2MB.</div>";
            $uploadOk = false;
        }

        if ($uploadOk) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile)) {
                // Delete old avatar if exists
                if (!empty($user['avatar']) && file_exists("uploads/avatars/" . $user['avatar'])) {
                    unlink("uploads/avatars/" . $user['avatar']);
                }
                $avatarFileName = basename($targetFile);
            } else {
                $message = "<div class='alert alert-danger'>Error uploading your file.</div>";
                $uploadOk = false;
            }
        }
    }

    if ($uploadOk) {
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE user_id = ?");
        $stmt->bind_param("si", $avatarFileName, $user_id);
        $stmt->execute();
        $stmt->close();

        $message = "<div class='alert alert-success'>Profile updated successfully!</div>";

        // Refresh user data
        $stmt = $conn->prepare("SELECT name, email, avatar FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile - LokalMart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . "/includes/header.php"; ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm p-4">
        <h4 class="fw-bold text-success mb-3">Edit Profile</h4>
        <?= $message ?>

        <form method="POST" enctype="multipart/form-data">
          <div class="text-center mb-3">
            <img src="<?= !empty($user['avatar']) ? 'uploads/avatars/' . htmlspecialchars($user['avatar']) : 'assets/default_user.png'; ?>" 
                 alt="Avatar" class="rounded-circle mb-3" width="120" height="120">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Full Name</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Upload New Avatar</label>
            <input type="file" name="avatar" class="form-control" accept=".jpg,.jpeg,.png">
            <div class="form-text text-muted">Only JPG/PNG files up to 2MB are allowed.</div>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-success"><i class="bi bi-upload"></i> Save Changes</button>
          </div>
        </form>

        <div class="text-center mt-3">
          <a href="my_account.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to My Account</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
