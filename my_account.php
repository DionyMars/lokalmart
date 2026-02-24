<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/includes/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ---------------------------
   FETCH USER DATA
---------------------------- */
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userData) die("<div class='alert alert-danger text-center mt-5'>User not found.</div>");

/* ---------------------------
   HANDLE AVATAR UPLOAD
---------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $allowed = ['jpg','jpeg','png'];
    $max_size = 2*1024*1024;

    if ($file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            echo "<script>alert('Only JPG and PNG files are allowed.');</script>";
        } elseif ($file['size'] > $max_size) {
            echo "<script>alert('Image size must not exceed 2MB.');</script>";
        } else {
            $filename = "user_{$user_id}_".time().".{$ext}";
            $upload_dir = __DIR__."/uploads/avatars/";
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            if (!empty($userData['avatar']) && file_exists($upload_dir.$userData['avatar'])) {
                unlink($upload_dir.$userData['avatar']);
            }
            if (move_uploaded_file($file['tmp_name'],$upload_dir.$filename)) {
                $update = $conn->prepare("UPDATE users SET avatar=? WHERE user_id=?");
                $update->bind_param("si",$filename,$user_id);
                $update->execute();
                $update->close();
                $userData['avatar'] = $filename;
                echo "<script>alert('Avatar updated successfully!'); location.href='my_account.php';</script>";
                exit;
            }
        }
    }
}

/* ---------------------------
   HANDLE PROFILE UPDATE
---------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);

    $update = $conn->prepare("UPDATE users SET name=?, email=?, contact_number=? WHERE user_id=?");
    $update->bind_param("sssi",$name,$email,$contact_number,$user_id);
    $update->execute();
    $update->close();

    header("Location: my_account.php");
    exit;
}

/* ---------------------------
   HANDLE ADD/EDIT ADDRESS
---------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_address']) || isset($_POST['edit_address']))) {
    $region = trim($_POST['region']);
    $province = trim($_POST['province']);
    $municipality = trim($_POST['municipality']);
    $barangay = trim($_POST['barangay']);
    $label = trim($_POST['label']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if ($region && $province && $municipality && $barangay) {
        $full_address = "$barangay, $municipality, $province, $region";

        if ($is_default) {
            $conn->query("UPDATE addresses SET is_default=0 WHERE user_id=$user_id");
        }

        if (isset($_POST['edit_address']) && !empty($_POST['address_id'])) {
            $address_id = intval($_POST['address_id']);
            $stmt = $conn->prepare("UPDATE addresses SET label=?, address=?, is_default=? WHERE address_id=? AND user_id=?");
            $stmt->bind_param("ssiii", $label, $full_address, $is_default, $address_id, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO addresses(user_id,label,address,is_default) VALUES(?,?,?,?)");
            $stmt->bind_param("issi",$user_id,$label,$full_address,$is_default);
        }

        $stmt->execute();
        $stmt->close();
        header("Location: my_account.php#address");
        exit;
    } else {
        echo "<script>alert('Please fill all address fields.'); location.href='my_account.php#address';</script>";
        exit;
    }
}

/* ---------------------------
   HANDLE DELETE ADDRESS
---------------------------- */
if (isset($_GET['delete_address'])) {
    $address_id = intval($_GET['delete_address']);
    $stmt = $conn->prepare("DELETE FROM addresses WHERE address_id=? AND user_id=?");
    $stmt->bind_param("ii", $address_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: my_account.php#address");
    exit;
}

/* ---------------------------
   FETCH ADDRESSES
---------------------------- */
$addresses = [];
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) $addresses[] = $row;
$stmt->close();

/* ---------------------------
   FETCH ORDERS
---------------------------- */
$orders = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'orders'");
if($tableCheck && $tableCheck->num_rows>0){
    $stmt=$conn->prepare("SELECT * FROM orders WHERE buyer_id=? ORDER BY created_at DESC");
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $orders=$stmt->get_result();
    $stmt->close();
}

