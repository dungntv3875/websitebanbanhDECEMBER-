<?php
// Thông tin máy chủ và cơ sở dữ liệu mà ứng dụng sẽ kết nối tới.
$host = 'localhost';
$dbname = 'websitebanbanhDECEMBER-';
$username = 'root';
$password = '';

try {
    // Tạo kết nối PDO; khai báo charset UTF-8 để dữ liệu tiếng Việt được lưu đúng.
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    // Đưa lỗi truy vấn thành exception để có thể xử lý tập trung ở khối catch.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tạo bảng theo dõi ngày cập nhật tồn kho; mỗi hệ thống chỉ cần một dòng có id = 1.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS daily_stock_reset (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            last_reset_date DATE NOT NULL
        ) ENGINE=InnoDB
    ");

    // Dùng múi giờ Việt Nam để xác định ngày reset theo giờ địa phương.
    $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
    $today = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');

    // Khởi tạo mốc ngày khi chạy lần đầu; INSERT IGNORE không ghi đè ngày đã lưu,
    // nhờ đó không reset số bánh còn lại ngay khi ứng dụng mới được cài đặt.
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO daily_stock_reset (id, last_reset_date)
        VALUES (1, ?)
    ");
    $stmt->execute([$today]);

    // Gộp việc kiểm tra ngày, reset tồn kho và cập nhật mốc ngày thành một giao dịch.
    $pdo->beginTransaction();

    // Khóa dòng mốc ngày đến khi giao dịch kết thúc để các lượt truy cập đồng thời
    // không cùng thực hiện reset tồn kho trong một ngày.
    $lastReset = $pdo->query("
        SELECT last_reset_date
        FROM daily_stock_reset
        WHERE id = 1
        FOR UPDATE
    ")->fetchColumn();

    $today = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');

    // Chỉ đặt lại tồn kho khi ngày lưu gần nhất cũ hơn ngày hiện tại.
    if ($lastReset < $today) {
        // Đặt tồn kho của tất cả sản phẩm về 30 chiếc cho ngày mới.
        $pdo->exec("UPDATE products SET stock = 30");

        // Ghi nhận ngày vừa reset để những lượt truy cập tiếp theo không reset lại.
        $stmt = $pdo->prepare("
            UPDATE daily_stock_reset
            SET last_reset_date = ?
            WHERE id = 1
        ");
        $stmt->execute([$today]);
    }

    // Xác nhận các thay đổi sau khi toàn bộ thao tác trong giao dịch thành công.
    $pdo->commit();
} catch (Throwable $e) {
    // Nếu có lỗi giữa chừng, hoàn tác giao dịch để tránh lưu trạng thái chưa hoàn chỉnh.
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Dừng xử lý và hiển thị nguyên nhân lỗi kết nối hoặc cập nhật tồn kho.
    die("Lỗi kết nối hoặc cập nhật tồn kho: " . $e->getMessage());
}
