<?php
include 'includes/db.php';

$products_q = $conn->query("
    SELECT p.*, s.shop_name, s.seller_id, s.owner_name, s.email, s.phone
    FROM products p
    JOIN sellers s ON p.seller_id = s.seller_id
    WHERE p.status='approved' AND p.visibility='visible'
    ORDER BY p.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Products | LokalMart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.card-hover:hover { cursor: pointer; transform: scale(1.03); transition: 0.2s; }
.card-img-top { height: 200px; object-fit: cover; }
</style>
</head>
<body>

<div class="container mt-5">
    <h2 class="mb-4">Products</h2>
    <div class="row">
        <?php if ($products_q && $products_q->num_rows > 0): ?>
            <?php while ($product = $products_q->fetch_assoc()): ?>
                <?php
                    $image = !empty($product['image']) ? 'assets/images/' . htmlspecialchars($product['image']) : 'assets/images/no-image.png';
                    $name = htmlspecialchars($product['name'], ENT_QUOTES);
                    $description = htmlspecialchars($product['description'], ENT_QUOTES);
                    $shop = htmlspecialchars($product['shop_name'], ENT_QUOTES);
                    $owner = htmlspecialchars($product['owner_name'], ENT_QUOTES);
                    $email = htmlspecialchars($product['email'], ENT_QUOTES);
                    $phone = htmlspecialchars($product['phone'], ENT_QUOTES);
                    $price = number_format($product['price'], 2);
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card card-hover h-100"
                         data-bs-toggle="modal"
                         data-bs-target="#productModal"
                         data-product-id="<?= $product['product_id'] ?>"
                         data-seller-id="<?= $product['seller_id'] ?>"
                         data-name="<?= $name ?>"
                         data-description="<?= $description ?>"
                         data-price="<?= $price ?>"
                         data-image="<?= $image ?>"
                         data-shop="<?= $shop ?>"
                         data-owner="<?= $owner ?>"
                         data-email="<?= $email ?>"
                         data-phone="<?= $phone ?>">
                        <img src="<?= $image ?>" class="card-img-top" alt="<?= $name ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= $name ?></h5>
                            <p class="card-text">₱<?= $price ?></p>
                            <p class="small text-muted">Store: <?= $shop ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center text-muted">No products available.</p>
        <?php endif; ?>
    </div>
</div>

<!-- =================== PRODUCT DETAILS MODAL =================== -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <img id="modalImage" src="" class="img-fluid" alt="Product Image">
          </div>
          <div class="col-md-6">
            <p><strong>Price:</strong> ₱<span id="modalPrice"></span></p>
            <p><strong>Description:</strong> <span id="modalDescription"></span></p>
            <p><strong>Store:</strong> <a href="#" id="modalShop"></a></p>
            <p><strong>Owner:</strong> <span id="modalOwner"></span></p>
            <p><strong>Email:</strong> <span id="modalEmail"></span></p>
            <p><strong>Phone:</strong> <span id="modalPhone"></span></p>
            <a id="viewMoreBtn" href="#" class="btn btn-primary mt-2">View More Details</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
var productModal = document.getElementById('productModal');
var viewMoreBtn = document.getElementById('viewMoreBtn');
var modalShop = document.getElementById('modalShop');

if (productModal) {
  productModal.addEventListener('show.bs.modal', function(event) {
    var card = event.relatedTarget;
    document.getElementById('productModalLabel').textContent = card.getAttribute('data-name');
    document.getElementById('modalDescription').textContent = card.getAttribute('data-description');
    document.getElementById('modalPrice').textContent = card.getAttribute('data-price');
    document.getElementById('modalImage').src = card.getAttribute('data-image');
    modalShop.textContent = card.getAttribute('data-shop');
    document.getElementById('modalOwner').textContent = card.getAttribute('data-owner');
    document.getElementById('modalEmail').textContent = card.getAttribute('data-email');
    document.getElementById('modalPhone').textContent = card.getAttribute('data-phone');

    // Set View More Details link
    viewMoreBtn.href = 'product_full.php?product_id=' + card.getAttribute('data-product-id');

    // Set Shop link
    modalShop.href = 'seller.php?seller_id=' + card.getAttribute('data-seller-id');
  });
}
</script>

</body>
</html>
