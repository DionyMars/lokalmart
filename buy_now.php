<?php
session_start();
include __DIR__ . "/includes/db.php";

// ✅ Validate product_id
if (!isset($_POST['product_id'])) {
    header("Location: index.php");
    exit;
}

$product_id = intval($_POST['product_id']);

// ✅ Get quantity (default to 1)
$qty = isset($_POST['qty']) ? max(1, intval($_POST['qty'])) : 1;

// ✅ Get return URL if provided (use index.php as fallback)
$return_url = isset($_POST['return_url']) && !empty($_POST['return_url'])
    ? $_POST['return_url']
    : "index.php";

// ✅ Store in session for buy now
$_SESSION['buy_now'] = [
    $product_id => $qty
];

// ✅ Store return URL in session to use on checkout Back button
$_SESSION['buy_now_return'] = $return_url;

// ✅ Redirect to checkout page in buy-now mode
header("Location: checkout.php?mode=buy-now");
exit;
