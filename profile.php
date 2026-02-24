<?php
session_start();
include('includes/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'admin') {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// If form submitted
if(isset($_POST['upload'])) {
    if(!empty($_FILES['avatar']['name'])) {
        $file = $_FILES['avatar'];

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png'];

        if(in_array(strtolower($ext), $allowed)) {
            $filename = "avatar_" . $user_id . "_" . time() . "." . $ext;
            $path = "uploads/avatars/" . $filename;

            move_uploaded_file($file['tmp_name'], $path);

            // Save to DB
            $stmt = $conn->prepare("UPDATE users SET avatar=? WHERE id=?");
            $stmt->bind_param("si", $filename, $user_id);
            $stmt->execute();

            $_SESSION['user_avatar'] = $filename;
            $msg = "Avatar updated successfully!";
        } else {
            $msg = "Only JPG / PNG allowed.";
        }
    } else {
        $msg = "Please select an image.";
    }
}

// Get user data
$stmt = $conn->prepare("SELECT name, email, avatar FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html>
<head>
<title>My Profile</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="card p-4 shadow">
    <h3 class="fw-bold text-success">My Profile</h3>
    
    <?php if(isset($msg)): ?>
      <div class="alert alert-info"><?= $msg ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="text-center mb-3">
        <img src="<?= $user['avatar'] ? 'uploads/avatars/'.$user['avatar'] : 'assets/default_user.png' ?>" 
             class="rounded-circle" width="120" height="120">
      </div>

      <div class="mb-3">
        <label class="form-label">Change Avatar</label>
        <input type="file" name="avatar" class="form-control" accept="image/*">
      </div>

      <button class="btn btn-success w-100" name="upload">Update Avatar</button>
    </form>

    <a href="index.php" class="btn btn-outline-secondary mt-3">Back</a>
  </div>
</div>

</body>
</html>
