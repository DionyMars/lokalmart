<?php
session_start();
ob_start(); // Prevent “headers already sent” issues

include "includes/db.php";

// ------------------------
// LOGIN CHECK FIRST
// ------------------------
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ------------------------
// FETCH CART ITEMS
// ------------------------
$sql = "
    SELECT c.cart_id, c.quantity, 
           p.product_id, p.name, p.description, p.price, p.image
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Include header AFTER login validation
include "includes/header.php";

// ------------------------
// IF CART IS EMPTY
// ------------------------
if ($result->num_rows === 0): ?>

<div class="container p-5 text-center">
    <h3>Your cart is empty 🛒</h3>
    <a href="index.php" class="btn btn-success mt-3">Continue Shopping</a>
</div>

<?php 
include "includes/footer.php";
ob_end_flush();
exit;
endif;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart - LokalMart</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
    img.cart-thumb {
        width: 90px;
        height: 90px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
</style>
</head>

<body>

<div class="container py-4">

    <h2 class="mb-4">Your Cart 🛒</h2>

    <table class="table table-bordered align-middle text-center">
        <thead class="table-success">
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>Thumbnail</th>
                <th>Product</th>
                <th>Unit Price</th>
                <th width="120">Qty</th>
                <th>Total</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($row = $result->fetch_assoc()): 
            $total = $row['price'] * $row['quantity'];
            $img = trim($row['image']);
            $imagePath = "uploads/products/" . $img;

            if (empty($img) || !file_exists($imagePath)) {
                $imagePath = "images/default-product.png";
            }
        ?>
            <tr data-id="<?= $row['cart_id'] ?>" data-price="<?= $row['price'] ?>">

                <td>
                    <input type="checkbox" class="product-check" value="<?= $row['product_id'] ?>">
                </td>

                <td>
                    <a href="product_full.php?product_id=<?= $row['product_id'] ?>">
                        <img src="<?= htmlspecialchars($imagePath) ?>" class="cart-thumb shadow-sm">
                    </a>
                </td>

                <td class="text-start">
                    <a href="product_full.php?product_id=<?= $row['product_id'] ?>" 
                       class="fw-bold text-dark text-decoration-none">
                        <?= htmlspecialchars($row['name']) ?>
                    </a>
                    <div>
                        <small class="text-muted">
                            <?= htmlspecialchars(substr($row['description'], 0, 60)) ?>...
                        </small>
                    </div>
                </td>

                <td>₱<?= number_format($row['price'], 2) ?></td>

                <td>
                    <input type="number" 
                           class="form-control form-control-sm qty-input text-center mx-auto" 
                           value="<?= $row['quantity'] ?>" 
                           min="1" 
                           style="width:70px;">
                </td>

                <td class="rowTotal fw-semibold">
                    ₱<?= number_format($total, 2) ?>
                </td>

                <td>
                    <a href="remove_from_cart.php?id=<?= $row['cart_id'] ?>" 
                       class="btn btn-danger btn-sm">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>

            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h4 class="text-end mb-4">
        Grand Total: ₱<span id="selectedTotal">0.00</span>
    </h4>

    <div class="text-end mt-3">
        <button class="btn btn-warning btn-lg" id="checkoutBtn" disabled>
            Proceed to Checkout
        </button>
    </div>

</div>

<script>

// ELEMENTS
const checkboxes = document.querySelectorAll('.product-check');
const totalDisplay = document.getElementById('selectedTotal');
const checkoutBtn = document.getElementById('checkoutBtn');
const selectAll = document.getElementById('selectAll');

// ------------------------
// UPDATE TOTALS
// ------------------------
function updateTotals() {
    let selectedSum = 0;

    document.querySelectorAll("tr[data-id]").forEach(row => {
        const qty = parseInt(row.querySelector(".qty-input").value) || 1;
        const price = parseFloat(row.dataset.price);
        const rowTotal = qty * price;

        row.querySelector(".rowTotal").innerHTML =
            "₱" + rowTotal.toLocaleString(undefined, { minimumFractionDigits: 2 });

        if (row.querySelector(".product-check").checked) {
            selectedSum += rowTotal;
        }
    });

    totalDisplay.innerText = selectedSum.toLocaleString(undefined, { minimumFractionDigits: 2 });
    checkoutBtn.disabled = selectedSum === 0;
}

// SELECT ALL
selectAll.addEventListener("change", () => {
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateTotals();
});

// INDIVIDUAL CHECKBOXES
checkboxes.forEach(cb => cb.addEventListener("change", updateTotals));

// UPDATE QUANTITY IN DB
document.querySelectorAll(".qty-input").forEach(input => {
    input.addEventListener("input", function() {
        const row = this.closest("tr");
        fetch("update_cart.php", {
            method: "POST",
            body: new URLSearchParams({
                cart_id: row.dataset.id,
                qty: this.value
            })
        }).then(updateTotals);
    });
});

// ------------------------
// CHECKOUT CLICK
// ------------------------
checkoutBtn.addEventListener("click", () => {

    const selectedProducts = {};

    document.querySelectorAll('.product-check:checked').forEach(cb => {
        const row = cb.closest("tr");
        const qty = parseInt(row.querySelector(".qty-input").value) || 1;
        selectedProducts[cb.value] = qty;
    });

    if (Object.keys(selectedProducts).length === 0) return;

    fetch("set_cart_session.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart: selectedProducts })
    }).then(() => {
        window.location.href = "checkout.php?return_url=cart.php";
    });

});

updateTotals();

</script>

<?php 
include "includes/footer.php"; 
ob_end_flush();
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
