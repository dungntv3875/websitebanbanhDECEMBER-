<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: account.php?type=login");
    exit;
}

$user_id = (int)($_SESSION['user']['id'] ?? 0);
if ($user_id <= 0) {
    header("Location: account.php?type=login");
    exit;
}

// Xử lý khi dùng AJAX trái tim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    if ($product_id > 0) {
        $check = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ? LIMIT 1");
        $check->execute([$user_id, $product_id]);

        if ($check->rowCount() > 0) {
            $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?")
                ->execute([$user_id, $product_id]);
            echo "removed";
        } else {
            $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)")
                ->execute([$user_id, $product_id]);
            echo "added";
        }
    }
    exit;
}

// Nếu là GET (click icon ❤ ở header)
$sql = "SELECT p.id, p.name, p.price, p.image
        FROM products p
        INNER JOIN favorites f ON p.id = f.product_id
        WHERE f.user_id = ?
        ORDER BY f.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container mt-5">
  <h3>Sản phẩm yêu thích</h3>
  <div class="row">
    <?php
    $has_favorites = false;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $has_favorites = true;
        $images = array_filter(array_map('trim', explode(',', $row['image'] ?? '')));
        $first_image = !empty($images) ? reset($images) : 'default.jpg';
    ?>
      <div class="col-md-3 mb-4">
        <div class="card">
          <img src="uploads/<?php echo htmlspecialchars($first_image); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name']); ?>">
          <div class="card-body text-center">
            <h6><?php echo htmlspecialchars($row['name']); ?></h6>
            <p class="price"><?php echo number_format((float)$row['price']); ?>đ</p>
            <a href="product.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-primary">Xem chi tiết</a>
          </div>
        </div>
      </div>
    <?php } ?>

    <?php if (!$has_favorites): ?>
        <p>Bạn chưa có sản phẩm yêu thích nào.</p>
    <?php endif; ?>
  </div>
</div>
<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>