<?php
include 'includes/db.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {
    exit;
}

if (!$conn) {
    exit('Database connection error.');
}

$like = "%{$q}%";

// Products query
$productQuery = "
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
";

// Sellers query (no shop image)
$sellerQuery = "
    SELECT 
        s.seller_id AS id,
        s.shop_name AS title,
        'shop' AS type,
        NULL AS image,
        NULL AS price,
        NULL AS shop
    FROM sellers s
    WHERE s.shop_name LIKE ? AND s.status = 'approved'
";

// Combine queries
$stmt = $conn->prepare("$productQuery UNION $sellerQuery LIMIT 10");
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="p-2 text-muted small">No results found.</div>';
    exit;
}

while ($row = $result->fetch_assoc()):
    $title = htmlspecialchars($row['title']);
    
    // Determine image path
    if ($row['type'] === 'product') {
        $image = !empty($row['image']) 
                 ? 'uploads/products/' . htmlspecialchars($row['image']) 
                 : 'assets/images/no-image.png';
    } else {
        $image = 'assets/images/shop-default.png'; // default shop image
    }

    if ($row['type'] === 'product'):
?>
    <div class="suggestion-item d-flex align-items-center p-2 border-bottom" 
         onclick="window.location.href='product-details.php?id=<?= $row['id'] ?>'">
      <img src="<?= $image ?>" alt="<?= $title ?>" 
           style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
      <div>
        <strong class="d-block text-dark"><?= $title ?></strong>
        <small class="text-success">₱<?= number_format($row['price'], 2) ?></small><br>
        <small class="text-muted"><?= htmlspecialchars($row['shop'] ?? '') ?></small>
      </div>
    </div>
<?php
    else:
?>
    <div class="suggestion-item d-flex align-items-center p-2 border-bottom" 
         onclick="window.location.href='shop.php?name=<?= urlencode($row['title']) ?>'">
      <img src="<?= $image ?>" 
           alt="<?= $title ?>" 
           style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
      <div>
        <strong class="d-block text-dark"><?= $title ?></strong>
        <small class="text-muted">Shop</small>
      </div>
    </div>
<?php
    endif;
endwhile;
?>