/* ---------------------------
   FETCH LOCATION DATA
---------------------------- */
$regions = $conn->query("SELECT * FROM regions ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account - LokalMart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
.avatar-upload { cursor: pointer; position: relative; display: inline-block; }
.avatar-upload input { display: none; }
.avatar-upload:hover::after {
  content: "Change"; position: absolute; bottom: 8px; left:0; right:0;
  background: rgba(0,0,0,0.6); color:#fff; text-align:center;
  border-radius:50%; padding:5px; font-size:.85rem;
}
.sidebar .nav-link.active { background-color:#0b5d1e; color:#fff !important; border-radius:5px; }
</style>
</head>
<body class="bg-light">

<?php include __DIR__ . "/includes/header.php"; ?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-md-3">
      <div class="card shadow-sm p-3 text-center">
        <form method="POST" enctype="multipart/form-data">
          <label class="avatar-upload">
            <?php
              $avatarPath = (!empty($userData['avatar']) && file_exists(__DIR__.'/uploads/avatars/'.$userData['avatar']))
                ? 'uploads/avatars/'.htmlspecialchars($userData['avatar'])
                : 'assets/default_user.png';
            ?>
            <img src="<?= $avatarPath ?>" alt="Avatar" class="rounded-circle mb-2" width="120" height="120" style="object-fit:cover;">
            <input type="file" name="avatar" accept=".jpg,.jpeg,.png" onchange="this.form.submit()">
          </label>
        </form>
        <h5 class="fw-bold mb-1"><?= htmlspecialchars($userData['name']) ?></h5>
        <p class="text-muted"><?= htmlspecialchars($userData['email']) ?></p>
        <hr>
        <div class="nav flex-column sidebar">
          <a class="nav-link active" href="#profile" data-bs-toggle="tab"><i class="bi bi-person"></i> Profile</a>
          <a class="nav-link" href="#address" data-bs-toggle="tab"><i class="bi bi-geo-alt"></i> Address</a>
          <a class="nav-link" href="#orders" data-bs-toggle="tab"><i class="bi bi-bag"></i> Purchases</a>
        </div>
      </div>
    </div>

    <div class="col-md-9">
      <div class="tab-content">

        <!-- PROFILE -->
        <div class="tab-pane fade show active" id="profile">
          <div class="card shadow-sm">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Profile Information</h5>
              <button type="button" id="editBtn" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Edit</button>
            </div>
            <div class="card-body">
              <form method="POST" id="profileForm">
                <input type="text" name="name" class="form-control mb-2" value="<?= htmlspecialchars($userData['name']) ?>" disabled required>
                <input type="email" name="email" class="form-control mb-2" value="<?= htmlspecialchars($userData['email']) ?>" disabled required>
                <input type="text" name="contact_number" class="form-control mb-2" value="<?= htmlspecialchars($userData['contact_number'] ?? '') ?>" disabled>
                <button type="submit" name="update_profile" id="saveBtn" class="btn btn-success d-none">Save Changes</button>
                <button type="button" id="cancelBtn" class="btn btn-secondary d-none">Cancel</button>
              </form>
            </div>
          </div>
        </div>

        <!-- ADDRESS -->
        <div class="tab-pane fade" id="address">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0">My Addresses</h5>
              <button class="btn btn-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addAddressForm">
                <i class="bi bi-plus"></i> Add Address
              </button>
            </div>
            <div class="card-body">

              <!-- ADD/EDIT ADDRESS -->
              <div class="collapse mb-3" id="addAddressForm">
                <form method="POST" id="addressForm">
                  <input type="hidden" name="address_id" value="">
                  <input type="text" name="label" class="form-control mb-2" placeholder="Label (Home, Work, etc.)" required>

                  <select name="region" class="form-select mb-2" id="regionSelect" required>
                    <option value="">Select Region</option>
                    <?php foreach($regions as $r): ?>
                      <option value="<?= htmlspecialchars($r['id']) ?>"><?= htmlspecialchars($r['name']) ?></option>
                    <?php endforeach; ?>
                  </select>

                  <select name="province" class="form-select mb-2" id="provinceSelect" required>
                    <option value="">Select Province</option>
                  </select>

                  <input type="text" name="municipality" class="form-control mb-2" placeholder="Municipality" required>
                  <input type="text" name="barangay" class="form-control mb-2" placeholder="Barangay" required>

                  <div class="form-check mb-2">
                    <input type="checkbox" name="is_default" value="1" class="form-check-input" id="isDefault">
                    <label class="form-check-label" for="isDefault">Set as default</label>
                  </div>
                  <button type="submit" name="add_address" class="btn btn-success w-100">Save Address</button>
                </form>
              </div>

              <!-- EXISTING ADDRESSES -->
              <?php if (!empty($addresses)): ?>
                <ul class="list-group">
                  <?php foreach($addresses as $addr): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <strong><?= htmlspecialchars($addr['label'] ?: 'Address') ?></strong><br>
                        <?= nl2br(htmlspecialchars($addr['address'])) ?>
                        <?php if($addr['is_default']): ?>
                          <span class="badge bg-success ms-2">Default</span>
                        <?php endif; ?>
                      </div>
                      <div>
                        <a href="?delete_address=<?= $addr['address_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure to delete this address?')">Delete</a>
                        <button class="btn btn-sm btn-outline-primary" onclick="editAddress(<?= htmlspecialchars(json_encode($addr)) ?>)">Edit</button>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-center text-muted mt-3">No addresses added yet.</p>
              <?php endif; ?>

            </div>
          </div>
        </div>

        <!-- ORDERS -->
        <div class="tab-pane fade" id="orders">
          <div class="card shadow-sm">
            <div class="card-header bg-success text-white"><h5 class="mb-0">My Purchases</h5></div>
            <div class="card-body">
              <?php if ($orders->num_rows ?? 0): ?>
                <table class="table table-bordered align-middle">
                  <thead class="table-light">
                    <tr><th>#</th><th>Total</th><th>Status</th><th>Date</th></tr>
                  </thead>
                  <tbody>
                  <?php while($o = $orders->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($o['order_id']) ?></td>
                      <td>₱<?= number_format($o['total_amount'],2) ?></td>
                      <td>
                        <span class="badge bg-<?= match($o['status']){
                          'pending'=>'warning','completed'=>'success','cancelled'=>'danger',default=>'secondary'
                        } ?>"><?= ucfirst($o['status']) ?></span>
                      </td>
                      <td><?= date('M d, Y h:i A',strtotime($o['created_at'])) ?></td>
                    </tr>
                  <?php endwhile; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <div class="text-center text-muted py-4"><i class="bi bi-bag-x fs-1 mb-2"></i>No purchases found.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Profile edit
const editBtn = document.getElementById('editBtn');
const saveBtn = document.getElementById('saveBtn');
const cancelBtn = document.getElementById('cancelBtn');
const inputs = document.querySelectorAll('#profileForm input');

editBtn.addEventListener('click', () => {
  inputs.forEach(i=>i.removeAttribute('disabled'));
  saveBtn.classList.remove('d-none');
  cancelBtn.classList.remove('d-none');
  editBtn.classList.add('d-none');
});
cancelBtn.addEventListener('click', () => {
  inputs.forEach(i=>i.setAttribute('disabled',true));
  saveBtn.classList.add('d-none');
  cancelBtn.classList.add('d-none');
  editBtn.classList.remove('d-none');
});

// Edit address
function editAddress(addr){
    const form = document.querySelector('#addAddressForm form');
    form.address_id.value = addr.address_id;
    form.label.value = addr.label;
    const parts = addr.address.split(',').map(s => s.trim());
    form.barangay.value = parts[0] ?? '';
    form.municipality.value = parts[1] ?? '';
    form.province.value = parts[2] ?? '';
    form.region.value = addr.region_id ?? ''; // you may need to store region_id in DB
    form.is_default.checked = addr.is_default == 1;

    form.querySelector('button[type="submit"]').name = 'edit_address';
    const collapse = new bootstrap.Collapse(document.getElementById('addAddressForm'), {show:true});
}

// Dynamic province filtering
document.getElementById('regionSelect').addEventListener('change', function(){
    const regionId = this.value;
    const provinceSelect = document.getElementById('provinceSelect');
    provinceSelect.innerHTML = '<option value="">Loading...</option>';

    fetch('get_provinces.php?region_id='+regionId)
        .then(res => res.json())
        .then(data => {
            let options = '<option value="">Select Province</option>';
            data.forEach(p => options += `<option value="${p.id}">${p.name}</option>`);
            provinceSelect.innerHTML = options;
        })
        .catch(()=> provinceSelect.innerHTML = '<option value="">Select Province</option>');
});
</script>
</body>
</html>
