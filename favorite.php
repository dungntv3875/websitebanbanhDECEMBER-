<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: account.php?type=login");
    exit;
}

$user_id = (int)($_SESSION['user']['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];

    if ($product_id > 0 && $user_id > 0) {
        $check = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ? LIMIT 1");
        $check->execute([$user_id, $product_id]);

        if ($check->fetchColumn()) {
            $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?")
                ->execute([$user_id, $product_id]);
            echo "removed";
        } else {
            $pdo->prepare("INSERT INTO favorites(user_id, product_id) VALUES(?, ?)
                ->execute([$user_id, $product_id]);
            echo "added";
        }
    }
    exit;
}

$sql = "
    SELECT p.*
    FROM products p
    INNER JOIN favorites f ON p.id = f.product_id
    WHERE f.user_id = ?
    ORDER BY f.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$favoriteProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <?php if (empty($favoriteProducts)): ?>
        <div class="col-12">
            <p class="mt-3">Bạn chưa có sản phẩm yêu thích nào.</p>
        </div>
    <?php else: ?>
        <?php foreach ($favoriteProducts as $row): ?>
          <div class="col-md-3 mb-4">
            <div class="card h-100">
              <?php
                $images = array_filter(array_map('trim', explode(',', $row['image'] ?? '')));
                $first_image = !empty($images) ? reset($images) : 'default.jpg';
              ?>
              <img src="uploads/<?php echo htmlspecialchars($first_image); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name']); ?>">
              <div class="card-body text-center">
                <h6><?php echo htmlspecialchars($row['name']); ?></h6>
                <p class="price"><?php echo number_format((float)$row['price']); ?>đ</p>
                <a href="product.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Xem chi tiết</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>