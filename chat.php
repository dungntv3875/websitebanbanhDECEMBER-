<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (!is_array($input)) {
        goto end_chat_post;
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    try {
        $chat_action = $input['chat_action'] ?? '';

        // Gửi tin nhắn tới nhân viên.
        if ($chat_action === 'send_to_admin') {
            $message = trim($input['message'] ?? '');
            $user_id = $_SESSION['user']['id'] ?? 0;

            if (!$message) {
                echo json_encode([
                    'ok' => false,
                    'error' => 'Thiếu tin nhắn'
                ]);
                exit;
            }

            if (!$user_id) {
                echo json_encode([
                    'ok' => false,
                    'error' => 'login_required'
                ]);
                exit;
            }

            $pdo->prepare("
                INSERT INTO admin_chats (user_id, sender, message)
                VALUES (?, 'user', ?)
            ")->execute([$user_id, $message]);

            echo json_encode(['ok' => true]);
            exit;
        }

        // Lấy lịch sử và phản hồi của nhân viên.
        if ($chat_action === 'get_admin_replies') {
            $user_id = $_SESSION['user']['id'] ?? 0;
            $since_id = (int)($input['since_id'] ?? 0);

            if (!$user_id) {
                echo json_encode([
                    'ok' => false,
                    'error' => 'login_required'
                ]);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT id, sender, message, created_at
                FROM admin_chats
                WHERE user_id = ? AND id > ?
                ORDER BY id ASC
                LIMIT 50
            ");
            $stmt->execute([$user_id, $since_id]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pdo->prepare("
                UPDATE admin_chats
                SET is_read = 1
                WHERE user_id = ? AND sender = 'admin' AND is_read = 0
            ")->execute([$user_id]);

            echo json_encode([
                'ok' => true,
                'messages' => $rows
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $defaultReply = 'December chào bạn, bạn cần December tư vấn gì bạn cứ hỏi bên mình sẽ hỗ trợ bạn nhanh nhất có thể ạ!';

        $defaultSuggestions = [
            'Mẫu bánh mới',
            'Bánh bán chạy',
            'Kết nối nhân viên'
        ];

        $messages = is_array($input['messages'] ?? null)
            ? $input['messages']
            : [];

        if (empty($messages)) {
            echo json_encode([
                'reply' => $defaultReply,
                'suggestions' => $defaultSuggestions,
                'type' => 'text'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $lastMessage = end($messages);
        $rawMsg = trim($lastMessage['content'] ?? '');
        $userMsg = mb_strtolower($rawMsg, 'UTF-8');

        $product_id = (int)($input['product_id'] ?? 0);
        $current_product = null;

        if ($product_id > 0) {
            $stmtP = $pdo->prepare("
                SELECT id, name, stock, price, sale_price
                FROM products
                WHERE id = ?
            ");
            $stmtP->execute([$product_id]);
            $current_product = $stmtP->fetch(PDO::FETCH_ASSOC);
        }

        $reply = $defaultReply;
        $suggestions = $defaultSuggestions;
        $type = 'text';

        // BÁNH MỚI / BEST SELLER.
        if (
            preg_match(
                '/(bánh mới|mẫu bánh mới|hàng mới|sản phẩm mới|bán chạy|best\s*seller)/ui',
                $userMsg
            )
        ) {
            $isBest = preg_match(
                '/(bán chạy|best\s*seller)/ui',
                $userMsg
            );

            if ($isBest) {
                // Bán chạy dựa vào số lượng trong đơn đã hoàn tất.
                $stmt = $pdo->query("
                    SELECT
                        p.id,
                        p.name,
                        p.price,
                        p.sale_price,
                        p.stock,
                        SUM(oi.quantity) AS sold
                    FROM products p
                    JOIN order_items oi ON oi.product_id = p.id
                    JOIN orders o ON o.id = oi.order_id
                    WHERE LOWER(o.status) = 'done'
                      AND p.stock > 0
                    GROUP BY
                        p.id,
                        p.name,
                        p.price,
                        p.sale_price,
                        p.stock
                    ORDER BY sold DESC, p.id DESC
                    LIMIT 5
                ");
            } else {
                // Schema hiện tại chưa có ngày thêm sản phẩm.
                // Dùng ID giảm dần để lấy các mẫu được thêm gần đây.
                $stmt = $pdo->query("
                    SELECT id, name, price, sale_price, stock
                    FROM products
                    WHERE stock > 0
                    ORDER BY id DESC
                    LIMIT 5
                ");
            }

            $cakes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$cakes) {
                $reply = $isBest
                    ? 'December chưa có dữ liệu bánh bán chạy từ đơn hoàn tất. Bạn xem các mẫu bánh mới nhé!'
                    : 'Hiện chưa có mẫu bánh mới còn hàng để gợi ý. Bạn có thể kết nối nhân viên để được hỗ trợ nhé!';
            } else {
                $reply = $isBest
                    ? "🍩 **Bánh bán chạy theo đơn đã hoàn tất:**\n\n"
                   : "✨ **Các mẫu bánh mới:**\n\n";

                foreach ($cakes as $cake) {
                    $price = !empty($cake['sale_price'])
                        ? $cake['sale_price']
                        : $cake['price'];

                    $reply .= '• **' . $cake['name'] . '**'
    . ' — ' . number_format($price, 0, ',', '.') . "đ\n";
                }

                $type = 'product';
            }
        }

        // TỒN KHO SẢN PHẨM ĐANG XEM.
        elseif (
            $current_product
            && preg_match(
                '/(còn hàng|hết hàng|còn không|có hàng|stock|tồn kho|còn sẵn|có sẵn)/ui',
                $userMsg
            )
        ) {
            $stock = (int)$current_product['stock'];
            $name = $current_product['name'];

            if ($stock > 0) {
                $reply = "✅ Bánh **$name** hiện còn **$stock bánh**.";

                if ($stock < 5) {
                    $reply .= "\nChỉ còn ít bánh, bạn có thể đặt hàng ngay nhé!";
                }
            } else {
                $reply = "Bánh **$name** hiện đã hết hàng. Bạn xem các mẫu bánh khác hoặc kết nối nhân viên để được hỗ trợ nhé!";
            }

            $type = 'product';
        }

        // CHÀO HỎI.
        elseif (
            preg_match(
                '/(^hi$|^hello$|chào|xin chào|bắt đầu|giúp gì|quay lại hỏi bot)/ui',
                $userMsg
            )
        ) {
            $reply = $defaultReply;
        }

        // KẾT NỐI NHÂN VIÊN.
        elseif (
            preg_match(
                '/(người bán|nhân viên|tổng đài|trực tiếp|chat với|tư vấn viên|quản lý|admin)/ui',
                $userMsg
            )
        ) {
            $reply = 'Bạn bấm **Chat ngay** bên dưới để gửi tin nhắn trực tiếp cho nhân viên December nhé!';
            $type = 'contact';
        }

        // THANH TOÁN VÀ VẬN CHUYỂN.
        elseif (
            preg_match(
                '/(cod|thanh toán|ship|vận chuyển|giao hàng|chuyển khoản|momo|vnpay)/ui',
                $userMsg
            )
        ) {
            $reply = "December hỗ trợ:\n"
                . "- **COD:** thanh toán khi nhận hàng.\n"
                . "- **Chuyển khoản ngân hàng:** theo hướng dẫn VietQR.\n"
                . "- **MoMo mô phỏng:** dùng để thử chức năng, không trừ tiền thật.\n\n"
                . "**Vận chuyển:**\n"
                . "- Hiện hỗ trợ giao hàng trong nội thành Hà Nội.\n"
                . "- Phí giao hàng: **30.000đ**.\n"
                . "- Miễn phí giao hàng khi tiền bánh **trên 500.000đ**.\n"
                . "- Nhận đặt hàng từ **08:00 đến 20:00**.";

            $suggestions = [
                'Theo dõi đơn hàng',
                'Chính sách đổi trả',
                'Kết nối nhân viên'
            ];
        }

        // BẢO QUẢN BÁNH.
        elseif (
            preg_match(
                '/(bảo quản|giữ bánh|giữ lạnh|nhiệt độ|tủ lạnh|care)/ui',
                $userMsg
            )
        ) {
            $reply = "🧁 **Chính sách bảo quản bánh của December**\n\n"
                . "**Bánh ngọt thông thường**\n"
                . "- Bảo quản nơi khô ráo, thoáng mát, tránh ánh nắng trực tiếp.\n"
                . "- Nên ăn trong vòng 24 giờ sau khi nhận hàng.\n"
                . "- Tránh nơi quá nóng hoặc ẩm mốc.\n\n"
                . "**Bánh có nhân kem / cream / mousse**\n"
                . "- Bảo quản trong tủ lạnh ở nhiệt độ 0–5°C.\n"
                . "- Không để ngoài tủ lạnh quá lâu.\n"
                . "- Có thể để bánh ở nhiệt độ phòng khoảng 10–15 phút trước khi dùng.\n\n"
                . "**Lưu ý**\n"
                . "- Để bánh trong hộp kín, tránh thực phẩm có mùi mạnh.\n"
                . "- Không sử dụng bánh có dấu hiệu ôi thiu, mùi lạ.\n\n"
                . "December khuyến khích khách hàng giữ bánh đúng cách để đảm bảo hương vị và chất lượng tốt nhất.";

            $suggestions = [
                'Chính sách đổi trả',
                'Mẫu bánh mới',
                'Kết nối nhân viên'
            ];
        }

        // ĐỔI TRẢ.
        elseif (
            preg_match(
                '/(đổi trả|đổi bánh|trả hàng|hoàn tiền|hoàn hàng|bánh lỗi|defect|return)/ui',
                $userMsg
            )
        ) {
            $reply = "**Chính sách đổi trả của December**\n\n"
                . "**Được hỗ trợ khi:**\n"
                . "- Bánh bị lỗi hoặc hỏng trong quá trình vận chuyển.\n"
                . "- Giao sai loại bánh hoặc số lượng.\n"
                . "- Sản phẩm không đúng như đơn đặt.\n\n"
                . "**Không hỗ trợ khi:**\n"
                . "- Khách hàng đổi ý hoặc đặt nhầm hương vị.\n"
                . "- Bánh đã qua sử dụng hoặc bảo quản sai cách.\n\n"
                . "Bạn vui lòng chụp ảnh/video khi nhận hàng và liên hệ nhân viên để được hỗ trợ nhé!";

            $suggestions = [
                'Kết nối nhân viên',
                'Hướng dẫn bảo quản',
                'Theo dõi đơn hàng'
            ];
        }

        // ĐẶT HÀNG.
        elseif (
            preg_match('/(đặt hàng|đặt bánh|mua ngay|checkout)/ui', $userMsg)
        ) {
            $reply = "Bạn chọn bánh yêu thích, bấm **Mua ngay** hoặc thêm vào giỏ hàng rồi tiến hành thanh toán nhé!\n"
                . "Bạn cũng có thể bấm **Chat ngay** để nhờ nhân viên hỗ trợ.";
            $type = 'contact';
        }

        // THEO DÕI ĐƠN.
        elseif (
            preg_match(
                '/(đơn hàng|order|theo dõi|tracking|tình trạng đơn|mã vận đơn)/ui',
                $userMsg
            )
        ) {
            $reply = "📦 Bạn đăng nhập tài khoản December và vào **Đơn hàng của tôi** để xem trạng thái đơn.\n"
                . "Nếu cần hỗ trợ thêm, hãy gửi mã đơn cho nhân viên nhé!";

            $suggestions = [
                'Kết nối nhân viên',
                'Chính sách đổi trả'
            ];
        }

        // TÌM BÁNH THEO TÊN; KHÔNG KHỚP THÌ TRẢ LỜI MẶC ĐỊNH.
        else {
            $stopwords = [
                'tìm', 'có', 'không', 'mẫu', 'cái', 'sản phẩm',
                'còn hàng', 'check', 'kiểm tra', 'xem',
                'cho', 'mình', 'hỏi', '?', '"', 'muốn', 'mua'
            ];

            $search = str_replace($stopwords, '', $userMsg);
            $search = trim(preg_replace('/\s+/', ' ', $search));

            // Escape ký tự wildcard để câu hỏi "%" không trả về toàn bộ bánh.
            $searchTerm = str_replace(
                ['!', '%', '_'],
                ['!!', '!%', '!_'],
                $search
            );

            if (mb_strlen($search, 'UTF-8') > 1) {
                $stmt = $pdo->prepare("
                    SELECT name, price, sale_price, stock
                    FROM products
                    WHERE name LIKE :term ESCAPE '!'
                    ORDER BY id DESC
                    LIMIT 5
                ");

                $stmt->execute(['term' => '%' . $searchTerm . '%']);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($products) {
                    $reply = "🍩 **Các mẫu bánh phù hợp:**\n\n";

                    foreach ($products as $product) {
                        $stock = (int)$product['stock'];
                        $price = !empty($product['sale_price'])
                            ? $product['sale_price']
                            : $product['price'];

                        $reply .= '• **' . $product['name'] . '**'
                            . ' — ' . number_format($price, 0, ',', '.') . 'đ'
                            . ' — '
                            . ($stock > 0 ? "Còn $stock bánh" : 'Hết hàng')
                            . "\n";
                    }

                    $type = 'product';
                }
            }
        }

        echo json_encode([
            'reply' => $reply,
            'suggestions' => $suggestions,
            'type' => $type
        ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        error_log('December chatbot: ' . $e->getMessage());

        echo json_encode([
            'reply' => 'December đang gặp sự cố kết nối. Bạn vui lòng thử lại sau nhé!',
            'suggestions' => ['Kết nối nhân viên'],
            'type' => 'text'
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

end_chat_post:

// Không hiển thị hai chatbot nếu cả hai file đều được include.
if (defined('DECEMBER_CHAT_RENDERED')) {
    return;
}
define('DECEMBER_CHAT_RENDERED', true);
?>

<button id="rb-toggle" aria-label="Mở chat hỗ trợ">
    <svg width="24" height="24" fill="#fff" viewBox="0 0 24 24">
        <path d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
    </svg>
    <span id="rb-badge" style="display:none">1</span>
</button>

<div id="rb-window" role="dialog" aria-label="Chat hỗ trợ December">
    <div class="rb-header">
        <div class="rb-avatar">
            <svg width="20" height="20" fill="#fff" viewBox="0 0 24 24">
                <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/>
            </svg>
        </div>

        <div class="rb-header-info">
            <div class="rb-header-name">December Assistant</div>
            <div class="rb-header-status">
                <span class="rb-status-dot"></span> Đang hoạt động
            </div>
        </div>

        <button class="rb-close" id="rb-close" aria-label="Đóng">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    </div>

    <div class="rb-messages" id="rb-messages"></div>

    <div class="rb-footer">
        <textarea
            id="rb-input"
            rows="1"
            placeholder="Nhắn tin cho December..."
        ></textarea>

        <button id="rb-send" aria-label="Gửi">
            <svg width="16" height="16" fill="#fff" viewBox="0 0 24 24">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
            </svg>
        </button>
    </div>
</div>

<div id="rb-admin-chat">
    <div class="rb-admin-header">
        <div class="rb-admin-avatar">
            <svg width="18" height="18" fill="#fff" viewBox="0 0 24 24">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
        </div>

        <div class="rb-header-info">
            <div class="rb-header-name">Nhân viên December</div>
            <div class="rb-header-status">Gửi tin nhắn hỗ trợ</div>
        </div>

        <button class="rb-close" id="rb-admin-close" aria-label="Đóng">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    </div>

    <div id="rb-admin-messages" class="rb-messages"></div>

    <div class="rb-footer">
        <textarea
            id="rb-admin-input"
            rows="1"
            placeholder="Nhắn với nhân viên..."
        ></textarea>

        <button id="rb-admin-send" aria-label="Gửi">
            <svg width="16" height="16" fill="#fff" viewBox="0 0 24 24">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
            </svg>
        </button>
    </div>
</div>

<script>
(function () {
    const CHATBOT_URL = 'chat.php';

    const DEFAULT_REPLY =
        'December chào bạn, bạn cần December tư vấn gì bạn cứ hỏi bên mình sẽ hỗ trợ bạn nhanh nhất có thể ạ!';

    const DEFAULT_SUGGESTIONS = [
        'Mẫu bánh mới',
        'Bánh bán chạy',
        'Kết nối nhân viên'
    ];

    const PRODUCT_ID = (function () {
        if (!location.pathname.endsWith('/product.php')) return 0;

        const meta = document.querySelector('meta[name="product-id"]');
        if (meta) return parseInt(meta.content, 10) || 0;

        const params = new URLSearchParams(location.search);
        return parseInt(params.get('id'), 10) || 0;
    })();

    const win = document.getElementById('rb-window');
    const msgsEl = document.getElementById('rb-messages');
    const inputEl = document.getElementById('rb-input');
    const sendBtn = document.getElementById('rb-send');
    const badge = document.getElementById('rb-badge');
    const toggleBtn = document.getElementById('rb-toggle');

    const adminPanel = document.getElementById('rb-admin-chat');
    const adminMessages = document.getElementById('rb-admin-messages');
    const adminInput = document.getElementById('rb-admin-input');
    const adminSendBtn = document.getElementById('rb-admin-send');

    let history = [];
    let isOpen = false;
    let sending = false;

    let adminChatOpen = false;
    let adminLastId = 0;
    let adminPoll = null;
    let adminSending = false;
    let adminLoading = false;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderText(value) {
        return escapeHtml(value)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    function getTime(value) {
        const date = value
            ? new Date(String(value).replace(' ', 'T'))
            : new Date();

        if (Number.isNaN(date.getTime())) return '';

        return String(date.getHours()).padStart(2, '0')
            + ':'
            + String(date.getMinutes()).padStart(2, '0');
    }

    function append(role, text, suggestions = [], type = 'text') {
        const div = document.createElement('div');
        div.className = 'rb-msg rb-' + role;

        const bubble = document.createElement('div');
        bubble.className = 'rb-bubble';
        bubble.innerHTML = renderText(text);
        div.appendChild(bubble);

        const time = document.createElement('div');
        time.className = 'rb-time';
        time.textContent = getTime();
        div.appendChild(time);

        if (
            role === 'bot'
            && Array.isArray(suggestions)
            && suggestions.length
        ) {
            const buttons = document.createElement('div');
            buttons.className = 'rb-suggestions';

            suggestions.forEach(label => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'rb-suggestion';
                button.textContent = '💬 ' + label;

                button.addEventListener('click', () => {
                    if (sending) return;
                    inputEl.value = label;
                    send();
                });

                buttons.appendChild(button);
            });

            div.appendChild(buttons);
        }

        if (type === 'contact') {
            const bar = document.createElement('div');
            bar.className = 'rb-contact-bar';

            const label = document.createElement('span');
            label.textContent = '📞 Kết nối nhân viên trực tiếp';

            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = 'Chat ngay';
            button.addEventListener('click', openAdminChat);

            bar.append(label, button);
            div.appendChild(bar);
        }

        msgsEl.appendChild(div);
        msgsEl.scrollTop = msgsEl.scrollHeight;
    }

    function showTyping() {
        const el = document.createElement('div');
        el.className = 'rb-typing';
        el.id = 'rb-typing';

        el.innerHTML = `
            <div class="rb-typing-bubble">
                <div class="rb-dot"></div>
                <div class="rb-dot"></div>
                <div class="rb-dot"></div>
            </div>
        `;

        msgsEl.appendChild(el);
        msgsEl.scrollTop = msgsEl.scrollHeight;
    }

    function hideTyping() {
        document.getElementById('rb-typing')?.remove();
    }

    async function request(payload) {
        const response = await fetch(CHATBOT_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        return response.json();
    }

    async function send() {
        const message = inputEl.value.trim();
        if (!message || sending) return;

        sending = true;
        sendBtn.disabled = true;

        append('user', message);
        inputEl.value = '';
        inputEl.style.height = 'auto';

        history.push({role: 'user', content: message});
        if (history.length > 20) history = history.slice(-20);

        showTyping();

        try {
            const data = await request({
                messages: history,
                product_id: PRODUCT_ID
            });

            hideTyping();

            const reply = data.reply || DEFAULT_REPLY;

            append(
                'bot',
                reply,
                data.suggestions || DEFAULT_SUGGESTIONS,
                data.type || 'text'
            );

            history.push({
                role: 'assistant',
                content: reply
            });
        } catch (error) {
            hideTyping();

            append(
                'bot',
                'Không kết nối được với December. Bạn vui lòng thử lại sau nhé!',
                ['Kết nối nhân viên']
            );
        } finally {
            sending = false;
            sendBtn.disabled = false;
        }
    }

    toggleBtn.addEventListener('click', () => {
        isOpen = !isOpen;
        win.classList.toggle('rb-open', isOpen);

        if (isOpen) {
            badge.style.display = 'none';
            inputEl.focus();
        }
    });

    document.getElementById('rb-close').addEventListener('click', () => {
        isOpen = false;
        win.classList.remove('rb-open');
    });

    sendBtn.addEventListener('click', send);

    inputEl.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            send();
        }
    });

    inputEl.addEventListener('input', () => {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 90) + 'px';
    });

    // Giữ tương thích với các nút gọi rbSend của website.
    window.rbSend = function (text) {
        if (sending) return;
        inputEl.value = text;
        send();
    };

    function appendAdminMsg(sender, text, id = null, createdAt = null) {
        const numericId = parseInt(id, 10) || 0;

        if (numericId && numericId <= adminLastId) return;
        if (numericId) adminLastId = numericId;

        const div = document.createElement('div');
        div.className = 'rb-msg '
            + (sender === 'user' ? 'rb-user' : 'rb-bot');

        const bubble = document.createElement('div');
        bubble.className = 'rb-bubble';
        bubble.style.whiteSpace = 'pre-wrap';
        bubble.textContent = text;

        const time = document.createElement('div');
        time.className = 'rb-time';
        time.textContent = getTime(createdAt);

        div.append(bubble, time);
        adminMessages.appendChild(div);
        adminMessages.scrollTop = adminMessages.scrollHeight;
    }

    async function pollAdminMessages(initial = false) {
        if (!adminChatOpen || adminLoading) return;

        adminLoading = true;

        try {
            const data = await request({
                chat_action: 'get_admin_replies',
                since_id: adminLastId
            });

            if (!adminChatOpen) return;

            if (data.error === 'login_required') {
                clearInterval(adminPoll);

                appendAdminMsg(
                    'admin',
                    'Bạn cần đăng nhập tại trang Tài khoản để nhắn với nhân viên.'
                );
                return;
            }

            if (!data.ok) {
                if (initial) {
                    appendAdminMsg(
                        'admin',
                        'Chưa tải được lịch sử tin nhắn. Bạn vui lòng thử lại.'
                    );
                }
                return;
            }

            const messages = Array.isArray(data.messages)
                ? data.messages
                : [];

            if (initial && messages.length === 0) {
                appendAdminMsg(
                    'admin',
                    'December chào bạn! Bạn hãy để lại tin nhắn để nhân viên hỗ trợ nhé!'
                );
            }

            messages.forEach(message => {
                appendAdminMsg(
                    message.sender,
                    message.message,
                    message.id,
                    message.created_at
                );
            });
        } catch (error) {
            if (initial && adminChatOpen) {
                appendAdminMsg(
                    'admin',
                    'Không tải được lịch sử tin nhắn. Bạn vui lòng thử lại sau.'
                );
            }
        } finally {
            adminLoading = false;
        }
    }

    function openAdminChat() {
        if (adminChatOpen) {
            adminInput.focus();
            return;
        }

        adminChatOpen = true;
        adminPanel.classList.add('rb-admin-open');
        adminPanel.style.display = 'flex';

        adminMessages.innerHTML = '';
        adminLastId = 0;

        clearInterval(adminPoll);
        adminPoll = setInterval(() => pollAdminMessages(), 4000);

        pollAdminMessages(true);
        adminInput.focus();
    }

    window.rbOpenAdminChat = openAdminChat;

    document.getElementById('rb-admin-close').addEventListener('click', () => {
        adminChatOpen = false;
        adminPanel.classList.remove('rb-admin-open');
        adminPanel.style.display = 'none';
        clearInterval(adminPoll);
    });

    async function sendAdminMessage() {
        const message = adminInput.value.trim();
        if (!message || adminSending) return;

        adminSending = true;
        adminSendBtn.disabled = true;

        try {
            const data = await request({
                chat_action: 'send_to_admin',
                message: message
            });

            if (data.ok) {
                adminInput.value = '';
                adminInput.style.height = 'auto';
                await pollAdminMessages();
            } else if (data.error === 'login_required') {
                appendAdminMsg(
                    'admin',
                    'Bạn cần đăng nhập tại trang Tài khoản để nhắn với nhân viên.'
                );
            } else {
                appendAdminMsg(
                    'admin',
                    'Chưa gửi được tin nhắn. Bạn vui lòng thử lại.'
                );
            }
        } catch (error) {
            appendAdminMsg(
                'admin',
                'Lỗi kết nối. Tin nhắn chưa được gửi, bạn vui lòng thử lại.'
            );
        } finally {
            adminSending = false;
            adminSendBtn.disabled = false;
        }
    }

    adminSendBtn.addEventListener('click', sendAdminMessage);

    adminInput.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendAdminMessage();
        }
    });

    adminInput.addEventListener('input', () => {
        adminInput.style.height = 'auto';
        adminInput.style.height =
            Math.min(adminInput.scrollHeight, 90) + 'px';
    });

    append('bot', DEFAULT_REPLY, DEFAULT_SUGGESTIONS);

    setTimeout(() => {
        if (!isOpen) badge.style.display = 'flex';
    }, 2000);
})();
</script>