<?php
/**
 * Rubies Chatbot Widget
 * Nhúng vào footer.php:  <?php include 'chatbot-widget.php'; ?>
 * Đặt TRƯỚC thẻ </body>
 *
 * Yêu cầu: $pdo đã có sẵn (từ db.php include ở trang cha)
 *          session_start() đã được gọi ở trang cha
 */

// Lấy danh mục để hiển thị quick replies theo đúng dữ liệu thật
$rb_categories = [];
try {
    $rbCatStmt = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY id LIMIT 8");
    $rb_categories = $rbCatStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Lấy tên user nếu đã đăng nhập
$rb_username = $_SESSION['user']['name'] ?? $_SESSION['user']['fullname'] ?? '';
?>

<!-- ════════════════════════════════════
     RUBIES CHATBOT WIDGET
     include vào footer.php trước </body>
════════════════════════════════════ -->
<style>
:root {
  --rb-ruby:      #8B1A2F;
  --rb-ruby-dk:   #6B1223;
  --rb-blush:     #F9EEF1;
  --rb-border:    #E8D5DA;
  --rb-ivory:     #FAF8F6;
  --rb-dark:      #1A1015;
  --rb-muted:     #7A6570;
  --rb-white:     #FFFFFF;
  --rb-radius:    20px;
}

/* ── Toggle button ── */
#rb-toggle {
  position: fixed; bottom: 26px; right: 26px;
  width: 58px; height: 58px; border-radius: 50%;
  background: var(--rb-ruby); border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 18px rgba(139,26,47,.38);
  transition: transform .2s, box-shadow .2s;
  z-index: 9999;
}
#rb-toggle:hover { transform: scale(1.09); box-shadow: 0 6px 26px rgba(139,26,47,.48); }
#rb-toggle svg { width: 25px; height: 25px; fill: #fff; }
#rb-toggle .rb-ic-close { display: none; }

/* Unread badge */
#rb-badge {
  position: absolute; top: -2px; right: -2px;
  width: 18px; height: 18px; border-radius: 50%;
  background: #e74c3c; color: #fff;
  font-size: 10px; font-weight: 700;
  display: none; align-items: center; justify-content: center;
  border: 2px solid #fff;
}

/* ── Chat window ── */
#rb-window {
  position: fixed; bottom: 98px; right: 26px;
  width: 355px; height: 515px;
  display: flex; flex-direction: column;
  background: var(--rb-white);
  border-radius: var(--rb-radius);
  border: 1px solid var(--rb-border);
  box-shadow: 0 16px 48px rgba(139,26,47,.14), 0 2px 10px rgba(0,0,0,.07);
  overflow: hidden;
  z-index: 9998;
  opacity: 0; transform: translateY(14px) scale(.97); pointer-events: none;
  transition: opacity .25s, transform .25s;
}
#rb-window.rb-open {
  opacity: 1; transform: translateY(0) scale(1); pointer-events: all;
}

