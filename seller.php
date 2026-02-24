<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/includes/db.php';

// Validate seller_id
if (!isset($_GET['seller_id'])) {
    die("Seller not found");
}
$seller_id = intval($_GET['seller_id']);

// Fetch seller info
$stmt = $conn->prepare("
    SELECT seller_id, shop_name, shop_logo, shop_description, shop_address, phone 
    FROM sellers 
    WHERE seller_id = ?
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

if (!$seller) {
    die("Seller not found");
}

// Determine shop logo
$shop_logo = 'no-avatar.png';
if (!empty($seller['shop_logo'])) {
    $file_path = __DIR__ . "/uploads/sellers/" . $seller['shop_logo'];
    if (file_exists($file_path)) {
        $shop_logo = $seller['shop_logo'];
    }
}

// Fetch seller's products
$products_stmt = $conn->prepare("
    SELECT product_id, name, price 
    FROM products 
    WHERE seller_id = ? AND visibility = 'visible' 
    ORDER BY product_id DESC
");
$products_stmt->bind_param("i", $seller_id);
$products_stmt->execute();
$products_result = $products_stmt->get_result();
$products = $products_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($seller['shop_name']) ?> - Seller Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    .seller-avatar {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #198754;
    }
    .product-card img {
        height: 200px;
        object-fit: cover;
    }
</style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container py-5">
    <!-- Seller Info -->
    <div class="row mb-5 align-items-center">
        <div class="col-md-3 text-center">
            <img src="uploads/sellers/<?= htmlspecialchars($shop_logo) ?>" alt="Seller Avatar" class="seller-avatar mb-3">
        </div>
        <div class="col-md-9">
            <h2 class="fw-bold"><?= htmlspecialchars($seller['shop_name']) ?></h2>

            <?php if (!empty($seller['shop_description'])): ?>
                <p><?= nl2br(htmlspecialchars($seller['shop_description'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($seller['shop_address'])): ?>
                <p><i class="bi bi-geo-alt-fill"></i> Address: <?= htmlspecialchars($seller['shop_address']) ?></p>
            <?php endif; ?>

            <?php if (!empty($seller['phone'])): ?>
                <p><i class="bi bi-telephone-fill"></i> Contact: <?= htmlspecialchars($seller['phone']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seller Products -->
    <h4 class="mb-4">Products by <?= htmlspecialchars($seller['shop_name']) ?>:</h4>
    <?php if (!empty($products)): ?>
        <div class="row g-4">
            <?php foreach ($products as $product): ?>
                <div class="col-md-3">
                    <div class="card product-card h-100 shadow-sm">
                        <?php
                        // Fetch first product image
                        $img_stmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? LIMIT 1");
                        $img_stmt->bind_param("i", $product['product_id']);
                        $img_stmt->execute();
                        $img_result = $img_stmt->get_result()->fetch_assoc();
                        $product_img = 'no-image.png';
                        if (!empty($img_result['image']) && file_exists(__DIR__ . "/uploads/products/" . $img_result['image'])) {
                            $product_img = $img_result['image'];
                        }
                        ?>
                        <a href="product_full.php?product_id=<?= $product['product_id'] ?>">
                            <img src="uploads/products/<?= htmlspecialchars($product_img) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                        </a>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text text-success fw-bold">₱<?= number_format($product['price'], 2) ?></p>
                        </div>
                        <div class="card-footer text-center">
                            <a href="product_full.php?product_id=<?= $product['product_id'] ?>" class="btn btn-primary w-100">
                                View Product
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted">This seller has not uploaded any products yet.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
