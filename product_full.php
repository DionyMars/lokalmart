<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/includes/db.php';

// ✅ Validate product_id
if (!isset($_GET['product_id'])) {
    die("Product not found");
}
$product_id = intval($_GET['product_id']);

// ✅ Fetch product details
$stmt = $conn->prepare("
    SELECT p.*, s.shop_name, s.seller_id
    FROM products p
    JOIN sellers s ON p.seller_id = s.seller_id
    WHERE p.product_id = ? AND p.visibility = 'visible'
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found");
}

// ✅ Fetch product images
$images_q = $conn->prepare("SELECT image FROM product_images WHERE product_id = ?");
$images_q->bind_param("i", $product_id);
$images_q->execute();
$images_result = $images_q->get_result();
$images = array_column($images_result->fetch_all(MYSQLI_ASSOC), 'image');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['name']) ?> - LokalMart</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    .unit-price {
        font-size: 1.5rem;
        font-weight: bold;
        color: #198754; /* Bootstrap success color */
        margin-bottom: 1rem;
    }
    .total-price {
        font-size: 1.25rem;
        font-weight: bold;
        color: #0d6efd; /* Bootstrap primary color */
        margin-top: 0.5rem;
    }
</style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6 mb-4">
            <?php if (!empty($images)): ?>
                <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner border rounded shadow-sm">
                        <?php foreach ($images as $i => $img): ?>
                            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                <img src="uploads/products/<?= htmlspecialchars($img) ?>" class="d-block w-100" alt="Product Image">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                </div>
            <?php else: ?>
                <img src="assets/images/no-image.png" class="img-fluid rounded shadow-sm" alt="No Image Available">
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="col-md-6">
            <h2 class="fw-bold"><?= htmlspecialchars($product['name']) ?></h2>

            <!-- Unit Price Highlighted -->
            <p class="unit-price">₱<span id="unitPrice"><?= number_format($product['price'], 2) ?></span></p>

            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

            <p class="fw-semibold">
                Shop: 
                <a href="seller.php?seller_id=<?= $product['seller_id'] ?>" class="text-decoration-none">
                    <?= htmlspecialchars($product['shop_name']) ?>
                </a>
            </p>

            <!-- Quantity Input -->
            <label class="form-label fw-semibold">Quantity:</label>
            <input type="number" id="quantity" class="form-control mb-2" value="1" min="1" oninput="updatePrice()">

            <!-- Total Price Below Quantity -->
            <p class="total-price">Total: ₱<span id="totalPrice"><?= number_format($product['price'], 2) ?></span></p>

            <!-- Buttons -->
            <div class="d-flex gap-2 mt-3">
                <button id="addToCartBtn" type="button" class="btn btn-success flex-fill">
                    <i class="bi bi-cart-plus"></i> Add to Cart
                </button>

                <form action="buy_now.php" method="POST" class="flex-fill">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <input type="hidden" id="buyQty" name="qty" value="1">
                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES) ?>">
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="bi bi-bag-check"></i> Buy Now
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Price Update Script -->
<script>
const unitPriceValue = <?= $product['price'] ?>;

function updatePrice() {
    let qty = parseInt(document.getElementById('quantity').value) || 1;
    if (qty < 1) qty = 1;

    let total = unitPriceValue * qty;

    document.getElementById('unitPrice').textContent = unitPriceValue.toFixed(2);
    document.getElementById('totalPrice').textContent = total.toFixed(2);
    document.getElementById('buyQty').value = qty;
}

// Initialize prices
updatePrice();
</script>

<!-- Add to Cart AJAX -->
<script>
document.getElementById('addToCartBtn').addEventListener('click', function() {
    const qty = parseInt(document.getElementById('quantity').value) || 1;
    const productId = <?= $product_id ?>;

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}&qty=${qty}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-bg-success border-0 position-fixed bottom-0 end-0 m-3';
            toast.style.zIndex = '1055';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">✅ ${data.message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toast);
            new bootstrap.Toast(toast).show();
        } else {
            alert('⚠️ ' + data.message);
        }
    })
    .catch(err => alert('Error adding to cart: ' + err));
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
