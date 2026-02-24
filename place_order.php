<?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$buyer_id = $_SESSION['user_id'];

/* ============================
   VALIDATE REQUIRED FIELDS
   ============================ */
$required = ['fullname', 'phone', 'address_id', 'payment_method'];

foreach ($required as $r) {
    if (!isset($_POST[$r]) || trim($_POST[$r]) === '') {
        echo "<script>alert('Missing required field: $r'); history.back();</script>";
        exit;
    }
}

$fullname       = trim($_POST['fullname']);
$phone          = trim($_POST['phone']);
$address_id     = (int)$_POST['address_id'];
$payment_method = trim($_POST['payment_method']);
$mode           = $_POST['mode'] ?? "";

/* ============================
   LOAD CART OR BUY-NOW
   ============================ */
$cart = ($mode === "buy-now" && isset($_SESSION['buy_now']))
        ? $_SESSION['buy_now']
        : ($_SESSION['cart'] ?? []);

if (empty($cart)) {
    echo "<script>alert('Your cart is empty.'); window.location='index.php';</script>";
    exit;
}

/* ============================
   FETCH PRODUCT INFO
   ============================ */
$product_ids = implode(",", array_map('intval', array_keys($cart)));

$sql = "SELECT product_id, price, seller_id FROM products WHERE product_id IN ($product_ids)";
$result = $conn->query($sql);

$total_amount = 0;
$order_items  = [];

while ($row = $result->fetch_assoc()) {
    $qty = $cart[$row['product_id']];
    $subtotal = $qty * $row['price'];
    $total_amount += $subtotal;

    $order_items[] = [
        "product_id" => $row['product_id'],
        "seller_id"  => $row['seller_id'],
        "price"      => $row['price'],
        "qty"        => $qty
    ];
}

/* ============================
   CREATE ORDER
   ============================ */
$stmt = $conn->prepare("
    INSERT INTO orders (
        buyer_id, fullname, phone, address_id, total_amount, payment_method, status
    ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
");

$stmt->bind_param(
    "issids",
    $buyer_id,
    $fullname,
    $phone,
    $address_id,
    $total_amount,
    $payment_method
);

$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();

/* ============================
   INSERT ORDER ITEMS
   ============================ */
$stmt_item = $conn->prepare("
    INSERT INTO order_items (order_id, product_id, seller_id, quantity, price)
    VALUES (?, ?, ?, ?, ?)
");

foreach ($order_items as $item) {
    $stmt_item->bind_param(
        "iiiid",
        $order_id,
        $item['product_id'],
        $item['seller_id'],
        $item['qty'],
        $item['price']
    );
    $stmt_item->execute();
}

$stmt_item->close();

/* ============================
   CLEAR CART
   ============================ */
if ($mode === "buy-now") {
    unset($_SESSION['buy_now']);
} else {
    unset($_SESSION['cart']);
}

/* ============================
   REDIRECT AFTER ORDER CREATION
   ============================ */

if ($payment_method === "GCash") {
    // Redirect to the GCash input page with correct order_id
    header("Location: gcash_payment.php?order_id=" . $order_id);
    exit;
}

// COD
header("Location: order_success.php?order_id=" . $order_id);
exit;

?>