/* ── Header ── */
.rb-header {
  background: var(--rb-ruby);
  padding: 14px 16px;
  display: flex; align-items: center; gap: 10px;
  flex-shrink: 0;
}
.rb-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 17px; color: #fff; flex-shrink: 0;
}
.rb-h-name  { font-size: 15px; color: #fff; font-weight: 500; letter-spacing: .05em; line-height: 1.2; }
.rb-h-sub   { font-size: 11px; color: rgba(255,255,255,.72); margin-top: 2px; }
.rb-dot     { width: 6px; height: 6px; background: #74EFA3; border-radius: 50%; display: inline-block; margin-right: 4px; vertical-align: middle; }

/* ── Messages ── */
.rb-messages {
  flex: 1; overflow-y: auto;
  padding: 14px 12px;
  display: flex; flex-direction: column; gap: 10px;
  background: var(--rb-ivory);
  scroll-behavior: smooth;
}
.rb-messages::-webkit-scrollbar { width: 3px; }
.rb-messages::-webkit-scrollbar-thumb { background: var(--rb-border); border-radius: 4px; }

.rb-msg { display: flex; flex-direction: column; max-width: 82%; }
.rb-msg.rb-bot  { align-self: flex-start; }
.rb-msg.rb-user { align-self: flex-end; }

.rb-bubble {
  padding: 10px 13px; border-radius: 16px;
  font-size: 13.5px; line-height: 1.55;
}
.rb-bot  .rb-bubble { background: var(--rb-white); color: var(--rb-dark); border: 1px solid var(--rb-border); border-bottom-left-radius: 4px; }
.rb-user .rb-bubble { background: var(--rb-ruby); color: #fff; border-bottom-right-radius: 4px; }

.rb-time { font-size: 10px; color: var(--rb-muted); margin-top: 3px; padding: 0 2px; }

/* Links trong bubble bot */
.rb-bubble a { color: var(--rb-ruby); text-decoration: underline; font-weight: 500; }
.rb-user .rb-bubble a { color: #ffc0cb; }

/* ── Typing indicator ── */
.rb-typing-wrap { display: flex; align-items: center; gap: 4px; padding: 11px 13px; }
.rb-typing-wrap span {
  width: 6px; height: 6px; background: var(--rb-border); border-radius: 50%;
  animation: rbBounce 1.2s ease-in-out infinite;
}
.rb-typing-wrap span:nth-child(2) { animation-delay: .2s; }
.rb-typing-wrap span:nth-child(3) { animation-delay: .4s; }
@keyframes rbBounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }

/* ── Quick replies ── */
.rb-qrs { display: flex; flex-wrap: wrap; gap: 6px; padding: 4px 0 6px; }
.rb-qr {
  background: var(--rb-blush); border: 1px solid var(--rb-border);
  color: var(--rb-ruby); border-radius: 20px;
  padding: 5px 11px; font-size: 12px; cursor: pointer;
  transition: background .15s; white-space: nowrap;
}
.rb-qr:hover { background: var(--rb-border); }

/* ── Input area ── */
.rb-footer {
  padding: 9px 11px; background: var(--rb-white);
  border-top: 1px solid var(--rb-border);
  display: flex; gap: 7px; align-items: flex-end;
  flex-shrink: 0;
}
#rb-input {
  flex: 1; border: 1px solid var(--rb-border); border-radius: 20px;
  padding: 8px 13px; font-size: 13.5px; font-family: inherit;
  outline: none; resize: none; max-height: 88px; overflow-y: auto;
  color: var(--rb-dark); background: var(--rb-ivory);
  transition: border-color .15s; line-height: 1.4;
}
#rb-input:focus { border-color: var(--rb-ruby); }
#rb-input::placeholder { color: var(--rb-muted); }

#rb-send {
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--rb-ruby); border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background .15s, transform .15s; flex-shrink: 0;
}
#rb-send:hover   { background: var(--rb-ruby-dk); transform: scale(1.06); }
#rb-send:disabled{ background: var(--rb-border); cursor: default; transform: none; }
#rb-send svg { width: 15px; height: 15px; fill: #fff; }

@media (max-width: 480px) {
  #rb-window { width: calc(100vw - 20px); right: 10px; bottom: 88px; }
  #rb-toggle { right: 14px; bottom: 14px; }
}
</style>

<!-- Toggle Button -->
<button id="rb-toggle" aria-label="Mở chat tư vấn Rubies" style="position:fixed">
  <svg class="rb-ic-chat" viewBox="0 0 24 24"><path d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm-2 10H6v-2h12v2zm0-3H6V7h12v2z"/></svg>
  <svg class="rb-ic-close" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
  <span id="rb-badge">1</span>
</button>

<!-- Chat Window -->
<div id="rb-window" role="dialog" aria-label="Rubies Fashion Assistant">
  <div class="rb-header">
    <div class="rb-avatar">✿</div>
    <div>
      <div class="rb-h-name">Rubies Assistant</div>
      <div class="rb-h-sub"><span class="rb-dot"></span>Tư vấn thời trang nữ</div>
    </div>
  </div>

  <div class="rb-messages" id="rb-messages"></div>

  <div class="rb-footer">
    <textarea id="rb-input" rows="1" placeholder="Nhắn tin tư vấn..."></textarea>
    <button id="rb-send" aria-label="Gửi">
      <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
    </button>
  </div>
</div>

