<?php
$host = 'localhost';
$dbname = 'websitebanbanhDECEMBER-';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Đảm bảo bảng favorites luôn có cấu trúc đúng để lưu và hiển thị sản phẩm yêu thích.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS favorites (
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_product (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    try {
        $pdo->exec("ALTER TABLE favorites MODIFY id INT NOT NULL AUTO_INCREMENT");
    } catch (Throwable $e) {
        // Bỏ qua nếu cột đã đúng cấu trúc.
    }

    try {
        $pdo->exec("ALTER TABLE favorites ADD UNIQUE KEY unique_user_product (user_id, product_id)");
    } catch (Throwable $e) {
        // Bỏ qua nếu unique key đã tồn tại.
    }

    // Lưu ngày reset tồn kho gần nhất.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS daily_stock_reset (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            last_reset_date DATE NOT NULL
        ) ENGINE=InnoDB
    ");

    $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
    $today = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');

    // Lần đầu cài đặt: giữ nguyên số bánh còn lại trong ngày.
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO daily_stock_reset (id, last_reset_date)
        VALUES (1, ?)
    ");
    $stmt->execute([$today]);

    $pdo->beginTransaction();

    // Tránh nhiều lượt truy cập cùng reset lặp.
    $lastReset = $pdo->query("
        SELECT last_reset_date
        FROM daily_stock_reset
        WHERE id = 1
        FOR UPDATE
    ")->fetchColumn();

    $today = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');

    if ($lastReset < $today) {
        $pdo->exec("UPDATE products SET stock = 30");

        $stmt = $pdo->prepare("
            UPDATE daily_stock_reset
            SET last_reset_date = ?
            WHERE id = 1
        ");
        $stmt->execute([$today]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Lỗi kết nối hoặc cập nhật tồn kho: " . $e->getMessage());
}