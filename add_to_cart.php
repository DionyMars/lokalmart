<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/includes/db.php';

// ✅ Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Please log in first."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id'] ?? 0);
$qty = intval($_POST['qty'] ?? 1);

// ✅ Validate input
if ($product_id <= 0 || $qty <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid product or quantity."]);
    exit;
}

// ✅ Check if the product exists and is visible
$check = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? AND visibility='visible'");
$check->bind_param("i", $product_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Product not found."]);
    exit;
}

// ✅ Check if the item already exists in the cart
$stmt = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update the quantity
    $row = $result->fetch_assoc();
    $new_qty = $row['quantity'] + $qty;

    $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
    $update->bind_param("ii", $new_qty, $row['cart_id']);
    $update->execute();
} else {
    // Insert a new record
    $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $insert->bind_param("iii", $user_id, $product_id, $qty);
    $insert->execute();
}

// ✅ Respond to AJAX with success
echo json_encode(["status" => "success", "message" => "Added to cart successfully!"]);
exit;
?>
