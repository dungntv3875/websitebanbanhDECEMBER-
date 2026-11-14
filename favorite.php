<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user'])){
    header("Location: account.php?type=login"); // type=login khớp với logic của bạn
    exit;
}
// Lấy ID từ trong mảng user
$user_id = $_SESSION['user']['id'];


// Xử lý khi dùng AJAX trái tim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $check = $pdo->prepare("SELECT * FROM favorites WHERE user_id=? AND product_id=?");
    $check->execute([$user_id, $product_id]);

    if ($check->rowCount() > 0) {
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
        WHERE f.user_id = ?";
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
    // Thực hiện lấy dữ liệu
    $stmt->execute([$user_id]); 
    $has_favorites = false;

    // Vòng lặp hiển thị từng sản phẩm
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){ 
        $has_favorites = true;
    ?>
      <div class="col-md-3 mb-4">
        <div class="card">
          <?php 
    // Tách chuỗi ảnh thành một mảng dựa trên dấu phẩy
    $images = explode(',', $row['image']);
    // Lấy phần tử đầu tiên trong mảng
    $first_image = trim($images[0]); 
?>
<img src="uploads/<?php echo $first_image; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name']); ?>">
          <div class="card-body text-center">
            <h6><?php echo $row['name']; ?></h6>
            <p class="price"><?php echo number_format($row['price']); ?>đ</p>
            <a href="product.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Xem chi tiết</a>
          </div>
        </div>
      </div>
    <?php } ?>

    <?php if(!$has_favorites): ?>
        <p>Bạn chưa có sản phẩm yêu thích nào.</p>
    <?php endif; ?>
  </div>
</div>
<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>