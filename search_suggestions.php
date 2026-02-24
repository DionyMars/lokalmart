<?php
include 'includes/db.php';

if (!isset($_GET['q']) || trim($_GET['q']) === '') {
    exit;
}

$q = trim($_GET['q']);
$q_escaped = "%" . $conn->real_escape_string($q) . "%";

$query = "
    SELECT 
        p.product_id, 
        p.name, 
        p.price, 
        p.image, 
        s.shop_name
    FROM products p
    LEFT JOIN sellers s ON p.seller_id = s.seller_id
    WHERE (p.name LIKE ? OR s.shop_name LIKE ? OR p.description LIKE ?)
      AND p.visibility = 'visible'
    ORDER BY p.created_at DESC
    LIMIT 8
";

$stmt = $conn->prepare($query);
$stmt->bind_param('sss', $q_escaped, $q_escaped, $q_escaped);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0): 
    while ($row = $result->fetch_assoc()):
        $image = !empty($row['image']) 
            ? 'uploads/products/' . htmlspecialchars($row['image']) 
            : 'assets/images/no-image.png';
        $price = '₱' . number_format($row['price'], 2);
?>
    <div class="suggestion-item d-flex align-items-center" 
         data-id="<?= $row['product_id']; ?>"
         data-value="<?= htmlspecialchars($row['name']); ?>">
      <img src="<?= $image; ?>" 
           alt="<?= htmlspecialchars($row['name']); ?>" 
           style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
      <div>
        <strong class="d-block text-dark"><?= htmlspecialchars($row['name']); ?></strong>
        <small class="text-success"><?= $price; ?></small><br>
        <small class="text-muted"><?= htmlspecialchars($row['shop_name'] ?? 'Unknown Seller'); ?></small>
      </div>
    </div>
<?php 
    endwhile;
else: 
?>
  <div class="suggestion-item text-muted text-center py-2">
    No results found for “<?= htmlspecialchars($q); ?>”
  </div>
<?php 
endif;
?>