<script>
(function () {
  /* ── CONFIG ── */
  const CHATBOT_URL = 'chatbot.php';

  /* ── STATE ── */
  const toggle   = document.getElementById('rb-toggle');
  const win      = document.getElementById('rb-window');
  const msgsEl   = document.getElementById('rb-messages');
  const inputEl  = document.getElementById('rb-input');
  const sendBtn  = document.getElementById('rb-send');
  const badge    = document.getElementById('rb-badge');

  let history   = [];   // [{role:'user'|'bot', content:'...'}]
  let loading   = false;
  let opened    = false;

  /* ── Quick replies từ danh mục thật trong DB ── */
  const categories = <?php echo json_encode($rb_categories, JSON_UNESCAPED_UNICODE); ?>;
  const username   = <?php echo json_encode($rb_username, JSON_UNESCAPED_UNICODE); ?>;

  const defaultQRs = [
    { label: '🏷️ Đang sale', msg: 'Có sản phẩm nào đang giảm giá không?' },
    { label: '✨ Hàng mới', msg: 'Cho mình xem sản phẩm mới nhất' },
    { label: '📏 Tư vấn size', msg: 'Tư vấn cách chọn size quần áo' },
    { label: '🛍️ Đơn hàng', msg: 'Kiểm tra đơn hàng của mình' },
  ];

  // Thêm quick reply theo danh mục thật (tối đa 3 danh mục đầu)
  const catQRs = categories.slice(0, 3).map(c => ({
    label: '👗 ' + c.name,
    msg: 'Gợi ý trang phục ' + c.name
  }));
  const allQRs = [...defaultQRs, ...catQRs];

  /* ── INIT: hiện tin chào ── */
  function init() {
    const greeting = username
      ? `Xin chào <strong>${escHtml(username)}</strong>! Mình là trợ lý thời trang của <strong>Rubies</strong> ✨<br>Bạn cần tư vấn gì hôm nay?`
      : `Xin chào! Mình là trợ lý thời trang của <strong>Rubies</strong> ✨<br>Bạn cần tư vấn gì hôm nay?`;
    appendMsg('bot', greeting, allQRs, true);
    showBadge();
  }

  /* ── TOGGLE OPEN/CLOSE ── */
  toggle.addEventListener('click', () => {
    opened = !opened;
    win.classList.toggle('rb-open', opened);
    toggle.querySelector('.rb-ic-chat').style.display  = opened ? 'none'  : 'block';
    toggle.querySelector('.rb-ic-close').style.display = opened ? 'block' : 'none';
    if (opened) { hideBadge(); inputEl.focus(); scrollBot(); }
  });

  /* ── QUICK REPLIES: event delegation ── */
  msgsEl.addEventListener('click', e => {
    if (e.target.classList.contains('rb-qr')) {
      send(e.target.dataset.msg);
    }
  });

  /* ── INPUT ── */
  inputEl.addEventListener('input', () => {
    inputEl.style.height = 'auto';
    inputEl.style.height = Math.min(inputEl.scrollHeight, 88) + 'px';
  });
  inputEl.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
  });
  sendBtn.addEventListener('click', () => send());

  /* ── SEND ── */
  function send(text) {
    const msg = (text || inputEl.value).trim();
    if (!msg || loading) return;

    appendMsg('user', escHtml(msg));
    history.push({ role: 'user', content: msg });
    inputEl.value = ''; inputEl.style.height = 'auto';

    const typingEl = appendTyping();
    loading = true; sendBtn.disabled = true;

    fetch(CHATBOT_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ messages: history })
    })
    .then(r => r.json())
    .then(data => {
      typingEl.remove();
      loading = false; sendBtn.disabled = false;

      if (data.error) {
        appendMsg('bot', '😔 Xin lỗi, mình đang gặp sự cố kỹ thuật. Bạn thử lại sau nhé!');
        return;
      }

      const reply = data.reply;
      // Chuyển markdown link [text](url) → <a>
      const html = escHtml(reply)
        .replace(/\n/g, '<br>')
        .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');

      appendMsg('bot', html, null, true);
      history.push({ role: 'bot', content: reply });

      // Giới hạn lịch sử 20 tin để tránh token quá lớn
      if (history.length > 20) history = history.slice(-20);

      if (!opened) showBadge();
    })
    .catch(() => {
      typingEl.remove();
      loading = false; sendBtn.disabled = false;
      appendMsg('bot', '😔 Không kết nối được. Kiểm tra mạng rồi thử lại nhé!');
    });
  }

  /* ── HELPERS ── */
  function appendMsg(role, htmlContent, qrs, isHtml) {
    const wrap = document.createElement('div');
    wrap.className = `rb-msg rb-${role}`;

    const bubble = document.createElement('div');
    bubble.className = 'rb-bubble';
    if (isHtml) bubble.innerHTML = htmlContent;
    else        bubble.textContent = htmlContent;

    const time = document.createElement('div');
    time.className = 'rb-time';
    time.textContent = getTime();

    wrap.appendChild(bubble);
    wrap.appendChild(time);

    if (qrs && qrs.length) {
      const qrDiv = document.createElement('div');
      qrDiv.className = 'rb-qrs';
      qrs.forEach(q => {
        const btn = document.createElement('button');
        btn.className = 'rb-qr';
        btn.textContent = q.label;
        btn.dataset.msg = q.msg;
        qrDiv.appendChild(btn);
      });
      wrap.appendChild(qrDiv);
    }

    msgsEl.appendChild(wrap);
    scrollBot();
    return wrap;
  }

  function appendTyping() {
    const wrap = document.createElement('div');
    wrap.className = 'rb-msg rb-bot';
    wrap.innerHTML = `<div class="rb-bubble rb-typing-wrap"><span></span><span></span><span></span></div>`;
    msgsEl.appendChild(wrap);
    scrollBot();
    return wrap;
  }

  function scrollBot() { setTimeout(() => msgsEl.scrollTop = msgsEl.scrollHeight, 50); }
  function getTime()   { const d = new Date(); return String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0'); }
  function escHtml(s)  { const d = document.createElement('div'); d.appendChild(document.createTextNode(s)); return d.innerHTML; }
  function showBadge() { badge.style.display = 'flex'; }
  function hideBadge() { badge.style.display = 'none'; }

  init();
})();
</script>
<!-- ════ END RUBIES CHATBOT ════ -->