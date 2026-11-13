<?php
/**
 * RUBIES - CHAT API (Admin ↔ User)
 * Endpoint: chat_api.php
 * Dùng chung cho cả user gửi lên và admin gửi xuống.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

ob_clean();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// =====================================================
// Kiểm tra đăng nhập
// =====================================================
$current_user  = $_SESSION['user'] ?? null;
$is_admin      = $current_user && $current_user['role'] === 'admin';
$is_logged_in  = $current_user !== null;

if (!$is_logged_in) {
    echo json_encode(['ok' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$my_id = (int)$current_user['id'];

// =====================================================
// ACTION: user / admin GỬI tin nhắn
// =====================================================
if ($action === 'send') {
    $input   = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');

    // Admin cần biết gửi cho user_id nào
    $target_user_id = $is_admin
        ? (int)($input['user_id'] ?? 0)
        : $my_id;

    if (!$message || !$target_user_id) {
        echo json_encode(['ok' => false, 'error' => 'Thiếu dữ liệu']);
        exit;
    }

    $sender = $is_admin ? 'admin' : 'user';

    $stmt = $pdo->prepare(
        "INSERT INTO admin_chats (user_id, sender, message) VALUES (?, ?, ?)"
    );
    $stmt->execute([$target_user_id, $sender, $message]);

    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// =====================================================
// ACTION: LẤY tin nhắn (poll / load)
// =====================================================
if ($action === 'get_messages') {
    // user_id cần xem — admin chọn, user lấy của mình
    $target_user_id = $is_admin
        ? (int)($_GET['user_id'] ?? 0)
        : $my_id;

    if (!$target_user_id) {
        echo json_encode(['ok' => false, 'error' => 'Thiếu user_id']);
        exit;
    }

    $since_id = (int)($_GET['since_id'] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT id, sender, message, is_read, created_at
         FROM admin_chats
         WHERE user_id = ? AND id > ?
         ORDER BY id ASC
         LIMIT 100"
    );
    $stmt->execute([$target_user_id, $since_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Đánh dấu đã đọc cho phía nhận
    if ($rows) {
        $mark_sender = $is_admin ? 'user' : 'admin';
        $pdo->prepare(
            "UPDATE admin_chats SET is_read = 1
             WHERE user_id = ? AND sender = ? AND is_read = 0"
        )->execute([$target_user_id, $mark_sender]);
    }

    echo json_encode(['ok' => true, 'messages' => $rows]);
    exit;
}

// =====================================================
// ACTION: ADMIN - danh sách users đang có chat
// =====================================================
if ($action === 'get_users' && $is_admin) {
    $rows = $pdo->query("
        SELECT u.id, u.name, u.email,
               (SELECT COUNT(*) FROM admin_chats
                WHERE user_id = u.id AND sender = 'user' AND is_read = 0) AS unread,
               (SELECT message FROM admin_chats
                WHERE user_id = u.id ORDER BY id DESC LIMIT 1) AS last_msg,
               (SELECT created_at FROM admin_chats
                WHERE user_id = u.id ORDER BY id DESC LIMIT 1) AS last_time
        FROM users u
        WHERE EXISTS (SELECT 1 FROM admin_chats WHERE user_id = u.id)
        ORDER BY last_time DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'users' => $rows]);
    exit;
}

// =====================================================
// ACTION: USER - kiểm tra có tin nhắn mới từ admin
// =====================================================
if ($action === 'check_unread') {
    $count = $pdo->prepare(
        "SELECT COUNT(*) FROM admin_chats
         WHERE user_id = ? AND sender = 'admin' AND is_read = 0"
    );
    $count->execute([$my_id]);
    echo json_encode(['ok' => true, 'unread' => (int)$count->fetchColumn()]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Action không hợp lệ']);