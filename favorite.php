<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user'])){
    header("Location: account.php?type=login"); // type=login khớp với logic của bạn
    exit;
}
// Lấy ID từ trong mảng user
$user_id = (int) $_SESSION['user']['id'];


// Xử lý khi dùng AJAX trái tim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    if (!$product_id || $product_id < 1) {
        http_response_code(400);
        echo "invalid_product";
        exit;
    }

    $check = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ? LIMIT 1");
    $check->execute([$user_id, $product_id]);

    if ($check->fetchColumn() !== false) {
        $pdo->prepare("DELETE FROM favorites WHERE user_id=? AND product_id=?")
            ->execute([$user_id, $product_id]);
        echo "removed";
    } else {
        $pdo->prepare("INSERT INTO favorites(user_id, product_id) VALUES(?, ?)")
            ->execute([$user_id, $product_id]);
        echo "added";
    }
    exit;
}

// Nếu là GET (click icon ❤ ở header)
$sql = "SELECT p.* FROM products p 
        JOIN favorites f ON p.id = f.product_id 
        WHERE f.user_id = ?
        ORDER BY f.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
<div class="container mt-4">
  <div class="product-section mb-5">
    <div class="text-center mb-4">
      <h3 class="text-uppercase fw-light" style="letter-spacing: 3px;">Sản phẩm yêu thích</h3>
      <div style="width: 50px; height: 2px; background: #355638; margin: 10px auto;"></div>
    </div>
    <div class="product-slider">
    <?php foreach ($favorites as $row): ?>
      <div class="product-item favorite-product-item">
        <div class="card h-100 border-0">
          <?php
            $images = array_filter(array_map('trim', explode(',', (string) $row['image'])));
            $first_image = !empty($images) ? reset($images) : 'no-image.jpg';
            $discount = !empty($row['sale_price']) ? round((1 - $row['sale_price'] / $row['price']) * 100) : 0;
          ?>
          <div style="position:relative;">
            <a href="product.php?id=<?php echo $row['id']; ?>">
              <img src="uploads/<?php echo htmlspecialchars($first_image, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>">
            </a>
            <?php if (!empty($row['sale_price'])): ?>
              <span style="position:absolute;top:8px;left:8px;background:#355638;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:4px;">
                -<?php echo $discount; ?>%
              </span>
            <?php endif; ?>
          </div>
          <div class="card-body text-center p-2">
            <h6 class="fw-normal small"><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></h6>
            <?php if (!empty($row['sale_price'])): ?>
              <p class="price small fw-bold">
                <?php echo number_format($row['sale_price']); ?>đ
                <span style="font-size:11px;color:#aaa;font-weight:400;text-decoration:line-through;margin-left:4px;">
                  <?php echo number_format($row['price']); ?>đ
                </span>
              </p>
            <?php else: ?>
              <p class="price small fw-bold"><?php echo number_format($row['price']); ?>đ</p>
            <?php endif; ?>
            <p class="small mb-2" style="font-size:12px;color:#687363;">
              <?php echo ((int) $row['stock'] > 0) ? 'Còn ' . (int) $row['stock'] . ' sản phẩm' : 'Hết hàng'; ?>
            </p>
            <button class="btn btn-outline-danger btn-sm btn-favorite border-0 active" data-id="<?php echo $row['id']; ?>">
              ❤️ Đã yêu thích
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
    <?php if (empty($favorites)): ?>
      <p class="text-center">Bạn chưa có sản phẩm yêu thích nào.</p>
    <?php endif; ?>
  </div>
</div>
<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>