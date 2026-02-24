<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/includes/db.php";

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===========================
   FETCH USER INFO
   =========================== */
$stmt = $conn->prepare("SELECT name, phone FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ===========================
   FETCH ADDRESSES
   =========================== */
$addresses = [];
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { $addresses[] = $row; }
$stmt->close();

/* ===========================
   CHECKOUT MODE
   =========================== */
$mode = $_GET['mode'] ?? 'cart';

$return_url = ($mode === "buy-now" && !empty($_SESSION['buy_now_return']))
    ? $_SESSION['buy_now_return']
    : "cart.php";

/* ===========================
   GET CART ITEMS
   =========================== */
$cart = ($mode === "buy-now" && !empty($_SESSION['buy_now']))
    ? $_SESSION['buy_now']
    : ($_SESSION['cart'] ?? []);

if (empty($cart)) {
    header("Location: index.php");
    exit;
}

/* ===========================
   FETCH PRODUCTS
   =========================== */
$ids = implode(",", array_map("intval", array_keys($cart)));
$sql = "SELECT * FROM products WHERE product_id IN ($ids)";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "<script>alert('Product(s) not found.'); window.location='index.php';</script>";
    exit;
}

$products = [];
while ($row = $result->fetch_assoc()) { $products[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - LokalMart</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
.product-img {
    width: 70px;
    height: 70px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #ddd;
}
</style>

</head>
<body>

<?php include __DIR__ . "/includes/header.php"; ?>

<div class="container py-5">
    <h2 class="mb-4">Checkout</h2>

    <a href="<?= htmlspecialchars($return_url, ENT_QUOTES) ?>" class="btn btn-secondary mb-4">
        <i class="bi bi-arrow-left"></i> Back
    </a>

    <div class="row">
        <div class="col-lg-8">

            <!-- ORDER SUMMARY -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <strong>Your Order</strong>
                </div>

                <div class="card-body">
                <?php 
                $totalAmount = 0;

                foreach ($products as $p):
                    $qty = $cart[$p['product_id']];
                    $itemTotal = $qty * $p['price'];
                    $totalAmount += $itemTotal;

                    $image_file = !empty($p['image']) ? $p['image'] : "no-image.png";
                    $image_path = "uploads/products/" . $image_file;

                    if (!file_exists($image_path)) {
                        $image_path = "assets/images/no-image.png";
                    }
                ?>

                    <div class="d-flex align-items-center border-bottom pb-3 mb-3">
                        <img src="<?= $image_path ?>" class="product-img me-3" alt="">
                        <div class="flex-grow-1">
                            <strong><?= htmlspecialchars($p['name']); ?></strong><br>
                            Qty: <?= $qty; ?> × ₱<?= number_format($p['price'], 2); ?>
                        </div>
                        <div class="fw-bold text-success">
                            ₱<?= number_format($itemTotal, 2); ?>
                        </div>
                    </div>

                <?php endforeach; ?>

                    <h4 class="text-end mt-3">
                        Total: <span class="text-success fw-bold">₱<?= number_format($totalAmount, 2); ?></span>
                    </h4>
                </div>
            </div>

            <!-- SHIPPING INFO -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <strong>Shipping Information</strong>
                </div>

                <div class="card-body">

                    <!-- 
                        FORM IS NOW HANDLED BY JAVASCRIPT BELOW.
                        If user selects COD → place_order.php 
                        If user selects GCash → gcash_payment.php
                    -->
                    <form id="checkoutForm" method="POST">

                        <input type="hidden" name="mode" value="<?= $mode; ?>">

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="fullname" class="form-control" required
                                   value="<?= htmlspecialchars($user_info['name'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Address</label>
                            <select name="address_id" class="form-select" required>
                                <option value="">-- Select an address --</option>
                                <?php foreach ($addresses as $addr): ?>
                                    <option value="<?= $addr['address_id'] ?>" <?= $addr['is_default'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($addr['label'] ?: 'Address') ?> - 
                                        <?= htmlspecialchars($addr['address']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" required
                                   value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                <option value="COD">Cash on Delivery (COD)</option>
                                <option value="GCash">GCash</option>
                            </select>
                        </div>

                        <button class="btn btn-warning btn-lg w-100" type="submit">
                            <i class="bi bi-credit-card"></i> Continue
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>

<script>
// Redirect user based on payment method
document.getElementById("checkoutForm").addEventListener("submit", function(e) {
    const payment = document.getElementById("payment_method").value;

    if (payment === "GCash") {
        this.action = "gcash_payment.php";   // Redirect to GCash page
    } else {
        this.action = "place_order.php";     // COD normal flow
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
