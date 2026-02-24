<?php
session_start();
include "includes/db.php";

/* ================================
   REQUIRE LOGIN
================================ */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$buyer_id = $_SESSION['user_id'];

/* ================================
   CHECK ORDER ID
================================ */
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo "<script>alert('Invalid order.'); window.location='index.php';</script>";
    exit;
}

$order_id = (int)$_GET['order_id'];

/* ================================
   VERIFY ORDER BELONGS TO BUYER
================================ */
$stmt = $conn->prepare("
    SELECT order_id, total_amount, status 
    FROM orders 
    WHERE order_id = ? AND buyer_id = ?
");
$stmt->bind_param("ii", $order_id, $buyer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "<script>alert('Order not found or access denied.'); window.location='index.php';</script>";
    exit;
}

/* ================================
   HANDLE PAYMENT SUBMISSION
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $gcash_number = trim($_POST['gcash_number']);

    // Validate GCash number
    if (empty($gcash_number)) {
        echo "<script>alert('Please enter your GCash number.');</script>";
    }
    else if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== 0) {
        echo "<script>alert('Please upload a valid payment screenshot.');</script>";
    }
    else {

        $file = $_FILES['receipt'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];

        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            echo "<script>alert('Only JPG or PNG images are allowed.');</script>";
        }
        // Validate size (< 2MB)
        else if ($file['size'] > 2 * 1024 * 1024) {
            echo "<script>alert('Image file must not exceed 2MB.');</script>";
        }
        else {

            // Prepare upload directory
            if (!file_exists("uploads/gcash")) {
                mkdir("uploads/gcash", 0777, true);
            }

            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = "gcash_" . $order_id . "_" . time() . "." . $ext;
            $upload_path = "uploads/gcash/" . $new_filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {

                // Update database
                $stmt = $conn->prepare("
                    UPDATE orders
                    SET payment_method = 'GCash',
                        gcash_number = ?,
                        gcash_receipt = ?,
                        status = 'awaiting_verification'
                    WHERE order_id = ?
                ");
                $stmt->bind_param("ssi", $gcash_number, $new_filename, $order_id);
                $stmt->execute();
                $stmt->close();

                echo "<script>
                        alert('GCash payment submitted successfully!');
                        window.location='order_success.php?order_id=$order_id';
                      </script>";
                exit;

            } else {
                echo "<script>alert('Failed to upload screenshot. Try again.');</script>";
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
<title>GCash Payment - LokalMart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include "includes/header.php"; ?>

<div class="container py-5">

    <a href="checkout.php" class="btn btn-secondary mb-4">
        <i class="bi bi-arrow-left"></i> Back
    </a>

    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">GCash Payment</h4>
        </div>

        <div class="card-body">

            <p><strong>Order ID:</strong> <?= $order_id ?></p>
            <p><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>

            <hr>

            <form action="" method="POST" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">GCash Number</label>
                    <input type="text" name="gcash_number" class="form-control" placeholder="09xxxxxxxxx" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Upload Screenshot (Max 2MB)</label>
                    <input type="file" name="receipt" class="form-control" accept="image/*" required>
                </div>

                <button class="btn btn-primary w-100 btn-lg">
                    Submit GCash Payment
                </button>

            </form>

        </div>
    </div>

</div>

<?php include "includes/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
