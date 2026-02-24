<?php
include 'includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Category not found.'); window.location='index.php';</script>";
    exit;
}

$category_id = intval($_GET['id']);

// ✅ Get category name
$stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id=?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();

if (!$category) {
    echo "<script>alert('Invalid category.'); window.location='index.php';</script>";
    exit;
}

// ✅ Get products under this category
$query = "
    SELECT p.*, s.shop_name, s.owner_name, s.email, s.phone
    FROM products p
    LEFT JOIN sellers s ON p.seller_id = s.seller_id
    WHERE p.category_id=? AND p.visibility='visible'
    ORDER BY p.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($category['category_name']) ?> | LokalMart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container py-5">
  <h2 class="fw-bold text-success mb-4"><?= htmlspecialchars($category['category_name']) ?></h2>
  <div class="row g-4">
    <?php if ($products->num_rows > 0): ?>
      <?php while ($p = $products->fetch_assoc()): ?>
        <?php $img = !empty($p['image']) ? 'uploads/products/' . htmlspecialchars($p['image']) : 'assets/images/no-image.png'; ?>
        <div class="col-md-3 col-sm-6">
          <div class="card shadow-sm h-100">
            <img src="<?= $img ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>">
            <div class="card-body text-center">
              <h6 class="fw-bold"><?= htmlspecialchars($p['name']) ?></h6>
              <small class="text-muted"><?= htmlspecialchars($p['shop_name']) ?></small>
              <p class="text-success fw-bold mb-0">₱<?= number_format($p['price'], 2) ?></p>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="col-12 text-center text-muted">
        <p>No products available in this category.</p>
      </div>
    <?php endif; ?>
  </div>
  <div class="text-center mt-4">
    <a href="index.php" class="btn btn-outline-success"><i class="bi bi-arrow-left"></i> Back to Home</a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
