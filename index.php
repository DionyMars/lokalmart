<?php
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LokalMart | Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .carousel { height: 400px; overflow: hidden; transition: all 0.4s ease; }
    .carousel-item { height: 400px; }
    .carousel-item img { width: 100%; height: 100%; object-fit: contain; background-color: #f8f9fa; }

    .product-card img { height: 200px; object-fit: contain; }

    /* Category styles */
    .categories-section {
      background-color: yellowgreen;
      padding: 40px 0;
      margin-top: 10px;
    }
    .category-card {
      border: 1px solid #ddd;
      border-radius: 15px;
      transition: all 0.3s ease;
      cursor: pointer;
      background: #fff;
      text-align: center;
      padding: 20px;
    }
    .category-card:hover {
      background-color: #d1e7dd;
      transform: translateY(-5px);
    }
  </style>
</head>

<body>

<?php if (!isset($_SESSION['user_id'])): ?>
  <!-- ✅ HERO SECTION FOR GUESTS -->
  <section class="hero bg-light py-5 text-center">
    <div class="container">
      <h1 class="fw-bold text-success">Welcome to LokalMart</h1>
      <p class="text-muted mb-4">Your trusted marketplace for local products in the Cordillera region.</p>
      <a href="auth/login.php" class="btn btn-success px-4 py-2">Shop Now</a>
    </div>
  </section>
<?php else: ?>
  <!-- ✅ CAROUSEL SECTION -->
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4000">
    <div class="carousel-inner">
      <?php
      $carousel = $conn->query("SELECT * FROM carousel_images ORDER BY created_at DESC");
      if ($carousel && $carousel->num_rows > 0):
        $first = true;
        while ($img = $carousel->fetch_assoc()):
          $filePath = "uploads/carousel/" . htmlspecialchars($img['image_path']);
          if (!file_exists($filePath)) continue;
      ?>
        <div class="carousel-item <?= $first ? 'active' : '' ?>">
          <img src="<?= $filePath ?>" alt="Carousel Image">
        </div>
      <?php
          $first = false;
        endwhile;
      else:
      ?>
        <div class="carousel-item active">
          <img src="assets/images/default-banner.jpg" alt="Default Banner">
        </div>
      <?php endif; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>
<?php endif; ?>


<!-- ✅ CATEGORIES SECTION -->
<section class="categories-section" id="categories">
  <div class="container">
    <h2 class="text-center fw-bold text-success mb-4">Shop by Category</h2>
    <div class="row g-4 justify-content-center">
      <!-- ✅ "Show All" Card -->
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <a href="index.php" class="text-decoration-none text-dark category-link">
          <div class="category-card shadow-sm">
            <i class="bi bi-grid fs-1 text-success mb-2"></i>
            <h6 class="fw-bold">Show All</h6>
          </div>
        </a>
      </div>

      <?php
      $categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
      if ($categories && $categories->num_rows > 0):
        while ($cat = $categories->fetch_assoc()):
      ?>
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
          <a href="index.php?category_id=<?= $cat['category_id'] ?>" class="text-decoration-none text-dark category-link">
            <div class="category-card shadow-sm">
              <i class="bi bi-tags fs-1 text-success mb-2"></i>
              <h6 class="fw-bold"><?= htmlspecialchars($cat['category_name']) ?></h6>
            </div>
          </a>
        </div>
      <?php endwhile; else: ?>
        <div class="col-12 text-center text-muted">
          <p>No categories available.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>


<!-- ✅ PRODUCTS SECTION -->
<section class="products py-5" id="products">
  <div class="container">
    <h2 class="text-center fw-bold mb-4 text-success">
      <?php
        if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
          $category_id = intval($_GET['category_id']);
          $catRes = $conn->query("SELECT category_name FROM categories WHERE category_id = $category_id");
          $catName = $catRes && $catRes->num_rows > 0 ? $catRes->fetch_assoc()['category_name'] : "Unknown Category";
          echo "Category: " . htmlspecialchars($catName);
        } else {
          echo "Daily Discovery";
        }
      ?>
    </h2>

    <div class="row g-4">
      <?php
      $categoryFilter = "";
      if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
        $category_id = intval($_GET['category_id']);
        $categoryFilter = "AND p.category_id = $category_id";
      }

      $query = "
        SELECT p.*, c.category_name, s.shop_name, s.seller_id, s.owner_name, s.email, s.phone
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN sellers s ON p.seller_id = s.seller_id
        WHERE p.visibility = 'visible' $categoryFilter
        ORDER BY p.created_at DESC
      ";
      $products = $conn->query($query);

      if ($products && $products->num_rows > 0):
        while ($product = $products->fetch_assoc()):
          $img = !empty($product['image']) ? 'uploads/products/' . htmlspecialchars($product['image']) : 'assets/images/no-image.png';
          $name = htmlspecialchars($product['name'], ENT_QUOTES);
          $description = htmlspecialchars($product['description'], ENT_QUOTES);
          $shop = htmlspecialchars($product['shop_name'], ENT_QUOTES);
          $owner = htmlspecialchars($product['owner_name'], ENT_QUOTES);
          $email = htmlspecialchars($product['email'], ENT_QUOTES);
          $phone = htmlspecialchars($product['phone'], ENT_QUOTES);
          $price = number_format($product['price'], 2);
      ?>
        <div class="col-md-3 col-sm-6">
          <div class="card product-card shadow-sm h-100" style="cursor:pointer;"
               data-bs-toggle="modal"
               data-bs-target="#productModal"
               data-product-id="<?= $product['product_id'] ?>"
               data-seller-id="<?= $product['seller_id'] ?>"
               data-name="<?= $name ?>"
               data-description="<?= $description ?>"
               data-price="<?= $price ?>"
               data-image="<?= $img ?>"
               data-shop="<?= $shop ?>"
               data-owner="<?= $owner ?>"
               data-email="<?= $email ?>"
               data-phone="<?= $phone ?>">
            <img src="<?= $img ?>" class="card-img-top" alt="<?= $name ?>">
            <div class="card-body text-center">
              <h6 class="card-title fw-bold"><?= $name ?></h6>
              <small class="text-muted d-block mb-2"><?= $shop ?></small>
              <p class="text-success fw-bold mb-1">₱<?= $price ?></p>
              <p class="text-muted small mb-0"><?= substr($description, 0, 60) ?>...</p>
            </div>
          </div>
        </div>
      <?php endwhile; else: ?>
        <div class="col-12 text-center text-muted">
          <p>No products available in this category.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
  // ✅ Smooth scroll & hide carousel on category click
  document.querySelectorAll('.category-link').forEach(link => {
    link.addEventListener('click', function() {
      // Hide carousel smoothly
      const carousel = document.getElementById('heroCarousel');
      if (carousel) carousel.style.display = 'none';
      // Scroll to products section
      setTimeout(() => {
        document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
      }, 300);
    });
  });

  // ✅ Hide carousel if already filtered
  <?php if (isset($_GET['category_id'])): ?>
    document.addEventListener('DOMContentLoaded', () => {
      const carousel = document.getElementById('heroCarousel');
      if (carousel) carousel.style.display = 'none';
      document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
    });
  <?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
