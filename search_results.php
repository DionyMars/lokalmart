<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
// Adjust path relative to this file
include __DIR__ . '/includes/db.php';

// Include navbar/header
include __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LokalMart - Search Results</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Main Styles -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container mt-4">
    <h2>Search Results for: <?= htmlspecialchars($_GET['q'] ?? '') ?></h2>
    
    <?php
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($q !== ''):
        $like = "%{$q}%";

        $stmt = $conn->prepare("
            SELECT 
                p.product_id AS id,
                p.name AS title,
                'product' AS type,
                p.image AS image,
                p.price AS price,
                s.shop_name AS shop
            FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.seller_id
            WHERE p.name LIKE ? AND p.visibility = 'visible'

            UNION

            SELECT 
                s.seller_id AS id,
                s.shop_name AS title,
                'shop' AS type,
                NULL AS image,
                NULL AS price,
                NULL AS shop
            FROM sellers s
            WHERE s.shop_name LIKE ? AND s.status = 'approved'

            LIMIT 20
        ");
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0):
            echo '<p class="text-muted">No results found.</p>';
        else:
            while ($row = $result->fetch_assoc()):
                $title = htmlspecialchars($row['title']);
                if ($row['type'] === 'product') {
                    $image = !empty($row['image']) 
                             ? 'uploads/products/' . htmlspecialchars($row['image']) 
                             : 'assets/images/no-image.png';
                } else {
                    $image = 'assets/images/shop-default.png';
                }
    ?>
                <div class="card mb-2" onclick="window.location.href='<?= $row['type'] === 'product' ? "product-details.php?id={$row['id']}" : "shop.php?name=" . urlencode($title) ?>'" style="cursor:pointer;">
                    <div class="row g-0 align-items-center">
                        <div class="col-auto">
                            <img src="<?= $image ?>" alt="<?= $title ?>" class="img-fluid" style="width:60px; height:60px; object-fit:cover; margin:10px;">
                        </div>
                        <div class="col">
                            <div class="card-body p-2">
                                <strong><?= $title ?></strong><br>
                                <?php if ($row['type'] === 'product'): ?>
                                    <small class="text-success">₱<?= number_format($row['price'],2) ?></small><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['shop'] ?? '') ?></small>
                                <?php else: ?>
                                    <small class="text-muted">Shop</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
    <?php
            endwhile;
        endif;
    endif;
    ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
